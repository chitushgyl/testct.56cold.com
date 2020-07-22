<?php
namespace app\modules\api\controllers;

use app\models\AppBulk;
use app\models\AppCartype;
use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use app\models\AppCustomerAccount;
use app\models\AppGroup;
use app\models\AppLine;
use app\models\AppOrder;
use app\models\AppPayment;
use app\models\AppPickorder;
use app\models\AppReceive;
use app\models\AppSendorder;
use app\models\AppUnusual;
use app\models\AppVehical;
use app\models\Car;
use app\models\Customer;
use Yii;

/**
 * Default controller for the `api` module
 */
class CustomerOrderController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionLogin(){
        $input = Yii::$app->request->post();
        $username = $input['username'];
        $password = $input['password'];
        if (empty($username)){
            $data = $this->encrypt(['code'=>400,'msg'=>'账号不能为空']);
            return $this->resultInfo($data);
        }
        if(empty($password)){
            $data = $this->encrypt(['code'=>400,'msg'=>'密码不能为空']);
            return $this->resultInfo($data);
        }
        $model = AppCustomerAccount::find()
            ->alias('a')
            ->leftJoin('app_customer b','a.customer_id = b.id')
            ->select('a.username,a.group_id,a.customer_id As id,b.all_name,b.delete_flag,b.use_flag')
            ->where(['a.username'=>$username,'a.password'=>md5($password)])
            ->asArray()
            ->one();
        if ($model){
            if($model['delete_flag'] == 'Y' && $model['use_flag'] == 'Y'){
                $data = $this->encrypt(['code'=>200,'msg'=>'登陆成功','data'=>$model]);
                return $this->resultInfo($data);
            }elseif($model['delete_flag'] == 'N' || $model['use_flag'] == 'N'){
                $data = $this->encrypt(['code'=>400,'msg'=>'账号异常']);
                return $this->resultInfo($data);
            }
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'登陆失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 已下订单列表
     * */
    public function actionIndex(){
        $request = Yii::$app->request;
        $input = $request->post();
        $group_id = $input['group_id'];
        $customer_id = $input['customer_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $ordernumber = $input['ordernumber'] ?? '';
        $begintime = $input['begintime'] ?? '';

        $data = [
            'code' => 200,
            'msg' => '',
            'status' => 400,
            'count' => 0,
            'data' => []
        ];
        if (!$customer_id || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $list = AppOrder::find()
            ->alias('v')
            ->select(['v.*', 't.carparame','a.group_name'])
            ->leftJoin('app_cartype t', 'v.cartype=t.car_id')
            ->leftJoin('app_group a','a.id= v.group_id')
            ->where(['v.company_id' => $customer_id,'v.group_id'=>$group_id,'v.delete_flag' => 'Y']);
        if ($ordernumber) {
            $list->andWhere(['like', 'v.ordernumber', $ordernumber]);
        }
        if ($begintime) {
            $time_s = $begintime . ' 00:00:00';
            $time_e = $begintime . ' 23:59:59';
            $list->andWhere(['between', 'v.time_start', $time_s, $time_e]);
        }

        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.time_start' => SORT_DESC])
            ->asArray()
            ->all();
        foreach ($list as $key =>$value){
            $list[$key]['startstr'] = json_decode($value['startstr'],true);
            $list[$key]['endstr'] = json_decode($value['endstr'],true);
            $list[$key]['driverinfo'] = json_decode($value['driverinfo'],true);
        }
        $data = [
            'code' => 200,
            'msg' => '正在请求中...',
            'status' => 200,
            'count' => $count,
            'data' => precaution_xss($list)
        ];
        return json_encode($data);
    }

    public function actionAdd(){
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        $company_id = $input['company_id'];
        $start_time = $input['start_time'];
        $end_time = $input['end_time'] ?? '';
        $cartype = $input['cartype'];
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startstr = $input['startstr'];
        $endstr = $input['endstr'];
        $cargo_name = $input['name'];
        $cargo_number = $input['number'];
        $cargo_number2 = $input['number2'];
        $cargo_weight = $input['weight'];
        $cargo_volume = $input['volume'];
        $remark = $input['remark'];
        $temperture = $input['temperture'];
        $picktype = $input['picktype'] ?? 1;
        $sendtype = $input['sendtype'] ?? 1;
        $price = $input['price'];
        $order_type = $input['order_type'];
        if (empty($group_id) || empty($company_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $order = new AppOrder();
        if (empty($order_type)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择下单类型']);
            return $this->resultInfo($data);
        }

        if (empty($start_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车开始时间不能为空']);
            return $this->resultInfo($data);
        }

        if ($order_type == 3){
            if($cartype == 0){
                $data = $this->encrypt(['code'=>400,'msg'=>'请选择车型']);
                return $this->resultInfo($data);
            }
            if (empty($end_time)){
                $data = $this->encrypt(['code'=>400,'msg'=>'预约用车结束时间不能为空']);
                return $this->resultInfo($data);
            }
        }

        if (empty($temperture)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择温度']);
            return $this->resultInfo($data);
        }

        if (empty($cargo_name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'货品名称不能为空！']);
            return $this->resultInfo($data);
        }
        if (empty($cargo_weight)){
            $data = $this->encrypt(['code'=>400,'msg'=>'重量不能为空！']);
            return $this->resultInfo($data);
        }
        if (empty($cargo_volume)){
            $data = $this->encrypt(['code'=>400,'msg'=>'体积不能为空！']);
            return $this->resultInfo($data);
        }

        if (empty($startcity)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择起始地']);
            return $this->resultInfo($data);
        }
        if (empty($endcity)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择目的地']);
            return $this->resultInfo($data);
        }

        if (empty($startstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'发货地不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($endstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'收货地不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($price)){
            $data = $this->encrypt(['code'=>400,'msg'=>'运费不能为空']);
            return $this->resultInfo($data);
        }

        $order->cartype = $cartype;
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $arr_startstr = json_decode($startstr,true);
        $order->endstr = $endstr;
        $arr_endstr = json_decode($endstr,true);
        $order->company_id = $company_id;
        $order->ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->takenumber = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->name = $cargo_name;
        $order->number = $cargo_number;
        $order->number2 = $cargo_number2;
        $order->weight = $cargo_weight;
        $order->volume = $cargo_volume;
        $order->group_id = $group_id;
        $order->temperture = $temperture;
        $order->remark = $remark;
        $order->picktype = $picktype;
        $order->sendtype = $sendtype;
        $order->time_start = $start_time;//用车时间
        if ($order_type == 3){
            $order->time_end = $end_time;//预计到达时间
        }
        $order->price = $price;
        $order->total_price = $price;
        $order->order_type = $order_type;
        $order->money_state = 'N';
        $res_r = true;
        $transaction= AppOrder::getDb()->beginTransaction();
        try{
            $res =  $order->save();
            $receive = new AppReceive();
            $receive->compay_id = $company_id;
            $receive->receivprice = $order->total_price;
            $receive->trueprice = 0;
            $receive->order_id = $order->id;
            $receive->group_id = $group_id;
            $receive->ordernumber = $order->ordernumber;
            $res_r = $receive->save();
            if ($res_r  && $res){
                $transaction->commit();
                $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
                return $this->resultInfo($data);
            }else{
                $transaction->rollBack();
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
     * 编辑订单
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $group_id = $input['group_id'] ?? '';
        $company_id = $input['company_id'] ?? '';
        $start_time = $input['start_time'];
        $end_time = $input['end_time'];
        $cartype = $input['cartype'];
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startstr = $input['startstr'];
        $endstr = $input['endstr'];
        $cargo_name = $input['name'];
        $cargo_number = $input['number'];
        $cargo_number2 = $input['number2'];
        $cargo_weight = $input['weight'];
        $cargo_volume = $input['volume'];
        $remark = $input['remark'];
        $temperture = $input['temperture'];
        $picktype = $input['picktype'];
        $sendtype = $input['sendtype'];
        $price = $input['price'] ?? 0;
        $order_type = $input['order_type'];
        if (empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $order = AppOrder::findOne($id);
        if (empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        if ($order->order_status == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已调度不可以修改']);
            return $this->resultInfo($data);
        }

        if (empty($start_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车开始时间不能为空']);
            return $this->resultInfo($data);
        }

        if ($order_type == 1 || $order_type == 8 || $order_type == 3 || $order_type == 5){
            if (empty($end_time)){
                $data = $this->encrypt(['code'=>400,'msg'=>'预约用车结束时间不能为空']);
                return $this->resultInfo($data);
            }
            if($cartype == 0){
                $data = $this->encrypt(['code'=>400,'msg'=>'请选择车型']);
                return $this->resultInfo($data);
            }
        }

        if (empty($temperture)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择温度']);
            return $this->resultInfo($data);
        }

        if (empty($cargo_name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'货品名称不能为空！']);
            return $this->resultInfo($data);
        }

        if (empty($startcity)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择起始地']);
            return $this->resultInfo($data);
        }
        if (empty($endcity)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择目的地']);
            return $this->resultInfo($data);
        }

        if (empty($startstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'发货地不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($endstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'收货地不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($price)){
            $data = $this->encrypt(['code'=>400,'msg'=>'运费不能为空']);
            return $this->resultInfo($data);
        }
        $order->cartype = $cartype;
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $order->line_start_contant = $startstr;
        $order->line_end_contant = $endstr;
        $arr_startstr = json_decode($startstr,true);
        $order->endstr = $endstr;
        $arr_endstr = json_decode($endstr,true);
        $order->name = $cargo_name;
        $order->number = $cargo_number;
        $order->number2 = $cargo_number2;
        $order->weight = $cargo_weight;
        $order->volume = $cargo_volume;
        $order->temperture = $temperture;
        if ($company_id){
            $order->company_id = $company_id;
        }
        $order->remark = $remark;
        $order->picktype = $picktype;
        $order->sendtype = $sendtype;
        $order->time_start = $start_time;//用车时间
        $order->time_end = $end_time;//预计到达时间
        $order->price = $price;
        $order->total_price = $price;
        $order->order_type = $order_type;
        $res =  $order->save();
        $receive = AppReceive::find()->where(['order_id'=>$id,'group_id'=>$group_id])->one();
        $receive->receivprice = $order->total_price;
        $receive->trueprice = 0;
        $arr = $receive->save();
        if ($res){
            $data = $this->encrypt(['code'=>200,'msg'=>'修改成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'修改失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 详情
     * */
    public function actionView(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        if ($id) {
            $model = AppOrder::find()->where(['id'=>$id])->asArray()->one();
        } else {
            $model = new AppOrder();
        }
        $car_list = AppCartype::get_list();
        $group_id = $model['group_id'];
        $customer = Customer::get_list($group_id);
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'customer'=>$customer,'group_id'=>$group_id,'car_list'=>$car_list]);
        return $this->resultInfo($data);
    }

    /*
     * 取消下单
     * */
    public function actionCancel(){
         $input = Yii::$app->request->post();
         $id = $input['id'];
         if (empty($id)){
             $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
             return $this->resultInfo($data);
         }
         $order = AppOrder::findOne($id);
         if ($order->line_status == 2){
             $data = $this->encrypt(['code'=>400,'msg'=>'请联系承运公司取消该订单']);
             return $this->resultInfo($data);
         }
         if($order->order_stage != 1){
             $data = $this->encrypt(['code'=>400,'msg'=>'不可以取消该订单']);
             return $this->resultInfo($data);
         }
         $order->order_status = 8;
        $transaction= AppOrder::getDb()->beginTransaction();
        try{
            $res_o = $order->save();
            $receive = AppReceive::find()->where(['order_id'=>$id,'group_id'=>$order->group_id])->one();
            $res_r = $receive->delete();
            if ($res_o && $res_r){
                $transaction->commit();
                $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollback();
            $data = $this->encrypt(['code'=>400,'msg'=>'取消失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     *对账单
     * */
    public function actionWaybill(){
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        $receive_status = $input['receive_status'] ?? '';
        $end_time = $input['end_time'] ?? '';
        $start_time = $input['start_time'] ?? '';
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $customer = $input['customer'];
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (!$group_id){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $list = AppReceive::find()
            ->alias('r')
            ->select(['r.*','g.group_name'])
            ->leftJoin('app_group g','r.group_id=g.id');

        if ($end_time && $start_time) {
            $list->andWhere(['between','r.update_time',$start_time.' 00:00:00',$end_time.' 23:59:59']);
        } else {
            if ($start_time) {
                $list->andWhere(['>=','r.update_time',$start_time.' 00:00:00',$end_time.' 23:59:59']);
            } else if($end_time) {
                $list->andWhere(['<=','r.update_time',$end_time.' 23:59:59']);
            }
        }

        if ($receive_status) {
            $list->andWhere(['r.status'=>$receive_status]);
        }
        $list->andWhere(['r.group_id'=>$group_id,'compay_id'=>$customer]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['r.update_time'=>SORT_DESC])
            ->asArray()
            ->all();
        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'data'  => precaution_xss($list),
        ];
        return json_encode($data);
    }

    /*
     *统计
     * */
    public function actionFinance(){
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        $customer_id = $input['customer_id'];
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
        if (empty($group_id) || empty($customer_id)){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

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
            $receive = AppReceive::find()
                ->select('sum(receivprice),sum(trueprice)')
                ->where(['group_id'=>$group_id,'compay_id'=>$customer_id])
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
            'data'  => precaution_xss($res)
        ];
        return json_encode($data);
    }

    /*
     * 零担
     * */
    public function actionList(){
        $request = Yii::$app->request;
        $input = $request->post();
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $line_city = $input['line_city'] ?? '';
        $line_start_city = $input['line_start_city'] ?? '';
        $line_end_city = $input['line_end_city'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (!$group_id){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $list = AppLine::find();
        if ($line_start_city) {
            $list->andWhere(['like','startcity',$line_start_city]);
        }          
        if ($line_end_city) {
            $list->andWhere(['like','endcity',$line_end_city]);
        }          

        if ($begintime) {
            $time_y = $begintime . ' 00:00:00';
            $time_n = $begintime . ' 23:59:59';
            $list->andWhere(['between','start_time',$time_y,$time_n]);
        }        

        $list->andWhere(['group_id'=>$group_id,'delete_flag'=>'Y','state'=>1]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['update_time'=>SORT_DESC])
            ->asArray()
            ->all();

        foreach ($list as $k => $v) {
            $begin_store = json_decode($v['begin_store'],true);
            $end_store = json_decode($v['end_store'],true);
            $transfer_info = json_decode($v['transfer_info'],true);
            
            $list[$k]['weight_price'] = json_decode($v['weight_price'],true);
            $list[$k]['begin_store'] = $begin_store;
            $list[$k]['end_store'] = $end_store;
            $list[$k]['transfer_info'] = $transfer_info;

             $list[$k]['begin_store_pro'] = $begin_store[0]['pro']. ' '. $begin_store[0]['city'] . ' ' . $begin_store[0]['area'];
            $list[$k]['begin_store_info'] = $begin_store[0]['info'];

            $list[$k]['end_store_pro'] = $end_store[0]['pro']. ' '. $end_store[0]['city'] . ' ' . $end_store[0]['area'];
            $list[$k]['end_store_info'] = $end_store[0]['info']; 

            if ($transfer_info[0]['pro']) {
                $list[$k]['transfer_pro'] = $transfer_info[0]['pro']. ' '. $transfer_info[0]['city'] . ' ' . $transfer_info[0]['area'];
                $list[$k]['transfer_info'] = $transfer_info[0]['info'];
            } else {
                $list[$k]['transfer_pro'] = '';
                $list[$k]['transfer_info'] = '';
            } 

        }
        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }

    /*
     * 零担下单
     * */
    public function actionBulk_add(){
        $input = Yii::$app->request->post();
        $shiftid = $input['shiftid'];
        $weight = $input['weight'];
        $volume = $input['volume'];
        $number = $input['number'] ?? 0;
        $number1 = $input['number1'] ?? 0;
        $goodsname = $input['goodsname'];
        $temperture = $input['temperture'];
        $lineprice = $input['line_price'];
        $pickprice = $input['pickprice'] ?? 0;
        $sendprice = $input['sendprice'] ?? 0;
        $picktype = $input['picktype'];
        $sendtype = $input['sendtype'];
        $begin_info = $input['begin_info'] ?? '';
        $end_info = $input['end_info'] ?? '';
        $group_id = $input['group_id'];
        $remark  =  $input['remark'] ?? '';
        $customer_id = $input['customer_id'];
        $pay_state = $input['pay_state'];//结算方式
        if (empty($shiftid) || empty($group_id)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($weight)){
            $data = $this->encrypt(['code' => '400', 'msg' => '重量不能为空']);
            return $this->resultInfo($data);
        }
        if(empty($volume)){
            $data = $this->encrypt(['code' => '400', 'msg' => '体积不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($goodsname)){
            $data = $this->encrypt(['code' => '400', 'msg' => '物品名称不能为空']);
            return $this->resultInfo($data);
        }
        if (empty($lineprice)){
            $data = $this->encrypt(['code' => '400', 'msg' => '干线价格不能为空']);
            return $this->resultInfo($data);
        }

        $bulk = new AppBulk();
        $line = AppLine::findOne($shiftid);
        $bulk->customer_id = $customer_id;
        $bulk->ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $bulk->begincity = $line->startcity;
        $bulk->endcity = $line->endcity;
        $bulk->goodsname = $goodsname;
        $bulk->number = $number;
        $bulk->number1 = $number1;
        $bulk->weight = $weight;
        $bulk->volume = $volume;
        $bulk->temperture = $temperture;
        $bulk->lineprice = $lineprice;
        $bulk->shiftid = $shiftid;

        $bulk->pickprice = $pickprice;
        if ($picktype == 1) {
            $bulk->begin_info = $begin_info;
        } else {
            $bulk->begin_info = $line->begin_store;
        }
        $bulk->sendprice = $sendprice;
        if ($sendtype == 1) {
            $bulk->end_info = $end_info;
        } else {
            $bulk->end_info = $line->end_store;
        }

        $bulk->picktype = $picktype;
        $bulk->sendtype = $sendtype;
        $bulk->group_id = $group_id;
        $bulk->create_user_id = '';
        $bulk->remark = $remark;
        $bulk->pay_state = $pay_state;
        $bulk->total_price = $lineprice + $bulk->pickprice + $bulk->sendprice;
        $bulk->line_type = 3;
        $res = $bulk->save();
        if ($picktype == 1) {
            $pickorder = new AppPickorder();
            $pickorder->order_id = $bulk->id;
            $pickorder->startcity = $line->startcity;
            $pickorder->endcity = $line->startcity;
            $pickorder->startstr_pick = $begin_info;
            $pickorder->endstr_pick = $line->begin_store;
            $pickorder->goodsname = $goodsname;
            $pickorder->pick_volume = $volume;
            $pickorder->pick_number = $number;
            $pickorder->pick_weight = $weight;
            $pickorder->temperture = $temperture;
            $pickorder->pick_number1 = $number1;
            $pickorder->pick_price = $bulk->pickprice;
            $pickorder->group_id = $group_id;
            $res_p = $pickorder->save();
        }
        if ($sendtype == 1) {
            $sendorder = new AppSendorder();
            $sendorder->order_id = $bulk->id;
            $sendorder->startcity = $line->endcity;
            $sendorder->endcity = $line->endcity;
            $sendorder->startstr_send = $line->end_store;
            $sendorder->endstr_send = $end_info;
            $sendorder->goodsname = $goodsname;
            $sendorder->send_volume = $volume;
            $sendorder->send_number = $number;
            $sendorder->send_number1 = $number1;
            $sendorder->send_weight = $weight;
            $sendorder->temperture = $temperture;
            $sendorder->send_price = $bulk->sendprice;
            $sendorder->group_id = $group_id;
            $res_s = $sendorder->save();
        }

        if ($customer_id) {
            $receive = new AppReceive();
            $time = date('Y-m-d H:i:s',time());
            $receive->compay_id = $customer_id;
            $receive->receivprice = $bulk->total_price;
            $receive->trueprice = 0;
            $receive->order_id = $bulk->id;
            $receive->receive_info = '';
            $receive->create_user_id = '';
            $receive->create_user_name = '';
            $receive->group_id = $group_id;
            $receive->create_time = $time;
            $receive->update_time = $time;
            $receive->type = 2;
            $arr = $receive->save();
        }

        if ($res){
            $data = $this->encrypt(['code'=>'200','msg'=>'下单成功','data'=>$bulk->id]);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'下单失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     *已下零担订单列表
     * */
    public function actionBulk_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $group_id = $input['group_id'];
        $customer_id = $input['customer_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $ordernumber = $input['name'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $endtime = $input['endtime'] ?? '';
        $line_city = $input['line_city'] ?? '';
        $status = $input['orderstate'] ?? '';

        $data = [
            'code' => 200,
            'msg' => '',
            'status' => 400,
            'count' => 0,
            'data' => []
        ];
        if (!$customer_id || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $list = AppBulk::find()
            ->alias('a')
            ->select(['a.*','b.start_time','b.trunking','b.begin_store','b.end_store','b.transfer_info','b.state','b.group_id','c.group_name','u.content'])
            ->leftJoin('app_line b','a.shiftid = b.id')
            ->leftJoin('app_group c','b.group_id = c.id')
            ->leftJoin('app_unusual u','a.id = u.orderid');
        if ($line_city) {
            $list->andWhere(['like','a.begincity',$line_city])->orWhere(['like','a.endcity',$line_city]);
        }
        if ($ordernumber) {
            $list->andWhere(['like', 'a.ordernumber', $ordernumber]);
        }
        if ($begintime && $endtime) {
            $list->andWhere(['between', 'a.create_time', $begintime, $endtime]);
        }
        if ($status) {
            $list->andWhere(['orderstate' => $status]);
        }
        $list->andWhere(['a.group_id' => $group_id,'a.customer_id'=>$customer_id]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['a.update_time' => SORT_DESC])
            ->asArray()
            ->all();

        foreach ($list as $k => $v) {
            $list[$k]['begin_store'] = json_decode($v['begin_store'],true);
            $list[$k]['end_store'] = json_decode($v['end_store'],true);
            $list[$k]['begin_info'] = json_decode($v['begin_info'],true);
            $list[$k]['end_info'] = json_decode($v['end_info'],true);
            $list[$k]['transfer_info'] =json_decode($v['transfer_info'],true);
        }

        $data = [
            'code' => 200,
            'msg' => '正在请求中...',
            'status' => 200,
            'count' => $count,
            'data' => precaution_xss($list)
        ];
        return json_encode($data);
    }


    /*
     * 零担订单详情
     * */
    public function actionBulk_view(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        if ($id) {
            $model = AppBulk::find()
                ->alias('a')
                ->select(['a.*','b.begin_store','b.start_time','b.group_id','b.end_store','b.transfer_info','c.group_name'])
                ->leftJoin('app_line b','a.shiftid=b.id')
                ->leftJoin('app_group c','b.group_id = c.id')
                ->where(['a.id'=>$id])->asArray()->one();
            $model['begin_info'] = json_decode($model['begin_info'],true);
            $model['end_info'] = json_decode($model['end_info'],true);
            $receipt = $model['receipt'];
            if ($receipt && count(json_decode($receipt,true)) >= 1) {
                $model['receipt'] = json_decode($model['receipt'],true);
            } else {
                $model['receipt'] = '';
            }
        } else {
            $model = new AppBulk();
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model]);
        return $this->resultInfo($data);
    }




}
