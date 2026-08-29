<?php
$sessionId = $argv[1] ?? '';
if ($sessionId === '') { echo "USAGE: php get_session_token.php <sessionId>\n"; exit(1);} 
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=smart_cryptopay;charset=utf8mb4','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$stmt=$pdo->prepare('SELECT payload FROM sessions WHERE id = ? LIMIT 1');
$stmt->execute([$sessionId]);
$row=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$row){ echo "SESSION_NOT_FOUND\n"; exit(2);} 
$payload = $row['payload'];
$decoded = @unserialize(base64_decode($payload));
if($decoded === false){ echo "UNSERIALIZE_FAILED\n"; exit(3);} 
if(isset($decoded['_token'])) echo $decoded['_token'] . PHP_EOL; else echo "NO_TOKEN_IN_SESSION\n";