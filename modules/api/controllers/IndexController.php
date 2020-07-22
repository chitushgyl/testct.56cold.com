<?php
namespace app\modules\api\controllers;

use app\models\AppAskForCompany;
use app\models\AppBulk;
use app\models\AppGroup;
use app\models\AppPayment;
use app\models\AppReceive;
use app\models\User;
use Yii;

/**
 * Default controller for the `api` module
 */
class IndexController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex(){
         $input = Yii::$app->request->post();
         $token = $input['token'];
         if (empty($token)){
             $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
             return $this->resultInfo($data);
         }
        $check_result = $this->check_token($token,false);//验证令牌
        $user = $check_result['user'];
        $bulk = AppBulk::find()
            ->alias('a')
            ->select('a.number,a.shiftid,a.volume,a.weight,a.goodsname,a.temperture,a.total_price,a.orderstate ,b.startcity,b.endcity')
            ->leftJoin('app_line b','a.shiftid = b.id')
            ->where(['a.group_id'=>$user->group_id])
            ->orderBy([ 'a.create_time'=> SORT_DESC])
            ->limit(6)
            ->asArray()
            ->all();
        $data = $this->encrypt(['code'=>'200','msg'=>'查询成功','data'=>$bulk]);
        return $this->resultInfo($data);
    }

    /*
     * 认证企业用户
     * */
    public function actionAsk_bussiness(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $company_name = $input['id']; //企业名称
        $address = $input['address']; //企业地址
        $name = $input['name'];// 姓名
        $email = $input['email'];//邮箱
        $image = $input['image'];//企业资质
        $mobile = $input['mobile'];//手机号
        if(empty($token)){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];

//        $group = AppGroup::find()->where(['id'=>$user->group_id])->one();
//        $group->level_id = 3;
//        $group->save();
        $account = User::find($user->id);
        $account->level_id = 3;
        $arr = $account->save();
        if ($arr){
            $data = $this->encrypt(['code'=>'200','msg'=>'认证成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'认证失败']);
            return $this->resultInfo($data);
        }
    }

    public function actionAccount(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $company_name = $input['id']; //企业名称
        $address = $input['address'] ??''; //企业地址
        $name = $input['name'];// 姓名
        $email = $input['email'] ?? '';//邮箱
        $image = $input['image'] ?? "";//企业资质
        $mobile = $input['mobile'];//手机号
        if(empty($token)){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if(empty($company_name)){
            $data = $this->encrypt(['code'=>'400','msg'=>'公司名称不能为空']);
            return $this->resultInfo($data);
        }
        if(empty($name)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写您的姓名']);
            return $this->resultInfo($data);
        }
        if(empty($mobile)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写手机号']);
            return $this->resultInfo($data);
        }

        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];

        $company = new AppAskForCompany();
        $company->name = $name;
        $company->group_name =$company_name;
        $company->address = $address;
        $company->tel = $mobile;
        $company->email = $email;
        $company->image = $image;
        $company->group_id = $user->group_id;
        $company->account_id = $user->id;
        $res = $company->save();
        if ($res){
            $this->hanldlog($user->id,'认证企业账户');
            $data = $this->encrypt(['code'=>'200','msg'=>'提交资料成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'数据填写有误']);
            return $this->resultInfo($data);
        }


     }
}
