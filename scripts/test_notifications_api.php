<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\NotificationController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::find(44);
if (!$user) {
    echo "User 44 not found\n";
    exit(1);
}

// Log in as the user
Auth::loginUsingId(44);

$controller = new NotificationController();

$res = $controller->getUnreadCount();
echo "UNREAD COUNT RESPONSE:\n" . $res->getContent() . "\n";

$res2 = $controller->getNotifications();
echo "NOTIFICATIONS LIST RESPONSE:\n" . $res2->getContent() . "\n";
