<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 14:27
 */
namespace app\controllers\admin;
use Yii,
    yii\web\Controller,
    app\models\AdminRole,
    app\models\AdminIcons,
    app\models\Account,
    app\models\AdminPermissions;

class IndexController extends Controller
{

    /**
     * Desc: 后台首页
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:39
     */
    public function actionIndex(){
        $this->layout = false;
        $session = Yii::$app->session;
        if(!$session->get('admin_id')){
            return $this->redirect('/admin/login');
        }
        // define('TREE', $this->getTree());
        $trees = $this->getTree();
        return $this->render('index',['userInfo'=>array_merge($session->get('userInfo'),['role_name'=>$session->get('role_name')]),'trees'=>$trees]);
    }

    /**
     * Desc: 獲取登錄用戶的左邊側欄
     * Created by pysh
     * Date: 2020/2/2
     * Time: 16:46
     * @return array
     */
    private function getTree(){
        // 获取登入账户信息
        $per = AdminPermissions::getList();
        $son = [];
        $list = [];
        foreach ($per as $key => $value) {
            if ($value['parent_id'] == 0) {
                $list[$value['id']] = $value + ['son' => []];
            } elseif ($value['parent_id'] != 0) {
                $son[] = $value;
            }
        }
        foreach ($son as $k => $v) {
            if (isset($list[$v['parent_id']])) {
                $v['route'] = '/' . str_replace('.','/',$v['route']);

                $list[$v['parent_id']]['son'][] = $v;
            }
        }
        unset($son);
        return $list;
    }

    /**
     * Desc: 主页显示页面
     * Created by pysh
     * Date: 2020/2/2
     * Time: 15:50
     * @return string
     */
    public function actionCenter(){
        $this->layout = false;
        return $this->render('center');
    }

    /**
     * Desc: 圖標列表
     * Created by pysh
     * Date: 2020/2/2
     * Time: 15:50
     * @return string
     */
    public function actionIcons(){
        $icons = AdminIcons::find()->asArray()->all();
        return json_encode(['code' => 0, 'msg' => '请求成功', 'data' => $icons]);
    }
}