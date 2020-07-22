<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 09:52
 */
use app\models\AppAuthLeft;
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
if($pid==0){
    $parent_id = 0;
}else{
    $parent_id = AppAuthLeft::findOne(['id'=>$pid])->parent_id;
}
?>
<div class="layui-card">
    <div class="layui-card-header layuiadmin-card-header-auto">
        <div class="layui-btn-group ">
            <?php if(can('admin.top.add')){?>
            <a class="layui-btn layui-btn-sm" href="<?php echo route('admin.top.add','pid='.$pid);?>">添 加</a>
            <?php }?>
            <button class="layui-btn layui-btn-sm" id="returnParent" pid="<?php echo $parent_id?>">返回上级</button>
        </div>
    </div>
    <div class="layui-card-body">
        <table id="dataTable" lay-filter="dataTable"></table>
        <script type="text/html" id="icon">
            <i class="layui-icon {{ d.class }}"></i>
        </script>
        <script type="text/html" id="use_flag">
            <div class="layui-btn-group">
                {{# if(d.use_flag=='Y'){ }}
                    <a class="layui-btn layui-btn-sm layui-btn-disabled" lay-event="" style="color:green;">启用</a>
                {{# }else{ }}
                    <a class="layui-btn layui-btn-sm layui-btn-disabled" lay-event="" style="color:red;">禁用</a>
                {{# } }}
            </div>
        </script>        

        <script type="text/html" id="options">
            <div class="layui-btn-group">
                <a class="layui-btn layui-btn-sm" lay-event="children">子权限</a>
                <?php if(can('admin.top.edit')){?>
                <a class="layui-btn layui-btn-sm" lay-event="edit">编辑</a>
                <?php }?>
                <?php if(can('admin.top.del')){?>
                <a class="layui-btn layui-btn-danger layui-btn-sm" lay-event="del">刪除</a>
                <?php }?>
            </div>
        </script>
    </div>
</div>
<script>
    var pid = <?php echo $pid?$pid:0 ?>;
    layui.use(['layer','table','form'],function () {
        var layer = layui.layer;
        var form = layui.form;
        var table = layui.table;
        //用户表格初始化
        var dataTable = table.render({
            elem: '#dataTable'
            ,height: 500
            ,url: "<?php echo route('admin.top.index')?>"+"pid="+pid //数据接口
            ,page: true //开启分页
            ,cols: [[ //表头
                {field: 'id', title: 'ID',align:'center', width:80}
                ,{field: 'display_name', align:'center',title: '显示名称'}
                ,{field: 'url', align:'center',title: '页面跳转'}
                ,{field: 'route', align:'center',title: '路由'}
                ,{field: 'icon', align:'center',title: '图标'}
                ,{field: 'use_flag', align:'center',title: '状态', toolbar: '#use_flag'}
                ,{field: 'update_time', align:'center',title: '更新时间'}
                ,{field: 'sort', align:'center',title: '排序'}
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
                        url:"<?php echo  route('admin.top.del') ?>",
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
                location.href = '/admin/top/edit?id='+data.id;
            } else if (layEvent === 'children'){
                console.log(data.id);
                console.log(data.parent_id);
                $('#returnParent').attr('pid',data.id);
                location.href = '/admin/top/index?pid='+data.id;
            }
        });

        //返回上一级
        $("#returnParent").click(function () {
            var pid = $(this).attr("pid");
            location.href = '/admin/top/index?pid='+ pid;
        })
    })
</script>

