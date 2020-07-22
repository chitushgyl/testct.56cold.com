<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 14:08
 */?>
<div class="layui-form-item">
    <input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">
    <label for="" class="layui-form-label"><span class="required_red">*</span>职位名称</label>
    <div class="layui-input-inline">
        <input type="text" name="role" value="<?php echo $info['role']?$info['role']:'';?>" lay-verify="required" class="layui-input" placeholder="请输入职位名称">
    </div>
</div>
<div class="layui-form-item">
    <div class="layui-input-block">
        <button type="submit" class="layui-btn" lay-submit="" lay-filter="*">确 认</button>
        <a href="<?php echo route('admin.admin-role.index');?>" class="layui-btn"  >返 回</a>
    </div>
</div>


