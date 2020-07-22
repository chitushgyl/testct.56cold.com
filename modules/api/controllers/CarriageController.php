<?php

namespace app\modules\api\controllers;

use app\models\AppCarriageAccount;
use app\models\AppGroup;
use app\models\Carriage;
use app\models\Customer;
use app\models\User;
use Yii;

/**
 * Default controller for the `api` module
 */
class CarriageController extends CommonController
{
    /*
     * 承运商列表
     * */
    public function actionIndex(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $all_name = $input['name'] ?? '';
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
        $list = Carriage::find()
            ->where(['delete_flag'=>'Y']);
        if ($all_name) {
            $list->andWhere(['like','name',$all_name]);
        }
        if ($use_flag) {
            $list->andWhere(['use_flag'=>$use_flag]);
        }
        if ($group_id) {
            $list->andWhere(['group_id'=>$group_id]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['use_flag'=>SORT_DESC,'update_time'=>SORT_DESC])
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
     * 添加承运商
     * */
    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $name = $input['name'];
        $username = $input['username'];
        $password = $input['password'];
        $group_id = $input['group_id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'承运方名称不能为空！']);
            return $this->resultInfo($data);
        }
        $flag = Carriage::find()->where(['name'=>$name,'group_id'=>$group_id,'delete_flag'=>'Y'])->one();
        if ($flag){
            $data = $this->encrypt(['code'=>400,'msg'=>'承运方名称已存在！']);
            return $this->resultInfo($data);
        }
        if(empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        if ($username){
            $carriage = AppCarriageAccount::find()->where(['username'=>$username])->one();
            if ($carriage){
                $data = $this->encrypt(['code'=>400,'msg'=>'承运方账号已存在！']);
                return $this->resultInfo($data);
            }
            if (empty($password)){
                $data = $this->encrypt(['code'=>400,'msg'=>'账户密码不能为空']);
                return $this->resultInfo($data);
            }
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);
        $model = new Carriage();
        $model->name = $name;
        // $model->address = $input['address'];
        // $model->provinceid = $input['provinceid'];
        // $model->cityid = $input['cityid'];
        // $model->areaid = $input['areaid'];
        $model->group_id = $group_id;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $res = $model->save();
            if ($res){
                if ($username && $password){
                    $new = new AppCarriageAccount();
                    $new->username = $username;
                    $new->password = md5($password);
                    $new->group_id = $group_id;
                    $new->carriage_id = $model->cid;
                    $res_c = $new->save();
                }
                $transaction->commit();
                $this->hanldlog($user->id,$user->name.'添加承运方:'.$model->name);
                $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'添加失败1']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败2']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 修改承运商
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $name = $input['name'];
        $group_id = $input['group_id'];
        $username = $input['username'];
        $password = $input['password'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'承运方名称不能为空！']);
            return $this->resultInfo($data);
        }
        $flag = Carriage::find()->where(['name'=>$name,'group_id'=>$group_id,'delete_flag'=>'Y'])->andWhere(['!=','cid',$id])->one();
        if ($flag){
            $data = $this->encrypt(['code'=>400,'msg'=>'承运方名称已存在！']);
            return $this->resultInfo($data);
        }
        if ($username){
            $customer = AppCarriageAccount::find()->where(['username'=>$username])->asArray()->all();
            if (count($customer)>1){
                $data = $this->encrypt(['code'=>400,'msg'=>'承运方账号已存在！']);
                return $this->resultInfo($data);
            }
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);
        $model = Carriage::find()->where(['cid'=>$id])->one();
        $model->name = $name;
//        $model->address = $input['address'];
//        $model->provinceid = $input['provinceid'];
//        $model->cityid = $input['cityid'];
//        $model->areaid = $input['areaid'];
        $model->group_id = $group_id;
        $res_c = true;
        $new = AppCarriageAccount::find()->where(['carriage_id'=>$id,'group_id'=>$model->group_id])->one();
        if ($new){
            $new->username = $input['username'];
            if($password){
                $new->password = md5($password);
            }
            $res_c = $new->save();
        }else{
            if ($username){
                if (!$password){
                    $data = $this->encrypt(['code'=>400,'msg'=>'密码不能为空！']);
                    return $this->resultInfo($data);
                }
            }
            if ($username && $password){
                $account = new AppCarriageAccount();
                $account->username = $username;
                $account->password = md5($password);
                $account->group_id = $model->group_id;
                $account->carriage_id = $id;
                $res_c = $account->save();
            }
        }
        $res = $model->save();
        if ($res && $res_c){
            $this->hanldlog($user->id,$user->name.'修改承运方：'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'修改成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'修改失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 承运方详情
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
        $groups = AppGroup::group_list($user);
        if ($id) {
            $model = Carriage::find()
                ->alias('c')
                ->select(['c.*','b.username'])
                ->leftJoin('app_carriage_account b','c.cid = b.carriage_id')
                ->where(['c.cid'=>$id])
                ->asArray()
                ->one();
        } else {
            $model = new Carriage();
        }

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$model,'groups'=>$groups]);
        return $this->resultInfo($data);
    }

    /*
     *删除承运方
     * */
    public function actionDel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = Carriage::find()->where(['cid'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->delete_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'删除承运方:'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'删除成功']);
            return $this->resultInfo($data);
        }

        $data = $this->encrypt(['code'=>400,'msg'=>'删除失败']);
        return $this->resultInfo($data);
    }

    /*
     * 启用承运方
     * */
    public function actionUse_y(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = Carriage::find()->where(['cid'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'Y';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'启用承运方：'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 禁用承运方
     * */
    public function actionUse_n(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = Carriage::find()->where(['cid'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'禁用承运方'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

        /*
         * 检索承运商
         * */
    public function actionSelect()
    {
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        $val = $input['val'];

        $list = Carriage::find()
            ->select(['cid','name']);
        if ($val) {
            $list->orWhere(['like','name',$val]);
        }

        $list->andWhere(['group_id' => $group_id,'use_flag'=>'Y','delete_flag'=>'Y']);

        $l = json_encode($list);
        $list = $list
            ->orderBy(['update_time' => SORT_DESC])
            ->limit(20)
            ->asArray()
            ->all();

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list,'input'=>$input]);
        return $this->resultInfo($data);
    }

    /*
     * 订单选择
     * */
}

