<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Call center files are too big to read while the browser waits, so each one is read in the background.
    // This table follows each of those uploads: how far it has got, then its result or the problems found.
    // Every call center campaign shares it.
    public function up(): void
    {
        Schema::create('call_center_uploads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('initiative_id')->constrained()->cascadeOnDelete();

            // Which upload button: our calls file or the department list (InitiativeFile types).
            $table->string('type', 30);
            $table->string('original_name');

            // Where the file is kept while it is read. It stays there once it is saved.
            $table->string('stored_path');

            // The file's SHA-256, so the same calls file is never added twice.
            $table->char('fingerprint', 64)->nullable();

            // queued, running, done or failed; and while running, the step it is on.
            $table->string('status', 20)->default('queued');
            $table->string('phase', 30)->nullable();

            // Rows read so far, out of the rows in the file.
            $table->unsignedInteger('rows_total')->nullable();
            $table->unsignedInteger('rows_done')->default(0);

            // What was saved, or why nothing was.
            $table->json('result')->nullable();

            // The uploaded file's record, once the rows are saved.
            $table->foreignId('initiative_file_id')->nullable()->constrained('initiative_files')->nullOnDelete();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // When the person who uploaded the file was shown its result, so a result that came in while they
            // were away is shown when they come back, once.
            $table->timestamp('result_seen_at')->nullable();

            $table->timestamps();

            $table->index(['initiative_id', 'status']);
            $table->index(['initiative_id', 'type', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_center_uploads');
    }
};
