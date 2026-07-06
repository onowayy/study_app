<?php

require_once "db.php";

$message = $_POST["message"];
$drawing = $_POST["drawing"];

$sql = "INSERT INTO bottle_mails
(message, canvas_data)
VALUES
(:message, :drawing)";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(":message", $message);
$stmt->bindValue(":drawing", $drawing);

$stmt->execute();

echo "<h2>🌊 ボトルメールを海へ流しました！</h2>";

echo "<a href='../bottle_mail.php'>戻る</a>";

?>