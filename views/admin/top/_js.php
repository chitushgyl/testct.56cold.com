<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 11:46
 */
?>
<style>
    /*圖標展示*/
    .site-doc-icon{width: 1050px;background-color: #fff}
    .site-doc-icon li{cursor:pointer;display: inline-block; vertical-align: middle; width: 127px; height: 105px; line-height: 25px; padding: 20px 0; margin-right: -1px; margin-bottom: -1px; border: 1px solid #e2e2e2; font-size: 14px; text-align: center; color: #666; transition: all .3s; -webkit-transition: all .3s;}
    .site-doc-anim li{height: auto;}
    .site-doc-icon li .layui-icon{display: inline-block; font-size: 36px;}
    .site-doc-icon li .doc-icon-name,
    .site-doc-icon li .doc-icon-code{color: #c2c2c2;}
    .site-doc-icon li .doc-icon-code xmp{margin:0}
    .site-doc-icon li .doc-icon-fontclass{height: 40px; line-height: 20px; padding: 0 5px; font-size: 13px; color: #333; }
    .site-doc-icon li:hover{background-color: #f2f2f2; color: #000;}

</style>
<script>
    //选择圖標
    function chioceIcon(obj) {
        var icon_id = $(obj).data('id');
        $("input[name='icon_id']").val(icon_id);
        $("#icon_box").html('<i class="layui-icon '+$(obj).data('class')+'"></i> ');
        layer.closeAll();
    }

    //弹出圖標
    function showIconsBox() {
        var index = layer.load();
        $.get("<?php echo route('admin.index.icons')?>",function (res) {
            layer.close(index);
            if (res.code==0 && res.data.length>0){
                var html = '<ul class="site-doc-icon">';
                $.each(res.data,function (index,item) {
                    html += '<li onclick="chioceIcon(this)" data-id="'+item.id+'" data-class="'+item.class+'">';
                    html += '   <i class="layui-icon '+item.class+'"></i>';
                    html += '   <div class="doc-icon-code"><xmp>'+item.unicode+'</xmp></div>';
                    html += '   <div class="doc-icon-fontclass">'+item.class+'</div>';
                    html += '</li>'
                });
                html += '</ul>';
                layer.open({
                    type:1,
                    title:'选择图标',
                    area : ['1080px','600px'],
                    content:html
                })
            }else {
                layer.msg(res.msg);
            }
        },'json')
    }

    layui.use(['layer','table','form'],function () {
        var layer = layui.layer;
        var form = layui.form;
        form.on('submit(*)', function(){
            layer.load(2,{time:10*1000});
        })
    });
</script>

