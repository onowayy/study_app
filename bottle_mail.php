<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>ボトルメール</title>

    <!-- CSSを読み込む -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <div class="container">

        <h1>🌊 ボトルメール 🌊</h1>

        <p>メッセージを書いて海へ流そう！</p>

        <!-- 送信用フォーム -->
        <form action="db/save_mail.php" method="POST">

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

                document.querySelector("form").addEventListener("submit",function(){

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

    </div>

    <!-- Step3で作るJavaScript -->
    <script src="js/draw.js"></script>

</body>

</html>
