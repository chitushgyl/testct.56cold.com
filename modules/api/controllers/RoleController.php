<?php

namespace app\modules\api\controllers;

use app\models\User; 
use app\models\AppRole;
use app\models\AppGroup;
use app\models\AppAuthLeft;
use app\models\AppAuthTop;
use Yii;
use yii\web\Request;

/**
 * Customer controller for the `api` module
 */
class RoleController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {

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
        $list = AppRole::find()
            ->alias('r')
            ->select(['r.role_id','r.name','r.update_time','r.use_flag','g.group_name'])
            ->leftJoin('app_group g','r.group_id = g.id');
        if ($name) {
            $list->andWhere(['like','r.name',$name]);
        }
        if ($use_flag) {
            $list->andWhere(['r.use_flag'=>$use_flag]);
        }
        if ($group_id) {
            $list->andWhere(['r.group_id'=>$group_id]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['r.use_flag'=>SORT_DESC,'r.update_time'=>SORT_DESC])
            ->asArray()
            ->all();

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
     * 详情
     * */
    public function actionView(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $id = $input['id'];
        $check_result = $this->check_token($token);
        $user = $check_result['user'];
        $groups = AppGroup::group_list($user);
        if ($id) {
            $model = AppRole::find()->where(['role_id'=>$id])->asArray()->one();
        } else {
            $model = new AppRole();
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'ok','data'=>$model,'groups'=>$groups]);
        return $this->resultInfo($data);
    }    

    /*
     * 权限
     * */
    public function actionAuth(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);
        $user = $check_result['user'];

        $model = AppRole::find()->select(['role_id','name','role_auth','top_auth','group_id'])->where(['role_id'=>$id])->asArray()->one();

        $authority_id = $user->authority_id;
        $auth_ids = $model['role_auth'];
        $arr_ids = explode(',',$auth_ids);
        $auth_ids_top = $model['top_auth'];
        $arr_ids_top = explode(',',$auth_ids_top);

        if ($user->admin_id == 1 && $user->com_type == 1) {
            $permission = AppAuthLeft::getList();
            $permission_top = AppAuthTop::getList();
        } else {
            $permission = AppAuthLeft::find()->where(['in','id',$arr_ids])->andWhere(['use_flag'=>'Y'])->andWhere(['!=','route','/group_account/index'])->asArray()->all();
            $permission_top = AppAuthTop::find()->where(['in','id',$arr_ids])->andWhere(['use_flag'=>'Y'])->asArray()->all();
        }
        $tree = list_to_tree($permission);
        $tree_top = list_to_tree($permission_top);
        $groups = AppGroup::group_list($user);
        $data = [
            'code' => 200,
            'msg' => '',
            'id' => $id,
            'data' => $tree,
            'data_top' => $tree_top,
            'arr_ids' => $auth_ids,
            'arr_ids_top' => $auth_ids_top,
            'model'=> $model,
            'groups'=> $groups
        ];
        $data = $this->encrypt($data);
        return $this->resultInfo($data);
    }    

    /*
     * 角色添加权限
     * */
    public function actionAdd_auth(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $auth = $input['auth'];
        // $top_auth = $input['top_auth'];
        // $group = $input['group'];
        $token = $input['token'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $role_auth = implode(',',$auth);
        // $role_auth_top = implode(',',$top_auth);
        // $group_auth = implode(',',$group);
        $model = AppRole::find()->where(['role_id'=>$id])->one();

        $data = [
            'code' => 400,
            'msg' => '',
            'data' => ''
        ];

        if ($model) {
            $model->role_auth = $role_auth;
            // $model->top_auth = $role_auth_top;
            // $model->group_id = $group_auth;
            $res = $model->save();
            if ($res) {
                $data['code'] = 200;
                $data['msg'] = '操作成功';
            } else {
                $data['msg'] = '操作失败';
            }  
        } else {
            $data['msg'] = '角色已不存在';
        }

        $data = $this->encrypt($data);
        return $this->resultInfo($data);

    }

/*
 * 添加角色
 * */
    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $name = $input['name'];
        $group_id = $input['group_id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (!$name){
            $data = $this->encrypt(['code'=>400,'msg'=>'角色名称不能为空！']);
            return $this->resultInfo($data);
        }
        if (!$group_id){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];

        $flag = AppRole::find()->where(['name'=>$name,'group_id'=>$group_id])->one();
        if ($flag) {
            $data = $this->encrypt(['code'=>400,'msg'=>'角色名称已存在！']);
            return $this->resultInfo($data);
        }

        $model = new AppRole();
        $model->name = $name;
        $model->group_id = $group_id;
        $this->check_group_auth($model->group_id,$user);
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'添加角色：'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 编辑角色
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        $name = $input['name'];
        $group_id = $input['group_id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (!$name){
            $data = $this->encrypt(['code'=>400,'msg'=>'角色名称不能为空！']);
            return $this->resultInfo($data);
        }
        if (!$group_id){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];

        $flag = AppRole::find()->where(['name'=>$name,'group_id'=>$group_id])->andWhere(['!=','role_id',$id])->one();
        if ($flag) {
            $data = $this->encrypt(['code'=>400,'msg'=>'角色名称已存在！']);
            return $this->resultInfo($data);
        }

        $model = AppRole::find()->where(['role_id'=>$id])->one();
        $model->name = $name;
        $model->group_id = $group_id;
        $this->check_group_auth($model->group_id,$user);
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'编辑角色：'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'编辑成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'编辑失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 删除角色
     * */
    public function actionDel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $flag = User::find()->where(['authority_id'=>$id,'delete_flag'=>'Y'])->one();
        if ($flag) {
            $data = $this->encrypt(['code'=>400,'msg'=>'该角色正在使用，无法删除']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = AppRole::find()->where(['role_id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $res = $model->delete();
        if ($res){
            $this->hanldlog($user->id,$user->name.'删除角色：'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'删除成功']);
            return $this->resultInfo($data);
        }
        $data = $this->encrypt(['code'=>400,'msg'=>'删除失败']);
        return $this->resultInfo($data);
    }
    /*
     * 启用
     * */
    public function actionUse_y(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];

        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];

        $model = AppRole::find()->where(['role_id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'Y';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'启用角色：'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }
        $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
        return $this->resultInfo($data);
    }     

    /*
     * 禁用
     * */
    public function actionUse_n(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];

        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];

        $model = AppRole::find()->where(['role_id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'禁用角色：'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }

        $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
        return $this->resultInfo($data);
    }        

    /*
     * 获取公司下角色
     * */
    public function actionGroup_roles(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        // $user = $check_result['user'];
        $list = AppRole::find()->select(['role_id','name'])->where(['use_flag'=>'Y','group_id'=>$id])->asArray()->all();
        // $admin_id = $user->admin_id;
        // if ($admin_id == 1) {
        //     $list = AppRole::find()->select(['role_id','name'])->where(['use_flag'=>'Y','group_id'=>$user->group_id])->asArray()->all();
        // } else {
        //     $list = AppRole::find()->select(['role_id','name'])->where(['use_flag'=>'Y','group_id'=>$id])->asArray()->all();
        // }
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$list]);
        return $this->resultInfo($data);
    }    

}
