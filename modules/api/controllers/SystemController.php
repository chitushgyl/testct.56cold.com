<?php

namespace app\modules\api\controllers;

use app\models\AdminPermissions;
use app\models\AppAuthLeft;
use app\models\AppLevel;
use app\models\AppRole;
use app\models\User;
use Yii;

/**
 * System controller for the `api` module
 */
class SystemController extends CommonController
{
    /*
     * 系统菜单列表
     * */
    public function actionSystem_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
       if (empty($token)){
           $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
           return $this->resultInfo($data);
       }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        if ($user->admin_id == 1){
            $info = AppLevel::find()->select(['auth'])->where(['level_id'=>$user->parent_group_id])->asArray()->one();
        }elseif($user->admin_id == 2){
            $info = AppRole::find()->select(['role_auth'])->where(['role_id'=>$user->authority_id])->asArray()->one();
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功！','data'=>$info]);
        return $this->resultInfo($data);

    }


    /*
    * 添加/修改系统菜单
    * 0为顶级分类
    * */
    public function actionAdd_system(){
       $request = Yii::$app->request;
       $input = $request->post();
       $title = $input['title'];
       $url = $input['url'];
       $sort = $input['sort'];
       $use_flag = $input['use_flag'];
       $icon = $input['icon'];
       $level = $input['level'];
       $type = $input['type'];
       $menu = AdminPermissions::find()->where(['display_name'=>$title])->one();
       if ($menu){
           $data = $this->encrypt(['code'=>400,'msg'=>'路由已存在！']);
           return $this->resultInfo($data);
       }
       if ($type == 'add'){
           //添加菜单
           $system = new AdminPermissions();
           $system->display_name = $title;
           $system->route = $url;
           $system->sort = $sort;
           $system->parent_id = $level;
           $system->created_at = date('Y-m-d H:i:s',time());
           $system->icon_id = $icon;
           $system->status = $use_flag;
           $res = $system->save();
           if ($res){
               $data = $this->encrypt(['code'=>200,'msg'=>'添加成功！']);
               return $this->resultInfo($data);
           }else{
               $data = $this->encrypt(['code'=>400,'msg'=>'添加失败！']);
               return $this->resultInfo($data);
           }
       }
       if($type == 'update'){
           $id = $input['id'];
           $system = AdminPermissions::find()->where(['id'=>$id])->one();
           $system->display_name = $title;
           $system->route = $url;
           $system->sort = $sort;
           $system->parent_id = $level;
           $system->created_at = date('Y-m-d H:i:s',time());
           $system->icon_id = $icon;
           $system->status = $use_flag;
           $res = $system->save();
           if ($res){
               $data = $this->encrypt(['code'=>200,'msg'=>'修改成功！']);
               return $this->resultInfo($data);
           }else{
               $data = $this->encrypt(['code'=>401,'msg'=>'修改失败！']);
               return $this->resultInfo($data);
           }
       }

    }




}
