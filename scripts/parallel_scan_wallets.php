<?php
// Parallel wallet scanner: spawns multiple php processes that run run_wallet_scan_single.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Wallet;

// Simple arg parsing
$options = [];
foreach ($argv as $i => $a) {
    if ($i === 0) continue;
    if (str_starts_with($a, '--')) {
        $kv = explode('=', substr($a, 2), 2);
        $options[$kv[0]] = $kv[1] ?? true;
    }
}

$concurrency = isset($options['concurrency']) ? (int)$options['concurrency'] : 3;
$limitPerWallet = isset($options['limit']) ? (int)$options['limit'] : (int)config('ethereum.scan_wallets_per_job', 20);
$currencyFilter = isset($options['currency']) ? $options['currency'] : null;

echo "Parallel wallet scan starting (concurrency={$concurrency}, limitPerWallet={$limitPerWallet})\n";

$query = Wallet::query();
if ($currencyFilter) {
    $query->where('currency', $currencyFilter);
}
// prioritize oldest scanned
$query->orderByRaw('last_scanned_block IS NULL DESC')->orderBy('last_scanned_block','asc');
$walletIds = $query->pluck('id')->toArray();
$total = count($walletIds);
if ($total === 0) {
    echo "No wallets found to scan.\n";
    exit(0);
}

echo "Found {$total} wallets. Starting workers...\n";

$script = __DIR__ . DIRECTORY_SEPARATOR . 'run_wallet_scan_single.php';
$running = [];
$index = 0;

while ($index < $total || count($running) > 0) {
    // spawn new processes up to concurrency
    while ($index < $total && count($running) < $concurrency) {
        $wid = $walletIds[$index];
        $cmd = [
            PHP_BINARY,
            $script,
            (string)$wid,
            (string)$limitPerWallet,
        ];

        // On Windows use proc_open with pipes; on *nix same approach works.
        $descriptors = [1 => ['pipe','w'], 2 => ['pipe','w']];
        $proc = proc_open($cmd, $descriptors, $pipes);
        if (is_resource($proc)) {
            $running[(int)$proc] = ['proc' => $proc, 'pipes' => $pipes, 'wallet' => $wid, 'start' => microtime(true)];
            echo "Spawned scan for wallet {$wid} (procId=" . (int)$proc . ")\n";
        } else {
            echo "Failed to spawn process for wallet {$wid}, running inline.\n";
            // fallback to inline
            passthru(implode(' ', array_map('escapeshellarg', $cmd)));
        }

        $index++;
    }

    // poll running processes
    foreach ($running as $key => $meta) {
        $proc = $meta['proc'];
        $status = proc_get_status($proc);
        if (!$status['running']) {
            // read pipes
            $out = stream_get_contents($meta['pipes'][1]);
            $err = stream_get_contents($meta['pipes'][2]);
            fclose($meta['pipes'][1]); fclose($meta['pipes'][2]);
            proc_close($proc);
            $dur = round(microtime(true) - $meta['start'], 3);
            echo "Process {$key} for wallet {$meta['wallet']} finished (duration={$dur}s)\n";
            if (trim($out) !== '') echo "OUTPUT:\n" . $out . "\n";
            if (trim($err) !== '') echo "ERROR:\n" . $err . "\n";
            unset($running[$key]);
        }
    }

    // small sleep to avoid busy-loop
    usleep(200000); // 200ms
}

echo "All wallet scans completed.\n";
