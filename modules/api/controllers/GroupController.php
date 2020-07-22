<?php
namespace app\modules\api\controllers;

use Yii;
use app\models\AppGroup;
use app\models\User;
use app\models\AppSetting;
/**
 * Default controller for the `api` module
 */
class GroupController extends CommonController
{
     /*
     * 公司列表
     * */
    public function actionIndex(){
         $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $group_name = $input['group_name'] ?? '';
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
        $groups = AppGroup::group_list_arr($user);

        $list = AppGroup::find()
            ->alias('a')
            ->select(['a.id','a.group_name','a.name','a.tel','a.use_flag','a.update_time','a.main_id','g.group_name p_group_name'])
            ->leftJoin('app_group g','a.group_id=g.id')
            ->where(['a.delete_flag'=>'Y'])
            ->andWhere(['in','a.id',$groups]);
        if ($group_name) {
            $list->andWhere(['like','a.group_name',$group_name]);
        }
        if ($use_flag) {
            $list->andWhere(['a.use_flag'=>$use_flag]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['a.use_flag'=>SORT_DESC,'a.update_time'=>SORT_DESC])
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
     * 分公司详情
     * */
    public function actionView(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        if ($id) {
            $model = AppGroup::find()
                ->where(['id'=>$id])
                ->asArray()
                ->one();
        } else {
            $model = new AppGroup();
        }
       
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$model]);
        return $this->resultInfo($data);
    }    

    /*
     * 资金管理首页
     * */
    public function actionGroup_account(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        if ($user->group_id) {
            $model = AppGroup::find()
                ->where(['id'=>$user->group_id])
                ->asArray()
                ->one();
        } else {
            $model = new AppGroup();
        }

        $arr = [];
        $order_fixed_price = AppSetting::order_fixed_price();
        $order_percent = AppSetting::order_percent();
       
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$model,'arr'=>['order_fixed_price'=>$order_fixed_price,'order_percent'=>$order_percent]]);
        return $this->resultInfo($data);
       
    }

    /*
     * 修改group表level
     * */
    public function actionEdit_level(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $type = $input['type'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
//        $this->check_group_auth($group_id,$user);
        $group = AppGroup::find()->where(['id'=>$user->group_id])->one();
        if ($type == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
        if ($group->level == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
        if ($type == 1){
            $group->level = 1;
        }else{
            $group->level = 2;
        }
        $res = $group->save();
        if ($res){
            $this->hanldlog($user->id,'修改接单支付方式'.$group->group_name);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

   /*
    * 添加子公司
    * */
    public function actionAdd(){
         $request = Yii::$app->request;
         $input = $request->post();
         $token = $input['token'];
         $group_name = $input['group_name'];
         if(empty($token) || empty($group_name)){
             $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
             return $this->resultInfo($data);
         }
         $check_result = $this->check_token($token,true);//验证令牌
         $user = $check_result['user'];
         if ($user->admin_id != 1){
             $data = $this->encrypt(['code'=>400,'msg'=>'主公司主账号才有权此项操作']);
             return $this->resultInfo($data);
         }
         $tel = $input['tel'];
         $name = $input['name'];
         $group = AppGroup::find()->where(['group_name'=>$group_name,'delete_flag'=>'Y','group_id'=>$user->parent_group_id])->one();
         if ($group){
             $data = $this->encrypt(['code'=>400,'msg'=>'分公司名称已存在！']);
             return $this->resultInfo($data);
         }
         $this->check_group_auth($user->group_id,$user);
         $model = new AppGroup();
         $model->group_name = $group_name;
         $model->create_id = $user->id;
         $model->create_name = $user->name;
         $model->name = $name;
         $model->tel = $tel;
         $model->group_id = $user->parent_group_id;
         $res = $model->save();
         if ($res){
             $this->hanldlog($user->id,$user->name.'添加子公司：'.$group_name);
             $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
             return $this->resultInfo($data);
         }else{
             $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
             return $this->resultInfo($data);
         }

    }

    /*
     *修改子公司
     */
    public function actionEdit(){
        $request = Yii::$app->request;
         $input = $request->post();
         $token = $input['token'];
         $id = $input['id'];
         $group_name = $input['group_name'];
         if(empty($token) || empty($id)){
             $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
             return $this->resultInfo($data);
         }
         if(!$group_name){
             $data = $this->encrypt(['code'=>400,'msg'=>'分公司名称不能为空']);
             return $this->resultInfo($data);
         }
         $check_result = $this->check_token($token,true);//验证令牌
         $user = $check_result['user'];
         if ($user->admin_id != 1){
             $data = $this->encrypt(['code'=>400,'msg'=>'主公司账号才有权此项操作']);
             return $this->resultInfo($data);
         }
         $tel = $input['tel'];
         $name = $input['name'];
         $group = AppGroup::find()->where(['group_name'=>$group_name,'delete_flag'=>'Y','group_id'=>$user->parent_group_id])->andWhere(['!=','id',$id])->one();
         if ($group){
             $data = $this->encrypt(['code'=>400,'msg'=>'分公司名称已存在！']);
             return $this->resultInfo($data);
         }
         $this->check_group_auth($user->group_id,$user);
         $model = AppGroup::findOne($id);
         $model->group_name = $group_name;
         $model->name = $name;
         $model->tel = $tel;
         $res = $model->save();
         if ($res){
             $this->hanldlog($user->id,$user->name.'编辑子公司：'.$group_name);
             $data = $this->encrypt(['code'=>200,'msg'=>'编辑成功']);
             return $this->resultInfo($data);
         }else{
             $data = $this->encrypt(['code'=>400,'msg'=>'编辑失败']);
             return $this->resultInfo($data);
         }
    }


    /*
     * 删除子公司
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
        $group = AppGroup::find()->where(['id'=>$id])->one();
        $this->check_group_auth($id,$user);
        if ($group) {
            $group->delete_flag = 'N';
            $res = $group->save();
            if ($res){
               $this->hanldlog($user->id,'删除子公司'.$group->group_name);
               $data =  $this->encrypt(['code'=>200,'msg'=>'删除成功！']);
               return $this->resultInfo($data);
            }
        }
        $data = $this->encrypt(['code'=>400,'msg'=>'删除失败！']);
        return $this->resultInfo($data);
        
    }
    /*
     *启用子公司
     */
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
        $model = AppGroup::find()->where(['id'=>$id])->one();
        if ($model) {
            $model->use_flag = 'Y';
            $this->check_group_auth($id,$user);
            $res = $model->save();
            if ($res){
                $this->hanldlog($user->id,$user->name.'启用子公司：'.$model->name);
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                return $this->resultInfo($data);
            }
        }
        $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
        return $this->resultInfo($data);
    }

    /*
     *禁用子公司
     */
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
        $model = AppGroup::find()->where(['id'=>$id])->one();
        $this->check_group_auth($id,$user);
        if ($model) {
            $model->use_flag = 'N';
            $res = $model->save();
            if ($res){
                $this->hanldlog($user->id,$user->name.'禁用子公司：'.$model->name);
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                return $this->resultInfo($data);
            }
        }
        $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
        return $this->resultInfo($data);

    }
    
    // 获取有权限的公司列表
    public function actionGet_group_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        if(empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        $list = AppGroup::group_list($user);
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$list]);
        return $this->resultInfo($data);
    }
}
























