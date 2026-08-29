<?php
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=smart_cryptopay;charset=utf8mb4','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$stmt=$pdo->prepare('SELECT id,wallet_address,currency,encrypted_private_key,created_at FROM wallets WHERE id = 210 LIMIT 1');
$stmt->execute();
$row=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$row){ echo "NOT_FOUND\n"; exit(0);} 
echo json_encode($row, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
