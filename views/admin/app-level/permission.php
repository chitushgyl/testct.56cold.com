<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 14:08
 */
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<style>
    .cate-box{margin-bottom: 15px;padding-bottom:10px;border-bottom: 1px solid #f0f0f0}
    .cate-box dt{margin-bottom: 10px;}
    .cate-box dt .cate-first{padding:10px 20px}
    .cate-box dd{padding:0 50px}
    .cate-box dd .cate-second{margin-bottom: 10px}
    .cate-box dd .cate-third{padding:0 40px;margin-bottom: 10px}
</style>
<div class="layui-card">
    <div class="layui-card-header layuiadmin-card-header-auto">
        <h2>公司等级 【<?php echo  $info['name'] ?>】分配权限</h2>
    </div>
    <div class="layui-card-body">
        <form method="post" class="layui-form">
            <input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">

            <!-- <?php if(!empty($list_top)){ foreach($list_top as $first){?>
            <dl class="cate-box">
                <dt>
                    <div class="cate-first"><input id="menu<?php echo $first['id'];?>" type="checkbox" name="permissions_top[]" value="<?php echo $first['id'] ;?>" title="<?php echo $first['display_name'];?>" lay-skin="primary" <?php echo $first['own']?$first['own']:'';?> ></div>
                </dt>
                <?php if(isset($first['son'])){?>
                 <?php foreach($first['son'] as $second){?>
                <dd>
                    <div class="cate-second"><input id="menu<?php echo $first['id'];?>-<?php echo $second['id'];?>" type="checkbox" name="permissions_top[]" value="<?php echo $second['id'];?>" title="<?php echo $second['display_name'];?>" lay-skin="primary" <?php echo $second['own']?$second['own']:'';?>></div>
                     
                </dd>
                <?php }}?>
            </dl>
            <?php }}?> -->

            <?php if(!empty($list)){ foreach($list as $first){?>
            <dl class="cate-box">
                <dt>
                    <div class="cate-first"><input id="menu<?php echo $first['id'];?>" type="checkbox" name="permissions[]" value="<?php echo $first['id'] ;?>" title="<?php echo $first['display_name'];?>" lay-skin="primary" <?php echo $first['own']?$first['own']:'';?> ></div>
                </dt>
                <?php if(isset($first['son'])){?>
                 <?php foreach($first['son'] as $second){?>
                <dd>
                    <div class="cate-second"><input id="menu<?php echo $first['id'];?>-<?php echo $second['id'];?>" type="checkbox" name="permissions[]" value="<?php echo $second['id'];?>" title="<?php echo $second['display_name'];?>" lay-skin="primary" <?php echo $second['own']?$second['own']:'';?>></div>
                     <?php if(isset($second['son'])){?>
                    <div class="cate-third">
                        <?php foreach($second['son'] as $thild){?>
                        <?php if(isset($thild['son'])){?>
                        <div><input type="checkbox" id="menu<?php echo $first['id']?>-<?php echo $second['id']?>-<?php echo $thild['id']?>" name="permissions[]" value="<?php echo $thild['id']?>" title="<?php echo $thild['display_name']?>" lay-skin="primary" <?php echo $thild['own']?$thild['own']:'';?>></div>
                        <div class="cate-third">
                            <?php foreach($thild['son'] as $four){?>
                            <input type="checkbox" id="menu<?php echo $first['id']?>-<?php echo $second['id']?>-<?php echo $thild['id']?>-<?php echo $four['id']?>" name="permissions[]" value="<?php echo $four['id']?>" title="<?php echo $four['display_name']?>" lay-skin="primary" <?php echo $four['own']?$four['own']:'';?>>
                            <?php }?>
                        </div>
                        <?php }else{?>
                        <input type="checkbox" id="menu<?php echo $first['id']?>-<?php echo $second['id']?>-<?php echo $thild['id']?>" name="permissions[]" value="<?php echo $thild['id']?>" title="<?php echo $thild['display_name']?>" lay-skin="primary" <?php echo $thild['own']?$thild['own']:'';?>>
                        <?php }?>
                        <?php }?>
                    </div>
                    <?php }?>
                </dd>
                <?php }}?>
            </dl>
                <?php }}else{?>
            <div style="text-align: center;padding:20px 0;">
                无数据
            </div>
            <?php }?>
            <div class="layui-form-item">
                <button type="submit" class="layui-btn" lay-submit="" lay-filter="*">确 认</button>
                <a href="<?php echo route('admin.app-level.index')?>"  class="layui-btn" >返 回</a>
            </div>

        </form>
    </div>
</div>
<script type="text/javascript">
    layui.use(['layer','table','form'],function () {
        var layer = layui.layer;
        var form = layui.form;
        var table = layui.table;

        form.on('checkbox', function (data) {
            var check = data.elem.checked;//是否选中
            var checkId = data.elem.id;//当前操作的选项框
            if (check) {
                //选中
                var ids = checkId.split("-");
                if (ids.length == 4) {
                    //第四极菜单
                    //第四极菜单选中,则他的上级选中
                    $("#" + (ids[0] + '-' + ids[1]+ '-' + ids[2])).prop("checked", true);
                    $("#" + (ids[0] + '-' + ids[1])).prop("checked", true);
                }
                else if (ids.length == 3) {
                    //第三极菜单
                    //第三极菜单选中,则他的上级选中
                    $("#" + (ids[0] + '-' + ids[1])).prop("checked", true);
                    $("#" + (ids[0])).prop("checked", true);
                    $("input[id*=" + ids[0] + '-' + ids[1] + '-' + ids[2] + "]").each(function (i, ele) {
                        $(ele).prop("checked", true);
                    });
                } else if (ids.length == 2) {
                    //第二季菜单
                    $("#" + (ids[0])).prop("checked", true);
                    $("input[id*=" + ids[0] + '-' + ids[1] + "]").each(function (i, ele) {
                        $(ele).prop("checked", true);
                    });
                } else {
                    //第一季菜单不需要做处理
                    $("input[id*=" + ids[0] + "-]").each(function (i, ele) {
                        $(ele).prop("checked", true);
                    });
                }
            } else {
                //取消选中
                var ids = checkId.split("-");
                if (ids.length == 3) {
                    //第二极菜单
                    $("input[id*=" + ids[0] + '-' + ids[1] + '-' + ids[2] + "]").each(function (i, ele) {
                        $(ele).prop("checked", false);
                    });
                }
                else if (ids.length == 2) {
                    //第二极菜单
                    $("input[id*=" + ids[0] + '-' + ids[1] + "]").each(function (i, ele) {
                        $(ele).prop("checked", false);
                    });
                } else if (ids.length == 1) {
                    $("input[id*=" + ids[0] + "-]").each(function (i, ele) {
                        $(ele).prop("checked", false);
                    });
                }
            }
            form.render();
        });

        form.on('submit(*)', function(data){
            layer.load(2,{time:10*1000});
        })

    })
</script>
