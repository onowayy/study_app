<?php
require_once 'config/db_config.php';
require_once 'auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = "ユーザー名またはパスワードが違います。";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - 勉強大好きアプリ（仮）</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container" style="max-width: 400px;">
        <h1>🏝️ ログイン</h1>

        <?php if ($error): ?>
            <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST">
            <p>
                <label>ユーザー名:</label><br>
                <input type="text" name="username" required style="width: 100%;">
            </p>
            <p>
                <label>パスワード:</label><br>
                <input type="password" name="password" required style="width: 100%;">
            </p>
            <button type="submit">ログイン</button>
        </form>

        <p style="margin-top: 15px;">アカウントが無い方は <a href="register.php">新規登録</a></p>
    </div>
</body>
</html>
