<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 09:46
 */
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>

<div class="view-top">   
  <?php if(can('admin.account.edit')){?>
    <button class="layui-btn edit" onclick="">编辑</button>
  <?php } ?>
    <button class="layui-btn" onclick="window.history.go(-1);">返回</button>
</div>
<table class="layui-table" lay-skin="line" lay-size="lg">
  <colgroup>
    <col width="200">
    <col>
  </colgroup>
  <thead>
  </thead>
  <tbody>
    
  </tbody>
</table>

<script type="text/javascript">
    layui.use(['jquery'],function () {
        var $ = layui.jquery;
        var data = [
            {"key":"账户名称","value":"<?php echo $model->username;?>"}
            ,{"key":"真实姓名","value":"<?php echo $model->realname;?>"}
            ,{"key":"手机号","value":"<?php echo $model->phone;?>"}
            ,{"key":"性别","value":"<?php echo $model->sex ? '男' : '女';?>"}
            ,{"key":"职位","value":"<?php echo $role?$role:'';?>"}
            ,{"key":"邮箱","value":"<?php echo $model->email;?>"}
            ,{"key":"微信号","value":"<?php echo $model->weixin;?>"}
            ,{"key":"添加时间","value":"<?php echo date('Y-m-d H:i:s',$model->addtime);?>"}
        ];
        var node = viewData(data);
        $('.layui-table tbody').empty().append(node);

        $('.edit').click(function(){
            window.location.href= "/admin/account/edit?id="+"<?php echo $model->id;?>";
        });

    });
</script>