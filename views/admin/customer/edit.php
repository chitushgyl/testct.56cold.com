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
            <form class="layui-form" method="post" enctype="multipart/form-data">
                <?php echo \Yii::$app->view->renderFile('@app/views/admin/customer/_form.php',['info'=>$info]);?>
            </form>
        </div>
    </div>

<?php echo \Yii::$app->view->renderFile('@app/views/admin/customer/_js.php');?>