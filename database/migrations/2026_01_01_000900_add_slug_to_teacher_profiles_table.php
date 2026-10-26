<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('slug', 180)->nullable()->unique()->after('user_id');
        });

        // Backfill slugs for existing teachers.
        \App\Models\TeacherProfile::query()->whereNull('slug')->each(function ($teacher) {
            $base = \Illuminate\Support\Str::slug($teacher->user?->name ?? 'teacher');
            $slug = $base;
            $i = 1;

            while (\App\Models\TeacherProfile::where('slug', $slug)->where('id', '!=', $teacher->id)->exists()) {
                $slug = $base.'-'.(++$i);
            }

            $teacher->forceFill(['slug' => $slug])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
