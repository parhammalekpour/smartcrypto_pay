<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$txs = [300,301];
$out = [];
foreach($txs as $t) {
    $rows = DB::table('jobs')->where('payload','like','%"transactionId";i:'.$t.'%')->get();
    foreach($rows as $r) {
        $out[] = ['tx'=>$t,'id'=>$r->id,'queue'=>$r->queue,'attempts'=>$r->attempts,'created_at'=>$r->created_at,'payload_preview'=>substr($r->payload,0,400)];
    }
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;