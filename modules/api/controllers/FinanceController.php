<?php
namespace app\modules\api\controllers;

use app\models\AppOrder;
use app\models\AppPayment;
use app\models\AppReceive;
use app\models\CountReceive;
use Yii;

/**
 * Default controller for the `api` module
 */
class FinanceController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    /*
     *统计首页
     * */
    public function actionIndex(){
          $input = Yii::$app->request->post();
          $token = $input['token'];
          $starttime = $input['starttime'] ?? '';
          $endtime = $input['endtime'] ?? '';
          $limit = $input['limit'] ?? 10;
          $page = $input['page'] ?? 1;
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
    //        $groups = AppGroup::group_list_arr($user);
          $time = date('Y-m-d',strtotime("-7 day"));
          if (!$starttime){
              $data['msg'] = '请选择开始时间';
              return json_encode($data);
          }
          if (!$endtime){
              $data['msg'] = '请选择结束时间';
              return json_encode($data);
          }

            $time1 = (strtotime($endtime) - strtotime($starttime))/24/3600;
              if ($time1>31){
                  $data['msg'] = '时间不能超过一个月';
                  return json_encode($data);
              }
            $get_data = [];
            for($i=0;$i<=$time1;$i++){
              $time = '';
              if ($i == 0) {
                  $time = $endtime;
              } else {
                if ($i == 1) {
                    $time = date('Y-m-d',strtotime('-'.$i.' day',strtotime($endtime.' 23:59:59')));
                } else {
                    $time = date('Y-m-d',strtotime('-'.$i.' days',strtotime($endtime.' 23:59:59')));
                }
              }
            $starttime_sel = $time.' 00:00:00';
            $endtime_sel = $time.' 23:59:59';
            // echo $endtime_sel;
            $payment = AppPayment::find()
                ->select('sum(pay_price),sum(truepay)')
                ->where(['group_id'=>$user->group_id])
                ->andWhere(['between','create_time',$starttime_sel,$endtime_sel])
                ->asArray()
                ->one();
            if (!$payment['sum(pay_price)']){
                $payment['sum(pay_price)'] = '0.00';
            }
            if (!$payment['sum(truepay)']){
                $payment['sum(truepay)'] = '0.00';
            }
            $receive = AppReceive::find()
                ->select('sum(receivprice),sum(trueprice)')
                ->where(['group_id'=>$user->group_id])
                ->andWhere(['between','create_time',$starttime_sel,$endtime_sel])
                ->asArray()
                ->one();
            if (!$receive['sum(receivprice)']){
                $receive['sum(receivprice)'] = '0.00';
            }
            if (!$receive['sum(trueprice)']){
                $receive['sum(trueprice)'] = '0.00';
            }
            $arr = [];
            $arr['time'] =  date('m/d',strtotime($time));
            $arr['pay_price'] = $payment['sum(pay_price)'];
            $arr['truepay'] = $payment['sum(truepay)'];
            $arr['receivprice'] = $receive['sum(receivprice)'];
            $arr['trueprice'] = $receive['sum(trueprice)'];
            $get_data[] = $arr;
        }
        $count = count($get_data);
        $res = array_slice($get_data,($page-1)*$limit,$limit);
        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'auth' => $check_result['auth'],
            'data'  => precaution_xss($res)
        ];
        return json_encode($data);

    }

    /*
     * 统计总重量，总体积，总订单数量
     * */
    public function actionFinance_order(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        if (empty($token)){
              $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
              return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $begindate=date('Y-m-01 00:00:00', strtotime(date("Y-m-d")));
        $enddate = date('Y-m-d 23:59:59',time());

        $count_order = AppOrder::find()->where(['group_id'=>$user->group_id,'main_order'=>1])->andWhere(['between','create_time',$begindate,$enddate])->count();
        $weight  = AppOrder::find()->where(['group_id'=>$user->group_id,'main_order'=>1])->andWhere(['between','create_time',$begindate,$enddate])->sum('weight');
        $volume = AppOrder::find()->where(['group_id'=>$user->group_id,'main_order'=>1])->andWhere(['between','create_time',$begindate,$enddate])->sum('volume');
        $receive = AppReceive::find()->where(['group_id'=>$user->group_id])->andWhere(['between','create_time',$begindate,$enddate])->sum('receivprice');
        $payment = AppPayment::find()->where(['group_id'=>$user->group_id])->andWhere(['between','create_time',$begindate,$enddate])->sum('pay_price');
        $income = $receive;
        $list['count'] = $count_order;
        $list['weight'] = round($weight,2);
        $list['volume'] = round($volume,2);
        $list['income'] = round($income,2);

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','list'=>$list]);
        return $this->resultInfo($data);

    }


}
