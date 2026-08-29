<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$ids=[3767,3772];
$out=[];
foreach($ids as $id){
    $r=DB::table('jobs')->where('id',$id)->first();
    if($r){
        $out[]=['id'=>$r->id,'queue'=>$r->queue,'attempts'=>$r->attempts,'reserved_at'=>$r->reserved_at,'available_at'=>$r->available_at,'created_at'=>$r->created_at];
    } else { $out[]=['id'=>$id,'found'=>false]; }
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;