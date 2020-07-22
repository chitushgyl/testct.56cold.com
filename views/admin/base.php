<?php
/**
 * Created by Joker.
 * Date: 2019/7/5
 * Time: 10:04
 */
$session = Yii::$app->session;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" href="/static/admin/layuiadmin/layui/css/layui.css" media="all">
    <link rel="stylesheet" href="/static/admin/layuiadmin/style/admin.css" media="all">
    <style type="text/css">
        a.text-blue{
            text-decoration: underline;
        }

        .text-blue{
            color: #1E9FFF;
        }

        .text-red {
            color: #FF5722;
        }

        .text-green {
            color: #5FB878;
        }

        .text-purple {
            color: #3A55B1;
        }

        .text-yellow {
            color: #FF9629;
        }

        .text-brown {
            color: #969696;
        }

        .text-gray {
            color: #CBCBCB;
        }
    </style>
</head>
<script src="/js/jquery.min.js"></script>
<script src="/static/admin/layuiadmin/layui/layui.js"></script>
<script src="/js/functions.js"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': "<?php echo  \Yii::$app->request->csrfToken ?>"
        }
    });
    $(function(){
        layui.config({
            // base: '/static/admin/layuiadmin/' //静态资源所在路径
        }).extend({
            // index: 'lib/index' //主入口模块
        }).use(['element','form','layer','table','upload','laydate'],function () {
            var element = layui.element;
            var layer = layui.layer;
            var form = layui.form;
            var table = layui.table;
            var upload = layui.upload;
            var laydate = layui.laydate;

            if("<?php echo $session->get('error')?>"){
                layer.msg("<?php echo $session->get('error')?>",{icon:5});
                <?php $session->remove('error'); ?>
            }
            if("<?php echo $session->get('success')?>"){
                layer.msg("<?php echo $session->get('success')?>",{icon:6});
                <?php $session->remove('success'); ?>
            }
        });
    })
</script>