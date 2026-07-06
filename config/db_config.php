<?php
// データベース接続設定の共通ルール
$host = 'localhost';
$dbname = 'study_app'; // 全員このDB名で作成する
$user = 'root';        // XAMPPのデフォルト
$pass = '';            // XAMPPのデフォルト

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    // エラーを表示するモードに設定（デバッグしやすくするため）
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}
?>