<?php
ini_set('date.timezone','Asia/Shanghai');

?>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>微信扫码支付</title>
</head>
<body>
<div style="margin-left: 10px;color:#556B2F;font-size:30px;font-weight: bolder;">扫描支付</div><br/>
<img alt="模式二扫码支付" src="qrcode?data=<?php echo urlencode($url);?>" style="width:150px;height:150px;"/>

</body>
</html>
