<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 09:46
 */
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
    <div class="layui-card">
        <div class="layui-card-header layuiadmin-card-header-auto">
        </div>
        <div class="layui-card-body">
            <form class="layui-form" method="post">
                <?php echo \Yii::$app->view->renderFile('@app/views/admin/account/_form.php',['info'=>$info,'list'=>$list]);?>
            </form>
        </div>
    </div>

<?php echo \Yii::$app->view->renderFile('@app/views/admin/account/_js.php');?>