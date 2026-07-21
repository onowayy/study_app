<?php

require_once "db.php";

$sql = "
SELECT *
FROM bottle_mails
ORDER BY RAND()
LIMIT 1
";

$stmt = $pdo->query($sql);

$mail = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ボトル開封</title>
</head>
<body>

<h1>📩 ボトルを開封しました</h1>

<?php if($mail): ?>

    <h3>メッセージ</h3>

    <p>
        <?=
            $mail["message"] === ""
            ? "（メッセージなし）"
            : nl2br(htmlspecialchars($mail["message"]))
        ?>  
    </p>
    <h3>お絵描き</h3>

    <?php if(!empty($mail["canvas_data"])): ?>

        <img
            src="<?= $mail["canvas_data"] ?>"
            width="400">

    <?php else: ?>

        <p>絵はありません。</p>

    <?php endif; ?>

    <hr>

    <h3>リアクション</h3>

    <button
        type="button"
        onclick="alert('ナイス！を送りました！')">
        👍 ナイス！
    </button>

    <button
        type="button"
        onclick="alert('がんばれ！を送りました！')">
        🔥 がんばれ！
    </button>

    <button
        type="button"
        onclick="alert('いいね！を送りました！')">
        😊 いいね！
    </button>

<?php else: ?>

    <p>まだボトルが流れていません。</p>

<?php endif; ?>

<br>

<a href="../bottle_mail.php">
    戻る
</a>

</body>
</html>