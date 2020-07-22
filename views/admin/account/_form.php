<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:15
 */
// echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">
<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>账户名称</label>
    <div class="layui-input-block">
        <input type="text" name="username" value="<?php echo $info->username;?>" lay-verify="username" placeholder="请输入账户名称" class="layui-input">
    </div>
</div>
<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>真实姓名</label>
    <div class="layui-input-block">
        <input type="text" name="realname" value="<?php echo $info->realname;?>"  lay-verify="required" placeholder="请输入真实姓名" class="layui-input" >
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label"><span class="required_red">*</span>手机号</label>
    <div class="layui-input-block">
        <input type="number" name="phone" value="<?php echo $info->phone;?>"  placeholder="请输入手机号" class="layui-input" lay-verify="phone">
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label" >性别</label>
    <div class="layui-input-inline">
        <input type="radio" name="sex" value="1" title="男" <?php if($info['sex'] !== 0) {echo 'checked';} ?> >
        <input type="radio" name="sex" value="0" title="女" <?php if ($info['sex'] === 0) {echo 'checked';} ?> >
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label" >职位</label>
    <div class="layui-input-block">
        <?php foreach($list as $k=>$v){ ?>
            <?php if ($info['position'] == ''){ ?>
                <?php if ($k == 0){ ?>
                    <input type="radio" name="position" value="<?php echo $v['id'];?>" title="<?php echo $v['role'];?>" checked >
                <?php } else { ?>
                    <input type="radio" name="position" value="<?php echo $v['id'];?>" title="<?php echo $v['role'];?>"  >
                <?php } ?>
            <?php } else { ?>
                    <input type="radio" name="position" value="<?php echo $v['id'];?>" title="<?php echo $v['role'];?>" <?php if ($info['position'] == $v['id']) {echo 'checked';} ?> >
            <?php } ?>
            
        <?php } ?>
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label">邮箱</label>
    <div class="layui-input-block">
        <input type="email" name="email" value="<?php echo $info->email;?>"  placeholder="请输入邮箱" class="layui-input" lay-verify="email">
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label">微信号</label>
    <div class="layui-input-block">
        <input type="weixin" name="weixin" value="<?php echo $info->weixin;?>"  placeholder="请输入微信号" class="layui-input" >
    </div>
</div>

<div class="layui-form-item">
    <label for="" class="layui-form-label">登录密码</label>
    <div class="layui-input-block">
        <input type="password" name="password"  placeholder="<?php if(!empty($info['password'])){echo"不输入不更改!";}else{echo"密码默认为:123456";}?>" class="layui-input" lay-verify="password">
    </div>
</div>

<div class="layui-form-item">
    <div class="layui-input-block">
        <button type="submit" class="layui-btn" lay-submit="" lay-filter="*">确 认</button>
        <a class="layui-btn" href="#" onclick="window.history.go(-1);" >返 回</a>
    </div>
</div>