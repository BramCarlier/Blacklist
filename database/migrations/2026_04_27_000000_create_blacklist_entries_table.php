<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('blacklist_entries', function(Blueprint $t){ $t->id(); $t->string('first_name'); $t->string('last_name'); $t->date('birth_date')->nullable(); $t->string('id_document_number')->index(); $t->string('nationality')->nullable(); $t->string('reason'); $t->string('location')->index(); $t->enum('source',['manual','camera','nfc'])->default('manual'); $t->enum('status',['active','expired','appealed'])->default('active')->index(); $t->text('notes')->nullable(); $t->string('scanned_payload_hash',64)->nullable()->index(); $t->foreignId('created_by')->constrained('users'); $t->timestamp('last_checked_at')->nullable(); $t->timestamps(); $t->softDeletes(); $t->unique(['id_document_number','location','deleted_at'], 'blacklist_doc_location_unique'); }); }
 public function down(): void { Schema::dropIfExists('blacklist_entries'); }
};
