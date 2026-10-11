<?php
// ==========================================
// ログイン認証の共通処理
// ログインが必要なページの先頭（DB接続の直後）で
//   require_once 'auth.php';
//   require_login();
// として使う。db/ 配下のファイルからは
//   require_once __DIR__ . '/../auth.php';
// のようにパスを調整すること。
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ログインしていなければログイン画面に飛ばして処理を止める
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// 現在ログイン中のユーザーID（未ログインなら null）
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// 現在ログイン中のユーザー名（未ログインなら null）
function current_username() {
    return $_SESSION['username'] ?? null;
}
