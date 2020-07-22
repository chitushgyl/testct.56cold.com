<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:46
 */
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<div class="layui-card">
    <div class="layui-card-header layuiadmin-card-header-auto" >
        <span style="margin-right: 10px;"> 搜索 </span>
        <div class="layui-inline">
            <input class="layui-input" name="keyword" id="keyword" autocomplete="off" placeholder="请输入联系人、公司关键字" style="width: 320px">
        </div>

        <button class=" layui-btn layui-btn-normal" data-type="reload" id="searchBtn" style="margin-left: 40px">搜索</button>
        <?php if(can('admin.customer.add')){?>
        <a class="layui-btn layui-btn" href="<?php echo route('admin.customer.add');?>">添加</a>
        <?php } ?>
    </div>

    <div class="layui-card-body">
        <table id="dataTable" lay-filter="dataTable"></table>
    </div>
</div>

<script type="text/html" id="options">
    <div class="layui-btn-group">
        <?php if(can('admin.customer.edit')){?>
            <a class="layui-btn layui-btn-sm" lay-event="edit">编辑</a>
        <?php } ?>

        <?php if(can('admin.customer.del')){?>
            <a class="layui-btn layui-btn-danger layui-btn-sm " lay-event="del">删除</a>
        <?php } ?>
    </div>
</script>

<script>
    layui.use(['layer','table','form'],function () {
        var layer = layui.layer;
        var form = layui.form;
        var table = layui.table;

        //用户表格初始化
        var dataTable = table.render({
            elem: '#dataTable'
            ,height: 500
            ,url: "<?php echo route('admin.customer.index'); ?>" //数据接口
            // ,response:{
            //     dataName:'list',
            //     countName:'counts'
            // }
            ,page: true //开启分页
            ,cols: [[ //表头
                {field: 'admin_id', title: 'ID',width:80, align:'center',hide:true}
                ,{field: 'contact_name', title: '联系人', align:'center',width:150}
                ,{field: 'ellipsis_name', title: '公司简称', align:'center',width:150}
                ,{field: 'all_name', title: '公司全称', align:'center',width:250}
                ,{field: 'address', title: '公司地址', align:'center',width:350}
                ,{field: 'contact_phone', title: '联系方式', align:'center',width:200}
                ,{fixed: 'right', align:'center', toolbar: '#options',title: '操作'}
            ]]
        });

        //监听工具条
        table.on('tool(dataTable)', function(obj){ //注：tool是工具条事件名，dataTable是table原始容器的属性 lay-filter="对应的值"
            var data = obj.data //获得当前行数据
                ,layEvent = obj.event; //获得 lay-event 对应的值
            if(layEvent === 'del'){
                layer.confirm('确认刪除吗？', function(index){
                    $.ajax({
                        data:{_csrf:"<?php echo Yii::$app->request->csrfToken; ?>",id:data.id},
                        url:"<?php echo route('admin.customer.del'); ?>",
                        dataType:"JSON",
                        type:"POST",
                        beforeSend:function(){
                            layer.load(2, {time: 10*1000});
                        },
                        success: function (result) {
                            layer.closeAll();
                            if (result.retCode==1000){
                                obj.del(); //删除对应行（tr）的DOM结构
                                layer.msg('刪除成功!', {icon: 1, time: 1000});
                            }else{
                                layer.msg(result.retMsg, {icon: 2, time: 1000});
                            }
                        }
                    });
                });
            } else if(layEvent === 'edit'){
                location.href = '/admin/customer/edit?id='+data.id;
            }
        });

        //搜索
        $("#searchBtn").click(function () {
            var keyword = $("#keyword").val()
            dataTable.reload({
                where:{keyword:keyword},
                page:{curr:1}
            });

        })
    })
</script>
