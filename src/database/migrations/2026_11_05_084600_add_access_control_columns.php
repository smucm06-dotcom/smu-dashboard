<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A department role links to its department. The admin role has none.
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('guard_name')->unique()->constrained()->cascadeOnDelete();
        });

        // Each initiative has one permission, linked by id so renaming either one is safe.
        Schema::table('permissions', function (Blueprint $table) {
            $table->foreignId('initiative_id')->nullable()->after('guard_name')->unique()->constrained()->cascadeOnDelete();
        });

        // Initiative names can reach 255 characters, which is too long for a list of choices.
        Schema::table('initiatives', function (Blueprint $table) {
            $table->string('short_name', 60)->nullable()->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('must_change_password')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'must_change_password']);
        });

        Schema::table('initiatives', function (Blueprint $table) {
            $table->dropColumn('short_name');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('initiative_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
