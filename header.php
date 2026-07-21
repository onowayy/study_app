<?php
// ==========================================
// 全ページ共通のヘッダー・ナビゲーション
// 各ページの <body> 直後で
//   <?php require_once __DIR__ . '/header.php'; ?>
// として読み込む
// ==========================================
$current_page = basename($_SERVER['SCRIPT_NAME']);

function nav_link($href, $label, $current_page) {
    $active = ($href === $current_page) ? ' style="text-decoration: underline; font-weight: bold;"' : '';
    echo "<a href=\"{$href}\"{$active}>{$label}</a>";
}
?>
<header>
    <h1>🏝️ 勉強大好きアプリ（仮）</h1>
    <nav>
        <?php nav_link('index.php', 'ホーム（島とグラフ）', $current_page); ?> |
        <?php nav_link('task_reward.php', '課題管理と報酬', $current_page); ?> |
        <?php nav_link('self_study.php', '自習・理解度管理', $current_page); ?> |
        <?php nav_link('bottle_mail.php', 'ボトルメール', $current_page); ?>
    </nav>
</header>
