<?php
namespace app\modules\api\controllers;

use app\models\AppPayment;
use app\models\AppReceive;
use app\models\Carriage;
use Yii;
/**
 * Default controller for the `api` module
 */
class PaymentController extends CommonController
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
        $keyword = $input['keyword'] ?? '';
        $payment_status = $input['payment_status'] ?? '';
        $end_time = $input['end_time'] ?? '';
        $start_time = $input['start_time'] ?? '';
        $type = $input['type'];
        $chitu = $input['chitu'];

        $data = [
            'code' => 200,
            'msg' => '',
            'status' => 400,
            'count' => 0,
            'data' => []
        ];
        if (empty($token) || !$group_id ||empty($type)) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $check_result = $this->check_token_list($token,$chitu);//验证令牌
        if ($type == 1){
            $list = AppPayment::find()
                ->alias('a')
                ->select('a.*,b.name As group_name')
                ->leftJoin('app_carriage b','a.carriage_id = b.cid')
                ->where(['a.group_id'=>$group_id])
                ->andWhere(['in','a.pay_type',[1,2,3]]);

            if ($keyword){
                $list->andWhere(['like','b.name',$keyword])
                     ->orWhere(['like','a.driver_name',$keyword])
                     ->orWhere(['like','a.driver_car',$keyword])
                     ->andWhere(['a.group_id'=>$group_id]);

            }
        }else{
            $list = AppPayment::find()
                ->alias('a')
                ->select('a.*,b.group_name')
                ->leftJoin('app_group b','a.carriage_id = b.id')
                ->where(['a.group_id'=>$group_id])
                ->andWhere(['in','a.pay_type',[4,5]]);
            if ($keyword){
                $list->andWhere(['like','b.group_name',$keyword])
                    ->orWhere(['like','a.driver_name',$keyword])
                    ->orWhere(['like','a.driver_car',$keyword]);
            }
        }

        if ($end_time && $start_time) {
            $list->andWhere(['between','a.update_time',$start_time.' 00:00:00',$end_time.' 23:59:59']);
        } else {
            if ($start_time) {
                $list->andWhere(['>=','a.update_time',$start_time.' 00:00:00',$end_time.' 23:59:59']);
            } else if($end_time) {
                $list->andWhere(['<=','a.update_time',$end_time.' 23:59:59']);
            }
        }

        if ($payment_status) {
            $list->andWhere(['a.status'=>$payment_status]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['a.update_time' => SORT_DESC])
            ->asArray()
            ->all();
        foreach ($list as $key =>$value){
            if ($value['pay_type'] == 3 ||$value['pay_type'] == 1){
                $list[$key]['group_name'] = $value['driver_name'].'/'.$value['driver_car'];
            }
        }
        $data = [
            'code' => 200,
            'msg' => '正在请求中...',
            'status' => 200,
            'count' => $count,
            'auth' => $check_result['auth'],
            'data' => precaution_xss($list)
        ];
        return json_encode($data);
    }

    /*
     * 修改应付
     * */
    public function actionEdit_price(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $price = $input['price'];
        $reason = $input['case'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误!']);
            return $this->resultInfo($data);
        }
        if (empty($price)){
            $data = $this->encrypt(['code'=>400,'msg'=>'价格不能为空']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $payment = AppPayment::findOne($id);
        $this->check_group_auth($payment->group_id,$user);
        $payment->pay_price = $price;
        $payment->case = $reason;
        $res = $payment->save();
        if ($res){
            $this->hanldlog($user->id,'修改应付金额：'.$payment->order_id);
            $data = $this->encrypt(['code'=>200,'msg'=>'修改成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'修改失败']);
            return $this->resultInfo($data);
        }

    }
    /*
     * 对账
     * */
    public function actionPayment_over(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $payment = AppPayment::findOne($id);
        if($payment->pay_type != 3){
            if(empty($payment->carriage_id)){
                $data = $this->encrypt(['code'=>400,'msg'=>'数据有误，不能执行此操作']);
                return $this->resultInfo($data);
            }
        }
        if(empty($payment->pay_price)){
            $data = $this->encrypt(['code'=>400,'msg'=>'数据有误，不能执行此操作']);
            return $this->resultInfo($data);
        }
        $this->check_group_auth($payment->group_id,$user);
        $payment->status = 3;
        $payment->truepay = $payment->pay_price;
        $res = $payment->save();
        if ($res){
            $this->hanldlog($user->id,'对账完成：'.$payment->order_id);
            $data = $this->encrypt(['code'=>200,'msg'=>'对账完成']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'对账失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 应付详情
     * */
    public function actionView(){
        $input = Yii::$arr->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
    }

    /*
     * 实付总计
     * */
    public function actionCount_week(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $timestart = $input['timestart'] ?? '';
        $timeend = $input['timeend'] ?? '';
        $group_id = $input['group_id'];
        if (empty($token) || empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $group = AppPayment::find()->select('group_id')->all();
        var_dump($group);
        exit();
        $payment = AppPayment::find()->where(['group_id'=>$group_id]);
        $count = $payment->sum('truepay');
        $count1 = $payment->sum('pay_price');


        var_dump($count);
        var_dump($count1);

    }
}
