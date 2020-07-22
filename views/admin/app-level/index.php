<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 09:52
 */
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<div class="layui-card">
    <div class="layui-card-header layuiadmin-card-header-auto">
        <div class="layui-btn-group ">
            <?php if(can('admin.app-level.add')){?>
            <a class="layui-btn layui-btn-sm" href="<?php echo route('admin.app-level.add','pid='.$pid);?>">添 加</a>
            <?php }?>
        </div>
    </div>
    <div class="layui-card-body">
        <table id="dataTable" lay-filter="dataTable"></table>
        <script type="text/html" id="options">
            <div class="layui-btn-group">
                <?php if(can('admin.app-level.edit')){?>
                <a class="layui-btn layui-btn-sm" lay-event="edit">编辑</a>
                <?php }?>

                <?php if(can('admin.app-level.permission')){?>
                <a class="layui-btn layui-btn-sm layui-btn-normal" lay-event="permission">权限</a>
                <?php } ?>
            </div>
        </script>
    </div>
</div>
<script>
    layui.use(['layer','table','form'],function () {
        var layer = layui.layer;
        var form = layui.form;
        var table = layui.table;
        //用户表格初始化
        var dataTable = table.render({
            elem: '#dataTable'
            ,height: 500
            ,url: "<?php echo route('admin.app-level.index')?>"//数据接口
            ,page: true //开启分页
            ,cols: [[ //表头
                {field: 'level_id', title: 'ID',align:'center', width:80}
                ,{field: 'name', align:'center',title: '等级名称'}
                ,{field: 'update_time', align:'center',title: '更新时间'}
                ,{fixed: 'right',align:'center', toolbar: '#options',title: '操作'}
            ]]
        });

        //监听工具条
        table.on('tool(dataTable)', function(obj){ //注：tool是工具条事件名，dataTable是table原始容器的属性 lay-filter="对应的值"
            var data = obj.data //获得当前行数据
                ,layEvent = obj.event; //获得 lay-event 对应的值
            if(layEvent === 'del'){
                layer.confirm('确定刪除该权限吗？', function(index){

                    $.ajax({
                        data:{id:data.id,_csrf:"<?php echo  Yii::$app->request->csrfToken ?>"},
                        url:"<?php echo  route('admin.app-level.del') ?>",
                        dataType:"JSON",
                        type:"POST",
                        beforeSend:function(){
                            layer.load(2, {time: 10*1000});
                        },
                        success: function (result) {
                            layer.closeAll();
                            if (result.retCode==1000){
                                obj.del(); //删除对应行（tr）的DOM结构
                                layer.msg('刪除成功!!', {icon: 1, time: 1000});
                            }else{
                                layer.msg(result.retMsg, {icon: 2, time: 1000});
                            }
                        }
                    });
                });
            } else if(layEvent === 'edit'){
                location.href = '/admin/app-level/edit?id='+data.level_id;
            }  else if (layEvent === 'permission'){
                location.href = '/admin/app-level/permission?id='+data.level_id;
            }
        });
    })
</script>

