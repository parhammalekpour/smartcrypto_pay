<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$target = '0x09a09538c11f170a537564e55efe3f824d1583a1';
$rows = DB::table('transactions')->where('receiver_wallet_address',$target)->orWhere('to_address',$target)->orderBy('id','desc')->get();
$out=[];
foreach($rows as $r){ $out[] = ['id'=>$r->id,'status'=>$r->status,'tx_hash'=>$r->tx_hash,'created_at'=>$r->created_at,'amount'=>$r->amount]; }
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;