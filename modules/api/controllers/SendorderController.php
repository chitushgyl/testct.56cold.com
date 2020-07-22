<?php
namespace app\modules\api\controllers;

use app\models\AppPayment;
use app\models\AppSendCarriage;
use app\models\SendOrder;
use Yii;
use app\models\AppSendorder;

/**
 * Default controller for the `api` module
 */
class SendorderController extends CommonController
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
        $ordernumber = $input['name'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $endtime = $input['endtime'] ?? '';
        $status = $input['state'] ?? '';

        $data = [
            'code' => 200,
            'msg' => '',
            'status' => 400,
            'count' => 0,
            'data' => []
        ];
        if (empty($token) || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $check_result = $this->check_token_list($token);//验证令牌
        $list = AppSendorder::find()
            ->alias('v')
            ->select(['v.*', 'c.orderstate','c.paystate','c.line_type','c.remark','c.shiftid','e.start_time','c.temperture','c.volume','c.weight','c.number','c.number1'])
            ->leftJoin('app_bulk c', 'v.order_id=c.id')
            ->leftJoin('app_line e','c.shiftid = e.id');
//            ->leftJoin('app_customer b','c.customer_id=b.id');
        if ($begintime && $endtime) {
            $list->andWhere(['between', 'v.create_time', $begintime, $endtime]);
        }
        if ($status) {
            $list->andWhere(['v.status' => $status]);
        }
        $list->andWhere(['v.group_id' => $group_id]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.update_time' => SORT_DESC])
            ->asArray()
            ->all();
        foreach ($list as $key => $value){
            $list[$key]['startstr_send'] = json_decode($value['startstr_send'],true);
            $list[$key]['endstr_send'] = json_decode($value['endstr_send'],true);
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
     * 根据ID查询订单信息
     * */
    public function actionSelect_info(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $ids = $input['ids'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user= $check_result['user'];
        $list = AppSendorder::find()
            ->alias('v')
            ->select(['v.*', 'c.orderstate','c.paystate','c.line_type','c.remark','c.shiftid','e.start_time','c.temperture','c.volume','c.weight','c.number','c.number1'])
            ->leftJoin('app_bulk c', 'v.order_id=c.id')
            ->leftJoin('app_line e','c.shiftid = e.id')
            ->where(['in','v.id',$ids])
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['startstr_send'] = json_decode($v['startstr_send'],true);
            $list[$k]['endstr_send'] = json_decode($v['endstr_send'],true);
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list]);
        return $this->resultInfo($data);

    }
    /*
     * 合单列表
     * */
    public function actionOrder_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $begintime = $input['begintime'] ?? '';
        $endtime = $input['endtime'] ?? '';
        $status = $input['state'] ?? '';

        $data = [
            'code' => 200,
            'msg' => '',
            'status' => 400,
            'count' => 0,
            'data' => []
        ];
        if (empty($token) || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $check_result = $this->check_token_list($token);//验证令牌
        $list = SendOrder::find();

        if ($begintime && $endtime) {
            $list->andWhere(['between', 'create_time', $begintime, $endtime]);
        }
        if ($status) {
            $list->andWhere(['state' => $status]);
        }
        $list->andWhere(['group_id' => $group_id]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['update_time' => SORT_DESC])
            ->asArray()
            ->all();
        foreach ($list as $key => $value){

            $sendorder = AppSendorder::find()
                ->select('a.*,b.shiftid,c.start_time,b.temperture')
                ->alias('a')
                ->leftJoin('app_bulk b','a.order_id = b.id')
                ->leftJoin('app_line c','b.shiftid = c.id')
                ->where(['in','a.id',explode(',',$value['order_ids'])])
                ->asArray()
                ->all();
            if ($sendorder) { 
                foreach($sendorder as $k => $v) {
                    $sendorder[$k]['start_time'] = date('Y-m-d H:i:s',strtotime($v['start_time'])-7200);
                }
            }
            $carriage = AppSendCarriage::find()
                ->where(['send_id'=>$value['id']])
                ->asArray()
                ->all();
            $list[$key]['sendorder'] = json_encode($sendorder);
            $list[$key]['carriage'] = json_encode($carriage);
        }
        $data = [
            'code' => 200,
            'msg' => '正在请求中...',
            'status' => 200,
            'count' => $count,
            'auth' => $check_result['auth'],
            'data' => precaution_xss($list),
            'carriage'=>$carriage,
            'pickorder'=>$sendorder
        ];
        return json_encode($data);
    }

    /*
     * 调度
     * */
    public function actionDispatch(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $ids = $input['ids'];
        $group_id = $input['group_id'];
//        $ids =  explode(',',$ids);
        $type = $input['type'];
        $price = $input['price'];
        $carriage_info = json_decode($input['arr'],true);
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user= $check_result['user'];

        $list = AppSendorder::find()->where(['in','id',$ids])->andWhere(['group_id'=>$group_id])->asArray()->all();
        $volume =$this->get_params($ids,'send_volume',2);
        $weight = $this->get_params($ids,'send_weight',2);
        $number = $this->get_params($ids,'send_number',2);
        $number1 = $this->get_params($ids,'send_number1',2);
        $startstr = $endstr = $temperture = [];
//        var_dump($list);
//        exit();
        foreach ($list as $key =>$value){

            $startstr = array_merge($startstr,json_decode($value['startstr_send'],true));
            $endstr = array_merge($endstr,json_decode($value['endstr_send'],true));
            $order['startcity'] = $value['startcity'];
            $order['endcity']   = $value['endcity'];
//            array_push($temperture,$value['temperture']);

        }
//        var_dump($order);
//        exit();
        $startstr = array_unique($startstr,SORT_REGULAR);
        $endstr = array_unique($endstr,SORT_REGULAR);
//        $temperture = array_unique($temperture,SORT_REGULAR);
        $transaction= SendOrder::getDb()->beginTransaction();
        try {
            $send_order = new SendOrder();
            $send_order->startcity = $order['startcity'];
            $send_order->endcity = $order['endcity'];
            $send_order->startstr = json_encode($startstr,JSON_UNESCAPED_UNICODE);
            $send_order->endstr = json_encode($endstr,JSON_UNESCAPED_UNICODE);
            $send_order->order_ids = implode(',',$ids);
            $send_order->group_id = $group_id;
            $send_order->weight  = $weight;
            $send_order->number = $number;
            $send_order->number1 = $number1;
            $send_order->volume = $volume;
            $send_order->price = $price;
            $send_order->type = $type;
            $arr = $send_order->save();
            $res = $carriage = true;
            switch ($type) {
                case '1':
                    foreach ($carriage_info as $key => $value) {
                        $send_list['send_id'] = $send_order->id;
                        $send_list['group_id'] = $user->group_id;
                        $send_list['create_user_id'] = $user->id;
                        $send_list['carriage_price'] = $value['price'];
                        $send_list['type'] = $type;
                        $send_list['contant'] = $value['contant'];
                        $send_list['carnumber'] = $value['carnumber'];
                        $send_list['tel'] = $value['tel'];
                        $send_list['startstr'] = json_encode($startstr,JSON_UNESCAPED_UNICODE);
                        $send_list['endstr'] = json_encode($endstr,JSON_UNESCAPED_UNICODE);
                        $send_list['create_time'] = $send_list['update_time'] = date('Y-m-d H:i:s', time());
                        $pick_lists[] = $send_list;

                        $list_c['order_id'] = $value['id'];
                        $list_c['pay_price'] = $value['price'];
                        $list_c['truepay'] = 0;
                        $list_c['group_id'] = $user->group_id;
                        $list_c['create_user_id'] = $user->id;
                        $list_c['create_user_name'] = $user->name;
                        $list_c['carriage_id'] = $value['id'];
                        $list_c['driver_name'] = $value['contant'];
                        $list_c['driver_car'] = $value['carnumber'];
                        $list_c['driver_tel'] = $value['tel'];
                        $list_c['pay_type'] = 1;
                        $list_c['type'] = 5;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s', time());
                        $info_c[] = $list_c;
                        $deal_company = '';
                    }
                    $res = Yii::$app->db->createCommand()->batchInsert(AppSendCarriage::tableName(), ['send_id', 'group_id', 'create_user_id', 'carriage_price', 'type', 'contant', 'carnumber', 'tel','startstr','endstr', 'create_time', 'update_time'], $pick_lists)->execute();
                    $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id', 'driver_name', 'driver_car', 'driver_tel', 'pay_type', 'type', 'create_time', 'update_time'], $info_c)->execute();
                    break;
                case '2':
                    foreach ($carriage_info as $key => $value) {

                        $send_list['send_id'] = $send_order->id;
                        $send_list['group_id'] = $user->group_id;
                        $send_list['create_user_id'] = $user->id;
                        $send_list['carriage_price'] = $value['price'];
                        $send_list['type'] = $type;
                        $send_list['deal_company'] = $value['id'];
                        $send_list['contant'] = $value['contant'];
                        $send_list['carnumber'] = $value['carnumber'];
                        $send_list['tel'] = $value['tel'];
                        $send_list['startstr'] = json_encode($startstr,JSON_UNESCAPED_UNICODE);
                        $send_list['endstr'] = json_encode($endstr,JSON_UNESCAPED_UNICODE);
                        $send_list['create_time'] = $send_list['update_time'] = date('Y-m-d H:i:s', time());
                        $pick_lists[] = $send_list;

                        $list_c['order_id'] = $value['id'];
                        $list_c['pay_price'] = $value['price'];
                        $list_c['truepay'] = 0;
                        $list_c['group_id'] = $user->group_id;
                        $list_c['create_user_id'] = $user->id;
                        $list_c['create_user_name'] = $user->name;
                        $list_c['carriage_id'] = $value['id'];
                        $list_c['pay_type'] = 2;
                        $list_c['type'] = 5;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s', time());
                        $info_c[] = $list_c;
                        $deal_company = $value['id'];
                    }
                    $res = Yii::$app->db->createCommand()->batchInsert(AppSendCarriage::tableName(), ['send_id', 'group_id', 'create_user_id', 'carriage_price', 'type', 'deal_company', 'contant', 'carnumber', 'tel','startstr','endstr', 'create_time', 'update_time'], $pick_lists)->execute();
                    $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id', 'pay_type', 'type', 'create_time', 'update_time'], $info_c)->execute();
                    break;
                case '3':
                    foreach ($carriage_info as $key => $value) {
                        $send_list['send_id'] = $send_order->id;
                        $send_list['group_id'] = $user->group_id;
                        $send_list['create_user_id'] = $user->id;
                        $send_list['carriage_price'] = $value['price'];
                        $send_list['type'] = $type;
                        $send_list['contant'] = $value['contant'];
                        $send_list['carnumber'] = $value['carnumber'];
                        $send_list['tel'] = $value['tel'];
                        $send_list['startstr'] = json_encode($startstr,JSON_UNESCAPED_UNICODE);
                        $send_list['endstr'] = json_encode($endstr,JSON_UNESCAPED_UNICODE);
                        $send_list['create_time'] = $send_list['update_time'] = date('Y-m-d H:i:s', time());
                        $pick_lists[] = $send_list;

                        $list_c['order_id'] = $value['id'];
                        $list_c['pay_price'] = $value['price'];
                        $list_c['truepay'] = 0;
                        $list_c['group_id'] = $user->group_id;
                        $list_c['create_user_id'] = $user->id;
                        $list_c['create_user_name'] = $user->name;
                        $list_c['driver_name'] = $value['contant'];
                        $list_c['driver_car'] = $value['carnumber'];
                        $list_c['driver_tel'] = $value['tel'];
                        $list_c['pay_type'] = 3;
                        $list_c['type'] = 5;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s', time());
                        $info_c[] = $list_c;
                        $deal_company = '';
                    }
                    $res = Yii::$app->db->createCommand()->batchInsert(AppSendCarriage::tableName(), ['send_id', 'group_id', 'create_user_id', 'carriage_price', 'type', 'contant', 'carnumber', 'tel','startstr','endstr', 'create_time', 'update_time'], $pick_lists)->execute();
                    $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'driver_name', 'driver_car', 'driver_tel', 'pay_type', 'type', 'create_time', 'update_time'], $info_c)->execute();
                    break;
                default:
                    break;
            }
            $send_order->deal_company = $deal_company;
            $send_order->state = 3;
            if ($type == 2){
                $send_order->state = 1;
            }
            $res_pick =  $send_order->save();
            $lists = AppSendorder::updateAll(['status'=>2],['in', 'id', $ids]);

            if ($arr && $lists && $res && $carriage && $res_pick){
                $transaction->commit();
                $this->hanldlog($user->id,'调度送货订单:');
                $data = $this->encrypt(['code'=>200,'msg'=>'调度成功']);
                return $this->resultInfo($data);
            }else{
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'调度失败']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'调度失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 详情
     * */
    public function actionView(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->requestInfo($data);
        }
        $order = SendOrder::find()->where(['id'=>$id])->asArray()->one();
        $list = AppSendorder::find()
            ->select('a.*,b.shiftid,c.start_time,b.temperture')
            ->alias('a')
            ->leftJoin('app_bulk b','a.order_id = b.id')
            ->leftJoin('app_line c','b.shiftid = c.id')
            ->where(['in','a.id',explode(',',$order['order_ids'])])
            ->asArray()
            ->all();
        $carriage = AppSendCarriage::find()
            ->where(['send_id'=>$id])
            ->asArray()
            ->all();
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list,'carriage'=>$carriage,'order'=>$order]);
        return $this->resultInfo($data);
    }

    /*
     * 已完成
     * */
    public function actionDone(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $list = SendOrder::findOne($id);
        if ($list->state == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'请完成调度']);
            return $this->resultInfo($data);
        }
        if ($list->state == 7){
            $data = $this->encrypt(['code'=>400,'msg'=>'请勿重复操作']);
            return $this->resultInfo($data);
        }
        if ($list->state == 9){
            $data = $this->encrypt(['code'=>400,'msg'=>'已取消']);
            return $this->resultInfo($data);
        }

        $list->state = 7;
        $order = AppSendorder::find()->where(['in','id',explode(',',$list->order_ids)])->select('order_id')->asArray()->all();

        foreach ($order as $key => $value) {
            $ids[$key] = $value['order_id'];
        }

        $transaction= SendOrder::getDb()->beginTransaction();
        try {
            $arr_l = $list->save();
            $lists = AppSendorder::updateAll(['order_state'=>2],['and',['in', 'id', explode(',',$list->order_ids)],['<>','order_state',3]]);
            $bulk = AppBulk::updateAll(['orderstate'=>9],['in','id',[$ids]]);

            if ($bulk && $lists && $arr_l){
                $transaction->commit();
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                return $this->resultInfo($data);
            }
        }catch(\Exception $e){
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }
}
