<?php
namespace app\modules\api\controllers;

use Yii;
use app\models\User;
use app\models\AppGroup;

/**
 * Default controller for the `api` module
 */
class AccountController extends CommonController
{
    /*
     * 账号列表
     * */
    public function actionIndex(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $name = $input['name'] ?? '';
        $use_flag = $input['use_flag'] ?? '';
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (empty($token)){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $check_result = $this->check_token_list($token);//验证令牌
        $user = $check_result['user'];
        $where = [];
        $list = User::find()
            ->select(['a.id','a.login','a.name','a.admin_id','a.tel','a.email','a.create_user_name','a.update_time','a.use_flag','a.true_name','a.userimage','b.group_name','a.com_type','c.name as auth_name'])
            ->alias('a')
            ->leftJoin('app_group b','a.group_id = b.id')
            ->leftJoin('app_role c','a.authority_id = c.role_id')
            ->where(['a.group_id'=>$group_id,'a.delete_flag'=>'Y']);
        if ($name) {
            $list->andWhere(['like','a.name',$name]);
        }
        if ($use_flag) {
            $list->andWhere(['a.use_flag'=>$use_flag]);
        }
        if ($group_id){
            $list->andWhere(['a.group_id'=>$group_id]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['a.use_flag'=>SORT_DESC,'a.update_time'=>SORT_DESC])
            ->asArray()
            ->all();

        foreach ($list as $k=>$v) {
            if ($group_id != $user['parent_group_id']) {
                if ($v['admin_id'] == 1) {
                    $list[$k]['auth_name'] = '主账号('.$v['auth_name'].')';
                }
            } else {
                if ($v['admin_id'] == 1) {
                    $list[$k]['auth_name'] = '主账号';
                }
            }
        }

        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'auth' => $check_result['auth'],
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }

    
    /*
     * 添加账号
     * */
    public function actionAdd(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $authority_id = $input['authority_id'];
        $login = $input['login'];
        $admin_id = $input['admin_id'];
        $password = $input['pwd'];
        $password2 = $input['pwd2'];
        $name = $input['name'];
        $true_name = $input['true_name'];
        if(empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if(empty($login)){
            $data = $this->encrypt(['code'=>400,'msg'=>'账号不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'所属公司不能为空']);
            return $this->resultInfo($data);
        }
        $account = User::find()->where(['login'=>$login,'delete_flag'=>'Y'])->one();
        if ($account){
            $data = $this->encrypt(['code'=>400,'msg'=>'账户已存在！']);
            return $this->resultInfo($data);
        }
//        $flag =  preg_match("/^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$/",$login);
//        if (!$flag){
//            $data = $this->encrypt(['code'=>400,'msg'=>'邮箱账户格式不正确']);
//            return $this->resultInfo($data);
//        }

        if (empty($authority_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'角色不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'昵称不能为空']);
            return $this->resultInfo($data);
        }
        
        if (empty($true_name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'真实姓名不能为空']);
            return $this->resultInfo($data);
        }

        $flag = false;
        if ($password || $password2) {
            if ($password != $password2) {
                $data = $this->encrypt(['code'=>400,'msg'=>'密码和确认密码不一致']);
                return $this->resultInfo($data);
            }
            if (strlen($password) < 6) {
                $data = $this->encrypt(['code'=>400,'msg'=>'密码长度至少6位']);
                return $this->resultInfo($data);
            }
        } else {
            $flag = true;
        }

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);

        $transaction = User::getDb()->beginTransaction();
        try {
            $model = new User();
            $model->login = $login ;
            if ($flag) {
                $model->pwd = md5('123456');
            } else {
                $model->pwd = md5($password);
            }
            $model->name = $name;
            $model->admin_id = $admin_id;
            $model->tel = $input['tel'];
            $model->create_user_id = $user->id;
            $model->create_user_name = $user->name;
            $model->group_id = $group_id;
            $group = AppGroup::find()->select(['group_id','main_id','id'])->where(['id'=>$group_id])->one();
            $parent_group_id = $group->group_id;
            if ($group->main_id == 1) {
                $parent_group_id = $group->id;
            }
            $model->parent_group_id = $parent_group_id;
            $model->true_name = $true_name;
            $model->sex = $input['sex'];
            $model->authority_id = $authority_id;
            $model->com_type = 3;

            if ($admin_id == 1) {
               User::set_admin_id($group_id);
            }
            $res = $model->save();

            $transaction->commit();
        } catch(\Exception $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败,请重试！']);
            return $this->resultInfo($data);
        } catch(\Throwable $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败,请重试！']);
            return $this->resultInfo($data);
        }
        if ($res){
            $this->hanldlog($user->id,'添加账号'.$model->login);
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
     }
     /*
     * 编辑账号
     * */
    public function actionEdit(){
        $request = Yii::$app->request;
        $input = $request->post();
        $id = $input['id'];
        $token = $input['token'];
        $admin_id = $input['admin_id'];
        $group_id = $input['group_id'];
        $authority_id = $input['authority_id'];
        $login = $input['login'];
        $password = $input['pwd'];
        $password2 = $input['pwd2'];
        $name = $input['name'];
        $true_name = $input['true_name'];

        if(empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $transaction = User::getDb()->beginTransaction();
        try {
            $model = User::find()->where(['id'=>$id])->one();
            $check_result = $this->check_token($token,true);//验证令牌
            $user = $check_result['user'];
            if ($model->admin_id != 1) {
                if(empty($login)){
                    $data = $this->encrypt(['code'=>400,'msg'=>'账号不能为空']);
                    return $this->resultInfo($data);
                }
                if (empty($group_id)){
                    $data = $this->encrypt(['code'=>400,'msg'=>'所属公司不能为空']);
                    return $this->resultInfo($data);
                }
                $model->group_id = $group_id;
                $account = User::find()->where(['login'=>$login,'delete_flag'=>'Y'])->andWhere(['!=','id',$id])->one();
                if ($account){
                    $data = $this->encrypt(['code'=>400,'msg'=>'账户已存在！']);
                    return $this->resultInfo($data);
                }
                if (empty($authority_id)){
                    $data = $this->encrypt(['code'=>400,'msg'=>'角色不能为空']);
                    return $this->resultInfo($data);
                }
                $model->authority_id = $authority_id;
                $this->check_group_auth($group_id,$user);
            }
    //        $flag =  preg_match("/^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$/",$login);
    //        if (!$flag){
    //            $data = $this->encrypt(['code'=>400,'msg'=>'邮箱账户格式不正确']);
    //            return $this->resultInfo($data);
    //        }


            if (empty($name)){
                $data = $this->encrypt(['code'=>400,'msg'=>'昵称不能为空']);
                return $this->resultInfo($data);
            }
            
            if (empty($true_name)){
                $data = $this->encrypt(['code'=>400,'msg'=>'真实姓名不能为空']);
                return $this->resultInfo($data);
            }

            $flag = false;
            if ($password || $password2) {
                if ($password != $password2) {
                    $data = $this->encrypt(['code'=>400,'msg'=>'密码和确认密码不一致']);
                    return $this->resultInfo($data);
                }
                if (strlen($password) < 6) {
                    $data = $this->encrypt(['code'=>400,'msg'=>'密码长度至少6位']);
                    return $this->resultInfo($data);
                }
            } else {
                $flag = true;
            }

            
            $model->login = $login;
            if (!$flag) {
                $model->pwd = md5($password);
            }
            $model->name = $name;
            $model->admin_id = $admin_id;
            $model->tel = $input['tel'];
            $model->true_name = $true_name;
            $model->sex = $input['sex'];

            if ($admin_id == 1) {
                User::set_admin_id($group_id);
            }
            $res = $model->save();

            $transaction->commit();
        } catch(\Exception $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'编辑失败,请重试！']);
            return $this->resultInfo($data);
        } catch(\Throwable $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'编辑失败,请重试！']);
            return $this->resultInfo($data);
        }

        if ($res){
            $this->hanldlog($user->id,'编辑账号：'.$model->login);
            $data = $this->encrypt(['code'=>200,'msg'=>'编辑成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'编辑失败']);
            return $this->resultInfo($data);
        }
     }

     /*
      *账户详情
      * */
    public function actionView(){
     	$request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $id = $input['id'];
        if(empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $arr = $check_result['user'];
        $groups = AppGroup::group_list($arr);
        if ($id) {
            $user = User::find()->where(['id'=>$id])->asArray()->one();
        } else {
            $user = new User();
        }
        $auths = $this->left_auth($check_result['user']);
        $tree = list_to_tree($auths);
        $data = $this->encrypt(['code'=>200,'msg'=>'查看成功','data'=>$user,'groups'=>$groups,'tree'=>$tree]);
        return $this->resultInfo($data);
    }

     /*
     * 删除账号
     * */
    public function actionDel(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $id = $input['id'];
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = User::find()->where(['id'=>$id])->one();
        $model->delete_flag = 'N';
        $this->check_group_auth($user->group_id,$user);
        $res = $model->save();
        if($res){
            $this->hanldlog($user->id,$user->name.'删除账号:'.$model->login);
            $data =$this->encrypt(['code'=>200,'msg'=>'删除成功！']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'删除失败！']);
            return $this->resultInfo($data);
        }
    }
    /*
    *
    *启用账号
    **/
    public function actionUse_y(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $id = $input['id'];
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = User::find()->where(['id'=>$id])->one();
        $model->use_flag = 'Y';
        $this->check_group_auth($model->group_id,$user);
        $res = $model->save();
        if($res){
            $this->hanldlog($user->id,'启用员工:'.$model->login);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功！']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败！']);
            return $this->resultInfo($data);
        }
    }

    /*
    *
    *禁用账号
    **/
    public function actionUse_n(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $id = $input['id'];
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = User::find()->where(['id'=>$id])->one();
        $model->use_flag = 'N';
        $this->check_group_auth($model->group_id,$user);
        $res = $model->save();
        if($res){
            $this->hanldlog($user->id,'禁用员工'.$model->login);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功！']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败！']);
            return $this->resultInfo($data);
        }
    }
}
   





























 