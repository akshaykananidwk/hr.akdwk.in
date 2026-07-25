<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();
            $table->string('check_in_photo')->nullable();
            $table->enum('method', ['gps', 'qr', 'manual', 'face'])->default('gps');
            $table->enum('status', ['present', 'absent', 'half_day', 'leave', 'holiday', 'week_off'])->default('present');
            $table->boolean('is_late')->default(false);
            $table->decimal('working_hours', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('distance_travelled', 8, 2)->default(0); // km
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('work_summary')->nullable();
            $table->unsignedInteger('total_visits')->default(0);
            $table->unsignedInteger('total_calls')->default(0);
            $table->unsignedInteger('total_followups')->default(0);
            $table->unsignedInteger('total_demos')->default(0);
            $table->unsignedInteger('total_closings')->default(0);
            $table->decimal('total_collection', 12, 2)->default(0);
            $table->decimal('expenses', 12, 2)->default(0);
            $table->decimal('petrol_expense', 12, 2)->default(0);
            $table->string('voice_note')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('status', ['draft', 'submitted', 'reviewed'])->default('submitted');
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        // Individual businesses visited in a daily report
        Schema::create('report_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('owner_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('map_location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('outcome', ['interested', 'not_interested', 'follow_up', 'closed'])->default('interested');
            $table->string('reason')->nullable();
            $table->string('competitor')->nullable();
            $table->date('expected_closing_date')->nullable();
            $table->date('next_followup_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('photo')->nullable();
            $table->string('visiting_card')->nullable();
            $table->timestamps();
        });

        // GPS route track points captured through the day
        Schema::create('location_pings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_pings');
        Schema::dropIfExists('report_visits');
        Schema::dropIfExists('daily_reports');
        Schema::dropIfExists('attendances');
    }
};
