<?php
require_once 'config/db_config.php';

// プレイヤーレベル：総獲得資材量から算出し、user_status.player_level に保存して同期する
$sync = sync_player_level($pdo);
$materials = $sync['materials'];
$user_lvl  = $sync['level'];

// 教科別レベル（修練タブ・クエストタブで上がる）
$subjects       = get_subjects();
$subject_levels = get_subject_levels($pdo);

// 課題の残り件数を取得するサンプル（テーブル名に合わせて適宜変更）
$task_count = 3; 
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>勉強大好きアプリ（仮）- 島開拓ダッシュボード</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f0f7f9; font-family: sans-serif; color: #2b2d42; margin: 0; padding: 0; }
        main { max-width: 1000px; margin: 20px auto; padding: 0 15px; display: flex; flex-direction: column; gap: 20px; }
        
        .card { background: #ffffff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2ece9; }
        .card-title { margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: #023047; display: flex; align-items: center; gap: 8px; }

        .dashboard-top { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .dashboard-top { grid-template-columns: 1fr; } }

        .status-box { display: flex; gap: 15px; margin-bottom: 15px; }
        .status-item { flex: 1; background: #eef7f6; border-radius: 12px; padding: 12px; text-align: center; }
        .status-item .num { font-size: 1.6rem; font-weight: bold; color: #219ebc; }
        .status-item .label { font-size: 0.8rem; color: #666; }

        /* 資材表示バー */
        .resource-bar {
            background: #023047;
            color: #fff;
            padding: 15px 20px;
            border-radius: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .resource-val { color: #ffb703; font-size: 1.4rem; margin-left: 8px; }

        .subject-lv-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; }
        .subject-lv-item { background: #eef7f6; border-radius: 12px; padding: 12px; text-align: center; }
        .subject-lv-item .label { font-size: 0.85rem; color: #666; }
        .subject-lv-item .num { font-size: 1.5rem; font-weight: bold; color: #219ebc; }

        .island-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-top: 15px; }
        .build-card { background: #fafafa; border: 2px solid #e0e0e0; border-radius: 12px; padding: 15px; }
        .build-card.unlocked { background: #f0fdf4; border-color: #86efac; }
        .build-card.locked { background: #f5f5f5; opacity: 0.7; }

        .build-name { font-weight: bold; font-size: 1rem; margin-bottom: 5px; }
        .build-lvl { font-size: 0.85rem; color: #2563eb; font-weight: bold; }
        .cost-list { font-size: 0.85rem; color: #555; margin: 10px 0; background: rgba(255,255,255,0.8); padding: 8px; border-radius: 6px; }

        .btn { width: 100%; padding: 8px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-build { background: #219ebc; color: white; }
        .btn-build:hover { background: #1a829d; }
        .lock-msg { font-size: 0.75rem; color: #ef476f; font-weight: bold; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/header.php'; ?>

    <main>
        <div class="dashboard-top">
            <div class="card">
                <h3 class="card-title">📊 教科別 学習進捗</h3>
                <div style="max-width: 250px; margin: 0 auto;">
                    <canvas id="progressChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title">📝 クエスト＆スキル状況</h3>
                <div class="status-box">
                    <div class="status-item">
                        <div class="label">未完了の課題</div>
                        <div class="num"><?php echo $task_count; ?><span style="font-size:0.9rem"> 件</span></div>
                    </div>
                    <div class="status-item">
                        <div class="label">プレイヤーLv</div>
                        <div class="num"><?php echo $user_lvl; ?></div>
                    </div>
                </div>
                <div style="background: #fff8e7; padding: 12px; border-radius: 8px; border-left: 4px solid #ffb703; font-size: 0.85rem;">
                    💡 <b>ヒント:</b> 自習や課題をクリアすると「資材」を獲得できます！
                </div>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">🎓 教科別レベル</h3>
            <div class="subject-lv-grid">
                <?php foreach ($subjects as $key => $name): ?>
                    <div class="subject-lv-item">
                        <div class="label"><?php echo htmlspecialchars($name); ?>レベル</div>
                        <div class="num">Lv.<?php echo (int)$subject_levels[$key]; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">🏝️ 島開拓シミュレーション</h3>

            <div class="resource-bar">
                🧰 所持資材: <span id="res-materials" class="resource-val"><?php echo $materials; ?></span>
            </div>

            <div class="island-grid">
                <!-- 拠点キャンプ -->
                <div class="build-card unlocked">
                    <div class="build-name">⛺ 拠点キャンプ</div>
                    <div class="build-lvl">Lv. <span id="lvl-camp">1</span></div>
                    <div class="cost-list">次Lvに必要な資材: 🧰 <span id="cost-camp">30</span></div>
                    <button class="btn btn-build" onclick="upgradeBuilding('camp', 30, 1.5)">建設・強化</button>
                </div>

                <!-- 見張り台 -->
                <div class="build-card unlocked">
                    <div class="build-name">🗼 見張り台</div>
                    <div class="build-lvl">Lv. <span id="lvl-tower">0</span></div>
                    <div class="cost-list">次Lvに必要な資材: 🧰 <span id="cost-tower">60</span></div>
                    <button class="btn btn-build" onclick="upgradeBuilding('tower', 60, 1.6)">建設・強化</button>
                </div>

                <!-- 研究所（レベル3で解放） -->
                <div class="build-card <?php echo $user_lvl >= 3 ? 'unlocked' : 'locked'; ?>">
                    <div class="build-name">🔬 研究所</div>
                    <div class="build-lvl">Lv. <span id="lvl-lab">0</span></div>
                    <div class="cost-list">
                        <?php if ($user_lvl < 3): ?>
                            <span class="lock-msg">🔒 解放条件: プレイヤーLv. 3</span><br>
                        <?php endif; ?>
                        次Lvに必要な資材: 🧰 <span id="cost-lab">120</span>
                    </div>
                    <button class="btn btn-build" onclick="upgradeBuilding('lab', 120, 1.8)" <?php echo $user_lvl < 3 ? 'disabled' : ''; ?>>建設・強化</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // 1. 進捗円グラフ
        const ctx = document.getElementById('progressChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['数学', '英語', '物理', '化学', '情報'],
                datasets: [{
                    data: [30, 20, 15, 15, 20],
                    backgroundColor: ['#219ebc', '#ffb703', '#06d6a0', '#ef476f', '#8c97ff']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // 2. 開拓処理
        let currentMaterials = <?php echo $materials; ?>;
        let buildingLevels = { camp: 1, tower: 0, lab: 0 };

        function upgradeBuilding(id, baseCost, multiplier) {
            let lvl = buildingLevels[id];
            let reqCost = Math.floor(baseCost * Math.pow(multiplier, lvl));

            if (currentMaterials >= reqCost) {
                currentMaterials -= reqCost;
                buildingLevels[id]++;

                document.getElementById('res-materials').innerText = currentMaterials;
                document.getElementById(`lvl-${id}`).innerText = buildingLevels[id];

                let nextCost = Math.floor(baseCost * Math.pow(multiplier, buildingLevels[id]));
                document.getElementById(`cost-${id}`).innerText = nextCost;

                alert('建設・強化が完了しました！');
            } else {
                alert('資材が足りません！課題や自習で集めましょう。');
            }
        }
    </script>
</body>
</html>