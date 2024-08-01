<?php
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>无标题文档</title>
</head>
<link href="css/style.css" rel="stylesheet" type="text/css">
<body>
<div class="mr-cont">
  <span>点击图中画布，实现自动绘制随机圆形</span>
  <!--
    getCircle()
   /**
    * 画多个圆
    * 
    * @param {number|boolean} distance 间距（px）
    * @param {number[]|number} count 数量（默认为150~200），也可填入单个数值
    */-->
  <canvas height="900px" width="1600px" id="cav" onClick="getCircle(50)"></canvas>
</div>
</body>

<script src="js/line_and_circle.js"></script>
</html>
