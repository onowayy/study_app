<?php
// データベース接続が必要な場合はここで読み込む
require_once 'config/db_config.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>勉強大好きアプリ（仮）</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php require_once __DIR__ . '/header.php'; ?>

    <main>
        <h2>メイン画面（防衛ライン検証用）</h2>
        <!-- メンバーC：ここに円グラフや島を表示 -->
        <div id="dashboard">
            <p>ここに進行度の円グラフが表示されます</p>
            <canvas id="myChart"></canvas>
        </div>
    </main>

    <script src="js/graph.js"></script>
</body>
</html>
