<?php
namespace app\modules\api\controllers;

use app\models\AppBulk;
use app\models\AppPayment;
use app\models\AppPickCarriage;
use app\models\AppPickorder;
use app\models\PickOrder;
use Yii;


/**
 * Default controller for the `api` module
 */
class PickorderController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex(){
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
        $list = AppPickorder::find()
            ->alias('v')
            ->select(['v.*', 'c.orderstate','c.paystate','c.line_type','c.remark','c.shiftid','e.start_time','c.temperture','c.volume','c.weight','c.number','c.number1'])
            ->leftJoin('app_bulk c', 'v.order_id=c.id')
            ->leftJoin('app_line e','c.shiftid = e.id');
//            ->leftJoin('app_customer b','c.customer_id=b.id');

        if ($begintime && $endtime) {
            $list->andWhere(['between', 'v.create_time', $begintime, $endtime]);
        }
        if ($status) {
            $list->andWhere(['v.state' => $status]);
        }
        $list->andWhere(['v.group_id' => $group_id]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy([new \yii\db\Expression('FIELD (order_state, 1, 2, 3)'),'v.create_time' => SORT_DESC])
            ->asArray()
            ->all();

        foreach ($list as $key => $value){
            $list[$key]['startstr_pick'] = json_decode($value['startstr_pick'],true);
            $list[$key]['endstr_pick'] = json_decode($value['endstr_pick'],true);
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
        $list = AppPickorder::find()
            ->alias('v')
            ->select(['v.*', 'c.orderstate','c.paystate','c.line_type','c.remark','c.shiftid','e.start_time','c.temperture','c.volume','c.weight','c.number','c.number1'])
            ->leftJoin('app_bulk c', 'v.order_id=c.id')
            ->leftJoin('app_line e','c.shiftid = e.id')
            ->where(['in','v.id',$ids])
            ->asArray()
            ->all();

        foreach ($list as $k => $v) {
            $list[$k]['startstr_pick'] = json_decode($v['startstr_pick'],true);
            $list[$k]['endstr_pick'] = json_decode($v['endstr_pick'],true);
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list]);
        return $this->resultInfo($data);

    }

    /*
     *合单
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

        $list = AppPickorder::find()->where(['in','id',$ids])->andWhere(['group_id'=>$group_id])->asArray()->all();
        $volume =$this->get_params($ids,'pick_volume',1);
        $weight = $this->get_params($ids,'pick_weight',1);
        $number = $this->get_params($ids,'pick_number',1);
        $number1 = $this->get_params($ids,'pick_number1',1);
        $startstr = $endstr = $temperture = [];

        foreach ($list as $key =>$value){

            $startstr = array_merge($startstr,json_decode($value['startstr_pick'],true));
            $endstr = array_merge($endstr,json_decode($value['endstr_pick'],true));
            $order['startcity'] = $value['startcity'];
            $order['endcity']   = $value['endcity'];
//            array_push($temperture,$value['temperture']);

        }
        $startstr = array_unique($startstr,SORT_REGULAR);
        $endstr = array_unique($endstr,SORT_REGULAR);
//        $temperture = array_unique($temperture,SORT_REGULAR);
        $transaction= PickOrder::getDb()->beginTransaction();
        try {
            $pick_order = new PickOrder();
            $pick_order->startcity = $order['startcity'];
            $pick_order->endcity = $order['endcity'];
            $pick_order->startstr = json_encode($startstr,JSON_UNESCAPED_UNICODE);
            $pick_order->endstr = json_encode($endstr,JSON_UNESCAPED_UNICODE);
            $pick_order->order_ids = implode(',',$ids);
            $pick_order->group_id = $group_id;
            $pick_order->weight  = $weight;
            $pick_order->number = $number;
            $pick_order->number1 = $number1;
            $pick_order->volume = $volume;
            $pick_order->price = $price;
            $pick_order->type = $type;
            $pick_order->ordertype = 1;
            $arr = $pick_order->save();
            $res = $carriage = true;
            switch ($type) {
                case '1':
                    foreach ($carriage_info as $key => $value) {
                        $pick_list['pick_id'] = $pick_order->id;
                        $pick_list['group_id'] = $user->group_id;
                        $pick_list['create_user_id'] = $user->id;
                        $pick_list['carriage_price'] = $value['price'];
                        $pick_list['type'] = $type;
                        $pick_list['contant'] = $value['contant'];
                        $pick_list['carnumber'] = $value['carnumber'];
                        $pick_list['tel'] = $value['tel'];
                    $pick_list['startstr'] = json_encode($startstr,JSON_UNESCAPED_UNICODE);
                    $pick_list['endstr'] = json_encode($endstr,JSON_UNESCAPED_UNICODE);
                        $pick_list['create_time'] = $pick_list['update_time'] = date('Y-m-d H:i:s', time());
                        $pick_lists[] = $pick_list;

                        $list_c['order_id'] = $pick_order->id;
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
                        $list_c['type'] = 3;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s', time());
                        $info_c[] = $list_c;
                        $deal_company = '';
                    }
                    $res = Yii::$app->db->createCommand()->batchInsert(AppPickCarriage::tableName(), ['pick_id', 'group_id', 'create_user_id', 'carriage_price', 'type', 'contant', 'carnumber', 'tel','startstr','endstr', 'create_time', 'update_time'], $pick_lists)->execute();
                    $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id', 'driver_name', 'driver_car', 'driver_tel', 'pay_type', 'type', 'create_time', 'update_time'], $info_c)->execute();
                    break;
                case '2':
                    foreach ($carriage_info as $key => $value) {

                        $pick_list['pick_id'] = $pick_order->id;
                        $pick_list['group_id'] = $user->group_id;
                        $pick_list['create_user_id'] = $user->id;
                        $pick_list['carriage_price'] = $value['price'];
                        $pick_list['type'] = $type;
                        $pick_list['deal_company'] = $value['id'];
                        $pick_list['contant'] = $value['contant'];
                        $pick_list['carnumber'] = $value['carnumber'];
                        $pick_list['tel'] = $value['tel'];
                    $pick_list['startstr'] = json_encode($startstr,JSON_UNESCAPED_UNICODE);
                    $pick_list['endstr'] = json_encode($endstr,JSON_UNESCAPED_UNICODE);
                        $pick_list['create_time'] = $pick_list['update_time'] = date('Y-m-d H:i:s', time());
                        $pick_lists[] = $pick_list;

                        $list_c['order_id'] = $pick_order->id;
                        $list_c['pay_price'] = $value['price'];
                        $list_c['truepay'] = 0;
                        $list_c['group_id'] = $user->group_id;
                        $list_c['create_user_id'] = $user->id;
                        $list_c['create_user_name'] = $user->name;
                        $list_c['carriage_id'] = $value['id'];
                        $list_c['pay_type'] = 2;
                        $list_c['type'] = 3;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s', time());
                        $info_c[] = $list_c;
                        $deal_company = $value['id'];
                    }
                    $res = Yii::$app->db->createCommand()->batchInsert(AppPickCarriage::tableName(), ['pick_id', 'group_id', 'create_user_id', 'carriage_price', 'type', 'deal_company', 'contant', 'carnumber', 'tel','startstr','endstr', 'create_time', 'update_time'], $pick_lists)->execute();
                    $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id', 'pay_type', 'type', 'create_time', 'update_time'], $info_c)->execute();
                    break;
                case '3':
                    foreach ($carriage_info as $key => $value) {
                        $pick_list['pick_id'] = $pick_order->id;
                        $pick_list['group_id'] = $user->group_id;
                        $pick_list['create_user_id'] = $user->id;
                        $pick_list['carriage_price'] = $value['price'];
                        $pick_list['type'] = $type;
                        $pick_list['contant'] = $value['contant'];
                        $pick_list['carnumber'] = $value['carnumber'];
                        $pick_list['tel'] = $value['tel'];
                        $pick_list['startstr'] = json_encode($startstr,JSON_UNESCAPED_UNICODE);
                        $pick_list['endstr'] = json_encode($endstr,JSON_UNESCAPED_UNICODE);
                        $pick_list['create_time'] = $pick_list['update_time'] = date('Y-m-d H:i:s', time());
                        $pick_lists[] = $pick_list;

                        $list_c['order_id'] = $pick_order->id;
                        $list_c['pay_price'] = $value['price'];
                        $list_c['truepay'] = 0;
                        $list_c['group_id'] = $user->group_id;
                        $list_c['create_user_id'] = $user->id;
                        $list_c['create_user_name'] = $user->name;
                        $list_c['driver_name'] = $value['contant'];
                        $list_c['driver_car'] = $value['carnumber'];
                        $list_c['driver_tel'] = $value['tel'];
                        $list_c['pay_type'] = 3;
                        $list_c['type'] = 3;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s', time());
                        $info_c[] = $list_c;
                        $deal_company = '';
                    }
                    $res = Yii::$app->db->createCommand()->batchInsert(AppPickCarriage::tableName(), ['pick_id', 'group_id', 'create_user_id', 'carriage_price', 'type', 'contant', 'carnumber', 'tel','startstr','endstr', 'create_time', 'update_time'], $pick_lists)->execute();
                    $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'driver_name', 'driver_car', 'driver_tel', 'pay_type', 'type', 'create_time', 'update_time'], $info_c)->execute();
                    break;
                default:
                    break;
            }
            $pick_order->deal_company = $deal_company;
            $pick_order->state = 3;
            if ($type == 2){
                $pick_order->state = 2;
            }
            $res_pick =  $pick_order->save();
            $lists = AppPickorder::updateAll(['state'=>2,'pick_id'=> $pick_order->id],['in', 'id', $ids]);

            if ($arr && $lists && $res && $carriage && $res_pick){
                $transaction->commit();
                $this->hanldlog($user->id,'调度提货订单:');
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
        $list = PickOrder::find();

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

             $pickorder = AppPickorder::find()
                 ->select('a.*,b.shiftid,c.start_time,b.temperture')
                 ->alias('a')
                 ->leftJoin('app_bulk b','a.order_id = b.id')
                 ->leftJoin('app_line c','b.shiftid = c.id')
                 ->where(['in','a.id',explode(',',$value['order_ids'])])
                 ->asArray()
                 ->all();
            if ($pickorder) { 
                foreach($pickorder as $k => $v) {
                    $pickorder[$k]['start_time'] = date('Y-m-d H:i:s',strtotime($v['start_time'])-7200);
                }
            }
             $carriage = AppPickCarriage::find()
                 ->where(['pick_id'=>$value['id']])
                 ->asArray()
                 ->all();
             $list[$key]['pickorder'] = json_encode($pickorder);
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
            'pickorder'=>$pickorder
        ];
        return json_encode($data);
    }

    /*
     * 订单详情
     * */
    public function actionOrder_view(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        $order = PickOrder::find()->where(['id'=>$id])->asArray()->one();
         $list = AppPickorder::find()
             ->select('a.*,b.shiftid,c.start_time,b.temperture')
             ->alias('a')
             ->leftJoin('app_bulk b','a.order_id = b.id')
             ->leftJoin('app_line c','b.shiftid = c.id')
            ->where(['in','a.id',explode(',',$order['order_ids'])])
            ->asArray()
            ->all();
         foreach ($list as $key =>$value){
             $list[$key]['start_time'] = date('Y-m-d H:i:s',strtotime($list[$key]['start_time']) - 3*3600);
         }
         $carriage = AppPickCarriage::find()
             ->alias('a')
             ->select(['a.*,b.name'])
             ->leftJoin('app_carriage b','b.cid = a.id')
             ->where(['a.pick_id'=>$id])
             ->asArray()
             ->all();
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list,'carriage'=>$carriage,'order'=>$order]);
        return $this->resultInfo($data);
    }    

    /*
     * 订单列表
     * */
    public function actionOrder_view_list(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $order_type = $input['order_type'] ?? '';


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
        $order = PickOrder::find()->where(['id'=>$id])->asArray()->one();
        $list = AppPickorder::find()
            ->alias('v')
            ->select(['v.*', 'c.orderstate','c.paystate','c.line_type','c.remark','c.shiftid','e.start_time','c.temperture','c.volume','c.weight','c.number','c.number1','c.goodsname'])
            ->leftJoin('app_bulk c', 'v.order_id=c.id')
            ->leftJoin('app_line e','c.shiftid = e.id');

        if ($order_type) {
            $list->andWhere(['v.order_state' => $order_type]);
        }
        $list->andWhere(['in','v.id',explode(',',$order['order_ids'])]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy([new \yii\db\Expression('FIELD (order_state, 1, 2, 3)'),'v.create_time' => SORT_DESC])
            ->asArray()
            ->all();

        foreach ($list as $key => $value){
            $list[$key]['startstr_pick'] = json_decode($value['startstr_pick'],true);
            $list[$key]['endstr_pick'] = json_decode($value['endstr_pick'],true);
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
     *完成提货
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
        $list = PickOrder::findOne($id);
        if ($list->state == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'请完成调度']);
            return $this->resultInfo($data);
        }
        if ($list->state == 4){
            $data = $this->encrypt(['code'=>400,'msg'=>'请勿重复操作']);
            return $this->resultInfo($data);
        }
        if($list->state == 5){
            $data = $this->encrypt(['code'=>400,'msg'=>'运输中']);
            return $this->resultInfo($data);
        }
        if ($list->state == 9){
            $data = $this->encrypt(['code'=>400,'msg'=>'已取消']);
            return $this->resultInfo($data);
        }
        $list->state = 4;

        $order = AppPickorder::find()->where(['in','id',explode(',',$list->order_ids)])->select('order_id')->asArray()->all();
        foreach ($order as $key => $value){
            $ids[$key] = $value['order_id'];
        }

        $transaction= PickOrder::getDb()->beginTransaction();
        try {
            $res_l = $list->save();
            $lists = AppPickorder::updateAll(['order_state'=>2],['and',['in', 'id', explode(',',$list->order_ids)],['<>','order_state',3]]);
            $bulk = AppBulk::updateAll(['orderstate'=>8],['in','id',[$ids]]);
            if ($bulk && $lists &&$res_l){
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


    /*
     * 取消调度
     * */
    public function actionCancel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $pick_id = $input['pick_id'];
        if (empty($token) || empty($id) || empty($pick_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $pickorder = AppPickorder::findOne($id);
        if ($pickorder->order_state == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'不能取消，订单已完成']);
            return $this->resultInfo($data);
        }
        $pickorder->order_state = 1;
        $pickorder->state = 1;
        $order = PickOrder::find()->where(['id'=>$pick_id])->one();
        $volume =  $order->volume - $pickorder->pick_volume;
        $number = $order->number - $pickorder->pick_number;
        $number1 = $order->number1 - $pickorder->pick_number1;
        $weight = $order->weight - $pickorder->pick_weight;
        $ids = explode(',',$order->order_ids);
        foreach($ids as $key => $value){
            if ($value == $id){
                unset($ids[$key]);
            }
        }
        if(count($ids)<1){
//            $order->delete();
            $payment = AppPayment::find()->where(['order_id'=>$pick_id,'group_id'=>$order->group_id])->one();
            if ($payment){
                $payment->delete();
            }
            $carriage = AppPickCarriage::find()->where(['pick_id'=>$pick_id,'group_id'=>$order->group_id])->one();
            if ($carriage){
                $carriage->delete();
            }
        }
        $ids = implode(',',$ids);
        $order->state = 9;
        $order->order_ids = $ids;
        $order->volume = $volume;
        $order->number = $number;
        $order->number1 = $number1;
        $order->weight = $weight;
        $transaction= PickOrder::getDb()->beginTransaction();
        try {
            $res = $order->save();
            $res_p = $pickorder->save();
            if ($res && $res_p){
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
