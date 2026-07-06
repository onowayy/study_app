<?php
require_once 'config/db_config.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM user_status WHERE id = 1");
    $stmt->execute();

    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        $user_data = ['materials' => 0, 'food' => 0, 'math_level' => 1, 'eng_level' => 1, 'rikei_level' => 1];
    }
} catch (PDOException $e) {
    die("ゲームデータの読み込みに失敗しました： ". $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>課題とゲーム連動</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="game-status-box" style="background: #eef7ff; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <h3>現在の島の状況</h3>
        <p>
            🧱 建築資材： <strong><?php echo $user_data['materials']; ?></strong> 個 ｜ 
            🐟 エサ： <strong><?php echo $user_data['food']; ?></strong> 個 ｜ 
            📐 数学レベル： <strong>Lv. <?php echo $user_data['math_level']; ?></strong>
            🅰️ 英語レベル： <strong>Lv. <?php echo $user_data['eng_level']; ?></strong>
            🧪 理系レベル： <strong>Lv. <?php echo $user_data['rikei_level']; ?></strong>
            
        </p>
    </div>
    <!-- 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 🌟 -->

    <main>
        <!-- ここに浦井が作る課題一覧が載る -->
    </main>
</body>
</html>
