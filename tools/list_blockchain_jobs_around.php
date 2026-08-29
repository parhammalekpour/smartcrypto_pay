<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$rows = DB::table('jobs')->where('queue','blockchain')->orderBy('id','asc')->limit(80)->get();
$out=[];
foreach($rows as $r){ $out[]=['id'=>$r->id,'created_at'=>$r->created_at,'attempts'=>$r->attempts,'payload_preview'=>substr($r->payload,0,200)]; }
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;