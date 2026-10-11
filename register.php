<?php
require_once 'config/db_config.php';
require_once 'auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = "";
$old_username = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $old_username = $username;

    if ($username === '' || $password === '') {
        $error = "ユーザー名とパスワードを入力してください。";
    } elseif (mb_strlen($username) > 50) {
        $error = "ユーザー名は50文字以内で入力してください。";
    } elseif (strlen($password) < 4) {
        $error = "パスワードは4文字以上にしてください。";
    } elseif ($password !== $password_confirm) {
        $error = "パスワード（確認）が一致していません。";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $check->execute([':username' => $username]);

        if ($check->fetch()) {
            $error = "そのユーザー名は既に使われています。";
        } else {
            try {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :hash)");
                $insert->execute([':username' => $username, ':hash' => $hash]);
                $new_user_id = $pdo->lastInsertId();

                // 新規ユーザーの初期ステータス行も一緒に作成（資材0・各Lv.1からスタート）
                $initStatus = $pdo->prepare("
                    INSERT INTO user_status (user_id, materials, math_level, english_level, rikei_level)
                    VALUES (:user_id, 0, 1, 1, 1)
                ");
                $initStatus->execute([':user_id' => $new_user_id]);

                $pdo->commit();

                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['username'] = $username;

                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "登録中にエラーが発生しました: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録 - 勉強大好きアプリ（仮）</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container" style="max-width: 400px;">
        <h1>🏝️ 新規登録</h1>

        <?php if ($error): ?>
            <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST">
            <p>
                <label>ユーザー名:</label><br>
                <input type="text" name="username" required maxlength="50" style="width: 100%;"
                    value="<?= htmlspecialchars($old_username, ENT_QUOTES, 'UTF-8') ?>">
            </p>
            <p>
                <label>パスワード（4文字以上）:</label><br>
                <input type="password" name="password" required style="width: 100%;">
            </p>
            <p>
                <label>パスワード（確認）:</label><br>
                <input type="password" name="password_confirm" required style="width: 100%;">
            </p>
            <button type="submit">登録する</button>
        </form>

        <p style="margin-top: 15px;">すでにアカウントをお持ちの方は <a href="login.php">ログイン</a></p>
    </div>
</body>
</html>
