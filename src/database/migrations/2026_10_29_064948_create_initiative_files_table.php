<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initiative_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('initiative_id')
                ->constrained('initiatives')
                ->restrictOnDelete();

            // department_list, our_visits, our_report or department_response
            $table->string('type', 30);

            // A department response points to the report it answers.
            // Unique, so each report can only have one response.
            $table->foreignId('reply_to_id')
                ->nullable()
                ->unique()
                ->constrained('initiative_files')
                ->restrictOnDelete();

            $table->string('original_name');
            $table->string('stored_path');
            $table->unsignedInteger('row_count')->nullable();

            // The date the file was actually sent or received, not its upload date.
            $table->date('document_date')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['initiative_id', 'type'], 'initiative_files_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initiative_files');
    }
};
