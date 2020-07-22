<?php

namespace app\modules\api\controllers;

use app\models\AppCustomerAccount;
use app\models\Customer;
use app\models\CustomerContact;
use app\models\User;
use app\models\AppGroup;
use Yii;
use yii\web\Request;

/**
 * Customer controller for the `api` module
 */
class CustomerController extends CommonController
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
        $all_name = $input['all_name'] ?? '';
        $use_flag = $input['use_flag'] ?? '';
        $paystate = $input['paystate'] ?? '';
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


        $list = Customer::find()
            ->where(['delete_flag'=>'Y']);
        if ($all_name) {
            $list->andWhere(['like','all_name',$all_name]);
        }
        if ($use_flag) {
            $list->andWhere(['use_flag'=>$use_flag]);
        }
        if ($paystate) {
            $list->andWhere(['paystate'=>$paystate]);
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

    public function actionGet_list(){
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        if ($group_id) {
            $list = Customer::get_list($group_id);
        } else {
            $list = [];
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$list]);
        return $this->resultInfo($data);

    }
/*
 * 添加客户公司
 * */

    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $all_name = $input['all_name'];
        $group_id = $input['group_id'];
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (!$all_name){
            $data = $this->encrypt(['code'=>400,'msg'=>'客户名称不能为空！']);
            return $this->resultInfo($data);
        }        

        if (!$group_id){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        if ($username){
            $customer = AppCustomerAccount::find()->where(['username'=>$username])->one();
            if ($customer){
                $data = $this->encrypt(['code'=>400,'msg'=>'客户下单账号已存在！']);
                return $this->resultInfo($data);
            }
            if (empty($password)){
                $data = $this->encrypt(['code'=>400,'msg'=>'账户密码不能为空']);
                return $this->resultInfo($data);
            }
        }
        $check_result = $this->check_token($token,true);//验证令牌

        $flag = Customer::find()->where(['group_id'=>$group_id,'all_name'=>$all_name,'delete_flag'=>'Y'])->one();
        if ($flag) {
            $data = $this->encrypt(['code'=>400,'msg'=>'该客户已存在！']);
            return $this->resultInfo($data);
        }
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);
        $model = new Customer();
        $model->all_name = $all_name;
        $model->group_id = $group_id;
        $model->address = $input['address'];
        $model->paystate = $input['paystate'];
        $model->contact_name = $input['contact_name'];
        $model->contact_tel = $input['contact_tel'];
        $model->remark = $input['remark'];
        $model->province_id = $input['pro_id'];
        $model->city_id = $input['city_id'];
        $model->area_id = $input['area_id'];
        $model->group_id = $group_id;
        $model->title = $input['title'];
        $model->bank = $input['bank'];
        $model->bank_number = $input['bank_number'];
        $model->tax_number = $input['tax_number'];
        $model->com_address = $input['com_address'];
        $model->com_tel = $input['com_tel'];
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $res = $model->save();
            if ($res){
                if ($username && $password){
                    $new = new AppCustomerAccount();
                    $new->username = $username;
                    $new->password = md5($password);
                    $new->group_id = $group_id;
                    $new->customer_id = $model->id;
                    $res_c = $new->save();
                }
                $transaction->commit();
                $this->hanldlog($user->id,$user->name.'添加客户公司:'.$model->all_name);
                $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 客户公司详情
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
            $model = Customer::find()
                ->alias('a')
                ->select('a.*,b.username')
                ->leftJoin('app_customer_account b','a.id = b.customer_id')
                ->where(['a.id'=>$id])
                ->asArray()
                ->one();
        } else {
            $model = new Customer();
        }
       
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$model,'groups'=>$groups]);
        return $this->resultInfo($data);
       
    }

    /*
     * 修改客户公司
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $all_name = $input['all_name'];
        $group_id = $input['group_id'];
        $username = $input['username'];
        $password  = $input['password'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        if (!$all_name){
             $data = $this->encrypt(['code'=>400,'msg'=>'客户名称不能为空！']);
             return $this->resultInfo($data);
        }

        if (!$group_id){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        if ($username){
            $customer = AppCustomerAccount::find()->where(['username'=>$username])->asArray()->all();
            if (count($customer)>1){
                $data = $this->encrypt(['code'=>400,'msg'=>'客户下单账号已存在！']);
                return $this->resultInfo($data);
            }

        }
        $check_result = $this->check_token($token,false);//验证令牌
        $user = $check_result['user'];
        $flag = Customer::find()->where(['group_id'=>$group_id,'all_name'=>$all_name,'delete_flag'=>"Y"])->andWhere(['!=','id',$id])->one();
         if ($flag) {
             $data = $this->encrypt(['code'=>400,'msg'=>'该客户已存在！']);
             return $this->resultInfo($data);
         }
        $this->check_group_auth($group_id,$user);
        $model = Customer::find()->where(['id'=>$id])->one();
        $model->all_name = $input['all_name'];
        $model->group_id = $input['group_id'];
        $model->address = $input['address'];
        $model->paystate = $input['paystate'];
        $model->contact_name = $input['contact_name'];
        $model->contact_tel = $input['contact_tel'];
        $model->remark = $input['remark'];
        $model->province_id = $input['pro_id'];
        $model->city_id = $input['city_id'];
        $model->area_id = $input['area_id'];
        $model->title = $input['title'];
        $model->bank = $input['bank'];
        $model->bank_number = $input['bank_number'];
        $model->tax_number = $input['tax_number'];
        $model->com_address = $input['com_address'];
        $model->com_tel = $input['com_tel'];
        $res_c = true;
        $new = AppCustomerAccount::find()->where(['customer_id'=>$id,'group_id'=>$model->group_id])->one();
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
                $account = new AppCustomerAccount();
                $account->username = $username;
                $account->password = md5($password);
                $account->group_id = $model->group_id;
                $account->customer_id = $id;
                $res_c = $account->save();
            }
        }

        $res = $model->save();
        if ($res && $res_c){
            $this->hanldlog($user->id,$user->name.'修改客户公司:'.$model->all_name);
            $data = $this->encrypt(['code'=>200,'msg'=>'修改成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'修改失败']);
            return $this->resultInfo($data);
        }

    }

    /*
     * 删除客户公司
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
        $model = Customer::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->delete_flag = 'N';
        $res = $model->save();
        if ($res){
           $this->hanldlog($user->id,$user->name.'删除客户公司:'.$model->all_name);
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
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = Customer::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'Y';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'启用客户公司：'.$model->all_name);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }    

    /*
     * 禁用
     * */
    public function actionUse_n(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $state = $input['state'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = Customer::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'禁用客户公司'.$model->all_name);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 添加客户公司联系人
     * */
    public function actionAdd_contact(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $model = new CustomerContact();
        $model->name = $input['name'];
        $model->phone = $input['phone'];
        $model->position = $input['position'];
        $model->group_id = $id;
        $res = $model->save();
        if ($res){
            $this->hanldlog($check_result['user']->id,$check_result['user']->name.'添加了客户公司联系人'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 客户公司联系人列表
     * */
    public function actionCustomer_list(){
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
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        $groups = AppGroup::group_list($user);
        if (!$group_id) {
            $group_id = $groups[0]['id'];
        }
        $where = [];
        $list = CustomerContact::find()
            ->where(['group_id'=>$user->group_id,'delete_flag'=>'Y']);
        if ($name) {
            $list->andWhere(['like','name',$name]);
        }
        if ($use_flag) {
            $list->andWhere(['use_flag'=>$use_flag]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['update_time'=>SORT_DESC,'use_flag'=>SORT_DESC])
            ->asArray()
            ->all();

        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'group' => $groups,
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }

    /*
     * 修改联系人
     * */
    public function actionEdit_contact(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $model = CustomerContact::find()->where(['id'=>$id])->one();
        $model->name = $input['name'];
        $model->phone = $input['phone'];
        $model->position = $input['position'];
        $res = $model->save();
        if ($res){
            $this->hanldlog($check_result['user']->id,$check_result['user']->name.'修改了客户公司联系人'.$model->name);
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 删除客户公司联系人
     * */
    public function actionDel_contact(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $model = CustomerContact::find()->where(['id'=>$id])->one();
        $model->delete_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($check_result['user']->id,$check_result['user']->name.'删除了客户公司联系人'.$model->all_name);
            $data = $this->encrypt(['code'=>200,'msg'=>'删除成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'删除失败']);
            return $this->resultInfo($data);
        }
    }
}
