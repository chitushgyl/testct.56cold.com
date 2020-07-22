<?php
/**
 * Created by pysh.
 * Date: 2019/7/9
 * Time: 10:48
 */
use app\controllers\admin\AddLogController;
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<div class="layui-card">
    <div class="layui-card-header layuiadmin-card-header-auto">
        <form id="search-form" class="layui-form" method="post">
            <div class="layui-form-item">
                <div class="layui-input-inline">
                    <select name="c" lay-search>
                        <option value="">全部</option>
                        <?php foreach(AddLogController::$optModuleName as $key => $value){?>
                        <option value="<?php echo $key ?>"><?php echo $value ?></option>
                        <?php }?>
                    </select>
                </div>

                <div class="layui-input-inline" style="">
                    <input type="text" name="username" id="username" placeholder="操作人" class="layui-input">
                </div>

                <div class="layui-input-inline" style="width: 120px;">
                    <input type="text" name="time_start" id="time_start" placeholder="开始时间" class="layui-input">
                </div>

                <div class="layui-input-inline" style="width: 120px;">
                    <input type="text" name="time_end" id="time_end" placeholder="结束时间" class="layui-input">
                </div>
                <div class="layui-btn-inline" style="line-height:38px;">
                    <button class="layui-btn layui-btn-sm" lay-submit="" type="submit" data-type="reload" id="searchBtn" lay-filter="formDemo" style="height: 38px;">
                        搜索
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div class="layui-card-body" style="padding-bottom: 0">
<!--        <a id="export" style="float: right;margin-top: 7px" class="layui-btn layui-btn-sm" >导 出</a>-->
    </div>
    <div class="layui-card-body">
        <table id="dataTable" lay-filter="dataTable"></table>
    </div>
</div>
<script>
    var dataTable;
    layui.use(['layer', 'table', 'form', 'laydate'], function () {
        var layer = layui.layer;
        var form = layui.form;
        var table = layui.table;
        var laydate = layui.laydate;

        laydate.render({
            elem: '#time_start' //指定元素
        });
        laydate.render({
            elem: '#time_end' //指定元素
        });

        //用户表格初始化
        dataTable = table.render({
            elem: '#dataTable'
            , autoSort: false //禁用前端自动排序。注意：该参数为 layui 2.4.4 新增
            , height: 500
            , url: "<?php echo  route('admin.note.index')?>" //数据接口
            , page: true //开启分页
            , limit: 20
            , limits: [20, 50, 100]
            , cols: [[ //表头
                {field: 'id', title: 'ID', width: 80}
                , {field: 'username', title: '操作人', align: 'center', width: 150}
                , {field: 'c', title: '操作模块', align: 'center', width: 150}
                , {field: 'a', title: '操作內容', align: 'center'}
                , {field: 'addtime', title: '操作时间', align: 'center'}
            ]]
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
