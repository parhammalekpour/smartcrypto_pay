<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$ids = [3767,3772];
$out = [];
DB::beginTransaction();
try {
    foreach($ids as $id) {
        $r = DB::table('jobs')->where('id',$id)->first();
        if (!$r) { $out[] = ['id'=>$id,'found'=>false]; continue; }
        $before = ['id'=>$r->id,'queue'=>$r->queue,'payload_preview'=>substr($r->payload,0,200)];
        if ($r->queue !== 'blockchain') {
            DB::table('jobs')->where('id',$id)->update(['queue'=>'blockchain']);
        }
        $after = DB::table('jobs')->where('id',$id)->first();
        $out[] = ['id'=>$id,'before'=>$before,'after'=>['id'=>$after->id,'queue'=>$after->queue]];
    }
    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    echo json_encode(['error'=>$e->getMessage()]) . PHP_EOL;
    exit(1);
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;