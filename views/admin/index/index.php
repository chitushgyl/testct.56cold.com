<?php
use yii\helpers\Url;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>云途冷链管理</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" href="/static/admin/layuiadmin/layui/css/layui.css" media="all">
    <link rel="stylesheet" href="/static/admin/layuiadmin/style/admin.css" media="all">
    <style type="text/css">
        .layui-logo{
            height: 90px !important;
            line-height: 90px !important;
            font-size:18px !important;
            font-weight:bold !important;
        }
        .body-footer-parent{
            position:relative;
            width:100%;
        }
        .body-footer{
            font-size: 10px;
            height:40px;
            position: fixed;
            bottom:0;
            background: #fff;
            width:100%;
            z-index: 999;
            text-align:right;
            margin-right:20px;
            line-height: 40px;
        }
        .body-footer-span{
            padding-right:240px;
        }

        #LAY-system-side-menu{
            margin-top:90px;
        }
    </style>
</head>
<body class="layui-layout-body">

<div id="LAY_app">
    <div class="layui-layout layui-layout-admin">
        <div class="layui-header">
            <!-- 头部区域 -->
            <ul class="layui-nav layui-layout-left">
                <li class="layui-nav-item layadmin-flexible" lay-unselect>
                    <a href="javascript:;" layadmin-event="flexible" title="侧边伸缩">
                        <i class="layui-icon layui-icon-shrink-right" id="LAY_app_flexible"></i>
                    </a>
                </li>
                <li class="layui-nav-item" lay-unselect>
                    <a href="javascript:;" layadmin-event="refresh" title="刷新">
                        <i class="layui-icon layui-icon-refresh-3"></i>
                    </a>
                </li>
            </ul>
            <ul class="layui-nav layui-layout-right" lay-filter="layadmin-layout-right">
                <li class="layui-nav-item layui-hide-xs" lay-unselect>
                    <a href="javascript:;" layadmin-event="theme">
                        <i class="layui-icon layui-icon-theme"></i>
                    </a>
                </li>
                <li class="layui-nav-item layui-hide-xs" lay-unselect>
                    <a href="javascript:;" layadmin-event="note">
                        <i class="layui-icon layui-icon-note"></i>
                    </a>
                </li>
                <li class="layui-nav-item layui-hide-xs" lay-unselect>
                    <a href="javascript:;" layadmin-event="fullscreen">
                        <i class="layui-icon layui-icon-screen-full"></i>
                    </a>
                </li>
                <li class="layui-nav-item " lay-unselect style="margin-right: 20px;">
                    <a href="javascript:;">
                        <cite><?php echo $userInfo['username'] ?>(<?php echo $userInfo['role_name']?$userInfo['role_name']:"无身份" ?>)</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd id="change" style="text-align: center"><span style="color:#333333;cursor:pointer;">个人信息设定</span></dd>
                        <dd  style="text-align: center;"><a href="/admin/login/out">退出</a></dd>
                    </dl>
                </li>

            </ul>
        </div>

        <!-- 侧边菜单 -->
        <div class="layui-side layui-side-menu">
            <div class="layui-side-scroll">
                <div class="layui-logo" lay-href="<?php echo Url::to(['/admin/index/center']); ?>">
                    云途冷链管理
                </div>

                <ul class="layui-nav layui-nav-tree" lay-shrink="all" id="LAY-system-side-menu" lay-filter="layadmin-system-side-menu">
                    <li data-name="home" class="layui-nav-item layui-nav-itemed">
                        <a lay-href="<?php echo Url::to(['/admin/index/center']); ?>" lay-tips="首页" lay-direction="2">
                            <i class="layui-icon layui-icon-home"></i>
                            <cite>首页</cite>
                        </a>
                    </li>
                    <?php foreach($trees as $menu){?>
                        <?php if ($menu["route"] != ''){ ?>
                            <li data-name="home" class="layui-nav-item layui-nav-itemed">
                                <a lay-href="<?php echo route($menu["route"]); ?>" lay-tips="<?php echo $menu["display_name"]; ?>" lay-direction="2">
                                    <i class="layui-icon <?php echo $menu["class"]; ?>"></i>
                                    <cite><?php echo $menu["display_name"]; ?></cite>
                                </a>
                            </li>
                        <?php } else { ?>
                            <li data-name='<?php echo $menu["route"] ?>' class="layui-nav-item">
                                <a href="javascript:;" lay-tips="<?php echo $menu["display_name"] ?>" lay-direction="2">
                                    <i class="layui-icon <?php echo $menu['class'] ?>"></i>
                                    <cite><?php echo $menu["display_name"] ?></cite>
                                </a>
                                <dl class="layui-nav-child">
                                    <?php foreach($menu['son'] as $v){?>
                                    <dd data-name="<?php echo $v["display_name"] ?>" >
                                        <a lay-href="<?php echo $v["route"] ?>"><?php echo $v["display_name"] ?></a>
                                    </dd>
                                    <?php }?>
                                </dl>
                            </li>
                        <?php } ?>
                    <?php }?>
                </ul>
            </div>
        </div>

        <!-- 页面标签 -->
        <div class="layadmin-pagetabs" id="LAY_app_tabs">
            <div class="layui-icon layadmin-tabs-control layui-icon-prev" layadmin-event="leftPage"></div>
            <div class="layui-icon layadmin-tabs-control layui-icon-next" layadmin-event="rightPage"></div>
            <div class="layui-icon layadmin-tabs-control layui-icon-down">
                <ul class="layui-nav layadmin-tabs-select" lay-filter="layadmin-pagetabs-nav">
                    <li class="layui-nav-item" lay-unselect>
                        <a href="javascript:;"></a>
                        <dl class="layui-nav-child layui-anim-fadein">
                            <dd layadmin-event="closeThisTabs"><a href="javascript:;">关闭当前标签页</a></dd>
                            <dd layadmin-event="closeOtherTabs"><a href="javascript:;">关闭其它标签页</a></dd>
                            <dd layadmin-event="closeAllTabs"><a href="javascript:;">关闭全部标签页</a></dd>
                        </dl>
                    </li>
                </ul>
            </div>
            <div class="layui-tab" lay-unauto lay-allowClose="true" lay-filter="layadmin-layout-tabs">
                <ul class="layui-tab-title" id="LAY_app_tabsheader">
                    <li lay-id="<?php echo route('admin.index.center'); ?>" lay-attr="<?php echo Url::to(['/admin/index/center']); ?>" class="layui-this"><i class="layui-icon layui-icon-home"></i></li>
                </ul>
            </div>
        </div>

        <!-- 主体内容 -->
        <div class="layui-body" id="LAY_app_body">
            <div class="layadmin-tabsbody-item layui-show">
                <iframe src="<?php echo Url::to(['/admin/index/center']); ?>" frameborder="0" class="layadmin-iframe"></iframe>
            </div>

            <div class="body-footer-parent">
                <div class="body-footer">
                    © 2018-2028 赤途冷链
                    <span class="body-footer-span">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </div>
            </div>
        </div>

        <!-- 辅助元素，一般用于移动设备下遮罩 -->
        <div class="layadmin-body-shade" layadmin-event="shade"></div>
    </div>
</div>

<script src="/static/admin/layuiadmin/layui/layui.js"></script>
<script src="/js/jquery.min.js"></script>
<script>
    layui.config({
        base: '/static/admin/layuiadmin/' //静态资源所在路径
    }).extend({
        index: 'lib/index' //主入口模块
    }).use('index'); 

    layui.use(['layer','jquery'],function(){
        var layer = layui.layer;
        var $ = layui.jquery;
        $('#change').click(function(){
            var username = "<?php echo $userInfo['username'] ?>";
            var node = '';
            node +='<form class="layui-form" action="" method="post">';
            node +='    <input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">';
            node +='    <div class="layui-form-item">';
            node +='        <label for="" class="layui-form-label">登录名</label>';
            node +='        <div class="layui-input-inline">';
            node +='            <label class="layui-form-label">'+username+'</label>';
            node +='        </div>';
            node +='    </div>';
            node +='    <div class="layui-form-item">';
            node +='        <label for="" class="layui-form-label">登录密码</label>';
            node +='        <div class="layui-input-inline">';
            node +='            <input type="password" name="password"  id="password" placeholder="原密码" class="layui-input" lay-verify="required">';
            node +='        </div>';
            node +='    </div>';
            node +='    <div class="layui-form-item">';
            node +='        <label for="" class="layui-form-label">设置新密码</label>';
            node +='        <div class="layui-input-inline">';
            node +='            <input type="password" name="newpassword" id="newpassword" placeholder="请输入新密码" class="layui-input" lay-verify="required">';
            node +='        </div>';
            node +='    </div>';
            node +='    <div class="layui-form-item">';
            node +='        <label for="" class="layui-form-label">确认新密码</label>';
            node +='        <div class="layui-input-inline">';
            node +='            <input type="password" name="affirmpassword" id="affirmpassword" placeholder="请再次输入密码" class="layui-input" lay-verify="required">';
            node +='        </div>';
            node +='    </div>';
            node +='    <div class="layui-form-item">';
            node +='        <div class="layui-input-block">';
            node +='            <button type="button" class="layui-btn" id="button" lay-submit="" lay-filter="go">确 认</button>';
            node +='        </div>';
            node +='    </div>';
            node +='</form>';

            layer.open({
                type: 1,
                area: [600+'px', 450 +'px'],
                fix: false, //不固定
                // shade:0.4,
                title: "个人信息设定",
                moveOut:true,
                content: node,
                end:function(){

                }
            });
        });

        $(document).on('click','#button',function(){
            var password = $("#password").val();
            if(!password){
                layer.msg("登录密码不能为空!", {icon: 2, time: 1000});
                return false;
            }
            var newPassWord = $("#newpassword").val();
            if(!newPassWord){
                layer.msg("新密码不能为空!", {icon: 2, time: 1000});
                return false;
            }
            var affirmPassWord = $("#affirmpassword").val();
            if(!affirmPassWord){
                layer.msg("确认新密码不能为空!", {icon: 2, time: 1000});
                return false;
            }            

            if(password.length <6 || newPassWord.length <6 || affirmPassWord.length <6){
                layer.msg("密码最少6位!", {icon: 2, time: 1000});
                return false;
            }
            if(affirmPassWord != newPassWord){
                $("#affirmpassword").focus().addClass('layui-form-danger');
                layer.msg("两次输入的密码不一致!", {icon: 2, time: 1000});
                return false;
            }
            $.ajax({
                type: "POST",
                data: {newPassWord:newPassWord,password:password,_csrf:"<?php echo Yii::$app->request->csrfToken ?>"},
                dataType: "json",
                url:"<?php echo route('admin.account.change')?>",
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
        });
    })

</script>
</body>
</html>


