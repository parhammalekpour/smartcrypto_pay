<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendCryptoTransaction;

// Use a distinctive transaction id to find the job
$testTxId = 9999999;
$job = new SendCryptoTransaction($testTxId);

// Push directly onto blockchain queue (bypasses PendingDispatch/afterCommit delay)
Queue::pushOn('blockchain', $job);

// Give it a moment then query jobs table for the newest SendCryptoTransaction
$row = DB::table('jobs')->where('payload','like','%SendCryptoTransaction%')->orderBy('id','desc')->first();
if ($row) {
    echo json_encode(['id'=>$row->id,'queue'=>$row->queue,'created_at'=>$row->created_at,'payload_snippet'=>substr($row->payload,0,400)], JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo "No SendCryptoTransaction job found in jobs table\n";
}
