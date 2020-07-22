<div class="layui-form-item">
    <input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">
    <label for="" class="layui-form-label"><span class="required_red">*</span>父级</label>
    <div class="layui-input-block">
        <select name="parent_id" lay-search >
            <option value="0">顶级权限</option>
            <?php if(!isset($info)){$info=['parent_id'=>$pid];}else{ $pid = $info['parent_id'];} ?>
            <?php treeOption($tree,$info);?>
        </select>
    </div>
</div>
<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>显示名称</label>
    <div class="layui-input-block">
        <input type="text" name="display_name" value="<?php echo $info['display_name']?$info['display_name']:'';?>" lay-verify="required" class="layui-input" placeholder="如：系统管理" >
    </div>
</div>
<div class="layui-form-item">
    <label for="" class="layui-form-label">路由</label>
    <div class="layui-input-block">
        <input class="layui-input" type="text" name="route" value="<?php echo $info['route']?$info['route']:'';?>" placeholder="如：/center/index" >
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label">状态</label>
    <div class="layui-input-block">
        启用  <input type="radio" name="use_flag" value="Y" <?php echo $info['use_flag'] == 'Y' ? 'checked' : 'checked'; ?> >
        禁用  <input type="radio" name="use_flag" value="N" <?php echo $info['use_flag'] == 'N' ? 'checked' : ''; ?> >
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label">排序</label>
    <div class="layui-input-block">
        <input class="layui-input" type="text" name="sort" value="<?php echo $info['sort']?$info['sort']:'';?>"  placeholder="输入排序,越小排在越前面" onkeyup="number(this)">
    </div>
</div>
<div class="layui-form-item">
    <label for="" class="layui-form-label">图标</label>
    <div class="layui-input-block">
        <input class="layui-input" type="text" name="icon" value="<?php echo $info['icon']?$info['icon']:'';?>"  placeholder="" >
    </div>
</div>
<div class="layui-form-item">
    <div class="layui-input-block">
        <?php if (!$pid) {$pid=0;};?>
        <button type="submit" class="layui-btn" lay-submit="" lay-filter="*">确 认</button>
        <a href="<?php echo route('admin.left.index','pid='.$pid);?>" class="layui-btn"  >返 回</a>
    </div>
</div>

