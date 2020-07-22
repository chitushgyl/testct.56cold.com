<?php
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>微信支付样例</title>
    <style type="text/css">
        ul {
            margin-left:10px;
            margin-right:10px;
            margin-top:10px;
            padding: 0;
        }
        li {
            width: 32%;
            float: left;
            margin: 0px;
            margin-left:1%;
            padding: 0px;
            height: 100px;
            display: inline;
            line-height: 100px;
            color: #fff;
            font-size: x-large;
            word-break:break-all;
            word-wrap : break-word;
            margin-bottom: 5px;
        }
        a {
            -webkit-tap-highlight-color: rgba(0,0,0,0);
            text-decoration:none;
            color:#fff;
        }
        a:link{
            -webkit-tap-highlight-color: rgba(0,0,0,0);
            text-decoration:none;
            color:#fff;
        }
        a:visited{
            -webkit-tap-highlight-color: rgba(0,0,0,0);
            text-decoration:none;
            color:#fff;
        }
        a:hover{
            -webkit-tap-highlight-color: rgba(0,0,0,0);
            text-decoration:none;
            color:#fff;
        }
        a:active{
            -webkit-tap-highlight-color: rgba(0,0,0,0);
            text-decoration:none;
            color:#fff;
        }
        .into{
            width: 32%;
            float: left;
            margin: 0px;
            margin-left:1%;
            padding: 0px;
            height: 100px;
            background-color:#8B6914
        }
    </style>
</head>
<body>
<div align="center">
    <ul>
        <li style="background-color:#FF7F24"><a href="http://paysdk.weixin.qq.com/example/jsapi.php">JSAPI支付</a></li>
        <li style="background-color:#698B22"><a href="http://paysdk.weixin.qq.com/example/micropay.php">刷卡支付</a></li>
        <li style="background-color:#8B6914"><a id="submit" onclick="toSubmit()">扫码支付</a></li>
        <li style="background-color:#CDCD00"><a href="http://paysdk.weixin.qq.com/example/orderquery.php">订单查询</a></li>
        <li style="background-color:#CD3278"><a href="http://paysdk.weixin.qq.com/example/refund.php">订单退款</a></li>
        <li style="background-color:#848484"><a href="http://paysdk.weixin.qq.com/example/refundquery.php">退款查询</a></li>
        <li style="background-color:#8EE5EE"><a href="http://paysdk.weixin.qq.com/example/download.php">下载订单</a></li>

        <li style="background-color:#8B6914"><a href="<?php echo route('api.pay.wechatpay');?>">扫码支付</a></li>
        <li style="background-color:#8B6914"><a id="execlcheck" href="javascript:void (0);">导出</a></li>
    </ul>
    <div class="into">
        <form action="<?php echo route('api.excel.contact_into');?>" enctype="multipart/form-data" method="post">
            <input type="file" name="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
            <input type="hidden" name="group_id" value="3">
            <button type="submit">保存</button>
        </form>
    </div>

    <form action="" method="post" class="form form-horizontal" id="form-article-add">
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">回单上传：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <div class="uploader-list-container">
                    <div class="queueList">
                        <div id="dndArea" class="placeholder">
                            <div id="filePicker-2"></div>
                            <p>或将照片拖到这里，单次最多可选300张</p>
                        </div>
                    </div>
                    <div class="statusBar" style="display:none;">
                        <div class="progress"> <span class="text">0%</span> <span class="percentage"></span> </div>
                        <div class="info"></div>
                        <div class="btns">
                            <div id="filePicker2"></div>
                            <!--<div class="uploadBtn">开始上传</div>-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row cl">
            <div class="col-xs-8 col-sm-9 col-xs-offset-4 col-sm-offset-2">
                <input type="hidden" name="oid" value="{$oid}">
                <button onClick="tijiao()" class="btn btn-primary radius" type="button"> 提交</button>
                <button onClick="layer_close();" class="btn btn-default radius" type="button">&nbsp;&nbsp;取消&nbsp;&nbsp;</button>
            </div>
        </div>
    </form>

<!--    <form class="form-horizontal m-t" enctype="multipart/form-data" method="" action="" id="comForm">-->
<!--    <div class="col-sm-10">-->
<!--                         <form id="upform" enctype='multipart/form-data' >-->
<!--        <div class="form-group" style="display:none;">-->
<!--            <label for="upteainput">上传文件</label>-->
<!--            <input id="upteainput" onchange="PreviewImage(this,'imgPreview')" name="business" type="file"  class="form-control-file">-->
<!--        </div>-->
<!--                         </form>-->
<!--        <button id="uptea" type="button" class="btn btn-primary">上传资料</button>-->
<!--        <br>-->
<!--        <div id="imgPreview"></div>-->
<!--    </div>-->
<!--    </form>-->
</div>
</body>
<script>
    $(document).ready(function() {
        $("#execlcheck").click(function() {
            console.log(123);
            if (confirm("确认要导出订单吗？")) {
                var ids = new Array(15,16,17,18,19);
                console.log(ids);
                window.location.href = '/api/excel/car_out?id=' + ids;
                // $.ajax({
                //     url:'/api/excel/car_out',
                //     data:{ids:ids},
                //     type:'POST',
                //     success:function(w){
                //
                //     }
                // })
            } else {
                return false;
            }
        });
    })
</script>
</html>