<?php
// One-off script to dump wallet row
$host='127.0.0.1'; $port=3306; $db='smart_cryptopay'; $user='root'; $pass='';
try{
    $pdo=new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $stmt=$pdo->prepare('SELECT id,wallet_address,currency,encrypted_private_key FROM wallets WHERE id = 210 LIMIT 1');
    $stmt->execute();
    $row=$stmt->fetch(PDO::FETCH_ASSOC);
    if(!$row){ echo "NOT_FOUND\n"; exit(0);} 
    echo json_encode($row, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
