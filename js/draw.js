// Canvasを取得
const canvas = document.getElementById("mailCanvas");

// 2D描画モード
const ctx = canvas.getContext("2d");

// 描画中かどうか
let drawing = false;

// 線の太さ
ctx.lineWidth = 3;

// 線の色
ctx.strokeStyle = "black";

// 線の端を丸くする
ctx.lineCap = "round";
canvas.addEventListener("mousedown", function(e){

    drawing = true;

    ctx.beginPath();

    ctx.moveTo(e.offsetX, e.offsetY);

});
canvas.addEventListener("mousemove", function(e){

    if(!drawing){

        return;

    }

    ctx.lineTo(e.offsetX, e.offsetY);

    ctx.stroke();

});
canvas.addEventListener("mouseup", function(){

    drawing = false;

});
canvas.addEventListener("mouseleave", function(){

    drawing = false;

});
const clearButton =
document.getElementById("clearCanvas");

clearButton.addEventListener("click", function(){

    ctx.clearRect(
        0,
        0,
        canvas.width,
        canvas.height
    );

});