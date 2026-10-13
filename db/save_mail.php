<?php

require_once "db.php";

$client_id = get_client_id(); // 送信者（端末）を記録して送信履歴に使う

$message = $_POST["message"] ?? "";
$drawing = $_POST["drawing"] ?? "";

$sql = "INSERT INTO bottle_mails
(message, canvas_data, sender_token)
VALUES
(:message, :drawing, :sender)";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(":message", $message);
$stmt->bindValue(":drawing", $drawing);
$stmt->bindValue(":sender", $client_id);

$stmt->execute();

echo "<h2>🌊 ボトルメールを海へ流しました！</h2>";

echo "<a href='../bottle_mail.php'>戻る</a>";
echo " ｜ <a href='../bottle_mail.php#history'>送受信履歴を見る</a>";

?>
