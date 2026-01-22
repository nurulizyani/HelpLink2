<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {

            // AI classification result
            $table->string('ai_document_type')->nullable()->after('document');

            // Ringkasan maklumat penting (auto)
            $table->text('ai_summary')->nullable()->after('ai_document_type');

            // Maklumat berstruktur (JSON)
            $table->json('ai_extracted_data')->nullable()->after('ai_summary');

            // Keyakinan AI (0–100)
            $table->unsignedTinyInteger('ai_confidence')->nullable()->after('ai_extracted_data');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'ai_document_type',
                'ai_summary',
                'ai_extracted_data',
                'ai_confidence',
            ]);
        });
    }
};
