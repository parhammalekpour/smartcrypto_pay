<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = env('ALCHEMY_API_KEY');
if (empty($apiKey)) {
    echo json_encode(['error'=>'Missing ALCHEMY_API_KEY in Laravel environment']);
    exit(1);
}

$node = 'node';
$script = __DIR__ . '/ethereum-client.js';
$contract = $argv[1] ?? null;
$to = $argv[2] ?? null;
$limit = $argv[3] ?? '50';
$from = $argv[4] ?? null;
$toBlock = $argv[5] ?? null;
if (!$contract || !$to || !$from) {
    echo json_encode(['error'=>'Usage: php run_node_tokenTransfers.php <contract> <to> <limit> <fromBlock> [toBlock]']);
    exit(1);
}

$cmd = escapeshellcmd($node) . ' ' . escapeshellarg($script) . ' tokenTransfers ' . escapeshellarg($contract) . ' ' . escapeshellarg($to) . ' ' . escapeshellarg($limit) . ' ' . escapeshellarg($from);
if ($toBlock) $cmd .= ' ' . escapeshellarg($toBlock);

$descriptorspec = [1 => ['pipe','w'], 2 => ['pipe','w']];
$env = array_merge($_ENV, ['ALCHEMY_API_KEY' => $apiKey]);
$process = proc_open($cmd, $descriptorspec, $pipes, __DIR__, $env);
if (!is_resource($process)) {
    echo json_encode(['error'=>'failed_to_start_node']); exit(1);
}
$out = stream_get_contents($pipes[1]); fclose($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[2]); $rc = proc_close($process);

if ($rc !== 0) {
    echo json_encode(['exit' => $rc, 'stdout' => $out, 'stderr' => $err], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(1);
}

echo $out;
exit(0);
