<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 01:01
 */

namespace app\modules\api\controllers;
use app\models\AppAuthLeft;
use app\models\AppAuthTop;
use app\models\AppLevel;
use app\models\AppLog;
use app\models\AppPickorder;
use app\models\AppRole;
use app\models\AppOrder;
use app\models\AppSendorder;
use app\models\AppSetParam;
use app\models\TelCheck;
use app\models\User;
use app\models\AppGroup;
use app\models\UserToken;
use app\models\District;
use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use Illuminate\Support\Facades\DB;
use yii\web\Controller,
    yii\base\Module,
    yii\web\Response,
    Yii;
use yii\web\UploadedFile;
use app\models\Upload;

class CommonController extends Controller
{
    public $request;
    public $session;
    public $enableCsrfValidation = false;
    public $base64_salt = 'base_salt';

    public function __construct($id, Module $module, array $config = [])
    {
        $this->layout = false;
        parent::__construct($id, $module, $config);
        $session = Yii::$app->session;
        $this->request = Yii::$app->request;
        $this->session = $session;
    }

    // 数据返回结果
    public function resultInfo($data){
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $data;
    }

    // 数据加密
    public function encrypt($data){
        $data = base64_encode(json_encode($data));
        return $data;
    }    

    // 数据解密
    public function decrypt($data){
        $data = json_decode(base64_decode($data));
        return $data;
    }

    // 左边菜单栏
    public function left_auth($user){
        $tree = $auth = [];
        // 公司账户 只有主账号才能看到
        if ($user->admin_id == 1 && $user->com_type == 1) {
            $parent_group_id = $user->parent_group_id;
            // $group = AppGroup::findOne($parent_group_id);
            $level = AppLevel::findOne(3);
            $auth = $level->auth;
            $role_ids = explode(',',$auth);
            $auth = AppAuthLeft::find()
                ->select(['id','display_name','route','parent_id','icon','num'])
                ->where(['use_flag'=>'Y'])
                ->andWhere(['in','id',$role_ids])
                ->andWhere(['!=','route','/group_account/index'])
                ->orderBy(['sort'=>SORT_ASC])
                ->asArray()
                ->all();
            //主账号
            // $auth = AppAuthLeft::find()
            //     ->select(['id','display_name','route','parent_id','icon'])
            //     ->where(['use_flag'=>'Y'])
            //     ->andWhere(['!=','route','/group_account/index'])
            //     ->orderBy(['sort'=>SORT_ASC])
            //     ->asArray()
            //     ->all();
        } else {
            //子账号
            $role_id = $user->authority_id;
            if ($role_id) {
                $role_auth = AppRole::find()->select(['role_id','role_auth'])->where(['role_id'=>$role_id])->one();
                $role_ids = explode(',',$role_auth->role_auth);
                $auth = AppAuthLeft::find()
                    ->select(['id','display_name','route','parent_id','icon','num'])
                    ->where(['use_flag'=>'Y'])
                    ->andWhere(['in','id',$role_ids])
                    ->andWhere(['!=','route','/group_account/index'])
                    ->orderBy(['sort'=>SORT_ASC])
                    ->asArray()
                    ->all();
            } 
        }
        if ($auth) {
            $tree = list_to_tree($auth);
        }
        return $tree;
    }    

    public function left_auth_user($user){
        $tree = $auth = [];
        $level = AppLevel::find()->where(['level_id'=>1])->one();
        $auth = $level->auth;
        $role_ids = explode(',',$auth);
        $auth = AppAuthLeft::find()
            ->select(['id','display_name','route','parent_id','icon','num'])
            ->where(['use_flag'=>'Y'])
            ->andWhere(['in','id',$role_ids])
            ->andWhere(['!=','route','/group_account/index'])
            ->orderBy(['sort'=>SORT_ASC])
            ->asArray()
            ->all();
        if ($auth) {
            $tree = list_to_tree($auth);
        }
        return $tree;
    }

    // 头部菜单栏
    public function top_auth(){
        $tree = $auth = [];
        // if ($user->admin_id == 1 && $user->com_type == 1) {
        //     //主账号
        //     $auth = AppAuthTop::find()
        //         ->select(['id','display_name','route','url','parent_id','icon'])
        //         ->where(['use_flag'=>'Y'])
        //         ->orderBy(['sort'=>SORT_ASC])
        //         ->asArray()
        //         ->all();
        // } else {
        //     //子账号
        //     $role_id = $user->authority_id;
        //     if ($role_id) {
        //         $role_auth = AppRole::find()->select(['role_id','role_auth','top_auth'])->where(['role_id'=>$role_id])->one();
        //         $role_ids = explode(',',$role_auth->top_auth);
        //         $auth = AppAuthTop::find()
        //             ->select(['id','display_name','route','url','parent_id','icon'])
        //             ->where(['use_flag'=>'Y'])
        //             ->andWhere(['in','id',$role_ids])
        //             ->orderBy(['sort'=>SORT_ASC])
        //             ->asArray()
        //             ->all();
        //     } 
        // }

        $auth = AppAuthTop::find()
            ->select(['id','display_name','route','url','parent_id','icon'])
            ->where(['use_flag'=>'Y'])
            ->orderBy(['sort'=>SORT_ASC])
            ->asArray()
            ->all();
        if ($auth) {
            $tree = list_to_tree($auth);
        }
        return $tree;
    }

    public function check_address($pro,$city,$area){ 
        $flag_pro = District::find()->where(['name'=>$pro,'level'=>1])->one();
        if (!$flag_pro) {
            return ['position'=>'pro','msg'=>'省份名称不存在，请参考快捷操作-地址演示'];
        }
        $flag_city = District::find()->where(['name'=>$city,'level'=>2,'parent_id'=>$flag_pro->id])->one();
        if (!$flag_city) {
            return ['position'=>'city','msg'=>$pro.'下不存在'.$city.'，请参考快捷操作-地址演示'];
        }        
        if ($area) {
            $flag_area = District::find()->where(['name'=>$area,'level'=>3,'parent_id'=>$flag_city->id])->one();
            if (!$flag_area) {
                return ['position'=>'area','msg'=>$city.'下不存在'.$area.'，请参考快捷操作-地址演示'];
            }
        }
        return ['position'=>'ok','msg'=>''];
    }

    /*
    *判断 上传文件
    */
    public function check_upload_file($name){
        $excel_type = array('xlsx');
        $file_types = explode (".",$name);
        $excel_type = array('xlsx','xls');
        if (!in_array(strtolower(end($file_types)),$excel_type)){
            $data = $this->encrypt(['code'=>400,'msg'=>'不是指定Excel文件格式，请重新上传']);
            echo json_encode($data);exit;
        }
    }

    /*
      * 生成token令牌
      * */
    public  function product_token($user_id){
        $token_key = time().mt_rand('000000','999999')."chitucode";
        //判断数据是否已存在
        $condition['user_id'] = $user_id;
        //不存在新增
        $model = UserToken::find()->where($condition)->one();
        if (!$model) {
            $model = new UserToken();
        }
        $model->token = $token_key;
        $model->last_time = time();
        $model->user_id = $user_id;
        $res =  $model->save();
        $token = $this->encode($token_key);
        return $token;
    }
    /*
     * 校验token
     * */
    public function check_token($token,$check_auth = false,$chitu = 2){
        
        // 解密字符串
        $token_decode = $this->decode($token);
        // 查询
        $where['token'] = $token_decode;
        // 查询token
        $model = UserToken::find()->where($where)->one();
        // 验证token是否存在
        if(empty($model)){ // 不存在
            $data = $this->encrypt(['code'=>404,'msg'=>'非法请求']);
            echo json_encode($data);
            exit;
        }else{ // 存在
            // 验证是否超时：目前设置token有效时间为1年
            $oldtime = date('Y-m-d H:i:s',$model->last_time);
            $check_time = strtotime(date("Y-m-d H:i:s",strtotime("$oldtime   +1  year")));
            // 验证是否超时
            if($check_time  <  time()){
                $data = $this->encrypt(['code'=>404,'msg'=>'token已过期，请重新登录']);
                echo json_encode($data);
                exit;
            }else{
                $data['status'] = '3'; // 通过
                // 获取用户id
                $data['user_id'] = $model->user_id;
                // 验证用户是否存在
                $user = User::find()->where(['id' => $model->user_id, 'delete_flag'=>'Y'])->one();
                // 判断用户是否存在
                if(empty($user)){ // 如果没有定义非法请求
                    $data = $this->encrypt(['code'=>404,'msg'=>'非法请求']);
                    echo json_encode($data);
                    exit;
                } else {
                    if ($user->use_flag == 'N') {
                        $data = $this->encrypt(['code'=>400,'msg'=>'该账号已被禁用']);
                        echo json_encode($data);
                        exit;
                    }
                }
                $data['user'] = $user;
            }
        }

        if ($check_auth) {
            $controller = Yii::$app->controller->id;
            $action = Yii::$app->controller->action->id;
            $authstr = strtolower('/'.$controller.'/'.$action);
            $auth_id = AppAuthLeft::find()->select(['id'])->where(['route'=>$authstr,'use_flag'=>'Y'])->one();

            if ($auth_id){
                if ($chitu == 1) {
                    $info = AppLevel::find()->select(['auth'])->where(['level_id'=>1])->one()->auth;
                    // $auth_list = AppAuthLeft::find()->select(['id','display_name','route'])->where(['parent_id'=>$auth_id->id,'use_flag'=>'Y'])->asArray()->all();
                } else {
                    if ($user->admin_id == 1 && $user->com_type == 1){
                        if ($user->authority_id == ''){
                            $user->authority_id = 1;
                        }
                        // $group = AppGroup::find()->where(['id'=>$user->parent_group_id])->one();
                        // $level_id = $group->level_id;
                        $info = AppLevel::find()->select(['auth'])->where(['level_id'=>3])->one()->auth;
                    }else{
                        $info = AppRole::find()->select(['role_auth'])->where(['role_id'=>$user->authority_id,'use_flag'=>'Y'])->one()->role_auth;
                    }
                }
                // $data['info'] = $info;
                // $data = $this->encrypt(['code'=>401,'msg'=>'无权限操作！','data'=>$info,'chitu'=>$chitu,'id'=>$auth_id->id]);
                //     echo json_encode($data);
                //     exit;
                $auth_arr = explode(',',$info);
                if (!in_array($auth_id->id,$auth_arr)){
                    $data = $this->encrypt(['code'=>401,'msg'=>'无权限操作！']);
                    echo json_encode($data);
                    exit;
                }
            }else{
                $data = $this->encrypt(['code'=>402,'msg'=>'系统正在升级！']);
                echo json_encode($data);
                exit;
            }
        }
        return $data;
    }

    // 檢查列表權限
    public function check_token_list($token,$chitu = 2){
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        // 解密字符串
        $token_decode = $this->decode($token);
        // 查询
        $where['token'] = $token_decode;
        // 查询token
        $model = UserToken::find()->where($where)->one();
        // 验证token是否存在
        if(empty($model)){ // 不存在
            $data['status'] = 404;
            $data['msg'] = '非法请求';
            echo json_encode($data);
            exit;
        }else{ // 存在
            // 验证是否超时：目前设置token有效时间为1年
            $oldtime = date('Y-m-d H:i:s',$model->last_time);
            $check_time = strtotime(date("Y-m-d H:i:s",strtotime("$oldtime   +1  year")));
            // 验证是否超时
            if($check_time  <  time()){
                $data['status'] = 404;
                $data['msg'] = 'token已过期，请重新登录';
                echo json_encode($data);
                exit;
            }else{
                $data['status'] = '3'; // 通过
                // 获取用户id
                $data['user_id'] = $model->user_id;
                // 验证用户是否存在
                $user = User::find()->where(['id' => $model->user_id, 'delete_flag'=>'Y'])->one();
                // 判断用户是否存在
                if(empty($user)){ // 如果没有定义非法请求
                    $data['status'] = 404;
                    $data['msg'] = '非法请求';
                    echo json_encode($data);
                    exit;
                } else {
                    if ($user->use_flag == 'N') {
                        $data['status'] = 400;
                        $data['msg'] = '该账号已被禁用';
                        echo json_encode($data);
                        exit;
                    }
                }
                $data['user'] = $user;
            }
        }

        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;
        $authstr = strtolower('/'.$controller.'/'.$action);
        $auth_id = AppAuthLeft::find()->select(['id'])->where(['route'=>$authstr,'use_flag'=>'Y'])->one();
        $data['authstr'] = $authstr;
        $auth_btn = [];
        $data['auth'] = [];
        if ($auth_id){
            if ($chitu == 1) {
                $info = AppLevel::find()->select(['auth'])->where(['level_id'=>1])->one()->auth;
                $auth_list = AppAuthLeft::find()->select(['id','display_name','route'])->where(['parent_id'=>$auth_id->id,'use_flag'=>'Y'])->asArray()->all();
            } else {
                if ($user->admin_id == 1 && $user->com_type == 1){
                    if ($user->authority_id == ''){
                        $user->authority_id = 1;
                    }
                    $auth_btn = 'all';
                    // $group = AppGroup::find()->where(['id'=>$user->parent_group_id])->one();
                    // $level_id = $group->level_id;
                    $info = AppLevel::find()->select(['auth'])->where(['level_id'=>3])->one()->auth;
                    $data['auth'] = $auth_btn;
                    $auth_list = AppAuthLeft::find()->select(['id','display_name','route'])->where(['parent_id'=>$auth_id->id,'use_flag'=>'Y'])->asArray()->all();
                }else{
                    $info = AppRole::find()->select(['role_auth'])->where(['role_id'=>$user->authority_id,'use_flag'=>'Y'])->one()->role_auth;
                    // 查找页面内所有权限
                    $auth_list = AppAuthLeft::find()->select(['id','display_name','route'])->where(['parent_id'=>$auth_id->id,'use_flag'=>'Y'])->asArray()->all();
                }
            }
            $auth_arr = explode(',',$info);
            if (!in_array($auth_id->id,$auth_arr)){
                $data['status'] = 401;
                $data['msg'] = '无权限操作！';
                echo json_encode($data);
                exit;
            }

            if ($auth_btn != 'all' && $auth_list) {
                foreach ($auth_list as $v) {
                    if (in_array($v['id'],$auth_arr)) {
                        $auth_btn[] = $v['route'];
                    }
                }
                $data['auth'] = (array)$auth_btn;
            } else {
                $auth_btn = [];
                foreach ($auth_list as $v) {
                    $auth_btn[] = $v['route'];
                }
                $data['auth'] = (array)$auth_btn;
            }
        }else{
            $data['status'] = 402;
            $data['msg'] = '系统正在升级！';
            echo json_encode($data);
            exit;
        }
        return $data;
    }

    // 檢查头部列表權限
    public function check_token_list_top($token){
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        // 解密字符串
        $token_decode = $this->decode($token);
        // 查询
        $where['token'] = $token_decode;
        // 查询token
        $model = UserToken::find()->where($where)->one();
        // 验证token是否存在
        if(empty($model)){ // 不存在
            $data['status'] = 404;
            $data['msg'] = '非法请求';
            echo json_encode($data);
            exit;
        }else{ // 存在
            // 验证是否超时：目前设置token有效时间为1年
            $oldtime = date('Y-m-d H:i:s',$model->last_time);
            $check_time = strtotime(date("Y-m-d H:i:s",strtotime("$oldtime   +1  year")));
            // 验证是否超时
            if($check_time  <  time()){
                $data['status'] = 404;
                $data['msg'] = 'token已过期，请重新登录';
                echo json_encode($data);
                exit;
            }else{
                $data['status'] = '3'; // 通过
                // 获取用户id
                $data['user_id'] = $model->user_id;
                // 验证用户是否存在
                $user = User::find()->where(['id' => $model->user_id, 'delete_flag'=>'Y'])->one();
                // 判断用户是否存在
                if(empty($user)){ // 如果没有定义非法请求
                    $data['status'] = 404;
                    $data['msg'] = '非法请求';
                    echo json_encode($data);
                    exit;
                } else {
                    if ($user->use_flag == 'N') {
                        $data['status'] = 400;
                        $data['msg'] = '该账号已被禁用';
                        echo json_encode($data);
                        exit;
                    }
                }
                $data['user'] = $user;
            }
        }

        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;
        $authstr = strtolower('/'.$controller.'/'.$action);
        $auth_id = AppAuthTop::find()->select(['id'])->where(['route'=>$authstr,'use_flag'=>'Y'])->one();
        
        $auth_btn = [];
        $data['auth'] = [];
        if ($auth_id){
            if ($user->admin_id == 1 && $user->com_type == 1){
                if ($user->authority_id == ''){
                    $user->authority_id = 1;
                }

                $auth_btn = 'all';
                $group = AppGroup::find()->where(['id'=>$user->parent_group_id])->one();
                $level_id = $group->level_id;
                $info = AppLevel::find()->select(['top_auth'])->where(['level_id'=>$level_id])->one()->top_auth;

                $data['auth'] = $auth_btn;
                $auth_list = AppAuthTop::find()->select(['id','display_name','route'])->where(['parent_id'=>$auth_id->id,'use_flag'=>'Y'])->asArray()->all();
            }else{
                $info = AppRole::find()->select(['top_auth'])->where(['role_id'=>$user->authority_id,'use_flag'=>'Y'])->one()->top_auth;
                // 查找页面内所有权限
                $auth_list = AppAuthTop::find()->select(['id','display_name','route'])->where(['parent_id'=>$auth_id->id,'use_flag'=>'Y'])->asArray()->all();
            }
            $auth_arr = explode(',',$info);

            if (!in_array($auth_id->id,$auth_arr)){
                $data['status'] = 401;
                $data['msg'] = '无权限操作！';
                echo json_encode($data);
                exit;
            }

            if ($auth_btn != 'all' && $auth_list) {
                foreach ($auth_list as $v) {
                    if (in_array($v['id'],$auth_arr)) {
                        $auth_btn[] = $v['route'];
                    }
                }
                $data['auth'] = (array)$auth_btn;
            } else {
                $auth_btn = [];
                foreach ($auth_list as $v) {
                    $auth_btn[] = $v['route'];
                }
                $data['auth'] = (array)$auth_btn;
            }
        }else{
            $data['status'] = 402;
            $data['msg'] = '系统正在升级！';
            echo json_encode($data);
            exit;
        }
        return $data;
    }

    // 检查是否有数据权限
    public function check_group_auth($group_id,$user){
        $groups = AppGroup::group_list_arr($user);
        if (!in_array($group_id,$groups)) {
            $data = $this->encrypt(['code'=>401,'msg'=>'无权限操作该公司数据！']);
            echo json_encode($data);
            exit;
        }
    }
    /*
     * 字符串加密
     * */
    public  function encode($string = '') {
        $skey="ctapp200228";
        $strArr = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key < $strCount && $strArr[$key].=$value;
        return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
    }
    /*
     * 字符串解密
     * */
    public  function decode($string = '') {
        $skey="ctapp200228";
        $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key <= $strCount  && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        return base64_decode(join('', $strArr));
    }

    /*
     * 删除验证码
     * */
    public function delete_code($phone){
       $model =  TelCheck::find()->where(['tel'=>$phone])->one()->delete();
    }

    /**
     * Desc: 发送邮件公共方法
     * Created by: pysh
     * Date: 2020
     * Time: 17:08
     * @param $data
     * @param string $key_word:
     * @return array
     * @throws \Exception
     */
    public static function sendEmail($data,$key_word=''){
        if(empty($data['to'])){
            return ['code'=>400,"msg"=>"接收方邮箱不能为空!"];
        }
        if($key_word){
            // 替換模板中的內容關鍵字
            $template = 'hahaha';
            if(!$template){
                return ['code'=>400,"msg"=>"邮件模板不存在!"];
            }
            $data['content']['index_url'] = \Yii::$app->params['WEB_URL'];
            // 發送html格式的郵件還是普通文本格式的郵件
//            $body = createContent('woyebuzhidao',$data['content']);
            $body = 'tianxieshenme';
            $subject = '测试发送邮件';

            $from = Yii::$app->params['EMAIL']['senderEmail'];

            try{
                $mailer = Yii::$app->mailer->compose();
                $mailer->setFrom($from);
                $mailer->setTo($data['to']);
                $mailer->setSubject($subject);
                $mailer->setHtmlBody($body);
                $status = $mailer->send();
            }catch(\Exception $e){
                $status = 0;
                $data['error'] = $e->getMessage();
            }

        }else{
            // 按照传过来的信息发送邮件
            $subject = $data['subject'];
            $body = $data['body'];
            $et_id = 0;

            $from = Yii::$app->params['EMAIL']['senderEmail_Info'];

            try{
                $mailer = Yii::$app->mailerInfo->compose();
                $mailer->setFrom($from);
                $mailer->setTo($data['to']);
                $mailer->setSubject($subject);
                $mailer->setHtmlBody($body);
                $status = $mailer->send();
            }catch(\Exception $e){
                $status = 0;
                $data['error'] = $e->getMessage();
            }
        }
        return $status;
    }

    /*
     * 生成日志记录
     * */
    public function hanldlog($uid='',$content){
        $model = new AppLog();
        if ($uid !='') {
            $model->admin_id = $uid;
        }
        $model->content = $content;
        $model->update_time = date('Y-m-d H:i:s',time());
        $model->save();
    }

    /*
     * 根据城市ID查询城市名称
     * */
    public function getCity(){

    }

    /*
     * 查看权限
     * */
    public function checkAuth($user_id){
        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;
        $authstr = strtolower('/'.$controller.'/'.$action);
        $auth_id = AppAuthLeft::find()->select(['id'])->where(['route'=>$authstr,'use_flag'=>'Y'])->one();

        $user = User::find()->select(['admin_id','authority_id','parent_group_id','group_id','name'])->where(['id'=>$user_id])->one();
        if ($auth_id){ 
            if ($user->admin_id == 1 && $user->com_type == 1){
                if ($user->authority_id == ''){
                    $user->authority_id = 1;
                }
                $group = AppGroup::find()->where(['id'=>$user->parent_group_id])->one();
                $level_id = $group->level_id;
                $info = AppLevel::find()->select(['auth'])->where(['level_id'=>$level_id])->one()->auth;
            }else{
                $info = AppRole::find()->select(['role_auth'])->where(['role_id'=>$user->authority_id,'use_flag'=>'Y'])->one()->role_auth;
            }

            $auth_arr = explode(',',$info);
            if (!in_array($auth_id->id,$auth_arr)){
                $data = $this->encrypt(['code'=>401,'msg'=>'无权限操作！']);
                echo json_encode($data);
                exit;
            }
        }else{
            $data = $this->encrypt(['code'=>402,'msg'=>'系统正在升级！']);
            echo json_encode($data);
            exit;
        }
        return $user;
    }

    /*
     * 上传文件
     * */
    public function Upload($models,$file)
     {
         if(isset($file)) {
             $uploadDir = date("Ymd");  // DIRECTORY_SEPARATOR常量
             $dir = Yii::$app->basePath.'/web/uploads/'.$models.'/'.$uploadDir; //路径名,可以自己修改
             file_exists($dir) || (mkdir($dir,0777,true) && chmod($dir,0777));
             $fileName = rand(1000,9999).time().$file["name"];
             move_uploaded_file($file["tmp_name"],$dir.'/'.$fileName);
             $path = '/uploads/'.$models.'/'.$uploadDir.'/'.$fileName;
             return $path;
         }
     }    
     /*
     * 上传文件
     * */
    public function more_upload($models,$file)
     {
         if(isset($file)) {
            $arr = [];
            foreach($file['name'] as $k=>$v) {
                 $uploadDir = date("Ymd");  // DIRECTORY_SEPARATOR常量
                 $dir = Yii::$app->basePath.'/web/uploads/'.$models.'/'.$uploadDir; //路径名,可以自己修改
                 file_exists($dir) || (mkdir($dir,0777,true) && chmod($dir,0777));
                 $fileName = rand(1000,9999).time().$file["name"][$k];
                 move_uploaded_file($file["tmp_name"][$k],$dir.'/'.$fileName);
                 $path = '/uploads/'.$models.'/'.$uploadDir.'/'.$fileName;
                 $arr[] = $path;
            }
             return json_encode($arr);
         }
     }

     /*
      * 导入excel
      * */
    public function reander($file,$length = 5){
        require_once(\Yii::getAlias('@vendor').'/phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php');
        require_once(\Yii::getAlias('@vendor').'/phpoffice/phpexcel/Classes/PHPExcel.php');
        $fileType = \PHPExcel_IOFactory::identify($file);//自动获取文件的类型提供给phpexcel用
        $PHPReader = \PHPExcel_IOFactory::createReader($fileType);//获取文件读取操作对象
        $PHPExcel = $PHPReader->load($file);//引入文件
        $excelSheets = $PHPExcel->getSheet();
        $highestRow = $excelSheets->getHighestRow(); //最大行数
        $highestColumn = $excelSheets->getHighestColumn();//最大列数
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);//字母变数字
        $data = [];
        for($i = $length; $i<=$highestRow;$i++){
            for($j = 'B';$j<= $highestColumn;$j++){
                $address = $j.$i;
                $data[$i][$j] = $excelSheets->getCell($address)->getValue();
            }
        }
        return $data;
    }     

    /*
      * 导入excel
      * */
    public function reander_more($file,$length = 5){
        $objRead = new \PHPExcel_Reader_Excel2007();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $obj = $objRead->load($file);  //建立excel对象
        $currSheet = $obj->getSheet();   //获取指定的sheet表  
        $columnH = $currSheet->getHighestColumn();   //取得最大的列号  
        $columnCnt = array_search($columnH, $cellName);  
        $rowCnt = $currSheet->getHighestRow();   //获取总行数
        $data = array();
        // 行
        for($_row=$length; $_row<$rowCnt-$length; $_row++){  //读取内容
            // 列
            for($_column=0; $_column<=$columnCnt; $_column++){  
                $cellId = $cellName[$_column].$_row;  
                $cellValue = $currSheet->getCell($cellId)->getValue();  //获取内容
                $data[$_row][$cellName[$_column]] = $cellValue; 
            }  

        }
        return $data;

    }

    // 保存历史记录 地址和人员
    public function leading_in_address_user($arr,$group_id,$user_id){
        $data_a = [];
        $data_c = [];
        $time = date('Y-m-d H:i:s',time());
        foreach($arr as $v) {
            $all = $v['pro'] . $v['city'] . $v['area'] . $v['info'];
            $model_a = AppCommonAddress::find()->where(['group_id' => $group_id,'all' => $all])->one();
            if (!$model_a) {
                $data_a[] = [
                    'group_id' => $group_id,
                    'pro_id' => $v['pro'],
                    'city_id' => $v['city'],
                    'area_id' => $v['area'],
                    'address' => $v['info'],
                    'create_user_id' => $user_id,
                    'create_time' => $time,
                    'update_time' => $time,
                    'all' => $all
                ]; 
            } else {
                @$model_a->updateCounters(['count_views'=>1]);
            }
            $model_c = AppCommonContacts::find()->where(['user_id' => $user_id,'name' => $v['contant'],'tel'=>$v['tel']])->one();
            if (!$model_c) {
                $data_c[] = [
                    'user_id' => $user_id,
                    'name' => $v['contant'],
                    'tel' => $v['tel'],
                    'create_userid' => $user_id,
                    'create_time' => $time,
                    'update_time' => $time
                ]; 
            } else {
                @$model_c->updateCounters(['views'=>1]);
            }
        }

        if ($data_a) {
            @Yii::$app->db->createCommand()->batchInsert(AppCommonAddress::tableName(), [
                'group_id',
                'pro_id',
                'city_id',
                'area_id',
                'address',
                'create_user_id',
                'create_time',
                'update_time',
                'all'
            ], $data_a)->execute();
        }        

        if ($data_c) {
            @Yii::$app->db->createCommand()->batchInsert(AppCommonContacts::tableName(), [
                'user_id',
                'name',
                'tel',
                'create_userid',
                'create_time',
                'update_time'
            ], $data_c)->execute();
        }
    }


    private static function Get_ext($file) {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /*
     * 导出Excel
     * *@param $title: 头部信息
     * @param $data: 内容
     * @param $filename: 文件名
     * @param array $w: 列宽
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * */
    public function excelOut($title,$data,$filename,$w=array(),$type = 0){
        $word = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO");
        $count  = array( '应付','实际应付','应收','实际应收');
        $objExcel = new \PHPExcel();
        //設置title
        foreach ($title as $k => $v) {
            $objExcel->getActiveSheet()->setCellValue($word[$k].'1',$v);
        }
        //設置内容
        foreach ($data as $k => $v) {
            $i = $k+2;
            foreach ($v as $key => $value) {
                $objExcel->getActiveSheet()->setCellValue($word[$key].$i, $value);
            }
        }
        //設置寬
        if ($w) {
            foreach ($w as $k => $v) {
                $objExcel->getActiveSheet()->getColumnDimension($word[$k])->setWidth($v);
            }
        }
//        for($i=0;$i<count($data)+1;$i++){
//            for($j=0;$j<count($title);$j++){
//                if(in_array($title[$j],$count)) {
//                    $objExcel->getActiveSheet()->setCellValue($word[$j].(count($data)+2), '=SUM('.$word[$j].'2:'.$word[$j].(count($data)+1).')');
//                }
//            }
//        }
        $fileName = iconv("utf-8", "utf-8", './Data/excel/'.$filename.'.xlsx');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        if ($type == 0) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
        } else {
            $objWriter->save($fileName);
            return $fileName;
        }
//        exit;
    }
    /*
     * 统计导出
     * */
    public function out_stream($title,$data,$filename,$w=array(),$type = 0){
        $word = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO");
        $count  = array( '应付','实际应付','应收','实际应收');
        $objExcel = new \PHPExcel();
        //設置title
        foreach ($title as $k => $v) {
            $objExcel->getActiveSheet()->setCellValue($word[$k].'1',$v);
        }
        //設置内容
        foreach ($data as $k => $v) {
            $i = $k+2;
            foreach ($v as $key => $value) {
                $objExcel->getActiveSheet()->setCellValue($word[$key].$i, $value);
            }
        }
        //設置寬
        if ($w) {
            foreach ($w as $k => $v) {
                $objExcel->getActiveSheet()->getColumnDimension($word[$k])->setWidth($v);
            }
        }

        for($i=0;$i<count($data)+1;$i++){
            for($j=0;$j<count($title);$j++){
                if(in_array($title[$j],$count)) {
                    $objExcel->getActiveSheet()->setCellValue($word[$j].(count($data)+2), '=SUM('.$word[$j].'2:'.$word[$j].(count($data)+1).')');
                }
            }
        }
        $fileName = iconv("utf-8", "utf-8", './Data/excel/'.$filename.'.xlsx');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        if ($type == 0) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
        } else {
            $objWriter->save($fileName);
            return $fileName;
        }
    }
    /*
     * 退款
     * */
    public function refund($ordernumber,$price,$body){
        require_once(Yii::getAlias('@vendor').'/alipay/pagepay/service/AlipayTradeService.php');
        require_once(Yii::getAlias('@vendor').'/alipay/pagepay/buildermodel/AlipayTradeRefundContentBuilder.php');
        //商户订单号，商户网站订单系统中唯一订单号
        $out_trade_no = $ordernumber;
        //支付宝交易号
        $trade_no = '';
        //请二选一设置
        //需要退款的金额，该金额不能大于订单金额，必填
        $refund_amount = $price;
        //退款的原因说明
        $refund_reason = $body;
        //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
        $out_request_no = '';

        //构造参数
        $RequestBuilder=new \AlipayTradeRefundContentBuilder();
        $RequestBuilder->setOutTradeNo($out_trade_no);
        $RequestBuilder->setTradeNo($trade_no);
        $RequestBuilder->setRefundAmount($refund_amount);
        $RequestBuilder->setOutRequestNo($out_request_no);
        $RequestBuilder->setRefundReason($refund_reason);
        $config = Yii::$app->params['configpay'];
        $aop = new \AlipayTradeService($config);

        /**
         * alipay.trade.refund (统一收单交易退款接口)
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @return $response 支付宝返回的信息
         */
        $response = $aop->Refund($RequestBuilder);
        $res = json_encode($response);
//        $res = json_decode($res,true);
//        $arr = $res['alipay_trade_refund_response'];
        return $res;
    }

    /*
     * 多图上传
     * */
    public function base64($base){
        $arr = [];
        foreach ($base as $k => $v) {
            $image = $v;
            $start = strpos($v, '/');
            $end = strpos($v, ';');
            $hz = substr($v, $start + 1, $end - $start - 1);
            $imageName = md5(microtime(true) + mt_rand(0, 999999)) . date("His", time()) . '.' . $hz;
            if (strstr($image, ",")) {
                $image = explode(',', $image);
                $image = $image[1];
            }
            $filename = '/uploads/receipt/'.date("Ymd");
            $path =Yii::$app->basePath.'/web'.$filename;
            if (!is_dir($path)) { //判断目录是否存在 不存在就创建
                mkdir($path, 0777, true);
            }
            $imageSrc = $path . "/" . $imageName;
            $newname = $filename.'/'.$imageName;
            $r = file_put_contents($imageSrc, base64_decode($image));
            if ($r) {
                $arr[] = $newname;
            }
            }
            $arr = json_encode($arr);
            return $arr;

    }    

    /*
     *周转换为日期
     * */
    public function getTimeFromWeek($dayNum){
        $curDayNum=date("w");
        if($dayNum>$curDayNum) $timeFlag="next ";
//        elseif($dayNum==$curDayNum) $timeFlag="";
        else $timeFlag="next ";
        $arryWeekDay = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
        $timeStamp=strtotime("$timeFlag"."$arryWeekDay[$dayNum]");
        return $timeStamp;
    }

    /*
     * 计算公里数
     * */
    function mileage_interval($type,$km){
        // 查询里程系数标准
        $result = AppSetParam::find()->select('scale_km,scale_km_two,scale_km_three,scale_km_four')->where(['type'=>$type])->asArray()->one();
        // 默认计算后的里程数
        $finally = $km;
        // 获取0-100里程系数
        $scale_km = $result['scale_km'] == '' ? 1 : $result['scale_km'];
        // 获取100-300里程系数
        $scale_km_two = $result['scale_km_two'] == '' ? 1 : $result['scale_km_two'];
        // 获取300-1000里程系数
        $scale_km_three = $result['scale_km_three'] == '' ? 1 : $result['scale_km_three'];
        // 获取1000以上里程系数
        $scale_km_four = $result['scale_km_four'] == '' ? 1 : $result['scale_km_four'];
        // 判断0-100里程数所在范围并返回相应的里程数
        if($km >=0 && $km<= 100){
            $finally = $km*$scale_km;
            return $finally;
        }
        // 判断100-300里程数所在范围并返回相应的里程数
        if($km > 100 && $km<= 300){
            $finally = $km*$scale_km_two;
            return $finally;
        }
        // 判断300-1000里程数所在范围并返回相应的里程数
        if($km > 300 && $km<= 1000){
            $finally = $km*$scale_km_three;
            return $finally;
        }
        // 判断1000以上里程数所在范围并返回相应的里程数
        if($km > 1000){
            $finally = $km*$scale_km_four;
            return $finally;
        }
    }


    /*
     *
     * */
    public function get_params($id,$name){
        $arr = AppOrder::find()->where(['in','id',$id])->sum($name);
        return $arr;
    }

}