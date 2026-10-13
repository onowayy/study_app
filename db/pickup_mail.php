<?php

require_once "db.php";

$client_id = get_client_id();

// 自分が流したボトルは拾わない。まだ拾っていないボトルを優先してランダムに1通選ぶ
$sql = "
SELECT m.*
FROM bottle_mails m
WHERE m.sender_token IS NULL OR m.sender_token <> :me
ORDER BY
    EXISTS (SELECT 1 FROM bottle_mail_receipts r
            WHERE r.mail_id = m.id AND r.receiver_token = :me2) ASC,
    RAND()
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":me" => $client_id, ":me2" => $client_id]);

$mail = $stmt->fetch(PDO::FETCH_ASSOC);

// 受信履歴に記録（同じボトルは1回だけ）
if ($mail) {
    $log = $pdo->prepare("INSERT IGNORE INTO bottle_mail_receipts (mail_id, receiver_token) VALUES (:id, :t)");
    $log->execute([":id" => $mail["id"], ":t" => $client_id]);
}

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
            src="<?= htmlspecialchars($mail["canvas_data"], ENT_QUOTES, "UTF-8") ?>"
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
｜
<a href="../bottle_mail.php#history">送受信履歴を見る</a>

</body>
</html>