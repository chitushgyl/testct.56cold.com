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
    layui.use(['layer','table','form','upload'],function () {
        var layer = layui.layer;
        var form = layui.form;
        var upload = layui.upload;

        form.verify({
            contact_name : function(value, item){
                if (!value) {
                    return '联系人不能为空！';
                }
                if (value.length <2) {
                    return '联系人长度最少2位！';
                }
            },
            ellipsis_name : function(value, item){
                if (!value) {
                    return '公司简称不能为空！';
                }
            },            

            all_name : function(value, item){
                if (!value) {
                    return '公司全称不能为空！';
                }
            },            

            address : function(value, item){
                if (!value) {
                    return '公司地址不能为空！';
                }
            },
            contact_phone : function(value, item){
                if (!value) {
                    return '手机号不能为空！';
                }
                var m = checkMobile(value);
                if (m != 1) {
                    return m;
                }
            }
        });

        upload.render({
            elem: '#business' //绑定元素
            ,accept: 'images' //允许上传的文件类型
            ,auto: false 
            ,size: 51200 //最大允许上传的文件大小
            ,choose: function(obj){
                //预读本地文件示例，不支持ie8
                obj.preview(function(index, file, result){
                    var img = new Image();
                    img.src = result;
                    img.onload = function () {
                        //初始化夹在完成后获取上传图片宽高，判断限制上传图片的大小。
                        $('#business').attr('src', result); //图片链接（base64）
                        $('#business_url').val(result); //图片链接（base64）
                    }
                });
            }
        });

        form.on('submit(*)', function(){
            layer.load(2,{time:10*1000});
        })
    });

</script>
