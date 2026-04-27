<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blacklist_entries', function (Blueprint $table) {
            $table->enum('record_type', ['denied_access', 'official_warning', 'gas_fine_recommended', 'gas_fine_issued'])->default('denied_access')->after('nationality')->index();
            $table->date('incident_date')->nullable()->after('record_type')->index();
            $table->string('incident_reference')->nullable()->after('incident_date')->index();
            $table->boolean('official_warning_given')->default(false)->after('reason')->index();
            $table->timestamp('official_warning_given_at')->nullable()->after('official_warning_given');
            $table->boolean('gas_fine_recommended')->default(false)->after('official_warning_given_at')->index();
            $table->enum('gas_fine_status', ['none', 'recommended', 'sent_to_authority', 'issued', 'paid', 'cancelled', 'appealed'])->default('none')->after('gas_fine_recommended')->index();
            $table->string('gas_fine_reference')->nullable()->after('gas_fine_status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('blacklist_entries', function (Blueprint $table) {
            $table->dropColumn([
                'record_type',
                'incident_date',
                'incident_reference',
                'official_warning_given',
                'official_warning_given_at',
                'gas_fine_recommended',
                'gas_fine_status',
                'gas_fine_reference',
            ]);
        });
    }
};
