<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 19:19
 */

namespace app\controllers\admin;
use Yii,
    yii\web\Controller,
    app\models\LoginForm,
    app\models\AdminRole,
    app\models\AdminUser,
    app\models\Common,
    app\models\Account;

class LoginController extends Controller
{

    public function actions(){
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'maxLength'=>4,
                'minLength'=>4,
                'padding'=>5,
                'height'=>39,
                'width'=>100,
                'offset'=>3,
            ],
        ];
    }

    /**
     * Desc:登入页面
     * Created by pysh
     * Date: 2020/2/2
     * Time: 19:20
     */
    public function actionIndex(){
        $this->layout = false;
        $request = Yii::$app->request;
        if($request->isPost){
            $post = $request->post();
            $username = $post['username'];
            $password = $post['password'];
            $captcha = $post['captcha'];
            if(empty($username) || empty($password)){
                return pj(['code'=>400,'info'=>'账号或密码不能为空！']);
            }
           
            if(empty($captcha)){
                return pj(['code'=>400,'info'=>'验证码不能为空！']);
            }

            if (!$this->createAction('captcha')->validate($captcha, false)){
                return pj(['code'=>400,'info'=>'验证码错误！']);
            }

            $admin = Account::findOne([
                'username' => $username,
                'password' => md5($password),
                'state' => 1
            ]);

            if ($admin) {
                $session = Yii::$app->session;
                $session->set('userInfo', $admin->toArray());
                $session->set('admin_id', $admin->id);//账号id
                $session->set('admin_name', $admin->username);//账号名称

                AddLogController::addSysLog(AddLogController::login,'登录系统');
                return pj(['code'=>200,'info'=>'/admin/index/index']);
            } else {
                return pj(['code'=>400,'info'=>'账号或密码错误!']);
            }
        }else{
            $model = new LoginForm();
            return $this->render('login',['model' => $model]);
        }
    }    

    /**
     * Desc:注册页面
     * Created by pysh
     * Date: 2020/2/3
     * Time: 19:20
     */
    public function actionRegister(){
        $this->layout = false;
        $request = Yii::$app->request;
        if($request->isPost){
            $post = $request->post();
            $username = $post['username'];
            $password = $post['password'];
            $company = $post['company'];
            $captcha = $post['captcha'];
            if(empty($username) || empty($password)){
                return pj(['code'=>400,'info'=>'账号或密码不能为空！']);
            }
            if(empty($company)){
                return pj(['code'=>400,'info'=>'公司不能为空！']);
            }            
            if(empty($captcha)){
                return pj(['code'=>400,'info'=>'验证码不能为空！']);
            }
            $user = Account::findOne(['company' => $company]);
            if ($user) {
                return pj(['code'=>400,'info'=>'该公司已经注册！']);
            }

            if (!$this->createAction('captcha')->validate($captcha, false)){
                return pj(['code'=>400,'info'=>'验证码错误！']);
            }
            $time = time();
            $model = new Account();
            $model->username = $username;
            $model->realname = $company;
            $model->company = $company;
            $model->password = md5($password);
            $model->addtime = (string)$time;
            $res = $model->save();
            if ($res) {
                $id = $model->id;
                $role_default = [
                    [$id,'业务','0',$time],
                    [$id,'调度','0',$time],
                    [$id,'财务','0',$time]
                ];
                Yii::$app->db->createCommand()->batchInsert(AdminRole::tableName(), ['admin', 'role','permissions','addtime'], $role_default)->execute();
                return  pj(['code' => 200,'info'=>'注册成功']);
            } else {
                return  pj(['code' => 400,'info'=>'注册失败!'.$res.'model:'.json_encode($model).'user:'.json_encode($user)]);
            }
        } else {
            return pj(['code'=>400,'info'=>'错误！']);
        }
    }

    /**
     * Desc: 退出登入
     * Created by pysh
     * Date: 2020/2/2
     * Time: 15:23
     */
    public function actionOut(){
        session_start();
        session_destroy();
        return $this->redirect('/admin/login');
    }
}