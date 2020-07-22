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
            <input class="layui-input" name="keyword" id="keyword" autocomplete="off" placeholder="请输入账户关键字" style="width: 320px">
        </div>

        <button class=" layui-btn layui-btn-normal" data-type="reload" id="searchBtn" style="margin-left: 40px">搜索</button>
        <?php if(can('admin.account.add')){?>
        <a class="layui-btn layui-btn" href="<?php echo route('admin.account.add');?>">添加</a>
        <?php } ?>
    </div>

    <div class="layui-card-body">
        <table id="dataTable" lay-filter="dataTable"></table>
    </div>
</div>

<script type="text/html" id="sex">
    {{# if(d.sex==1){ }}
        男
    {{# }else{ }}
        女
    {{# } }}
</script>

<script type="text/html" id="options">
    <div class="layui-btn-group">
        <a class="layui-btn layui-btn-sm" lay-event="view">详情</a>
        {{# if(d.id != 1){ }}
            <?php if(can('admin.account.edit')){?>
                <a class="layui-btn layui-btn-sm" lay-event="edit">编辑</a>
            <?php } ?>
        {{# } }}

        {{# if(d.id != 1){ }}
            <?php if(can('admin.account.del')){?>
                <a class="layui-btn layui-btn-danger layui-btn-sm " lay-event="del">删除</a>
            <?php } ?>
        {{# } }}
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
            ,url: "<?php echo route('admin.account.index'); ?>" //数据接口
            // ,response:{
            //     dataName:'list',
            //     countName:'counts'
            // }
            ,page: true //开启分页
            ,cols: [[ //表头
                {field: 'admin_id', title: 'ID',width:80, align:'center',hide:true}
                ,{field: 'username', title: '账户', align:'center',width:150}
                // ,{field: 'company', title: '所属公司', align:'center',width:150}
                ,{field: 'realname', title: '姓名', align:'center',width:150}
                ,{field: 'sex', title: '性别', align:'center',width:100, toolbar: '#sex'}
                ,{field: 'phone', title: '联系电话', align:'center',width:150}
                ,{field: 'email', title: '邮箱', align:'center',width:250}
                ,{field: 'weixin', title: '微信', align:'center',width:150}
                ,{field: 'role', title: '职位', align:'center',width:200}
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
                        url:"<?php echo route('admin.account.del'); ?>",
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
                location.href = '/admin/account/edit?id='+data.id;
            } else if(layEvent === 'view'){
                location.href = '/admin/account/view?id='+data.id;
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
