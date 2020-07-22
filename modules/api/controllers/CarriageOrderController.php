<?php
namespace app\modules\api\controllers;

use app\models\AppBulk;
use app\models\AppCarriageAccount;
use app\models\AppCarriageList;
use app\models\AppCartype;
use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use app\models\AppGroup;
use app\models\AppLine;
use app\models\AppLineLog;
use app\models\AppList;
use app\models\AppMegerOrder;
use app\models\AppOrder;
use app\models\AppOrderCarriage;
use app\models\AppPayment;
use app\models\AppPickCarriage;
use app\models\AppPickorder;
use app\models\AppReceive;
use app\models\AppVehical;
use app\models\AppUnusual;
use app\models\Car;
use app\models\Carriage;
use app\models\Customer;
use app\models\PickOrder;
use Yii;

/**
 * Default controller for the `api` module
 */
class CarriageOrderController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     * 承运商登陆
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
        $model = AppCarriageAccount::find()
            ->alias('a')
            ->leftJoin('app_carriage b','a.carriage_id = b.cid')
            ->select('a.username,a.group_id,a.carriage_id As id,b.name all_name,b.delete_flag,b.use_flag')
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
     *已接订单
     * */
    public function actionList(){
        $request = Yii::$app->request;
        $input = $request->post();
        $group_id = $input['group_id'];
        $carriage_id = $input['carriage_id'];
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
        if (empty($carriage_id) || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $list = AppMegerOrder::find()
            ->alias('v')
            ->select(['v.*', 't.carparame','b.name'])
            ->leftJoin('app_cartype t', 'v.cartype=t.car_id')
            ->leftJoin('app_carriage b','v.deal_company = b.cid')
            ->where(['v.deal_company' => $carriage_id,'v.group_id'=>$group_id]);
        if ($ordernumber) {
            $list->andWhere(['like', 'v.ordernumber', $ordernumber]);
        }

        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.create_time' => SORT_DESC])
            ->asArray()
            ->all();
        foreach($list as $key => $value){
            $list[$key]['startstr'] = json_decode($value['startstr'],true);
            $list[$key]['endstr'] = json_decode($value['endstr'],true);
            $list[$key]['driverinfo'] = json_decode($value['driverinfo'],true);
            $list[$key]['count'] = count(explode(',',$value['order_ids']));
            if ($value['type'] != 2){
                $driver_info = json_decode($value['driverinfo'],true);
                if ($driver_info) {
                    foreach ($driver_info as $k => $v){
                        $car_info[] = $driver_info[$k]['carnumber'];
                    }
                }
                $list[$key]['car_info'] = $car_info;
            }
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
     * 详细订单列表
     * */
    public function actionMeger_order(){
        $request = Yii::$app->request;
        $input = $request->post();
        $group_id = $input['group_id'];
        $carriage_id = $input['carriage_id'];
        $ids = $input['ids'];
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
        if (empty($carriage_id) || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $list = AppOrder::find()
            ->alias('v')
            ->select(['v.*', 't.carparame'])
            ->leftJoin('app_cartype t', 'v.cartype=t.car_id')
            ->where(['v.group_id' => $group_id])
            ->andWhere(['in','id',explode(',',$ids)]);
        if ($ordernumber) {
            $list->andWhere(['like', 'v.ordernumber', $ordernumber]);
        }

        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.create_time' => SORT_DESC])
            ->asArray()
            ->all();
        foreach ($list as $key => $value){
            $list[$key]['startstr'] = json_decode($value['startstr'],true);
            $list[$key]['endstr'] = json_decode($value['endstr'],true);
            $list[$key]['line_start_contant'] = json_decode($value['line_start_contant'],true);
            $list[$key]['line_end_contant'] = json_decode($value['line_end_contant'],true);
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
     * 订单确认
     * */
     public function actionVehical_done(){
         $input = Yii::$app->request->post();
         $id = $input['id'];
         $group_id = $input['group_id'];
         $carriage_id = $input['carriage_id'];
         $carriage_info = json_decode($input['carriage_info'],true);
         if (empty($id)){
             $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
             return $this->resultInfo($data);
         }
         $order = AppMegerOrder::findOne($id);
         if ($order->carriage_state == 2){
             $data = $this->encrypt(['code'=>400,'msg'=>'订单已确认']);
             return $this->resultInfo($data);
         }
         $list = [];
         foreach($carriage_info as $key =>$value){
              $list['contant'] = $value['contant'];
              $list['carnumber'] = $value['carnumber'];
              $list['tel'] = $value['tel'];
         }
         $carriage_list = AppOrderCarriage::find()->where(['pick_id'=>$id,'group_id'=>$group_id])->one();

         $carriage_list->contant = $list['contant'];
         $carriage_list->carnumber = $list['carnumber'];
         $carriage_list->tel = $list['tel'];
         $order->carriage_state = 2;
         $order->driverinfo = json_encode($carriage_info,JSON_UNESCAPED_UNICODE);
         $order->state = 3;
         $transaction= AppMegerOrder::getDb()->beginTransaction();
         try {
             $res = $order->save();
             $carriage_list->save();
             $transaction->commit();
             $data = $this->encrypt(['code'=>200,'msg'=>'确认接单']);
             return $this->resultInfo($data);
         }catch(\Exception $e){
             $transaction->rollBack();
             $data = $this->encrypt(['code'=>400,'msg'=>'确认失败']);
             return $this->resultInfo($data);
         }
     }


    /*
     * 列表下的订单
     * */
    public function actionLine_order(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;

        $data = [
            'code' => 200,
            'msg' => '',
            'status' => 400,
            'count' => 0,
            'data' => []
        ];
        $line = AppLine::find()->where(['id'=>$id])->asArray()->one();
        $order = AppBulk::find()->where(['shiftid'=>$id,'group_id'=>$group_id]);

        $count = $order->count();
        $order = $order->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['update_time' => SORT_DESC])
            ->asArray()
            ->all();

        foreach ($order as $k => $v) {
            $order[$k]['begin_info'] = json_decode($v['begin_info'],true);
            $order[$k]['end_info'] = json_decode($v['end_info'],true);
            $order[$k]['deplay'] = 1;//1显示 2不显示
            if ($v['paystate'] == 1 && $v['line_type'] == 2){
                $order[$k]['deplay'] = 2;
            }
        }

        $data = [
            'code' => 200,
            'msg' => '正在请求中...',
            'status' => 200,
            'count' => $count,
            'line' => $line,
            'data' => precaution_xss($order)
        ];
        return json_encode($data);
    }

    /*
     * 订单详情
     * */
    public function actionOrder_view(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $carriage_id = $input['carriage_id'];
        $id = $input['id'];
        if (empty($group_id) ||empty($carriage_id) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        if ($id) {
            $model = AppOrder::find()->where(['id'=>$id])->asArray()->one();
        } else {
            $model = new AppOrder();
        }
        $car_list = AppCartype::get_list();

        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'group_id'=>$group_id,'car_list'=>$car_list]);
        return $this->resultInfo($data);
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
        $carriage_id = $input['carriage_id'];
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

        $list = AppPayment::find()
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
        $list->andWhere(['r.group_id'=>$group_id,'carriage_id'=>$carriage_id]);
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
        $carriage_id = $input['carriage_id'];
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
        if (empty($group_id) || empty($carriage_id)){
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
            $receive = AppPayment::find()
                ->select('sum(pay_price),sum(truepay)')
                ->where(['group_id'=>$group_id,'carriage_id'=>$carriage_id])
                ->andWhere(['between','create_time',$starttime_sel,$endtime_sel])
                ->asArray()
                ->one();
            if (!$receive['sum(pay_price)']){
                $receive['sum(pay_price)'] = '0.00';
            }
            if (!$receive['sum(truepay)']){
                $receive['sum(truepay)'] = '0.00';
            }
            $arr = [];
            $arr['time'] =  date('m/d',strtotime($time));
            $arr['pay_price'] = $receive['sum(pay_price)'];
            $arr['truepay'] = $receive['sum(truepay)'];
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
    * 添加车辆
    * */
    public function actionCar_add(){
        $request = \Yii::$app->request;
        $input = $request->post();
        $carnumber = $input['carnumber'];//车牌号
        $cartype = $input['cartype'] ? $input['cartype'] : 1;//车型
        $group_id = $input['group_id'];//所属公司ID
        $control = $input['control'];//温度
        $check_time = $input['check_time'];// 验车时间
        $board_time = $input['board_time'];//注册日期
        $driver_name = $input['driver_name'];//司机名称
        $mobile = $input['mobile'];//手机
        $weight = $input['weight'];//承重
        $volam = $input['volam'];//体积
        $state = $input['state'];//状态
        $remark = $input['remark'];//备注
        $carriage_id = $input['carriage_id'];
        if (empty($carriage_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误！']);
            return $this->resultInfo($data);
        }
        if (empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        if (empty($carnumber)){
            $data = $this->encrypt(['code'=>400,'msg'=>'车牌号不能为空！']);
            return $this->resultInfo($data);
        }
        $carriage = Carriage::findOne($carriage_id);
        $group = AppGroup::find()->where(['id'=>$group_id])->one();
        if ($group->level_id == 1){
            $has_car = Car::find()->where(['group_id'=>$group_id])->one();
            if ($has_car){
                $data = $this->encrypt(['code'=>400,'msg'=>'已添加车辆，请勿重复添加']);
                return $this->resultInfo($data);
            }
        }
        $time = date('Y-m-d H:i:s',time());
        $model = new Car();
        $model->carnumber = $carnumber;
        $model->cartype = $cartype;
        $model->group_id = $carriage->id;
        $model->control = $control;
        $model->check_time = $check_time;
        $model->create_name = $carriage->name;
        $model->create_id = $carriage->id;
        $model->board_time = $board_time;
        $model->driver_name = $driver_name;
        $model->mobile = $mobile;
        $model->weight = $weight;
        $model->volam = $volam;
        $model->state = $state;
        $model->type = 2;
        $model->remark = $remark;
        $model->create_time = $time;
        $model->update_time = $time;
        $res = $model->save();
        if ($res){
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 车辆列表
     * */
    public function actionCar_index(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $carriage_id = $input['carriage_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $carnumber = $input['carnumber'] ?? '';
        $use_flag = $input['use_flag'] ?? '';
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (empty($carriage_id) || empty($group_id)){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $list = Car::find()
            ->alias('c')
            ->select(['c.*','t.carparame'])
            ->leftJoin('app_cartype t','c.cartype=t.car_id')
            ->where(['c.delete_flag'=>'Y','type'=>2,'group_id'=>$carriage_id]);
        if ($carnumber) {
            $list->andWhere(['like','c.carnumber',$carnumber])->orWhere(['like','c.driver_name',$carnumber])->orWhere(['like','c.mobile',$carnumber]);
        }
        if ($use_flag) {
            $list->andWhere(['c.use_flag'=>$use_flag]);
        }
        if ($group_id) {
            $list->andWhere(['c.group_id'=>$group_id]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['c.update_time'=>SORT_DESC])
            ->asArray()
            ->all();
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
     * 删除车辆
     * */
    public function actionDelete_car(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $model = Car::find()->where(['id'=>$id])->one();
        $model->delete_flag = 'N';
        $res = $model->save();
        if ($res){
            $data = $this->encrypt(['code'=>200,'msg'=>'删除成功']);
            return $this->resultInfo($data);
        }
        $data = $this->encrypt(['code'=>400,'msg'=>'删除失败']);
        return $this->resultInfo($data);
    }

    /*
     * 线路模型列表
     * */
    public function actionLine_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $city = $input['city'] ?? '';
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $carriage_id = $input['carriage_id'];
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (empty($carriage_id) || !$group_id){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $list = AppLineLog::find();

        if ($city) {
            $list->orWhere(['like','startcity',$city])
                ->orWhere(['like','endcity',$city])
                ->orWhere(['like','centercity',$city]);
        }

        $list->andWhere(['group_id'=>$group_id,'delete_flag'=>'Y','carriage_id'=>$carriage_id]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['update_time'=>SORT_DESC])
            ->asArray()
            ->all();
        $arr = ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'];

        foreach ($list as $k => $v) {
            $week = json_decode($v['time_week']);
            $arr_week = [];
            foreach ($week as $key => $value) {
                $arr_week[] = $arr[$value];
            }
            $list[$k]['week'] = implode(',',$arr_week);

            $list[$k]['set_price'] = json_decode($v['weight_price'],true);
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
     * 添加线路模板
     * */
    public function actionAdd_line(){
        $input = Yii::$app->request->post();
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startarea = $input['startarea'] ?? '';
        $endarea = $input['endarea'] ?? '';
        $begin_store = $input['begin_store'];//起始仓地址
        $end_store = $input['end_store'];//目的仓地址
        $picktype = $input['picktype'];
        $sendtype = $input['sendtype'];
        $trunking = $input['trunking'];//时效
        $all_weight = $input['all_weight'];
        $all_volume = $input['all_volume'];
        $weight_price = $input['weight_price'];
        $freepick = $input['freepick'] ?? '';
        $time_week  = $input['time_week'];
        $group_id = $input['group_id'];
        $line_price = $input['line_price'];//干线最低收费
        $temperture = $input['temperture'] ?? '';
        $centercity = $input['centercity'] ?? '';
        $carriage_id = $input['carriage_id'];
        $time = $input['time'];
        if (empty($carriage_id) || !$group_id){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if(empty($startcity)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选起始地']);
            return $this->resultInfo($data);
        }
        if(empty($endcity)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选目的地']);
            return $this->resultInfo($data);
        }
        if (empty($time)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请选择发车时间']);
            return $this->resultInfo($data);
        }
        if (empty($begin_store)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选始发仓库地址/临时停靠点']);
            return $this->resultInfo($data);
        }
        if (empty($end_store)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选目的仓库地址/临时停靠点']);
            return $this->resultInfo($data);
        }
        if (empty($time_week)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写发车周期']);
            return $this->resultInfo($data);
        }
        if (empty($weight_price)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写重量区间价格']);
            return $this->resultInfo($data);
        }
        if (empty($line_price)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写干线最低收费']);
            return $this->resultInfo($data);
        }
        $model = new AppLineLog();
        $model->startcity = $startcity;
        $model->endcity = $endcity;
        $model->startarea = $startarea;
        $model->endarea = $endarea;
        $model->begin_store  = $begin_store;
        $model->end_store = $end_store;
        if ($picktype == 1){
            $model->pickprice = $input['pickprice'];
        }else{
            $model ->pickprice = 0;
        }
        if ($sendtype == 1){
            $model->sendprice = $input['sendprice'];
        }else{
            $model->sendprice = 0;
        }
        $model->line_price = $line_price;
        $model->weight_price = $weight_price;
        $model->time = $time;
        $model->picktype = $picktype;
        $model->sendtype = $sendtype;
        $model->time_week = $time_week;
        $model->freepick = $freepick;
        $model->group_id = $group_id;
        $model->all_weight = $all_weight;
        $model->all_volume = $all_volume;
        $model->temperture = $temperture;
        $model->trunking = $trunking;
        $model->centercity = $centercity;
        $model->center_store = $input['center_store'] ?? '';
        $model->carriage_id = $carriage_id;
        $model->expire_time = time()+7*24*3600;
        $res = $model->save();
        if ($res){
//            $this->line_auto($model->id,'add');
            $data = $this->encrypt(['code'=>'200','msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }

    private function line_auto($id,$type){
        if ($type == 'edit'){
            $line_a = AppLine::find()->where(['line_id'=>$id,'delete_flag'=>'Y'])->all();
            foreach ($line_a as $k =>$v){
                $line_b = AppLine::findOne($v['id']);
                $line_b->delete_flag = 'N';
                $line_b ->save();
            }
        }
        $list = AppLineLog::find()->where(['use_flag'=>'Y','delete_flag'=>'Y','id'=>$id])->asArray()->one();
        if (!$list){
            return false;
        }

        $time_week = json_decode($list['time_week']);
        foreach ($time_week as $k => $v){
            $time = $this->getTimeFromWeek($v);
            $time1 = date('Y-m-d'.' '.$list['time'],$time);
            $line = new AppLine();
            $line->startcity = $list['startcity'];
            $line->endcity = $list['endcity'];
            $line->startarea = $list['startarea'];
            $line->endarea = $list['endarea'];
            $line->line_price = $list['line_price'];
            $line->group_id = $list['group_id'];
            $line->trunking = $list['trunking'];
            $line->picktype = $list['picktype'];
            $line->sendtype = $list['sendtype'];
            $line->begin_store = $list['begin_store'];
            $line->end_store = $list['end_store'];
            $line->pickprice = $list['pickprice'];
            $line->sendprice = $list['sendprice'];
            $line->start_time = $time1;
            $line->arrive_time = date('Y-m-d H:i:s',(strtotime($time1) + $list['trunking']*24*3600));
            $line->all_volume = $list['all_volume'];
            $line->all_weight = $list['all_weight'];
            $line->weight_price = $list['weight_price'];
            $line->transfer = $list['centercity'];
            $line->create_user_id = $list['create_user_id'];
            $line->transfer_info = $list['center_store'];
            $line->line_id = $list['id'];
            $line->carriage_id = $list['carriage_id'];
            //获取最低单价
            $price = json_decode($list['weight_price'],true);
            foreach($price as $kk =>$vv){
                $price_a[] = $vv['price'];
            }
            $line->price = min($price_a);
            $line->eprice = min($price_a)*1000/2.5;
            $res = $line->save();
            if ($res){
                $line_e = AppLineLog::findOne($list['id']);
                $line_e->line_state = 2;
                $line_e->save();
                $this->hanldlog($list['create_user_id'],'生成线路'.$line->id.$line->startcity.'->'.$line->endcity);
            }else{
                continue;
            }
        }

    }

    /*
     * 修改模型
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startarea = $input['startarea'] ?? '';
        $endarea = $input['endarea'] ?? '';
        $begin_store = $input['begin_store'];//起始仓地址
        $end_store = $input['end_store'];//目的仓地址
        $picktype = $input['picktype'];
        $sendtype = $input['sendtype'];
        $trunking = $input['trunking'];//时效
        $all_weight = $input['all_weight'];
        $all_volume = $input['all_volume'];
        $weight_price = $input['weight_price'];
        $freepick = $input['freepick'] ?? '';
        $time_week  = $input['time_week'];
        $group_id = $input['group_id'];
        $line_price = $input['line_price'];//干线最低收费
        $temperture = $input['temperture'] ?? '';
        $centercity = $input['centercity'] ?? '';
        $time = $input['time'];
        $carriage_id = $input['carriage_id'];
        if (empty($carriage_id) || !$group_id || !$id){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if(empty($startcity)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选起始地']);
            return $this->resultInfo($data);
        }
        if(empty($endcity)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选目的地']);
            return $this->resultInfo($data);
        }
        if (empty($time)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请选择发车时间']);
            return $this->resultInfo($data);
        }
        if (empty($begin_store)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选始发仓库地址/临时停靠点']);
            return $this->resultInfo($data);
        }
        if (empty($end_store)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选目的仓库地址/临时停靠点']);
            return $this->resultInfo($data);
        }
        if (empty($time_week)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写发车周期']);
            return $this->resultInfo($data);
        }
        if (empty($weight_price)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写重量区间价格']);
            return $this->resultInfo($data);
        }
        $model = AppLineLog::findOne($id);
        $model->startcity = $startcity;
        $model->endcity = $endcity;
        $model->startarea = $startarea;
        $model->endarea = $endarea;
        $model->begin_store  = $begin_store;
        $model->end_store = $end_store;
        if ($picktype == 1){
            $model->pickprice = $input['pickprice'];
        }else{
            $model ->pickprice = 0;
        }
        if ($sendtype == 1){
            $model->sendprice = $input['sendprice'];
        }else{
            $model->sendprice = 0;
        }
        $model->line_price = $line_price;
        $model->weight_price = $weight_price;
        $model->time = $time;
        $model->picktype = $picktype;
        $model->sendtype = $sendtype;
        $model->time_week = $time_week;
        $model->freepick = $freepick;
        $model->group_id = $group_id;
        $model->all_weight = $all_weight;
        $model->all_volume = $all_volume;
        $model->temperture = $temperture;
        $model->trunking = $trunking;
        $model->centercity = $centercity;
        $model->center_store = $input['center_store'] ?? '';
        $model->carriage_id = $carriage_id;
        $model->expire_time = time()+7*24*3600;
        $res = $model->save();
        if ($res){
//            $this->line_auto($model->id,'edit');
            $data = $this->encrypt(['code'=>'200','msg'=>'编辑成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'编辑失败']);
            return $this->resultInfo($data);
        }
    }

    /*
    * 查看线路模型
    * */
    public function actionView(){
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        $carriage_id = $input['carriage_id'];
        $id = $input['id'];
        if (empty($group_id) || empty($carriage_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        if ($id) {
            $model = AppLineLog::find()
                ->where(['id'=>$id])
                ->asArray()
                ->one();
            $model['begin_store'] = json_decode($model['begin_store'],true);
            $model['end_store'] = json_decode($model['end_store'],true);
        } else {
            $model = new AppLineLog();
        }
        $carriage = Carriage::find()->select('cid,name')->where(['group_id'=>$group_id,'delete_flag'=>'Y','use_flag'=>'Y'])->asArray()->all();

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$model,'carriage'=>$carriage]);
        return $this->resultInfo($data);

    }

    /*
     * 删除线路模型
     * */
    public function actionDelete(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $carriage_id = $input['carriage_id'];
        $group_id = $input['group_id'];
        $id = $input['id'];
        if (empty($group_id) || empty($id) || empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $model = AppLineLog::find()->where(['id'=>$id])->one();
        if ($model->delete_flag == 'N'){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已删除']);
            return $this->resultInfo($data);
        }
        $model->delete_flag = 'N';
        $res = $model->save();
        if ($res){
            $data = $this->encrypt(['code'=>200,'msg'=>'删除成功']);
            return $this->resultInfo($data);
        }

        $data = $this->encrypt(['code'=>400,'msg'=>'删除失败']);
        return $this->resultInfo($data);
    }

    /*
     * 禁用
     * */
    public function actionUse_n(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $carriage_id = $input['carriage_id'];
        $group_id = $input['group_id'];
        if (empty($carriage_id) || empty($group_id) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $model = AppLineLog::find()->where(['id'=>$id,'delete_flag'=>'Y'])->one();
        if ($model->use_flag == 'N'){
            $data = $this->encrypt(['code'=>400,'msg'=>'已禁用']);
            return $this->resultInfo($data);
        }
        $model->use_flag = 'N';
        $res = $model->save();
        if ($res){
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 启用
     * */
    public function actionUse_y(){
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        $carriage_id = $input['carriage_id'];
        $id = $input['id'];
        if (empty($group_id) || empty($carriage_id) ||empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $model = AppLineLog::find()->where(['id'=>$id,'delete_flag'=>'Y'])->one();
        if ($model->use_flag == 'Y'){
            $data = $this->encrypt(['code'=>400,'msg'=>'已启用']);
            return $this->resultInfo($data);
        }
        $model->use_flag = 'Y';
        $res = $model->save();
        if ($res){
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }
}
