<?php 
use yii\captcha\Captcha;
use yii\bootstrap\ActiveForm;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.bootcss.com/twitter-bootstrap/3.3.1/css/bootstrap.min.css" rel="stylesheet">

    <title>登录</title>
    <style>
        html,body{
            width: 100%;
            height: 100%;
            margin: 0px;
            /*min-width: 1150px;*/
            min-height:600px;
        }
        /* form输入框区域样式 */
        .signinpanel{
            width: 480px;
            text-align: center;
            background: white;
            border-radius: 10px;
        }

        .signinpanel>div{
            padding: 35px 30px;
        }
        .signinpanel h2{
            font-weight: normal;
            margin: 0px 0px 30px;
            color: #1a8aff;
        }
        .signinpanel input{
            font-size: 14px;
            line-height: 46px;
            border: none;
            border-bottom: 2px solid rgb(238, 238, 238);
            display: block;
            width: 100%;
            margin-top: 20px;
            outline: none;
        }
        a{ 
            text-decoration:none; 
        }
        .signinpanel input:focus{
            border-bottom-color: #1a8aff;
        }
        input::-moz-placeholder{
            color: #969698;
        }
        input::-webkit-input-placeholder{
            color: #969698;
        }
        .signinpanel button{
            line-height: 44px;
            width: 100%;
            border: none;
            border-radius: 30px;
            margin-top: 0px;
            outline: none;
            font-size: 15px;
            color: white;
            background: #1a8aff;
        }
        /* form输入框end */

        /* 背景区域图片 */
        .single{
            width: 100%;
            height: 100%;
            background: url("/image/background_img.png") no-repeat center;
            background-size:100%  100%;
        }
        /* 背景图片区域end */


        /* 左侧图文区域 */
        .markArea{
            width: 400px;
            height: 320px;
            text-align: center;
            position: relative;
            top: 50%;
            left: 50%;
            transform: translate(-50%,-50%);
        }
        .markArea h2{
            font-size: 40px;
            /* font-weight: 800; */
            color: #fff;
            margin-top: -60px;
            margin-bottom: 70px;
        }
        /* 图片 */
        .markimg{
            width: 400px;
        }
        /* 底部CopyRight */
        .markFooter{
            position: relative;
            bottom: 40px;
            color: #fff;
            font-size: 16px;
            text-align: center;
            width: 100%;
        }
        /* form表单垂直居中 */
        .arerCenter{
            position: relative;
            top: 50%;
            transform: translateY(-50%);
            left:10%;
        }
        /* 验证码区域定位 */
        .yazArea{
            position: relative;
            width: 180px;

            /*top: -45px;*/
            /*left: 190px;*/
        }
        /* form表单底部footer */
        .signup-footer{
            font-size: 12px;
            color: #2972E9;
            margin-top: 15px;
        }
        a{
            text-decoration: none;
            color: #9EA9BC;
        }
        a:hover{
            text-decoration: none;
        }
        label{
            float: left;
            color: red;
        }
        @media screen and (min-width: 765px) {
            html,body{
                min-width: 1150px;
            }
        }
        @media screen and (max-width: 765px) {
            .miniNone {
                display: none;
            }
            html,body{
                min-width: 600px;
            }
        }
        .field-loginform-captcha img{
            border:1px solid #ccc;
            margin-top:-60px;
        }
        .field-loginform-captcha label{
            width:0;
            height:0;
            display: none;
        }
        .a-active{
            color: #2972E9;
        }

        /* 底部CopyRight */
        .markFooter{
            position: relative;
            bottom: 40px;
            color: #fff;
            font-size: 16px;
            text-align: center;
            width: 100%;
        }
        #commentForm input{
            height:40px;
        }
    </style>
</head>
<body class="gray-bg signin single">
<div class="row" style="height: 100%;">
    <!-- 左侧图文区域 -->
    <div class="col-xs-6 col-sm-6 miniNone" style="height: 100%;">
        <!-- <div class="title"></div> -->
        <div class="markArea">
            <h2>物流管理系统</h2>
            <img class="markimg" src="/image/perate.png" alt="">
        </div>
    </div>

    <div class="col-xs-12 col-sm-6" style="height: 100%;">
        <div class="signinpanel arerCenter">
            <!-- 右侧登录注册区域 -->
            <div class="">
                <!-- 切换登录注册按钮 -->
                <a href="#">
                    <span style="font-size: 30px;" class="a-span a-login a-active" data="登录">登录</span>
                </a>

                <!-- form表单提交 -->
                <?php $form = ActiveForm::begin(['id' => 'commentForm']); ?>
                    <input type="text" id="username" name="username" placeholder="请输入用户名" />
                    <input type="password" id="password" name="password" placeholder="请输入密码" />

                    <div class="yazArea" style="float: right;width:100%;">
                        
                        <?php echo $form->field($model,'captcha')->widget(
                            Captcha::className(),[
                            'captchaAction' => 'admin/login/captcha',
                            'options' => [
                                    'class' => 'input-text size-L',
                                    'style' => 'width:150px;',
                                    'placeholder' => '输入验证码',
                                ],
                                'template' => '
                                   {input}&nbsp;&nbsp;{image}
                                   ',
                                'imageOptions' => [
                                    'id'=>'captchaimg',
                                    'title'=>'换一个',
                                    'alt'=>'换一个',
                                    'style'=>'cursor:pointer;',
                                ]
                            ]
                        );?>
                    </div>
                  
                    <button type="button" style="margin-top: 20px" id="submit">登录</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

</div>

<div class="markFooter">
    <span>Copyright &copy; 赤途（上海）供应链管理有限公司版权所有 </span>
</div>

<script src="/static/admin/layuiadmin/layui/layui.js"></script>
<script>
    layui.config({
        base: '/static/admin/layuiadmin/' //静态资源所在路径
    }).use(['layer','form','jquery'],function () {
        var layer = layui.layer;
        var form = layui.form;
        var $ = layui.jquery;
        
        <?php if(!empty($error)){ ?>
            layer.msg('<?php echo $error ?>',{icon:5});
        <?php }?>

        // 登录注册切换
        function is_login(obj){
            var is_active = obj.hasClass('a-active');
            if (!is_active) {
                $('.a-span').removeClass('a-active');
                obj.addClass('a-active');
                var data = obj.attr('data');
                if (data == '登录') {
                    $('#b-company').css({'display':'block'});
                } else if (data == '注册') {
                    $('#b-company').css({'display':'none'});
                }
                $('#submit').html(data);
                changeVerifyCode();
            }
        }

        $('.a-span').click(function(){
            is_login($(this));
            var is_active = $(this).hasClass('a-active');
        });
        
        $('#submit').click(function(){
            var button = $(this).html();
            var data = {};
            data._csrf = $('input[name=_csrf]').val();
            data.username = $('#username').val();
            if (!data.username) {
                layer.msg('用户名不能为空！',{icon:5});
                return false;
            }            

            if (data.username.length <2) {
                layer.msg('用户名长度大于2！',{icon:5});
                return false;
            }

            data.password = $('#password').val();
            if (!data.password) {
                layer.msg('密码不能为空！',{icon:5});
                return false;
            }
            if (data.password.length<6) {
                layer.msg('密码最少为6位！',{icon:5});
                return false;
            }
            
            data.captcha = $('#loginform-captcha').val();
            if (!data.captcha) {
                layer.msg('验证码不能为空！',{icon:5});
                return false;
            }
            if (data.captcha.length != 4) {
                layer.msg('验证码长度错误！',{icon:5});
                return false;
            }

            data.b_company = $('#b-company').val() ? $('#b-company').val() : '';
            var url = '';
            if (button == '登录') {
                url = '/admin/login';
            } else if (button == '注册'){
                url = '/admin/login/register';
            }
            $.ajax({
            //使用ajax请求site/captcha方法，加上refresh参数，接口返回json数据
                url: url,
                dataType: 'json',
                type:'post',
                data: data,
                success: function (data) {
                    var code = data.code;
                    var info = data.info;
                    if (code == 200) {
                        if (button == '登录') {
                            window.location.href = info;
                        } else if (button == '注册'){
                            layer.msg(info,{icon:1});
                            $('#loginform-captcha').val('');
                            is_login($('.a-login'));
                        }
                    } else {
                        changeVerifyCode();
                        layer.msg(info,{icon:5});
                    }
                }
            });
        });

        //解决验证码不刷新的问题
        changeVerifyCode();
        $('#captchaimg').click(function () {
            changeVerifyCode();
        });

        function changeVerifyCode() {
        //项目URL
            $.ajax({
            //使用ajax请求site/captcha方法，加上refresh参数，接口返回json数据
                url: "/admin/login/captcha?refresh",
                dataType: 'json',
                cache: false,
                success: function (data) {
                //将验证码图片中的图片地址更换
                    $("#captchaimg").attr('src', data['url']);
                }
            });
        }
    })
    
</script>
</body>
</html>