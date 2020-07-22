<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 09:46
 */
?>
<script>
    function numAndLetter(obj){
        obj.value = obj.value.replace(/[^\w\/]/ig,'');
        obj.value = obj.value.substring(0,20);
    }
    layui.use(['layer','table','form'],function () {
        var layer = layui.layer;
        var form = layui.form;

        form.verify({
            username : function(value, item){
                if (!value) {
                    return '账户名称不能为空！';
                }
                if (value.length <2) {
                    return '账户名称长度最少2位！';
                }
                
            },
            phone : function(value, item){
                if (!value) {
                    return '手机号不能为空！';
                }
                var m = checkMobile(value);
                if (m != 1) {
                    return m;
                }
            },
            email : function(value, item){
                if (value) {
                    var m = checkEmail(value);
                    if (m != 1) {
                        return m;
                    }
                }
            },
            password : function(value, item){
                if (value) {
                    if (value.length < 6) {
                        return '密码长度最少6位！';
                    }
                }
            }
        });

        form.on('submit(*)', function(){
            layer.load(2,{time:10*1000});
        })
    });

</script>
