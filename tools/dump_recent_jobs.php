<?php
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=smart_cryptopay;charset=utf8mb4','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$stmt=$pdo->prepare('SELECT id,queue,payload,created_at FROM jobs ORDER BY id DESC LIMIT 10');
$stmt->execute();
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r){
    echo json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n\n";
}
