<?php
namespace app\modules\api\controllers;

use app\models\AppOrder;
use Yii;

/**
 * Default controller for the `api` module
 * 平台操作
 */
class PlatformController extends CommonController{
    /*
     * 订单完成
     * */
    public function actionOrder_done(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $order_type = $input['order_type'];
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status != 5){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单状态错误，请刷新重试!']);
            return $this->resultInfo($data);
        }

    }
}
