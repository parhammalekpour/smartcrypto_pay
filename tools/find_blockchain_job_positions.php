<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$ids = DB::table('jobs')->where('queue','blockchain')->orderBy('id','asc')->pluck('id')->toArray();
$targets=[3767,3772];
$out=[];
foreach($targets as $t){
    $pos = array_search($t,$ids);
    $out[] = ['id'=>$t,'found'=>($pos!==false),'position'=>$pos];
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;