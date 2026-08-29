<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$txs = [300,301];
$out = [];
DB::beginTransaction();
try {
    foreach($txs as $t) {
        // find job rows referencing this transactionId
        $rows = DB::table('jobs')->where('payload','like','%"transactionId";i:'.$t.'%')->get();
        foreach($rows as $r) {
            $before = ['id'=>$r->id,'queue'=>$r->queue,'payload_preview'=>substr($r->payload,0,200)];
            if ($r->queue !== 'blockchain') {
                DB::table('jobs')->where('id',$r->id)->update(['queue'=>'blockchain']);
            }
            $afterRow = DB::table('jobs')->where('id',$r->id)->first();
            $after = ['id'=>$afterRow->id,'queue'=>$afterRow->queue];
            $out[] = ['tx'=>$t,'before'=>$before,'after'=>$after];
        }
    }
    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    echo json_encode(['error'=>$e->getMessage()]) . PHP_EOL;
    exit(1);
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;