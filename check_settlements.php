<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\PaymentRequest;

$email = 'parhammalekpour30@gmail.com';
$u = User::where('email', $email)->first();
if (!$u) {
    echo "NO_USER\n";
    exit(0);
}

echo "USER_ID: " . $u->id . "\n";
$q = PaymentRequest::where('merchant_id', $u->id)->where('status', 'paid');

echo "COUNT: " . $q->count() . "\n";
echo "SUM: " . $q->sum('amount') . "\n";
echo "AVG: " . $q->avg('amount') . "\n";

foreach ($q->take(5)->get() as $p) {
    echo $p->id . '|' . $p->invoice_number . '|' . $p->amount . '|' . $p->status . "\n";
}
