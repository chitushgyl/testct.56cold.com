<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:15
 */
echo \Yii::$app->view->renderFile('@app/views/admin/base.php');
?>
<form class="layui-form" action="" method="post">
    <input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ;?>">
    <div class="layui-form-item">
        <label for="" class="layui-form-label">登录名</label>
        <div class="layui-input-inline">
            <label class="layui-form-label"><?php echo$userInfo->username?></label>
        </div>
    </div>

    <div class="layui-form-item">
        <label for="" class="layui-form-label">登录密码</label>
        <div class="layui-input-inline">
            <input type="password" name="password"  id="password" placeholder="原密码" class="layui-input" lay-verify="required">
        </div>
    </div>

    <div class="layui-form-item">
        <label for="" class="layui-form-label">设置新密码</label>
        <div class="layui-input-inline">
            <input type="password" name="newpassword" id="newpassword" placeholder="请输入新密码" class="layui-input" lay-verify="required">
        </div>
    </div>

    <div class="layui-form-item">
        <label for="" class="layui-form-label">确认新密码</label>
        <div class="layui-input-inline">
            <input type="password" name="affirmpassword" id="affirmpassword" placeholder="请再次输入密码" class="layui-input" lay-verify="required">
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-input-block">
            <button type="submit" class="layui-btn" id="button" lay-submit="" lay-filter="go">确 认</button>
        </div>
    </div>
</form>
<script>
    layui.use(['laydate','form','layer'], function(){
        var form = layui.form;
        form.on('submit(go)', function(data){
            var newPassWord = $("#newpassword").val();
            var password = $("#password").val();
            var affirmPassWord = $("#affirmpassword").val();
            if(affirmPassWord != newPassWord){
                $("#affirmpassword").focus().addClass('layui-form-danger');
                layer.msg("两次输入的密码不一致!", {icon: 2, time: 1000});
                return false;
            }
            // ajax修改密码
            $.ajax({
                type: "POST",
                data: {newPassWord:newPassWord,password:password,_csrf:"<?php echo Yii::$app->request->csrfToken ;?>"},
                dataType: "json",
                url:"<?php echo route('admin.admin-user.change');?>",
                beforeSend:function(){
                    layer.load(2, {time: 10*1000});
                },
                success: function(data){
                    layer.closeAll();
                    if (data['retCode']!=1000) {
                        // 弹窗提示
                        layer.msg(data['retMsg'],{icon:0,time:1000});
                    }else{
                        layer.msg(data['retMsg'],{icon:1,time:2000});
                        setTimeout(function(){
                            location.href = '/admin/login/out';
                        }, 2000);
                    }
                }
            });
            return false;
        });
    });
</script>