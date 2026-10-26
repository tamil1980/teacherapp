<?php
require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$rows = $kernel->call('db:raw', ['SELECT * FROM teacher_profiles']);
echo "Fetched {$rows} rows\n";

$teachers = App\Models\TeacherProfile::all();
foreach ($teachers as $t) {
    $t->generateSlug();
    $t->save();
    echo "Updated {$t->id}: {$t->user?->name} slug:{$t->slug}\n";
}
echo "Done generating slugs\n";