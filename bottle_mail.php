<?php
require_once __DIR__ . '/db/db.php';
$client_id = get_client_id();

// 送信履歴（自分が流したボトル）と、何人が拾ったか
$stmt = $pdo->prepare("
    SELECT m.id, m.message, m.canvas_data, m.created_at,
           (SELECT COUNT(*) FROM bottle_mail_receipts r WHERE r.mail_id = m.id) AS pickup_count
    FROM bottle_mails m
    WHERE m.sender_token = :t
    ORDER BY m.id DESC
    LIMIT 50
");
$stmt->execute([':t' => $client_id]);
$sent_mails = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 受信履歴（自分が拾ったボトル）
$stmt = $pdo->prepare("
    SELECT m.message, m.canvas_data, r.received_at
    FROM bottle_mail_receipts r
    JOIN bottle_mails m ON m.id = r.mail_id
    WHERE r.receiver_token = :t
    ORDER BY r.id DESC
    LIMIT 50
");
$stmt->execute([':t' => $client_id]);
$received_mails = $stmt->fetchAll(PDO::FETCH_ASSOC);

function render_mail_item(array $m, string $date, string $meta = ''): void {
    echo '<div class="history-item">';
    echo '<div class="history-date">' . h($date) . ($meta !== '' ? ' ／ ' . h($meta) : '') . '</div>';
    echo '<div class="history-msg">' . ($m['message'] === '' || $m['message'] === null
        ? '<span style="color:#888;">（メッセージなし）</span>'
        : nl2br(h($m['message']))) . '</div>';
    if (!empty($m['canvas_data']) && strpos($m['canvas_data'], 'data:image/') === 0) {
        echo '<img class="history-img" alt="お絵描き" src="' . h($m['canvas_data']) . '">';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>ボトルメール</title>

    <!-- CSSを読み込む -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .history-tabs { display: flex; gap: 8px; margin: 12px 0; }
        .history-tabs button { background: #e0e0e0; color: #333; border-radius: 20px; padding: 7px 16px; font-weight: bold; }
        .history-tabs button.active { background: #2f80c4; color: #fff; }
        .history-panel { display: none; }
        .history-panel.active { display: block; }
        .history-item { border: 1px solid #d5e3ec; border-radius: 8px; padding: 12px; margin-bottom: 10px; background: #fafcfe; }
        .history-date { font-size: 0.8rem; color: #667; margin-bottom: 6px; }
        .history-msg { word-break: break-word; }
        .history-img { max-width: 100%; width: 300px; height: auto; border: 1px solid #ccc; margin-top: 8px; background: #fff; }
        .history-empty { color: #888; }
    </style>
</head>

<body>

    <?php require_once __DIR__ . '/header.php'; ?>

    <div class="container">

        <h1>🌊 ボトルメール 🌊</h1>

        <p>メッセージを書いて海へ流そう！</p>

        <!-- 送信用フォーム -->
        <form id="mailForm" action="db/save_mail.php" method="POST">

            <!-- メッセージ入力欄 -->
            <label for="message">
                メッセージ
            </label>

            <br>

            <textarea
                id="message"
                name="message"
                rows="6"
                cols="50"
                placeholder="ここにメッセージを書いてください"></textarea>

            <br><br>

            <!-- Step3で使うキャンバス -->
            <canvas
                id="mailCanvas"
                width="400"
                height="250"
                style="border:1px solid black;">
            </canvas>

            <br><br>

            <button
                type="button"
                id="clearCanvas">
                消す
            </button>

            <br><br>

            <!-- 後で絵を送るため -->
            <input
                type="hidden"
                name="drawing"
                id="drawing">

            <script>

                document.getElementById("mailForm").addEventListener("submit",function(){

                const canvas = document.getElementById("mailCanvas");

                document.getElementById("drawing").value =
                canvas.toDataURL();});

            </script>    

            <!-- 送信ボタン -->
            <button type="submit">
                🌊 海に流す
            </button>

        </form>
    <hr>

    <h2>🏖 ボトルを拾う</h2>

    <form action="db/pickup_mail.php" method="GET">

        <button type="submit">
            📩 ボトルを拾う
        </button>

    </form>

    <hr>

    <h2 id="history">📜 送受信履歴</h2>

    <div class="history-tabs">
        <button type="button" class="active" data-target="panel-sent">📤 送信（<?= count($sent_mails) ?>）</button>
        <button type="button" data-target="panel-received">📥 受信（<?= count($received_mails) ?>）</button>
    </div>

    <div id="panel-sent" class="history-panel active">
        <?php if (empty($sent_mails)): ?>
            <p class="history-empty">まだボトルを流していません。</p>
        <?php else: ?>
            <?php foreach ($sent_mails as $m): ?>
                <?php render_mail_item($m, $m['created_at'], $m['pickup_count'] . '人が拾いました'); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="panel-received" class="history-panel">
        <?php if (empty($received_mails)): ?>
            <p class="history-empty">まだボトルを拾っていません。</p>
        <?php else: ?>
            <?php foreach ($received_mails as $m): ?>
                <?php render_mail_item($m, $m['received_at']); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    </div>

    <script>
        document.querySelectorAll('.history-tabs button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.history-tabs button').forEach(function (b) { b.classList.remove('active'); });
                document.querySelectorAll('.history-panel').forEach(function (p) { p.classList.remove('active'); });
                btn.classList.add('active');
                document.getElementById(btn.dataset.target).classList.add('active');
            });
        });
    </script>

    <!-- Step3で作るJavaScript -->
    <script src="js/draw.js"></script>

</body>

</html>
