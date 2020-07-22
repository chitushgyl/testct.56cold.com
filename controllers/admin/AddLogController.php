<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 10:05
 */

namespace app\controllers\admin;
use yii\web\Controller,
    Yii,
    app\models\Note;

class AddLogController  extends Controller
{
    static public function addSysLog($module,$content)
    {
        $session = Yii::$app->session;
        $userInfo = $session->get('userInfo');
        $model = new Note();
        $model->admin = (int)$session->get('admin_id');
        $model->uid = (int)$session->get('admin_id');
        $model->c = (string)$module;
        $model->a = (string)$content;
        $model->addtime = (int)time();
        $res = $model->save();
    }

    /**
     * 操作模块 module 名称
     */
    const login = '登录';   // 登录
    const account = '账户管理';   // 账户管理
    const customer = '客户管理';   // 客户管理
    const permission = '权限管理';   // 权限管理
    const note = '操作日志';   // 操作日志
    const left = 'PC左边栏管理';   
    const top = 'PC头部菜单栏';   
    const appLevel = '公司等级权限';   
    const appIpApply = '域名';   
    const setting = '参数设置';   

    public static $optModuleName = [
        self::login => '登录',
        self::account => '账户管理',
        self::customer => '客户管理',
        self::permission => '权限管理',   
        self::note => '操作日志',
        self::left => 'PC左边栏管理',
        self::top => 'PC头部菜单栏',
        self::appLevel => '公司等级权限',
        self::appIpApply => '域名',
        self::setting => '参数设置',
    ];

}