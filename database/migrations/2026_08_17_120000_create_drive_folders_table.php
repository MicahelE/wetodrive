<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maps a user's folder path to the Drive folder id we created for it.
     *
     * The app holds the drive.file scope, so it cannot search Drive for a folder
     * it made earlier: whatever it does not remember, it cannot find. This table
     * is that memory, and it doubles as the "recent folders" list on the form.
     */
    public function up(): void
    {
        Schema::create('drive_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // A '/'-joined path, created at the top level of My Drive. No row
            // exists for "no folder": that case never touches Drive at all.
            $table->string('path');
            $table->string('drive_folder_id');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_folders');
    }
};
