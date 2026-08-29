<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\SendCryptoTransaction;
use Illuminate\Support\Facades\DB;

$ids = [300,301];
$out = [];
foreach ($ids as $id) {
    echo "\n== Running SendCryptoTransaction for id=$id ==\n";
    $txBefore = DB::table('transactions')->where('id',$id)->first();
    echo "Before: status=" . ($txBefore->status ?? 'missing') . " tx_hash=" . ($txBefore->tx_hash ?? 'null') . "\n";
    $job = new SendCryptoTransaction($id);
    try {
        $job->handle();
        echo "Handle completed for id=$id\n";
    } catch (\Throwable $e) {
        echo "Handle threw for id=$id: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
    $txAfter = DB::table('transactions')->where('id',$id)->first();
    echo "After: status=" . ($txAfter->status ?? 'missing') . " tx_hash=" . ($txAfter->tx_hash ?? 'null') . "\n";
    $out[$id] = ['before' => $txBefore ? ['status'=>$txBefore->status,'tx_hash'=>$txBefore->tx_hash] : null, 'after' => $txAfter ? ['status'=>$txAfter->status,'tx_hash'=>$txAfter->tx_hash] : null];
}

echo json_encode($out, JSON_PRETTY_PRINT) . "\n";
