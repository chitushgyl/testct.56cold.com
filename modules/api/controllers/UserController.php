<?php

namespace app\modules\api\controllers;

use app\models\AppGroup;
use app\models\AppTemplate;
use app\models\TelCheck;
use app\models\User;
use app\models\UserToken;
use Yii;
use app\models\Send;

/**
 * Default controller for the `api` module
 */
class UserController extends CommonController
{

    //密码登陆
    public function actionLogin(){
        $request = Yii::$app->request;
        if($request->isPost){
            $post = $request->post();
            if (!$request->post('username') || !$request->post('password')){
                $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
                return $this->resultInfo($data);
            }
            $username = $post['username'];
            $password = md5($post['password']);
            $model = new User();
            $user = User::find()->where(['login'=>$username])->one();
            //验证账号信息
            if (!$user){
                $data = $this->encrypt(['code'=>400,'msg'=>'账号不存在']);
                return $this->resultInfo($data);
            }
            if ($user->pwd == $password && $user->use_flag == 'Y' && $user->delete_flag == 'Y'){
                $res = User::find()
                    ->alias('n')
                    ->select(['n.id','n.login','n.name','n.tel','n.group_id','n.true_name','n.userimage','n.sex','n.balance','n.position','n.admin_id','n.parent_group_id','n.com_type','b.group_name','a.group_name p_group_name','a.level_id','a.expire_time','b.main_id'])
                    ->leftJoin('app_group a','n.parent_group_id = a.id')
                    ->leftJoin('app_group b','n.group_id = b.id')
                    ->where(['n.login'=>$username])
                    ->asArray()
                    ->one();
                $res['token'] = $this->product_token($user->id);
                $this->hanldlog($res['id'],$res['login'].'登陆成功');
                $auths = $this->left_auth($user);
                $tree = list_to_tree($auths);

                $data = $this->encrypt(['code'=>200,'msg'=>'登录成功','data'=>$res,'tree'=>$tree]);
                return $this->resultInfo($data);
            }elseif($user->pwd == $password && $user->use_flag == 'N' && $user->delete_flag == 'Y'){
                $data = $this->encrypt(['code'=>400,'msg'=>'账号异常，请联系管理员']);
                return $this->resultInfo($data);
            }elseif($user->pwd == $password && $user->use_flag == 'Y' && $user->delete_flag == 'N'){
                $data = $this->encrypt(['code'=>400,'msg'=>'账号异常，请联系管理员']);
                return $this->resultInfo($data);
            }elseif($user->pwd == $password && $user->use_flag == 'N' && $user->delete_flag == 'N'){
                $data = $this->encrypt(['code'=>400,'msg'=>'账号异常，请联系管理员']);
                return $this->resultInfo($data);
            } elseif($user->pwd != $password){
                $data = $this->encrypt(['code'=>400,'msg'=>'密码错误！']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写正确的信息！']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 手机验证码登陆
     * */
    public function actionLogin_code(){
        $request = Yii::$app->request;
        $input = $request->post();
        $phone = $input['phone'];
        $code = $input['code'];
        // 判断是否传值
        if(empty($phone)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误！']);
            return $this->resultInfo($data);
        }
        $model = User::find()->where(['login'=>$phone])->one();
        if (!$model){
            $data = $this->encrypt(['code'=>400,'msg'=>'账号不存在！']);
            return $this->resultInfo($data);
        }
        //验证手机验证码是否正确
        $check = TelCheck::find()->where(['tel'=>$phone])->one();
//        var_dump($check->message);
//        exit();
        if(strlen($code) > 4){ // 验证码不能超过四位数字
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码不能超过四位数字']);
            return $this->resultInfo($data);
        }elseif($check->message != $code){ // 验证码是否正确
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码不正确！']);
            return $this->resultInfo($data);
        }elseif($check->expired_time < time()){ // 检查过期时间
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码已过期！']);
            return $this->resultInfo($data);
        }

        if ($model->use_flag == 'Y' && $model->delete_flag == 'Y'){
            $user = User::find()
                ->alias('n')
                ->select(['n.id','n.login','n.name','n.tel','n.group_id','n.true_name','n.userimage','n.sex','n.balance','n.position','n.admin_id','n.parent_group_id','n.com_type','b.group_name','a.group_name p_group_name','a.level_id','a.expire_time','b.main_id'])
                ->leftJoin('app_group a','n.parent_group_id = a.id')
                ->leftJoin('app_group b','n.group_id = b.id')
                ->where(['n.login'=>$phone])
                ->asArray()
                ->one();
            $user['token'] = $this->product_token($user['id']);
            $this->delete_code($phone);

            $auths = $this->left_auth($user);
            $tree = list_to_tree($auths);
            $this->hanldlog($user['id'],$user['login'].'登陆成功');
            $data = $this->encrypt(['code'=>200,'msg'=>'登录成功','data'=>$user,'tree'=>$tree]);
            return $this->resultInfo($data);
        }elseif($model->use_flag == 'N' && $model->delete_flag == 'Y'){
            $data = $this->encrypt(['code'=>400,'msg'=>'账号异常，请联系管理员']);
            return $this->resultInfo($data);
        }elseif($model->use_flag == 'Y' && $model->delete_flag == 'N'){
            $data = $this->encrypt(['code'=>400,'msg'=>'账号异常，请联系管理员']);
            return $this->resultInfo($data);
        }elseif($model->use_flag == 'N' && $model->delete_flag == 'N'){
            $data = $this->encrypt(['code'=>400,'msg'=>'账号异常，请联系管理员']);
            return $this->resultInfo($data);
        }
    }
    //注册账号（普通用户）
    public function actionRegister(){
        $request = Yii::$app->request;
        $input = $request->post();
        $phone = $input['phone'];
        $password = $input['password'];
        $password1 = $input['password2'];
        $code = $input['code'];
        //校验参数
        if (empty($phone) || empty($password) || empty($password1) || empty($code)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if ($password != $password1){
            $data = $this->encrypt(['code'=>400,'msg'=>'两次输入密码不一致，请重新输入']);
            return $this->resultInfo($data);
        }
        if (strlen($password)  < 6 || strlen($password1) < 6){
            $data = $this->encrypt(['code'=>400,'msg'=>'密码长度不能小于6']);
            return $this->resultInfo($data);
        }
        $user = User::find()->where(['login'=>$phone])->one();
        if ($user){
            $data = $this->encrypt(['code'=>400,'msg'=>'账号已存在，请勿重复注册']);
            return $this->resultInfo($data);
        }
        $userCheck = TelCheck::find()->where(['tel'=>$phone])->one();
        if ($userCheck->message != $code){
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码错误！']);
            return $this->resultInfo($data);
        }
        //验证验证码
        if(strlen($code) > 4){ // 验证码不能超过四位数字
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码不能超过四位数字']);
            return $this->resultInfo($data);
        }elseif($userCheck->message != $code){ // 验证码是否正确
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码不正确！']);
            return $this->resultInfo($data);
        }elseif($userCheck->expired_time < time()){ // 检查过期时间
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码已过期！']);
            return $this->resultInfo($data);
        }
        //保存数据
        $transaction= AppGroup::getDb()->beginTransaction();
        try {
            $group = new AppGroup();
            $group->tel = $phone;
            $group->group_name = $group->name = $phone;
            $group->main_id = 1;
            $group->level_id = 1;
            $arr = $group->save();

            $model = new User();
            $model->tel = $model->login = $model->name = $phone;
            $model->pwd = md5($password);
            $model->level_id = 1;
            $model->authority_id = 1;
            $model->com_type = 1;
            $model->group_id = $model->parent_group_id = $group->id;;
            $model->admin_id = 1;
            $res = $model->save();
            if ($arr && $res){
                $transaction->commit();
                $data = $this->encrypt(['code'=>200,'msg'=>'注册成功']);
                $this->delete_code($phone);
                return $this->resultInfo($data);
            }else{
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'注册失败！']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'注册失败！']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 注册账户（3pl）
     * */
    public function actionRegister_company(){
        $request = Yii::$app->request;
        $input = $request->post();
        $phone = $input['phone'];
        $password = $input['password'];
        $password1 = $input['password2'];
        $group_name  = $input['group_name'];
        $name = $input['name'];
        $code = $input['code'];
        //校验参数
        if (empty($phone) || empty($password) || empty($password1) || empty($code)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if ($password != $password1){
            $data = $this->encrypt(['code'=>400,'msg'=>'两次输入密码不一致，请重新输入']);
            return $this->resultInfo($data);
        }
        if (strlen($password)  < 6 || strlen($password1) < 6){
            $data = $this->encrypt(['code'=>400,'msg'=>'密码长度不能小于6']);
            return $this->resultInfo($data);
        }
        $user = User::find()->where(['login'=>$phone])->one();
        if ($user){
            $data = $this->encrypt(['code'=>400,'msg'=>'账号已存在，请勿重复注册']);
            return $this->resultInfo($data);
        }

        if ($group_name){
            $GROUP = AppGroup::find()->where(['group_name'=>$group_name,'delete_flag'=>'Y','use_flag'=>'Y'])->asArray()->one();
            if ($GROUP){
                $data = $this->encrypt(['code'=>400,'msg'=>'公司已存在，请勿重复注册']);
                return $this->resultInfo($data);
            }
        }
        $userCheck = TelCheck::find()->where(['tel'=>$phone])->one();
        if ($userCheck->message != $code){
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码错误！']);
            return $this->resultInfo($data);
        }
        //验证验证码
        if(strlen($code) > 4){ // 验证码不能超过四位数字
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码不能超过四位数字']);
            return $this->resultInfo($data);
        }elseif($userCheck->message != $code){ // 验证码是否正确
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码不正确！']);
            return $this->resultInfo($data);
        }elseif($userCheck->expired_time < time()){ // 检查过期时间
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码已过期！']);
            return $this->resultInfo($data);
        }
        //保存数据
        $transaction= AppGroup::getDb()->beginTransaction();
        try {
            $group = new AppGroup();
            $group->tel = $phone;
            $group->name = $name;
            $group->group_name = $group_name;
            $group->main_id = 1;
            $group->level_id = 3;
            $arr = $group->save();

            $model = new User();
            $model->tel = $model->login = $model->name = $phone;
            $model->pwd = md5($password);
            $model->level_id = 3;
            $model->authority_id = 1;
            $model->com_type = 1;
            $model->group_id = $model->parent_group_id = $group->id;;
            $model->admin_id = 1;
            $res = $model->save();
            if ($arr && $res){
                $transaction->commit();
                $data = $this->encrypt(['code'=>200,'msg'=>'注册成功']);
                $this->delete_code($phone);
                return $this->resultInfo($data);
            }else{
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'注册失败！']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'注册失败！']);
            return $this->resultInfo($data);
        }
    }
    /*
    * 发送手机验证码
    * */
    public  function actionSend_code(){
        // 获取手机号
        $request = Yii::$app->request;
        $input = $request->post();
        $phone = $input['phone'];
        // 判断是否传值
        if(empty($phone)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误！']);
            return $this->resultInfo($data);
        }
        // 判断手机号格式
        if(preg_match("/^1(3[0-9]|4[5,7]|5[012356789]|6[6]|7[0-8]|8[0-9]|9[189])\d{8}$/",$phone)){
            // 请求短信接口
            $verify = mt_rand(1000,9999);
            $code = json_encode(['code'=>$verify]);
            $send = new Send();
            $result = $send->send_verify($phone,$code);
            // 判断是否发送成功
            if($result){
                // 判断是否存在手机号
                $model = TelCheck::find()->where(['tel'=>$phone])->one();
                // 没有则插入有则更改验证吗
                if(!empty($model)){
                    $model->message = $verify;
                    $model->expired_time = strtotime('now')+10*60;   //过期时间:10分钟
                }else{
                    $model = new TelCheck();
                    $model->tel = $phone;
                    $model->create_time = time();
                    $model->message = $verify;
                    $model->expired_time = strtotime('now')+10*60 ;  //过期时间:10分钟
                }
                $model->save();
                $data = $this->encrypt(['code'=>200,'msg'=>'发送成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'发送失败']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写正确的手机号']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 发送邮件
     * */
    public function actionSend_email(){
        // 获取邮箱账户
        $request = Yii::$app->request;
        $input = $request->post();
        $email = $input['email'];
        // 判断是否传值
        if(empty($email)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误！']);
            return $this->resultInfo($data);
        }
        if (preg_match("/^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$/",$email)){
            $code = rand(0000,9999);
            $template = AppTemplate::find()->where(['type'=>1])->one();
            $body = str_replace('1234',$code,$template->content);
            $subject = $template->title;
            $arr = ['to'=>$email,'subject'=>$subject,'body'=>$body] ;
            $res =  $this->sendEmail($arr);
            // 判断是否存在邮箱
            $model = TelCheck::find()->where(['tel'=>$email])->one();
            // 没有则插入有则更改验证码
            if(!empty($model)){
                $model->message = $code;
                $model->expired_time = strtotime('now')+15*60;   //过期时间:10分钟
            }else{
                $model = new TelCheck();
                $model->tel = $email;
                $model->create_time = time();
                $model->message = $code;
                $model->expired_time = strtotime('now')+15*60 ;  //过期时间:10分钟
            }
            $model->save();
            if ($res){
                $data = $this->encrypt(['code'=>200,'msg'=>'发送成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'发送失败！']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'请输入正确的邮箱账号！']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 左边菜单栏
     * */
    public function actionLeftAuth(){
        $token = $_POST['token'];
        $type = $_POST['type'];
        // 解密字符串
        $token_decode = $this->decode($token);
        // 查询
        $where['token'] = $token_decode;
        // 查询token
        $auth = [];
        $model = UserToken::find()->where($where)->one();
        if($model) {
            $user = User::find()->where(['id' => $model->user_id])->one();
            if ($type == 1) {
                $auth = $this->left_auth_user($user);
            } else {
                $auth = $this->left_auth($user);
            }
            $data = $this->encrypt(['code'=>200,'msg'=>'ok','data'=>$auth,'type'=>$type]);
            return $this->resultInfo($data); 
        } else {
            $data = $this->encrypt(['code'=>404,'msg'=>'账号在其他浏览器登录，请重新登录！','data'=>[]]);
            return $this->resultInfo($data); 
        }
    }    

    /*
     * 头部菜单栏
     * */
    public function actionTopAuth(){
        header('content-type:application:json;charset=utf8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $auth = $this->top_auth();
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$auth]);
        return $this->resultInfo($data);
    }

    /*
     * 找回密码
     * */
    public function actionForget_password(){
        $request = Yii::$app->request;
        $input = $request->post;
        $phone = $input['username'];
        $password = $input['password'];
        $password1 = $input['password1'];
        $code = $input['code'];
        if (empty($phone) || empty($password) || empty($password1) || empty($password1) || empty($code)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if ($password != $password1){
            $data = $this->encrypt(['code'=>400,'msg'=>'两次密码不一致！']);
            return $this->resultInfo($data);
        }
        $model = User::find()->where(['login'=>$phone])->one();
        if ($model){
            $data = $this->encrypt(['code'=>400,'msg'=>'账号不存在']);
            return $this->resultInfo($data);
        }
        //验证验证吗是否正确
        if(strlen($code) > 4){ // 验证码不能超过四位数字
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码不能超过四位数字']);
            return $this->resultInfo($data);
        }elseif($model->message != $code){ // 验证码是否正确
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码不正确！']);
            return $this->resultInfo($data);
        }elseif($model->expired_time < time()){ // 检查过期时间
            $data = $this->encrypt(['code'=>400,'msg'=>'验证码已过期！']);
            return $this->resultInfo($data);
        }
        $model->pwd = md5($password);
        $res = $model->save();
        if ($res){
            $this->delete_code($phone);
            $data = $this->encrypt(['code'=>200,'msg'=>'修改成功！']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'修改失败！']);
            return $this->resultInfo($data);
        }
    }


}
