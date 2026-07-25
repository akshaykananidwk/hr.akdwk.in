<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isManager = $user->hasAnyRole(['Super Admin', 'Sales Manager', 'Team Leader']);

        $query = Lead::with(['product', 'assignee'])->latest();
        if (! $isManager) {
            $query->where('assigned_to', $user->id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$request->q}%")
                ->orWhere('business_name', 'like', "%{$request->q}%")
                ->orWhere('phone', 'like', "%{$request->q}%"));
        }

        $leads = $query->paginate(15)->withQueryString();
        $statusCounts = (clone $query)->getQuery() ? Lead::when(! $isManager, fn ($q) => $q->where('assigned_to', $user->id))
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status') : collect();

        return view('leads.index', compact('leads', 'statusCounts', 'isManager'));
    }

    public function create()
    {
        return view('leads.create', [
            'products' => Product::where('is_active', true)->get(),
            'executives' => $this->assignableUsers(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateLead($request);
        $data['created_by'] = auth()->id();
        $data['assigned_to'] = $data['assigned_to'] ?? auth()->id();

        $lead = Lead::create($data);
        $lead->activities()->create([
            'user_id' => auth()->id(), 'type' => 'created', 'new_status' => $lead->status,
            'note' => 'Lead created',
        ]);
        ActivityLogger::log('lead.create', $lead, "Created lead: {$lead->name}");

        return redirect()->route('leads.show', $lead)->with('success', 'Lead created successfully.');
    }

    public function show(Lead $lead)
    {
        $this->authorizeLead($lead);
        $lead->load(['product', 'assignee', 'creator', 'activities.user']);

        return view('leads.show', [
            'lead' => $lead,
            'statuses' => Lead::STATUSES,
            'executives' => $this->assignableUsers(),
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $data = $this->validateLead($request);
        $lead->update($data);
        ActivityLogger::log('lead.update', $lead, 'Updated lead');

        return back()->with('success', 'Lead updated.');
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Lead::STATUSES)],
            'note' => ['nullable', 'string'],
            'next_followup_at' => ['nullable', 'date'],
            'lost_reason' => ['nullable', 'string'],
        ]);

        $old = $lead->status;
        DB::transaction(function () use ($lead, $data, $old) {
            $lead->update([
                'status' => $data['status'],
                'next_followup_at' => $data['next_followup_at'] ?? $lead->next_followup_at,
                'lost_reason' => $data['status'] === 'lost' ? ($data['lost_reason'] ?? null) : null,
            ]);
            $lead->activities()->create([
                'user_id' => auth()->id(), 'type' => 'status_change',
                'old_status' => $old, 'new_status' => $data['status'],
                'note' => $data['note'] ?? null,
            ]);

            // Auto-create a sale + commission when a lead is won.
            if ($data['status'] === 'won' && $old !== 'won') {
                $this->convertToSale($lead);
            }
        });

        ActivityLogger::log('lead.status', $lead, "Status: {$old} → {$data['status']}");

        return back()->with('success', 'Lead status updated.');
    }

    public function addActivity(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $data = $request->validate([
            'type' => ['required', 'in:call,note,meeting,demo,whatsapp'],
            'note' => ['required', 'string'],
        ]);
        $lead->activities()->create($data + ['user_id' => auth()->id()]);

        return back()->with('success', 'Activity logged.');
    }

    private function convertToSale(Lead $lead): void
    {
        $product = $lead->product;
        $amount = (float) ($lead->expected_value ?: 0);

        $sale = Sale::create([
            'invoice_number' => 'INV-'.strtoupper(Str::random(8)),
            'lead_id' => $lead->id,
            'product_id' => $product?->id,
            'user_id' => $lead->assigned_to,
            'customer_name' => $lead->business_name ?: $lead->name,
            'customer_phone' => $lead->phone,
            'amount' => $amount,
            'payment_status' => 'pending',
            'subscription_start' => now()->toDateString(),
            'subscription_end' => now()->addYear()->toDateString(),
        ]);

        if ($product && $amount > 0) {
            $commission = $product->commission_type === 'percentage'
                ? round($amount * ($product->commission_value / 100), 2)
                : (float) $product->commission_value;

            Commission::create([
                'user_id' => $lead->assigned_to,
                'sale_id' => $sale->id,
                'type' => 'sale',
                'amount' => $commission,
                'status' => 'pending',
            ]);
        }
    }

    private function validateLead(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'business_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'source' => ['required', 'in:manual,website,whatsapp,facebook,instagram,reference,cold_visit'],
            'status' => ['required', 'in:'.implode(',', Lead::STATUSES)],
            'product_id' => ['nullable', 'exists:products,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'expected_value' => ['nullable', 'numeric', 'min:0'],
            'expected_closing_date' => ['nullable', 'date'],
            'next_followup_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function assignableUsers()
    {
        return User::role(['Sales Executive', 'Team Leader', 'Sales Manager', 'Support Executive'])
            ->where('is_active', true)->orderBy('name')->get();
    }

    private function authorizeLead(Lead $lead): void
    {
        abort_unless(
            $lead->assigned_to === auth()->id()
                || auth()->user()->hasAnyRole(['Super Admin', 'Sales Manager', 'Team Leader']),
            403
        );
    }
}
