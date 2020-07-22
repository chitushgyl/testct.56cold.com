<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 18:35
 */
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<div class="layui-card">
    <div class="layui-card-header layuiadmin-card-header-auto">
        <h2>用戶 【<?= $info->real_name ?>】分配角色</h2>
    </div>
    <div class="layui-card-body">
        <form class="layui-form" method="post">
            <div class="layui-form-item">
                <input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">
                <label for="" class="layui-form-label">角色</label>
                <div class="layui-input-inline">
                    <select name="role_id" lay-search>
                        <?php foreach($list as $v){?>
                            <option value="<?= $v['role_id']?>" <?php if($info->role_id == $v['role_id']){echo 'selected';} ?> ><?= $v['role_name']?></option>
                        <?php }?>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button type="submit" class="layui-btn" lay-submit="" >确 认</button>
                    <a href="<?php echo route('admin.admin-user.index');?>" class="layui-btn"  >返 回</a>
                </div>
            </div>
        </form>
    </div>
</div>
