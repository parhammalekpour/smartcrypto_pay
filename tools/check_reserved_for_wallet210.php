<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$rows = DB::table('transactions')->where('wallet_id',210)->whereIn('status',['processing','broadcasting','pending'])->get();
$out=[];
foreach($rows as $r){ $out[]=['id'=>$r->id,'status'=>$r->status,'tx_hash'=>$r->tx_hash,'amount'=>$r->amount,'created_at'=>$r->created_at]; }
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;