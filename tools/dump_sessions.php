<?php
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=smart_cryptopay;charset=utf8mb4','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$stmt=$pdo->prepare('SELECT id, payload, last_activity FROM sessions ORDER BY last_activity DESC LIMIT 20');
$stmt->execute();
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r){
    echo "ID: {$r['id']}\n";
    echo "LAST_ACTIVITY: {$r['last_activity']}\n";
    echo "PAYLOAD (base64): {$r['payload'][0]}...\n"; // don't print full binary
    // attempt to decode payload if it's base64
    $p = $r['payload'];
    $decoded = @unserialize(base64_decode($p));
    if ($decoded !== false) {
        echo "UNSERIALIZED_KEYS: " . json_encode(array_keys($decoded)) . "\n";
    }
    echo "----\n";
}
