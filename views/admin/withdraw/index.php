<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:46
 */
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>

    <style type="text/css">
        .layui-table-cell {
            height: auto;
            line-height: 40px;
        }
    </style>
    <div class="layui-card">
        <div class="layui-card-header layuiadmin-card-header-auto" >
            <span style="margin-right: 10px;"> 搜索 </span>
            <div class="layui-inline">
                <input class="layui-input" name="keyword" id="keyword" autocomplete="off" placeholder="请输入订单号/支付宝账户/姓名/归属公司" style="width: 320px">
            </div>

            <button class=" layui-btn layui-btn-normal" data-type="reload" id="searchBtn" style="margin-left: 40px">搜索</button>
        </div>

        <div class="layui-card-body">
            <table id="dataTable" lay-filter="dataTable"></table>
        </div>
    </div>

    <script type="text/html" id="state">
        {{# if(d.state==1){ }}
        <a class="layui-btn layui-btn-sm">提现中</a>
        {{# }else if(d.state == 2){ }}
        <a class="layui-btn layui-btn-normal layui-btn-sm" >成功</a>
        {{# }else{ }}
        <a class="layui-btn layui-btn-danger layui-btn-sm" >失败</a>
        {{# } }}
    </script>

    <script type="text/html" id="options">
        <div class="layui-btn-group">
            {{# if(d.state==1){ }}
            <?php if(can('admin.withdraw.success')){?>
                <a class="layui-btn layui-btn-sm" lay-event="edit">PASS</a>
            <?php } ?>

            <?php if(can('admin.withdraw.fail')){?>
                <a class="layui-btn layui-btn-danger layui-btn-sm " lay-event="del">FAIL</a>
            <?php } ?>
            {{# }else{ }}
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
                ,height: 800
                ,url: "<?php echo route('admin.withdraw.index'); ?>" //数据接口
                // ,response:{
                //     dataName:'list',
                //     countName:'counts'
                // }
                ,page: true //开启分页
                ,cols: [[ //表头
                    {field: 'id', title: 'ID',width:80, align:'center',hide:true}
                    ,{field: 'ordernumber', title: '订单号', align:'center',width:300}
                    ,{field: 'account', title: '支付宝账户', align:'center',width:250}
                    ,{field: 'name', title: '姓名', align:'center',width:100}
                    ,{field: 'price', title: '金额', align:'center',width:100}
                    ,{field: 'group_name', title: '归属公司', align:'center',width:250}
                    ,{field: 'state', title: '状态', align:'center',width:150,toolbar: '#state'}
                    ,{field: 'create_time', title: '申请时间', align:'center',width:200}
                    ,{fixed: 'right', align:'center', toolbar: '#options',title: '操作'}
                ]]
            });

            //监听工具条
            table.on('tool(dataTable)', function(obj){ //注：tool是工具条事件名，dataTable是table原始容器的属性 lay-filter="对应的值"
                var data = obj.data //获得当前行数据
                    ,layEvent = obj.event; //获得 lay-event 对应的值
                if(layEvent === 'del'){
                   var id= data.id
                    layer.prompt({
                        formType: 0,
                        title: '失败原因',
                        yes: function(index, layero){
                            var val = layero.find(".layui-layer-input").val();
                            console.log(val);
                            $.ajax({
                                url: '/admin/withdraw/fail',
                                type: 'POST',
                                dataType: 'json',
                                data: {reason: val,id:id},
                            })
                                .done(function(res) {
                                    console.log("success");
                                    if(res.code == 2000){

                                        layer.msg('操作成功',{icon:1,time:2000});
                                        reload();
                                        layer.close(index);
                                    }else{
                                        layer.msg('操作失败',{icon:2,time:2000});
                                        layer.close(index);
                                    }

                                })
                                .fail(function() {
                                    layer.close(index);
                                })
                                .always(function() {
                                    layer.close(index);
                                });
                        }
                    });
                }else{
                    layer.confirm('确认通过吗？', function(index){
                        $.ajax({
                            data:{_csrf:"<?php echo Yii::$app->request->csrfToken; ?>",id:data.id},
                            url:"<?php echo route('admin.withdraw.success'); ?>",
                            dataType:"JSON",
                            type:"POST",
                            success: function (res) {
                                if (res.code==2000){
                                    layer.msg(res.msg, {icon: 1, time: 2000});
                                    reload();
                                }else{
                                    layer.msg(res.msg, {icon: 2, time: 2000});
                                }
                            }
                        });
                    });
                    // location.href = '/admin/account/edit?id='+data.id;
                }
            });
            function reload(){
                var keyword = $("#keyword").val()
                dataTable.reload({
                    where:{keyword:keyword},
                    page:{curr:1}
                });
            }
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
<?php
