<?php
$teachers = App\Models\TeacherProfile::with('user')->get();
echo "Teachers: " . $teachers->count() . "\n";
foreach ($teachers as $t) {
    $slug = is_null($t->slug) ? 'NULL' : $t->slug;
    $name = is_null($t->user?->name) ? 'NO USER' : $t->user->name;
    echo "  - ID:{$t->id} Name:$name Slug:$slug\n";
}

$students = App\Models\User::where('role', 'parent')->get();
echo "Parent students: " . $students->count() . "\n";
foreach ($students as $s) {
    echo "  - ID:{$s->id} Name:{$s->name} Email:{$s->email}\n";
}