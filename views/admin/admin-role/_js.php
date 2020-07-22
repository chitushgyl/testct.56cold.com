<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 14:08
 */
?>
<script>
    layui.use(['layer','table','form'],function () {
        var layer = layui.layer;
        var form = layui.form;
        form.on('submit(*)', function(){
            layer.load(2,{time:10*1000});
        })
    });
</script>