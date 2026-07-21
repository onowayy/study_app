<?php
require_once 'config/db_config.php';

$message = "";

if (isset($_POST['complete_task_id'])) {
    $task_id = (int)$_POST['complete_task_id'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT subject, difficulty FROM tasks WHERE id = :id AND is_completed = 0");
        $stmt->execute([':id' => $task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task) {
            $subject = $task['subject'];
            $difficulty = (int)$task['difficulty'];

            // 難易度に応じた資源の加算量を決めるロジック（例：難易度1=10、難易度2=20、難易度3=30）
            $gain_materials = $difficulty * 10; 
            $gain_food = $difficulty * 10;

            $updateTask = $pdo->prepare("UPDATE tasks SET is_completed = 1 WHERE id = :id");
            $updateTask->execute([':id' => $task_id]);

            $level_column = "";
            $subject_label = "";

            switch ($subject) {
                case '数学':
                    $level_column = "math_level";
                    $subject_label = "数学";
                    break;
                case '英語':
                    $level_column = "eng_level";
                    $subject_label = "英語";
                    break;
                case '理系': 
                    $level_column = "rikei_level";
                    $subject_label = "理系";
                    break;
            }

            if ($level_column !== "") {
                $updateStatus = $pdo->prepare("
                    UPDATE user_status 
                    SET materials = materials + :materials, 
                        food = food + :food, 
                        {$level_column} = {$level_column} + 1 
                    WHERE id = 1
                ");
                $message = "<p style='color: blue; font-weight: bold;'>【課題完了】{$subject_label}Lvが1アップ！ 建築資材+{$gain_materials}、エサ+{$gain_food} 獲得！</p>";
            } else {
                // その他の教科（国語など）：建築資材とエサだけを増やす
                $updateStatus = $pdo->prepare("
                    UPDATE user_status 
                    SET materials = materials + :materials, 
                        food = food + :food 
                    WHERE id = 1
                ");
                $message = "<p style='color: green; font-weight: bold;'>【課題完了】建築資材+{$gain_materials}、エサ+{$gain_food} 獲得！</p>";
            }

            $updateStatus->execute([
                ':materials' => $gain_materials,
                ':food' => $gain_food
            ]);

            $pdo->commit();
        } else {
            $pdo->rollBack();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<p style='color: red;'>エラーが発生しました: " . $e->getMessage() . "</p>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title         = $_POST['title'];
    $subject       = $_POST['subject'];
    $deadline      = $_POST['deadline'];
    $required_time = (int)$_POST['required_time'];
    $difficulty    = (int)$_POST['difficulty'];   

    try {
        $sql = "INSERT INTO tasks (title, subject, deadline, required_time, difficulty, is_completed) 
                VALUES (:title, :subject, :deadline, :required_time, :difficulty, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title'         => $title,
            ':subject'       => $subject,
            ':deadline'      => $deadline,
            ':required_time' => $required_time,
            ':difficulty'    => $difficulty
        ]);
        $message = "<p style='color: green;'>【成功】新しい課題を登録しました！</p>";
    } catch (PDOException $e) {
        $message = "<p style='color: red;'>登録エラー: " . $e->getMessage() . "</p>";
    }
}

$statusStmt = $pdo->query("SELECT * FROM user_status WHERE id = 1");
$user_status = $statusStmt->fetch(PDO::FETCH_ASSOC);

$taskStmt = $pdo->query("SELECT * FROM tasks WHERE is_completed = 0");
$raw_tasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);

$today = new DateTime();

foreach ($raw_tasks as &$task) {
    // 1. 期限スコア（締切が近いほど高得点）
    $deadline_date = new DateTime($task['deadline']);
    $interval = $today->diff($deadline_date);
    $days_left = (int)$interval->format('%r%a'); 

    if ($days_left <= 0) {
        $deadline_score = 100;
    } elseif ($days_left === 1) {
        $deadline_score = 50;
    } elseif ($days_left === 2) {
        $deadline_score = 30;
    } elseif ($days_left <= 3) {
        $deadline_score = 20;
    } elseif ($days_left <= 7) {
        $deadline_score = 10;
    } else {
        $deadline_score = 0;
    }

    // 2. 難易度スコア（難しいほど高得点）
    $difficulty_score = (int)$task['difficulty'] * 10; // 10点〜30点

    // 3. 所要時間スコア（時間がかかるものほど高得点）
    $time_score = (int)$task['required_time'] * 0.1; // 60分なら6点

    $task['priority_score'] = $deadline_score + $difficulty_score + $time_score;
    $task['days_left'] = $days_left; // リマインド判定用に残り日数も保持
}
unset($task);

usort($raw_tasks, function($a, $b) {
    return $b['priority_score'] <=> $a['priority_score'];
});

// ==========================================================
// 【Step 8】メンバーC（フロント・円グラフ）用データの集計
// ==========================================================
// 科目ごとに「全体の課題数」と「完了した課題数」をSQLでカウント
$chartStmt = $pdo->query("
    SELECT 
        subject,
        COUNT(*) as total_count,
        SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_count
    FROM tasks
    GROUP BY subject
");
$chart_summary = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

// メンバーCがJSでそのまま扱えるようにJSON形式に変換しておく
$chart_json = json_encode($chart_summary, JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>課題管理・自動ソートシステム</title>
    <link rel="stylesheet" href="css/style.css"> <!-- -->
</head>
<body style="font-family: Arial, sans-serif; margin: 30px; line-height: 1.6;">

    <h1>📚 課題管理 ＆ 自動ソートロジック（メンバーB担当）</h1>
    <?php echo $message; ?>

    <div style="background-color: #f0f8ff; border: 2px solid #008080; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
        <h3>🎮 現在のゲームステータス（1端末内データ連動中）</h3>
        <p>
            <strong>建築資材（materials）:</strong> <?php echo $user_status['materials'] ?? 0; ?> | 
            <strong>エサ（food）:</strong> <?php echo $user_status['food'] ?? 0; ?> | <br>
            <strong>数学レベル:</strong> Lv.<?php echo $user_status['math_level'] ?? 1; ?> | 
            <strong>英語レベル:</strong> Lv.<?php echo $user_status['eng_level'] ?? 1; ?> | 
            <strong>理科レベル:</strong> Lv.<?php echo $user_status['rikei_level'] ?? 1; ?>
        </p>
    </div>

    <div style="border: 1px solid #ccc; padding: 20px; max-width: 500px; margin-bottom: 30px; background-color: #fafafa;">
        <h2>新規課題の登録</h2>
        <form action="task_reward.php" method="POST"> <!-- -->
            <input type="hidden" name="add_task" value="1">
            <p>
                <label>課題名:</label><br>
                <input type="text" name="title" required placeholder="例：数学プリント P.24" style="width: 90%;">
            </p>
            <p>
                <label>科目:</label><br>
                <select name="subject" required style="width: 93%;">
                    <option value="数学">数学</option>
                    <option value="英語">英語</option>
                    <option value="理系">理系</option>
                </select>
            </p>
            <p>
                <label>提出期限:</label><br>
                <input type="date" name="deadline" required style="width: 90%;">
            </p>
            <p>
                <label>所要時間 (分):</label><br>
                <input type="number" name="required_time" min="1" required placeholder="例：30" style="width: 90%;"> <!-- -->
            </p>
            <p>
                <label>難易度:</label><br>
                <input type="radio" id="d1" name="difficulty" value="1" checked><label for="d1">1: 簡単</label>
                <input type="radio" id="d2" name="difficulty" value="2"><label for="d2">2: 普通</label>
                <input type="radio" id="d3" name="difficulty" value="3"><label for="d3">3: 難しい</label>
            </p>
            <button type="submit" style="background-color: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer;">課題を登録する</button>
        </form>
    </div>

    <h2>📋 やるべき課題一覧（優先度が高い順に自動ソート）</h2>
    <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead style="background-color: #eee;">
            <tr>
                <th>優先順位</th>
                <th>課題名</th>
                <th>科目</th>
                <th>提出期限</th>
                <th>所要時間</th>
                <th>難易度</th>
                <th>算出スコア</th>
                <th>アクション</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($raw_tasks)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #888;">現在、やるべき課題はありません！素晴らしい！</td>
                </tr>
            <?php else: ?>
                <?php $rank = 1; ?>
                <?php foreach ($raw_tasks as $task): ?>
                    <tr>
                        <td><strong>No.<?php echo $rank++; ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?>
                            <!-- 防衛ライン①：リマインド機能（残り3日以内なら赤文字で通知） -->
                            <?php if ($task['days_left'] <= 3 && $task['days_left'] >= 0): ?>
                                <span style="color: red; font-weight: bold; margin-left: 10px;">⚠️【締切間近！】</span>
                            <?php elseif ($task['days_left'] < 0): ?>
                                <span style="color: purple; font-weight: bold; margin-left: 10px;">🚨【期限超過！】</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($task['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($task['deadline'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($task['required_time'], ENT_QUOTES, 'UTF-8'); ?> 分</td>
                        <td>レベル <?php echo htmlspecialchars($task['difficulty'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="color: #666; font-size: 0.9em;"><?php echo $task['priority_score']; ?> pt</td>
                        <td>
                            <!-- 防衛ライン②：完了ボタン（押すとPOSTされてデータが即座に連動） -->
                            <form action="task_reward.php" method="POST" style="margin: 0;"> <!-- -->
                                <input type="hidden" name="complete_task_id" value="<?php echo $task['id']; ?>"> <!-- -->
                                <button type="submit" style="background-color: #28a745; color: white; border: none; padding: 5px 10px; cursor: pointer;">完了！</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- ========================================================== -->
    <!--         メンバーC（円グラフ担当）へデータを渡す橋渡し -->
    <!-- ========================================================== -->
    <script>
        // メンバーCへ：この taskChartData 配列を使って円グラフ（Chart.js等）を描画してください！
        window.taskChartData = <?php echo $chart_json; ?>;
        console.log('【メンバーC用】円グラフデータ:', window.taskChartData);
    </script>
    <script src="js/graph.js"></script> <!-- メンバーCが作るJSファイル -->

</body>
</html>
