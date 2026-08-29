<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$txs = [300,301];
$out = [];
foreach($txs as $t) {
    $r = DB::table('transactions')->where('id',$t)->first();
    if ($r) {
        $out[] = ['id'=>$r->id,'status'=>$r->status,'tx_hash'=>$r->tx_hash,'wallet_id'=>$r->wallet_id,'amount'=>$r->amount,'created_at'=>$r->created_at];
    } else {
        $out[] = ['id'=>$t,'found'=>false];
    }
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;