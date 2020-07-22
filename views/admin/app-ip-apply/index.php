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
            <form id="search-form" class="layui-form" method="post">
                <div class="layui-form-item">
                    <div class="layui-input-inline" style="width:50px;">
                       <span>状态</span>
                    </div>
                    <div class="layui-input-inline">
                        <select name="status" id="status">
                            <option value="">全部</option>
                            <option value="2">待处理</option>
                            <option value="1">已通过</option>
                            <option value="3">已拒绝</option>
                            <option value="4">已取消</option>
                        </select>
                    </div>

                    <div class="layui-btn-inline" style="line-height:38px;">
                        <button class="layui-btn layui-btn-sm" lay-submit="" type="button" data-type="reload" id="searchBtn" lay-filter="" style="height: 38px;">
                            搜索
                        </button>
                    </div>
                </div>
            </form>
            
        </div>
    </div>
    <div class="layui-card-body">
        <table id="dataTable" lay-filter="dataTable"></table>

        <script type="text/html" id="table_logo">
            <img src="{{d.logo}}" height="28px">
        </script>   

        <script type="text/html" id="table_index">
            <img src="{{d.index}}" height="28px">
        </script>   

        <script type="text/html" id="status_data">
            {{# if(d.status == 1) { }}
                <span style="color:green;">已通过</span>
            {{# }else if(d.status == 2) { }}
                <span style="color:#333;">待处理</span>
            {{# }else if(d.status == 3) { }}
                <span style="color:red;">已拒绝</span>
            {{# }else if(d.status == 4) { }}
                <span style="">已取消</span>
            {{# } }}
        </script>

        <script type="text/html" id="options">
            <div class="layui-btn-group">
                {{# if(d.status == 2) { }}
                    <?php if(can('admin.app-ip-apply.add')){?>
                    <a class="layui-btn layui-btn-sm" lay-event="agree">通过</a>
                    <?php }?>

                    <?php if(can('admin.app-ip-apply.refuse')){?>
                    <a class="layui-btn layui-btn-sm layui-btn-danger" lay-event="refuse">拒绝</a>
                    <?php } ?>
                {{# } }}
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
            ,url: "<?php echo route('admin.app-ip-apply.index')?>"//数据接口
            ,page: true //开启分页
            ,cols: [[ //表头
                {field: 'id', title: 'ID',align:'center', width:80}
                ,{field: 'group_name', align:'center',title: '申请公司'}
                ,{field: 'url', align:'center',title: '域名'}
                ,{field: 'name', align:'center',title: '公司简称'}
                ,{field: 'full_name', align:'center',title: '公司全称'}
                ,{field: 'logo', align:'center',title: '浏览器logo', toolbar: '#table_logo'}
                ,{field: 'index', align:'center',title: '页面logo', toolbar: '#table_index'}
                ,{field: 'remark', align:'center',title: '备注'}
                ,{field: 'create_time', align:'center',title: '申请时间'}
                ,{field: 'status', align:'center',title: '状态', toolbar: '#status_data'}
                ,{fixed: 'right',align:'center', toolbar: '#options',title: '操作'}
            ]]
        });

        //监听工具条
        table.on('tool(dataTable)', function(obj){ //注：tool是工具条事件名，dataTable是table原始容器的属性 lay-filter="对应的值"
            var data = obj.data //获得当前行数据
                ,layEvent = obj.event; //获得 lay-event 对应的值
            if(layEvent === 'refuse'){
                layer.prompt({title:'确定拒绝域名('+data.url+')的申请吗？理由：',formType:2}, function(pass,index){

                    $.ajax({
                        data:{id:data.id,_csrf:"<?php echo  Yii::$app->request->csrfToken ?>",remark:pass},
                        url:"<?php echo  route('admin.app-ip-apply.refuse') ?>",
                        dataType:"JSON",
                        type:"POST",
                        beforeSend:function(){
                            layer.load(2, {time: 10*1000});
                        },
                        success: function (result) {
                            layer.closeAll();
                            if (result.retCode==1000){
                                layer.msg('操作成功!!', {icon: 1, time: 1000});
                                reload();
                            }else{
                                layer.msg(result.retMsg, {icon: 2, time: 1000});
                            }
                        }
                    });
                });
            } else if(layEvent === 'agree'){
                layer.confirm('确定同意域名('+data.url+')的申请吗？', function(index){
                    $.ajax({
                        data:{id:data.id,_csrf:"<?php echo  Yii::$app->request->csrfToken ?>"},
                        url:"<?php echo  route('admin.app-ip-apply.agree') ?>",
                        dataType:"JSON",
                        type:"POST",
                        beforeSend:function(){
                            layer.load(2, {time: 10*1000});
                        },
                        success: function (result) {
                            layer.closeAll();
                            if (result.retCode==1000){
                                layer.msg('操作成功!!', {icon: 1, time: 1000});
                                reload();
                            }else{
                                layer.msg(result.retMsg, {icon: 2, time: 1000});
                            }
                        }
                    });
                });
            }
        });

        function reload(){
           var status = $("#status").val()
            dataTable.reload({
                where:{status:status},
                page:{curr:1}
            }); 
        }

        //搜索
        $("#searchBtn").click(function () {
            reload();
        })
    })
</script>

