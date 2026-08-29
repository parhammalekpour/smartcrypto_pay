<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Use a known user who has wallets; user id 60 used earlier
$userId = 60;
\Illuminate\Support\Facades\Auth::loginUsingId($userId);

// Start session
session()->start();

$sessionId = session()->getId();
$sessionToken = session()->token();

echo "SESSION_ID:" . $sessionId . PHP_EOL;
echo "SESSION_TOKEN:" . $sessionToken . PHP_EOL;

// Render the send view (may require $wallets variable). Try to get wallets for user
$wallets = \App\Models\Wallet::where('user_id', $userId)->get();
try {
    $html = view('user.send', compact('wallets'))->render();
    // extract input _token value
    if (preg_match('/name="_token" value="([^"]+)"/', $html, $m)) {
        echo "FORM_TOKEN:" . $m[1] . PHP_EOL;
    } else {
        echo "FORM_TOKEN: NOT FOUND" . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "RENDER_ERROR:" . $e->getMessage() . PHP_EOL;
}

// Show corresponding session record in DB
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=smart_cryptopay;charset=utf8mb4','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$stmt=$pdo->prepare('SELECT id,payload,last_activity FROM sessions WHERE id = ? LIMIT 1');
$stmt->execute([$sessionId]);
$row=$stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "SESSION_ROW_ID:" . $row['id'] . PHP_EOL;
    echo "LAST_ACTIVITY:" . $row['last_activity'] . PHP_EOL;
    $payload = $row['payload'];
    $decoded = @unserialize(base64_decode($payload));
    if ($decoded !== false) {
        echo "PAYLOAD_KEYS:" . json_encode(array_keys($decoded)) . PHP_EOL;
        if (isset($decoded['_token'])) echo "PAYLOAD_TOKEN:" . $decoded['_token'] . PHP_EOL;
    } else {
        echo "PAYLOAD_NOT_UNSERIALIZABLE\n";
    }
} else {
    echo "NO_SESSION_ROW_FOUND\n";
}
