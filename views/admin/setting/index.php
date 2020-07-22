<?php
/**
 * Created by pysh.
 * Date: 2020/06/07
 * Time: 13:21
 */
use app\controllers\admin\AddLogController;
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<div class="layui-card">

    <div class="layui-card-body" style="padding-bottom: 0">
    </div>
    <div class="layui-card-body">
        <table id="dataTable" lay-filter="dataTable"></table>

        <script type="text/html" id="options">
            <div class="layui-btn-group">
                <?php if(can('admin.setting.edit')){?>
                    <a class="layui-btn layui-btn-sm" lay-event="edit">编辑</a>
                <?php }?>
            </div>
        </script>

    </div>
</div>
<script>
    var dataTable;
    layui.use(['layer', 'table', 'form'], function () {
        var layer = layui.layer;
        var form = layui.form;
        var table = layui.table;

        //用户表格初始化
        dataTable = table.render({
            elem: '#dataTable'
            , autoSort: false //禁用前端自动排序。注意：该参数为 layui 2.4.4 新增
            , height: 500
            , url: "<?php echo  route('admin.setting.index')?>" //数据接口
            , page: true //开启分页
            , limit: 20
            , limits: [20, 50, 100]
            , cols: [[ //表头
                {field: 'name', title: '参数名称', align: 'center'}
                , {field: 'value', title: '值', align: 'center'}
                , {field: 'id', title: '操作', align: 'center', toolbar: '#options'}
            ]]
        });

         //监听工具条
        table.on('tool(dataTable)', function(obj){ //注：tool是工具条事件名，dataTable是table原始容器的属性 lay-filter="对应的值"
            var data = obj.data //获得当前行数据
                ,layEvent = obj.event; //获得 lay-event 对应的值
            if(layEvent === 'edit'){
                var id = data.id;
                var name = data.name;
                var value = data.value;
                //prompt层
                  layer.prompt({title: '确认修改：'+name, formType: 0,value:value}, function(val, index){
                    $.ajax({
                        data:{id:id,value:val,_csrf:"<?php echo  Yii::$app->request->csrfToken ?>"},
                        url:"<?php echo  route('admin.setting.edit') ?>",
                        dataType:"JSON",
                        type:"POST",
                        beforeSend:function(){
                            layer.load(2, {time: 10*1000});
                        },
                        success: function (result) {
                            layer.closeAll();
                            if (result.retCode==1000){
                                layer.msg(result.retMsg, {icon: 1, time: 2000});
                                window.location.reload();
                            }else{
                                layer.msg(result.retMsg, {icon: 2, time: 2000});
                            }
                        }
                    });
                  });

            }
        });

        // 搜索
        form.on('submit(formDemo)', function (data) {
            field = data.field;
            dataTable.reload({
                where: field,
                page: {curr: 1}
            });
            return false; // 阻止表单跳转
        });
    });

    var field = {};

</script>
