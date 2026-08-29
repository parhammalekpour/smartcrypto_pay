<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$ids = [300,301];
$out=[];
foreach($ids as $id){
    $r = DB::table('transactions')->where('id',$id)->first();
    $out[$id] = $r ? (array)$r : null;
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;