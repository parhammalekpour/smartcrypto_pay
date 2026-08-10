<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Notification;

$rows = Notification::orderBy('id', 'desc')->take(10)->get()->map(function($n){
    return [
        'id' => $n->id,
        'user_id' => $n->user_id,
        'title' => $n->title,
        'message' => $n->message,
        'read' => (bool) $n->read,
        'created_at' => $n->created_at ? $n->created_at->toDateTimeString() : null,
    ];
});

echo json_encode($rows, JSON_PRETTY_PRINT) . PHP_EOL;
