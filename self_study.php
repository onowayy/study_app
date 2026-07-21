<?php
require_once 'config/db_config.php';

// 理解度レベルの設定（0:未着手 〜 3:マスター）
$level_labels = [
    0 => ['label' => '未着手',   'badge' => 'lvl-0'],
    1 => ['label' => '要復習★', 'badge' => 'lvl-1'],
    2 => ['label' => '理解★★', 'badge' => 'lvl-2'],
    3 => ['label' => 'マスター★★★', 'badge' => 'lvl-3']
];

// ==========================================
// レベルUP処理（ボタンが押されたら level + 1 ）
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_id = $_POST['unit_id'] ?? null;
    $current_level = isset($_POST['current_level']) ? (int)$_POST['current_level'] : 0;

    if ($unit_id) {
        // レベルを1上げる（3の次は0に戻る）
        $next_level = ($current_level + 1) % 4;

        try {
            $stmt = $pdo->prepare("UPDATE self_study_units SET understanding_level = :level WHERE id = :id");
            $stmt->execute([':level' => $next_level, ':id' => $unit_id]);
        } catch (PDOException $e) {
            // エラー処理
        }
    }
}

// データの取得と進捗率計算
$subjects = [
    'math' => '数学', 'english' => '英語', 'physics' => '物理', 'chemistry' => '化学', 'information' => '情報'
];

$units_by_subject = [];
$progress_data = [];

foreach ($subjects as $key => $name) {
    $stmt = $pdo->prepare("SELECT * FROM self_study_units WHERE subject = :subject ORDER BY id ASC");
    $stmt->execute([':subject' => $key]);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $units_by_subject[$key] = $units;

    // 理解度の合計ポイントから進捗％を算出
    $total_units = count($units);
    $max_points = $total_units * 3; // 全単元がLv.3なら満点
    $current_points = 0;

    foreach ($units as $u) {
        $current_points += $u['understanding_level'];
    }

    $percent = $max_points > 0 ? round(($current_points / $max_points) * 100) : 0;

    $progress_data[$key] = [
        'name' => $name, 'points' => $current_points, 'max' => $max_points, 'percent' => $percent
    ];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>段階的・自習進捗管理</title>
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 20px auto; padding: 0 15px; background: #f4f6f8; }
        .subject-block { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .progress-bar-bg { background: #e0e0e0; border-radius: 6px; height: 14px; width: 100%; overflow: hidden; margin: 10px 0; }
        .progress-bar-fill { background: linear-gradient(90deg, #4caf50, #81c784); height: 100%; transition: width 0.4s ease; }
        
        .unit-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 12px; margin-top: 15px; }
        .unit-card { background: #fafafa; border: 1px solid #e0e0e0; padding: 12px; border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between; }
        
        /* レベル別のデザイン（バッジ・枠線） */
        .lvl-0 { border-left: 5px solid #ccc; }
        .lvl-1 { border-left: 5px solid #ffb74d; background: #fffde7; }
        .lvl-2 { border-left: 5px solid #64b5f6; background: #e3f2fd; }
        .lvl-3 { border-left: 5px solid #81c784; background: #e8f5e9; }

        .btn-lvl { border: none; padding: 6px 12px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 0.85em; }
        .btn-lvl-0 { background: #e0e0e0; color: #666; }
        .btn-lvl-1 { background: #ffe082; color: #f57f17; }
        .btn-lvl-2 { background: #90caf9; color: #1565c0; }
        .btn-lvl-3 { background: #a5d6a7; color: #1b5e20; }
        .btn-lvl:hover { opacity: 0.8; transform: scale(1.03); }
    </style>
</head>
<body>

    <h1>📈 自習ステップアップ・進捗管理</h1>

    <?php foreach ($subjects as $key => $subject_name): ?>
        <?php $p = $progress_data[$key]; ?>
        <div class="subject-block">
            <h2>
                <?= htmlspecialchars($subject_name) ?> 
                <span style="font-size: 0.6em; color: #666; font-weight: normal;">
                    (熟練度: <?= $p['points'] ?> / <?= $p['max'] ?> pt — **<?= $p['percent'] ?>%**)
                </span>
            </h2>

            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?= $p['percent'] ?>%;"></div>
            </div>

            <div class="unit-list">
                <?php foreach ($units_by_subject[$key] as $unit): ?>
                    <?php 
                        $lvl = $unit['understanding_level']; 
                        $lvl_info = $level_labels[$lvl];
                    ?>
                    <div class="unit-card <?= $lvl_info['badge'] ?>">
                        <div style="font-weight: bold; margin-bottom: 8px; font-size: 0.9em; color: #333;">
                            <?= htmlspecialchars($unit['unit_name']) ?>
                        </div>
                        
                        <form method="POST" style="margin:0; text-align: right;">
                            <input type="hidden" name="unit_id" value="<?= $unit['id'] ?>">
                            <input type="hidden" name="current_level" value="<?= $lvl ?>">
                            <button type="submit" class="btn-lvl btn-<?= $lvl_info['badge'] ?>">
                                <?= $lvl_info['label'] ?> (クリックでUP)
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

</body>
</html>
