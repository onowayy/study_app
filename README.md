<?php
require_once 'config/db_config.php';

// ==========================================
// 安全ガード：テーブルが既に存在するか確認
// ==========================================
$check = $pdo->query("SHOW TABLES LIKE 'self_study_units'");
$table_exists = $check->fetch() !== false;

$force = isset($_GET['force']) && $_GET['force'] === '1';

// テーブルが既にあって、force指定も無いなら、ここで止める
if ($table_exists && !$force) {
    echo "⚠️ self_study_units テーブルは既に存在します。<br>";
    echo "このまま実行すると、これまでの理解度の進捗が全て消えて初期化されます。<br><br>";
    echo "本当に作り直したい場合は、";
    echo "<a href='create_table.php?force=1' onclick=\"return confirm('本当に全ての進捗をリセットしますか？この操作は取り消せません。');\">";
    echo "こちらをクリックして強制的に再作成</a>してください。";
    exit; // ここで処理を終了。DROPさせない
}

try {
    // 既存テーブル削除（構造変更のため、force=1の時だけ）
    $pdo->exec("DROP TABLE IF EXISTS self_study_units");

    // 新テーブル作成（understanding_level: 0〜3）
    $sql_create = "
    CREATE TABLE self_study_units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(50) NOT NULL,
        unit_name VARCHAR(100) NOT NULL,
        understanding_level TINYINT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    ";
    $pdo->exec($sql_create);

    // ...（$all_units の定義とINSERT処理はそのまま）...

    echo "✅ 段階的理解度システム対応のデータベースを作成しました！<br>";
    echo "👉 <a href='self_study.php'>self_study.php を開く</a>";

} catch (PDOException $e) {
    echo "❌ エラー: " . $e->getMessage();
}
?>
