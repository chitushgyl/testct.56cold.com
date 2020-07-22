<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 17:00
 */

namespace app\controllers\admin;
use app\models\AdminRole,
    Yii,
    app\models\AdminUser,
    app\models\Account;

class AccountController extends AdminBaseController
{
    /** 账户管理
     * Desc: 管理员自己修改自己的账户信息
     * Created by: pysh
     * Date: 2020/2/2
     * Time: 13:19
     * @return string
     */
    public function actionChange(){
        // 获取该用户的信息
    	$userInfo = Account::findOne(['id'=>$this->admin_id]);
        if($this->request->isAjax){
            if(empty($this->request->bodyParams['password']) || empty($this->request->bodyParams['newPassWord'])){
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'密码不能为空!!']);
            }elseif($userInfo->password != md5($this->request->bodyParams['password'])){
                return $this->resultInfo(['retCode'=>1002,'retMsg'=>'原密码错误!!']);
            }

            $userInfo->password = md5($this->request->bodyParams['newPassWord']);
            if(!$userInfo->save()){
                return $this->resultInfo(['retCode'=>1003,'retMsg'=>'系统错误!']);
            }
            AddLogController::addSysLog(AddLogController::account,'个人档案设定修改密码');
            return $this->resultInfo(['retCode'=>1000,'retMsg'=>'个人档案设定成功,正在退出登录,請重新登录!!']);
        }else{
        	return $this->render('change',['userInfo'=>$userInfo]);
        }
    }

    /**
     * Desc: 账户列表
     * Created by pysh
     * Date: 2020/2/2
     * Time: 17:42
     */
    public function actionIndex(){
        if($this->request->isAjax){
            $keyword = $this->request->get('keyword');
            $list = Account::find()
                ->alias('a')
                ->leftJoin('ct_admin_role r','r.id = a.position');
            if($keyword){
                $list->andWhere(['like','a.username',$keyword])
                    ->orWhere(['like','a.phone',$keyword]);
            }
            
            $count = $list->count();
            $list = $list->select(['a.id','a.username','a.realname','a.sex','a.phone','a.email','a.addtime','a.address','a.position','a.weixin','r.role','a.company'])
                ->orderBy(['a.id'=>SORT_DESC])
                ->offset(($this->request->get('page',1) - 1) * $this->request->get('limit',10))
                ->limit($this->request->get('limit',10))
                ->asArray()->all();
            $data = [
                'code' => 0,
                'msg'   => '正在請求中...',
                'count' => $count,
                'data'  => precaution_xss($list)
            ];
            return json_encode($data);
        }else{

            return $this->render('index');
        }
    }     

    /**
     * Desc: 账户详情
     * Created by pysh
     * Date: 2020/2/2
     * Time: 17:42
     */
    public function actionView(){
        $id = $_GET['id'];
        $model = Account::findOne(['id'=>$id]);
        if (!$model) {
            $model = new Account();
        }
        $role = '';
        if ($model->position) {
            $role = AdminRole::findOne(['id'=>$model->position]);
            $role = $role->role;
        }
        return $this->render('view',['model'=>$model,'role'=>$role]);
        
    }

    /**
     * Desc: 修改用戶角色
     * Created by pysh
     * Date: 2020/2/2
     * Time: 18:33
     * @return string
     */
    public function actionRole(){
        $admin_id = $this->request->get('admin_id');
        $info = AdminUser::findOne(['admin_id'=>$admin_id]);
        if($this->request->isPost){
            $info->role_id = $this->request->bodyParams['role_id']?$this->request->bodyParams['role_id']:0;
            $info->updated_at = date("Y-m-d H:i:s");
            if(!$info->save()){
                return $this->withErrors('修改失败!')->redirect(route('admin.admin-user.index','admin_id='.$admin_id));
            }
            AddLogController::addSysLog(AddLogController::admin_user,'修改管理员角色,管理员 id:'.$admin_id);
            return $this->withSuccess('修改成功!')->redirect(route('admin.admin-user.index'));
        }else{
            $list = AdminRole::find()->select(['admin_role.role_id','admin_role.role_name'])->asArray()->all();
            return $this->render('role',['info'=>$info,'list'=>$list]);
        }
    }

    /**
     * Desc: 編輯用户
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:47
     */
    public function actionEdit(){
        $id = $this->request->get('id');
        $info = Account::findOne(['id'=>$id]);
        if($this->request->isPost){
            $flag_error = true;
            if (!$this->now_auth) {
                $flag_error = false;
                $this->withErrors('权限不足!');
            } 
            $data = $this->request->bodyParams;
            $username = $data['username'];
            $realname = $data['realname'];
            $position = $data['position'];
            $password = $data['password'];
            $weixin = $data['weixin'];
            $email = $data['email'];
            $phone = $data['phone'];
            $sex = $data['sex'];
            
            if (!$info) {
                $flag_error = false;
                $this->withErrors('失败，该记录已被删除!');
            }

            if($flag_error && !$username){
                $flag_error = false;
                $this->withErrors('账户名称不能空!');
            }else{
                if ($flag_error && $username == $_SESSION['company']) {
                    $flag_error = false;
                    $this->withErrors('该账户名称禁止使用!');
                } 

                $account = Account::find()->where(['username'=>$username])->andWhere(['!=','id',$id])->one();
                if($flag_error && $account){
                    $flag_error = false;
                    $this->withErrors('该账户名称已经存在!');
                }
            }
            if($flag_error && !$realname){
                $flag_error = false;
                $this->withErrors('真实姓名不能空!');
            }            
            if($flag_error && !$position){
                $flag_error = false;
                $this->withErrors('请添加职位!');
            }

            $info->username = $username;
            $info->realname = $realname;
            $info->position = $position;
            $info->weixin = $weixin;
            $info->phone = $phone;
            $info->email = $email;
            $info->sex = $sex;

            if ($flag_error) {
                if($password){
                    $info->password = md5($password);
                }
                $res = $info->save();
                if($res){
                    AddLogController::addSysLog(AddLogController::account,'编辑账号,账号为:'.$username);
                    return $this->withSuccess('编辑成功!')->redirect(route('admin.account.index'));
                } else {
                    $this->withErrors('编辑失败，请重试!');
                }
            }
        }
        $list = AdminRole::getList();
        return $this->render('edit',['info'=>$info,'list'=>$list]);
    }

    /**
     * Desc: 新增用户
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:48
     */
    public function actionAdd(){
        $info = new Account();
        if($this->request->isPost){
            $flag_error = true;
            if (!$this->now_auth) {
                $flag_error = false;
                $this->withErrors('权限不足!');
            }

            $data = $this->request->bodyParams;
            $username = $data['username'];
            $realname = $data['realname'];
            $position = $data['position'];
            $weixin = $data['weixin'];
            $phone = $data['phone'];
            $email = $data['email'];
            $sex = $data['sex'];
            
            if($flag_error && !$username){
                $flag_error = false;
                $this->withErrors('账户名称不能空!');
            }else{
                if ($flag_error && $username == $_SESSION['company']) {
                    $flag_error = false;
                    $this->withErrors('该账户名称禁止使用!');
                } 
                $account = Account::findOne(['username'=>$username]);
                if($flag_error && $account){
                    $flag_error = false;
                    $this->withErrors('该账户名称已经存在!');
                }
            }
            if($flag_error && !$realname){
                $flag_error = false;
                $this->withErrors('真实姓名不能空!');
            }            
            if($flag_error && !$position){
                $flag_error = false;
                $this->withErrors('请添加职位!');
            }

            $info->username = $username;
            $info->realname = $realname;
            $info->position = $position;
            $info->phone = $phone;
            $info->sex = $sex;
            $info->email = $email;
            $info->weixin = $weixin;

            if ($flag_error) {
                if(empty($password)){
                    $info->password = md5('123456');
                } else {
                    $info->password = md5($password);
                }
                $info->addtime = (string)time();
                $res = $info->save();
                if($res){
                    AddLogController::addSysLog(AddLogController::account,'新增账号,账号为:'.$username);
                    return $this->withSuccess('新增成功!')->redirect(route('admin.account.index'));
                } else {
                    $this->withErrors('新增失败，请重试!');
                }
            }
        }
        $list = AdminRole::getList();
        return $this->render('add',['info'=>$info,'list'=>$list]);
    }

    /**
     * Desc: 删除用户
     * Created by pysh
     * Date: 2020/2/2
     * Time: 09:48
     */
    public function actionDel(){
        if($this->request->isAjax){
            if (!$this->now_auth) {
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'权限不足!']);
            }
            $id = $this->request->post('id');
            $model = Account::findOne(['id'=>$id]);
            $username = $model->username;
            if($model && $model->delete()){
                AddLogController::addSysLog(AddLogController::account,'刪除账户,账户为:'.$username);
                return $this->resultInfo(['retCode'=>1000,'retMsg'=>'删除成功!']);
            }else{
                return $this->resultInfo(['retCode'=>1001,'retMsg'=>'删除失败!']);
            }
        }else{
            return $this->resultInfo(['retCode'=>'00000','retMsg'=>'错误!']);
        }
    }

}