<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 11:49
 */
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<div class="layui-card">
    <div class="layui-card-header layuiadmin-card-header-auto">
        <h2>更新权限</h2>
    </div>
    <div class="layui-card-body">
        <form class="layui-form" method="post">
            <?php echo \Yii::$app->view->renderFile('@app/views/admin/permission/_form.php',['info'=>$info,'tree'=>$tree,'pid'=>$pid]);?>
        </form>
    </div>
</div>

<?php echo \Yii::$app->view->renderFile('@app/views/admin/permission/_js.php');?>
