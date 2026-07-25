<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds columns needed to import the legacy attendance system and to support
 * its richer attendance model (lunch punches) and WhatsApp notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Map back to the legacy users.id so imports are idempotent.
            $table->unsignedBigInteger('legacy_id')->nullable()->index()->after('id');
            $table->string('salary_type')->default('monthly')->after('status'); // hourly|monthly
            $table->time('shift_start_time')->nullable()->after('salary_type');
            $table->boolean('whatsapp_opt_in')->default(true)->after('shift_start_time');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->index()->after('id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->index()->after('id');
            $table->timestamp('lunch_out_at')->nullable()->after('check_out_at');
            $table->timestamp('lunch_in_at')->nullable()->after('lunch_out_at');
            $table->unsignedInteger('worked_minutes')->default(0)->after('working_hours');
            $table->text('leave_reason')->nullable()->after('remarks');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', fn (Blueprint $t) => $t->dropColumn('legacy_id'));
        Schema::table('attendances', function (Blueprint $t) {
            $t->dropColumn(['legacy_id', 'lunch_out_at', 'lunch_in_at', 'worked_minutes', 'leave_reason']);
        });
        Schema::table('branches', fn (Blueprint $t) => $t->dropColumn('legacy_id'));
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['legacy_id', 'salary_type', 'shift_start_time', 'whatsapp_opt_in']);
        });
    }
};
