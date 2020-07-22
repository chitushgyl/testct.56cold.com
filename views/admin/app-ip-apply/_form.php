<div class="layui-form-item">
    <input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">
</div>
<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>等级名称</label>
    <div class="layui-input-block">
        <input type="text" name="name" value="<?php echo $info['name']?$info['name']:'';?>" lay-verify="required" class="layui-input" placeholder="等级名称" >
    </div>
</div>

<div class="layui-form-item">
    <div class="layui-input-block">
        <button type="submit" class="layui-btn" lay-submit="" lay-filter="*">确 认</button>
        <a href="<?php echo route('admin.app-level.index');?>" class="layui-btn"  >返 回</a>
    </div>
</div>

