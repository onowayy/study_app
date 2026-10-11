<?php
// ==========================================
// self_study.php から fetch() で呼ばれるAjax専用エンドポイント
// 1単元の理解度レベルを1つ上げて、JSONで結果を返す
// レベルアップ時は資材＋該当教科のスキルLvも付与する
// ==========================================
require_once 'config/db_config.php';
require_once 'auth.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$user_id = current_user_id();

$level_labels = [
    0 => ['label' => '未着手',          'badge' => 'lvl-0'],
    1 => ['label' => '要復習★',         'badge' => 'lvl-1'],
    2 => ['label' => '理解★★',          'badge' => 'lvl-2'],
    3 => ['label' => 'マスター★★★',     'badge' => 'lvl-3'],
];

// 自習の教科は5つのまま、数学→math_level、英語→english_level、
// 物理・化学・情報→rikei_level にまとめる（self_study.php側と同じ対応）
$subject_level_column = [
    'math'        => 'math_level',
    'english'     => 'english_level',
    'physics'     => 'rikei_level',
    'chemistry'   => 'rikei_level',
    'information' => 'rikei_level',
];

$unit_id = $_POST['unit_id'] ?? null;

if (!$unit_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'unit_id is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 単元の実在確認＋教科の取得
    $stmt = $pdo->prepare("SELECT id, subject FROM self_study_units WHERE id = :id");
    $stmt->execute([':id' => $unit_id]);
    $unit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$unit) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'unit not found']);
        exit;
    }

    // 現在の理解度をDBから取得（進捗行が無ければ未着手=0扱い）
    $stmt2 = $pdo->prepare("SELECT understanding_level FROM self_study_progress WHERE user_id = :uid AND unit_id = :unit_id");
    $stmt2->execute([':uid' => $user_id, ':unit_id' => $unit_id]);
    $current_level = (int)($stmt2->fetchColumn() ?: 0);

    $next_level = ($current_level + 1) % 4;

    // 進捗行を作成または更新（無ければINSERT、あればUPDATE）
    $upsert = $pdo->prepare("
        INSERT INTO self_study_progress (user_id, unit_id, understanding_level)
        VALUES (:uid, :unit_id, :level)
        ON DUPLICATE KEY UPDATE understanding_level = :level2
    ");
    $upsert->execute([
        ':uid'     => $user_id,
        ':unit_id' => $unit_id,
        ':level'   => $next_level,
        ':level2'  => $next_level,
    ]);

    // レベルアップ時のみ資材と該当教科のスキルレベルを付与（3→0に戻る時は付与しない）
    $reward = null;
    if ($next_level > $current_level) {
        $reward_materials = 50;

        $pdo->prepare("UPDATE user_status SET materials = materials + :reward WHERE user_id = :uid")
            ->execute([':reward' => $reward_materials, ':uid' => $user_id]);

        $level_column = $subject_level_column[$unit['subject']] ?? null;
        if ($level_column) {
            $pdo->prepare("UPDATE user_status SET {$level_column} = {$level_column} + 1 WHERE user_id = :uid")
                ->execute([':uid' => $user_id]);
        }

        $reward = ['materials' => $reward_materials];
    }

    // この単元が属する科目の進捗（合計ポイント・％）を再計算して一緒に返す
    $subject = $unit['subject'];
    $sumStmt = $pdo->prepare("
        SELECT COUNT(*) AS total_units, COALESCE(SUM(p.understanding_level), 0) AS points
        FROM self_study_units u
        LEFT JOIN self_study_progress p ON p.unit_id = u.id AND p.user_id = :uid
        WHERE u.subject = :subject
    ");
    $sumStmt->execute([':uid' => $user_id, ':subject' => $subject]);
    $sum = $sumStmt->fetch(PDO::FETCH_ASSOC);

    $total_units = (int)$sum['total_units'];
    $max_points = $total_units * 3;
    $points = (int)$sum['points'];
    $percent = $max_points > 0 ? round(($points / $max_points) * 100) : 0;

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'unit_id' => (int)$unit_id,
        'new_level' => $next_level,
        'label' => $level_labels[$next_level]['label'],
        'badge' => $level_labels[$next_level]['badge'],
        'reward' => $reward,
        'subject' => [
            'key' => $subject,
            'points' => $points,
            'max' => $max_points,
            'percent' => $percent,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
