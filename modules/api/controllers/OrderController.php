<?php
namespace app\modules\api\controllers;


use app\models\AppBalance;
use app\models\AppBulk;
use app\models\AppCarriageList;
use app\models\AppCartype;
use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use app\models\AppGroup;
use app\models\AppLine;
use app\models\AppMegerOrder;
use app\models\AppMemberOrder;
use app\models\AppOrderCarriage;
use app\models\AppPayment;
use app\models\AppPaymessage;
use app\models\AppPickorder;
use app\models\AppReceive;
use app\models\AppVehical;
use app\models\Car;
use app\models\Customer;
use Yii;
use app\models\AppOrder;

/**
 * Default controller for the `api` module
 */
class OrderController extends CommonController
{
    /*   -------------      下单     ----------------*/
    /*
     * 添加订单
     * */
    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
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
        $pickprice = $input['pickprice'] ?? 0;
        $sendprice = $input['sendprice'] ?? 0;
        $price = $input['price'];
        $otherprice = $input['otherprice'] ?? 0;
        $more_price = $input['more_price'] ?? 0;
        $total_price = $input['total_price'];
        $order_type = $input['order_type'];
        $paytype = $input['paytype'] ?? '';
        $chitu = $input['chitu'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
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

        if ($order_type == 1){
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

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);

        $order->cartype = $cartype;
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $arr_startstr = json_decode($startstr,true);
        foreach ($arr_startstr as $k => $v){
            $all = $v['pro'].$v['city'].$v['area'].$v['info'];

            $common_address = AppCommonAddress::find()->where(['group_id'=>$user->parent_group_id,'all'=>$all])->one();
            if ($common_address){
                @$common_address->updateCounters(['count_views'=>1]);
            }else{
                $common_address = new AppCommonAddress();
                $common_address->pro_id = $v['pro'];
                $common_address->city_id = $v['city'];
                $common_address->area_id = $v['area'];
                $common_address->address = $v['info'];
                $common_address->all = $all;
                $common_address->group_id = $group_id;
                $common_address->create_user = $user->name;
                $common_address->create_user_id = $user->id;
                @$common_address->save();
            }

            $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
            if ($common_contact){
                @$common_contact->updateCounters(['views'=>1]);
            }else{
                $common_contact = new AppCommonContacts();
                $common_contact->name = $v['contant'];
                $common_contact->tel = $v['tel'];
                $common_contact->user_id = $user->id;
                $common_contact->create_user = $user->name;
                $common_contact->create_userid = $user->id;
                @$common_contact->save();
            }
        }
        $order->endstr = $endstr;
        $arr_endstr = json_decode($endstr,true);

        foreach ($arr_endstr as $k => $v){
            $all = $v['pro'].$v['city'].$v['area'].$v['info'];
            $common_address = AppCommonAddress::find()->where(['group_id'=>$group_id,'all'=>$all])->one();
            if ($common_address){
                @$common_address->updateCounters(['count_views'=>1]);
            }else{
                $common_address = new AppCommonAddress();
                $common_address->pro_id = $v['pro'];
                $common_address->city_id = $v['city'];
                $common_address->area_id = $v['area'];
                $common_address->address = $v['info'];
                $common_address->all = $all;
                $common_address->group_id = $group_id;
                $common_address->create_user = $user->name;
                $common_address->create_user_id = $user->id;
                @$common_address->save();
            }

            $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
            if ($common_contact){
                @$common_contact->updateCounters(['views'=>1]);
            }else{
                $common_contact = new AppCommonContacts();
                $common_contact->name = $v['contant'];
                $common_contact->tel = $v['tel'];
                $common_contact->user_id = $user->id;
                $common_contact->create_user = $user->name;
                $common_contact->create_userid = $user->id;
                @$common_contact->save();
            }
        }
        $order->company_id = $company_id;
        $order->ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->takenumber = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->name = $cargo_name;
        $order->number = $cargo_number;
        $order->number2 = $cargo_number2;
        $order->weight = $cargo_weight;
        $order->volume = $cargo_volume;
        $order->create_user_id = $user->id;
        $order->create_user_name = $user->name;
        $order->group_id = $group_id;
        $order->temperture = $temperture;
        $order->remark = $remark;
        $order->picktype = $picktype;
        $order->sendtype = $sendtype;
        $order->time_start = $start_time;//用车时间
        if ($order_type == 1){
            $order->time_end = $end_time;//预计到达时间
            $order->more_price = $more_price;
        }
        $order->pickprice = $pickprice;
        $order->sendprice = $sendprice;
        $order->price = $price;
        $order->otherprice = $otherprice;
        $order->paytype = $paytype;
        if ($total_price != (floor($pickprice) + floor($sendprice) + floor($price) + floor($otherprice) + floor($more_price))){
            $data = $this->encrypt(['code'=>400,'msg'=>'价格错误']);
            return $this->resultInfo($data);
        }
        $order->total_price = $total_price;
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
            $receive->receive_info = json_encode(['price'=>$price,'pickprice'=>$pickprice,'sendprice'=>$sendprice,'more_price'=>$more_price,'otherprice'=>$otherprice]);
            $receive->create_user_id = $user->id;
            $receive->create_user_name = $user->name;
            $receive->group_id = $group_id;
            $receive->paytype = $paytype;
            $receive->ordernumber = $order->ordernumber;
            if ($order_type == 2){
                $receive->type = 2;
            }
            $res_r = $receive->save();
            if ($res_r  && $res){
                $transaction->commit();
                $this->hanldlog($user->id,'添加订单:'.$order->startcity.'->'.$order->endcity);
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
     * 普通用户下单
     * */
    public function actionOrder_add(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
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
        $price = $input['price'] ?? '';
        $order_type = $input['order_type'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = new AppOrder();
        if (empty($start_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车开始时间不能为空']);
            return $this->resultInfo($data);
        }
        if ($order_type == 8){
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

        if (empty($cargo_weight)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写重量']);
            return $this->resultInfo($data);
        }
        if (empty($cargo_volume)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写体积']);
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
        $arr_startstr = json_decode($startstr,true);
        foreach ($arr_startstr as $k => $v){
            $all = $v['pro'].$v['city'].$v['area'].$v['info'];

            $common_address = AppCommonAddress::find()->where(['group_id'=>$user->parent_group_id,'all'=>$all])->one();
            if ($common_address){
                @$common_address->updateCounters(['count_views'=>1]);
            }else{
                $common_address = new AppCommonAddress();
                $common_address->pro_id = $v['pro'];
                $common_address->city_id = $v['city'];
                $common_address->area_id = $v['area'];
                $common_address->address = $v['info'];
                $common_address->all = $all;
                $common_address->group_id = $user->group_id;
                $common_address->create_user = $user->name;
                $common_address->create_user_id = $user->id;
                @$common_address->save();
            }

            $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
            if ($common_contact){
                @$common_contact->updateCounters(['views'=>1]);
            }else{
                $common_contact = new AppCommonContacts();
                $common_contact->name = $v['contant'];
                $common_contact->tel = $v['tel'];
                $common_contact->user_id = $user->id;
                $common_contact->create_user = $user->name;
                $common_contact->create_userid = $user->id;
                @$common_contact->save();
            }
        }
        $arr_endstr = json_decode($endstr,true);
        foreach ($arr_endstr as $k => $v){
            $all = $v['pro'].$v['city'].$v['area'].$v['info'];
            $common_address = AppCommonAddress::find()->where(['group_id'=>$user->group_id,'all'=>$all])->one();
            if ($common_address){
                @$common_address->updateCounters(['count_views'=>1]);
            }else{
                $common_address = new AppCommonAddress();
                $common_address->pro_id = $v['pro'];
                $common_address->city_id = $v['city'];
                $common_address->area_id = $v['area'];
                $common_address->address = $v['info'];
                $common_address->all = $all;
                $common_address->group_id = $user->group_id;
                $common_address->create_user = $user->name;
                $common_address->create_user_id = $user->id;
                @$common_address->save();
            }

            $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
            if ($common_contact){
                @$common_contact->updateCounters(['views'=>1]);
            }else{
                $common_contact = new AppCommonContacts();
                $common_contact->name = $v['contant'];
                $common_contact->tel = $v['tel'];
                $common_contact->user_id = $user->id;
                $common_contact->create_user = $user->name;
                $common_contact->create_userid = $user->id;
                @$common_contact->save();
            }
        }
        $order->ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->takenumber = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->time_start = $start_time;
        if ($order_type == 8){
            $order->time_end = $end_time;
        }
        $order->line_start_contant = $startstr;
        $order->line_end_contant = $endstr;
        $order->name = $cargo_name;
        $order->temperture = $temperture;
        $order->cartype = $cartype;
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $order->endstr = $endstr;
        $order->price = $price;
        $order->line_price = $price;
        $order->line_status = 2;
        $order->weight = $cargo_weight;
        $order->volume = $cargo_volume;
        $order->number = $cargo_number;
        $order->number2 = $cargo_number2;
        $order->picktype = $picktype;
        $order->sendtype = $sendtype;
        $order->remark = $remark;
        $order->group_id = $user->group_id;
        $order->total_price = $price;
        $order->create_user_id = $user->id;
        $order->create_user_name = $user->name;
        $order->money_state = 'N';
        $order->order_type = $order_type;
        $transaction= AppOrder::getDb()->beginTransaction();
        try{
            $res  = $order->save();
            if ($res){
                $payment = new AppPayment();
                $payment->order_id = $order->id;
                $payment->pay_price = $price;
                $payment->group_id = $user->group_id;
                $payment->create_user_id = $user->id;
                $payment->create_user_name = $user->name;
                $res_p =  $payment->save();
                $transaction->commit();
                $this->hanldlog($user->id,'添加整车订单'.$order->startcity.'->'.$order->endcity);
                $data = $this->encrypt(['code'=>'200','msg'=>'添加成功','data'=>$order->id]);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>'400','msg'=>'添加失败']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollback();
            $data = $this->encrypt(['code'=>'400','msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 订单详情（编辑）
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
        if ($id) {
            $model = AppOrder::find()->where(['id'=>$id])->asArray()->one();
        } else {
            $model = new AppOrder();
        }

        $groups = AppGroup::group_list($user);
        $car_list = AppCartype::get_list();

        if ($id) {
            $group_id = $model['group_id'];
        } else {
            $group_id = $groups[0]['id'];
        }
        $customer = Customer::get_list($group_id);
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'groups'=>$groups,'customer'=>$customer,'group_id'=>$group_id,'car_list'=>$car_list]);
        return $this->resultInfo($data);
    }

    /*
     * 编辑订单
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
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
        $pickprice = $input['pickprice'] ?? 0;
        $sendprice = $input['sendprice'] ?? 0;
        $price = $input['price'] ?? 0;
        $otherprice = $input['otherprice'] ?? 0;
        $more_price = $input['more_price'] ?? 0;
        $total_price = $input['total_price'];
        $order_type = $input['order_type'];
        $paytype = $input['paytype'];
        if (empty($token) || empty($id)){
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

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);

        $order->paytype = $paytype;
        $order->cartype = $cartype;
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $order->line_start_contant = $startstr;
        $order->line_end_contant = $endstr;
        $arr_startstr = json_decode($startstr,true);
        foreach ($arr_startstr as $k => $v){
            $all = $v['pro'].$v['city'].$v['area'].$v['info'];

            $common_address = AppCommonAddress::find()->where(['group_id'=>$user->parent_group_id,'all'=>$all])->one();
            if ($common_address){
                @$common_address->updateCounters(['count_views'=>1]);
            }else{
                $common_address = new AppCommonAddress();
                $common_address->pro_id = $v['pro'];
                $common_address->city_id = $v['city'];
                $common_address->area_id = $v['area'];
                $common_address->address = $v['info'];
                $common_address->all = $all;
                $common_address->group_id = $group_id;
                $common_address->create_user = $user->name;
                $common_address->create_user_id = $user->id;
                @$common_address->save();
            }

            $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
            if ($common_contact){
                @$common_contact->updateCounters(['views'=>1]);
            }else{
                $common_contact = new AppCommonContacts();
                $common_contact->name = $v['contant'];
                $common_contact->tel = $v['tel'];
                $common_contact->user_id = $user->id;
                $common_contact->create_user = $user->name;
                $common_contact->create_userid = $user->id;
                @$common_contact->save();
            }
        }
        $order->endstr = $endstr;
        $arr_endstr = json_decode($endstr,true);

        foreach ($arr_endstr as $k => $v){
            $all = $v['pro'].$v['city'].$v['area'].$v['info'];
            $common_address = AppCommonAddress::find()->where(['group_id'=>$group_id,'all'=>$all])->one();
            if ($common_address){
                @$common_address->updateCounters(['count_views'=>1]);
            }else{
                $common_address = new AppCommonAddress();
                $common_address->pro_id = $v['pro'];
                $common_address->city_id = $v['city'];
                $common_address->area_id = $v['area'];
                $common_address->address = $v['info'];
                $common_address->all = $all;
                $common_address->group_id = $group_id;
                $common_address->create_user = $user->name;
                $common_address->create_user_id = $user->id;
                @$common_address->save();
            }

            $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
            if ($common_contact){
                @$common_contact->updateCounters(['views'=>1]);
            }else{
                $common_contact = new AppCommonContacts();
                $common_contact->name = $v['contant'];
                $common_contact->tel = $v['tel'];
                $common_contact->user_id = $user->id;
                $common_contact->create_user = $user->name;
                $common_contact->create_userid = $user->id;
                @$common_contact->save();
            }
        }

        $order->name = $cargo_name;
        $order->number = $cargo_number;
        $order->number2 = $cargo_number2;
        $order->weight = $cargo_weight;
        $order->volume = $cargo_volume;
        $order->create_user_id = $user['id'];
        $order->create_user_name = $user['name'];
        $order->temperture = $temperture;
        $order->remark = $remark;
        if ($company_id){
            $order->company_id = $company_id;
        }
        if(empty($remark)){
            $order->remark = $order->remark;
        }
        if(empty($temperture)){
            $order->temperture = $order->temperture;
        }
        $order->picktype = $picktype;
        $order->sendtype = $sendtype;
        $order->time_start = $start_time;//用车时间
        $order->time_end = $end_time;//预计到达时间
        $order->pickprice = $pickprice;
        $order->sendprice = $sendprice;
        $order->price = $price;
        $order->otherprice = $otherprice;
        $order->more_price = $more_price;
        // if ($total_price != ($pickprice + $sendprice + $more_price + $otherprice + $price)){
        //     $data = $this->encrypt(['code'=>400,'msg'=>'价格计算错误']);
        //     return $this->resultInfo($data);
        // }
        $order->total_price = $total_price;
        $res =  $order->save();
        if($order->order_type != 5 || $order_type != 6 ||$order_type != 7){
            $group = AppGroup::findOne($user->parent_group_id);
            if ($group->level_id == 3 && $company_id){
                $receive = AppReceive::find()->where(['order_id'=>$id,'group_id'=>$user->group_id])->one();
                $receive->receivprice = $order->total_price;
                $receive->trueprice = 0;
                $receive->receive_info = json_encode(['price'=>$price,'pickprice'=>$pickprice,'sendprice'=>$sendprice,'more_price'=>$more_price,'otherprice'=>$otherprice]);
                $arr = $receive->save();
            }else if($group->level_id == 1 || $group->level_id == 2){
                $payment = AppPayment::find()->where(['order_id'=>$id,'group_id'=>$user->group_id])->one();
                $payment->pay_price = $price;
                $payment->save();
            }
        }

        if ($res){
            $this->hanldlog($user->id,'修改订单:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'修改成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'修改失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 订单详情（运输，回单）
     * */
    public function actionOrder_view(){

    }
    /* ------------     接单列表      ----------------*/
    /**
     * Renders the index view for the module
     * 接单
     * @return string
     */
    public function actionOrder_take(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $res_p = true;
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppOrder::find()->where(['id'=>$id])->one();
        if ($order->order_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已被承接']);
            return $this->resultInfo($data);
        }
        $group_id = $user->parent_group_id;
        $group = AppGroup::find()->where(['id'=>$group_id])->one();

        if ($group->level_id == 1){
            $has_car = Car::find()->where(['group_id'=>$group->id,'delete_flag'=>'Y','use_flag'=>'Y'])->one();
            if (!$has_car){
                $data = $this->encrypt(['code'=>400,'msg'=>'请先认证车辆']);
                return $this->resultInfo($data);
            }
            $has_order = AppOrder::find()->where(['group_id'=>$id])->andWhere(['not in','order_status',[1,6,7,8]])->all();
            if ($has_order){
                $data = $this->encrypt(['code'=>400,'msg'=>'请先完成当前订单']);
                return $this->resultInfo($data);
            }
        }

        if ($group->level_id == 1){
//            生成运单，应收赤途，order表保存车辆信息更改订单状态
            $order->order_status = 3;
            $order->deal_company = $user->group_id;
            $order->driverinfo = json_encode([['id'=>'','price'=>$order->line_price,'carnumber'=>$has_car->carnumber,'contant'=>$has_car->driver_name,'tel'=>$has_car->mobile]],JSON_UNESCAPED_UNICODE);
            $carriage = new AppCarriageList();
            $carriage->order_id = $id;
            $carriage->carriage_number = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
            $carriage->group_id = $group->group_id;
            $carriage->create_user_id = $user->id;
            $carriage->create_user_name = $user->name;
            $carriage->deal_company = $group->group_id;
            $carriage->contant = $has_car->driver_name;
            $carriage->tel = $has_car->mobile;
            $carriage->carriage_price = $order->line_price;
            $carriage->type = 1;

            $receive = new AppReceive();
            $receive->receivprice = $order->line_price;
            $receive->trueprice = 0;
            $receive->al_price = 0;
            $receive->order_id = $order->id;
            $receive->create_user_id = $user->id;
            $receive->create_user_name = $user->name;
            $receive->group_id = $user->group_id;
            $receive->ordernumber = $order->ordernumber;

            if($order->money_state == 'N'){
                $receive->company_type = 2;
                $receive->compay_id = $order->group_id;
                $payment = AppPayment::find()->where(['group_id'=>$order->group_id,'order_id'=>$id,'type'=>1])->one();
                $payment->carriage_id = $user->group_id;
                $payment->pay_type = 4;
                $res_p = $payment->save();
            }
            $transaction= AppOrder::getDb()->beginTransaction();
            try {
                $res = $order->save();
                $arr = $receive->save();
                $res_c = $carriage->save();
                if ($res && $arr && $res_c){
                    $transaction->commit();
                    $this->hanldlog($user->id,'接取订单'.$order->ordernumber);
                    $data = $this->encrypt(['code'=>200,'msg'=>'接单成功']);
                    return $this->resultInfo($data);
                }
            }catch (\Exception $e){

                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'接单失败']);
                return $this->resultInfo($data);
            }

        }else{
            $order->order_status = 2;
            $order->deal_company = $user->group_id;
            $receive = new AppReceive();
            $receive->receivprice = $order->line_price;
            $receive->trueprice = $order->line_price;
            $receive->al_price = $order->line_price;
            $receive->order_id = $order->id;
            $receive->create_user_id = $user->id;
            $receive->create_user_name = $user->name;
            $receive->group_id = $user->group_id;
            $receive->ordernumber = $order->ordernumber;
            if ($order->money_state == 'N'){
                $receive->company_type = 2;
                $receive->compay_id = $order->group_id;
                $receive->trueprice = 0;
                $payment = AppPayment::find()->where(['group_id'=>$order->group_id,'order_id'=>$id])->one();
                $payment->carriage_id = $user->group_id;
                $payment->pay_type = 4;
                $res_p = $payment->save();
            }
            $transaction= AppOrder::getDb()->beginTransaction();
            try {
                $res = $order->save();
                $arr = $receive->save();

                if ($res && $arr && $res_p){
                    $transaction->commit();
                    $this->hanldlog($user->id,'接取订单'.$order->ordernumber);
                    $data = $this->encrypt(['code'=>200,'msg'=>'接单成功']);
                    return $this->resultInfo($data);
                }
            }catch (\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'接单失败']);
                return $this->resultInfo($data);
            }
        }
    }

    /*
     * 接单（平台接单）
     * */
    public function actionPlatform_take(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $carnumber = $input['carnumber'];
        $driver_name = $input['driver_name'];
        $mobile = $input['mobile'];
        $res_p = true;
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if(empty($driver_name)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写联系人姓名']);
            return $this->resultInfo($data);
        }
        if(empty($mobile)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写联系人电话']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppOrder::find()->where(['id'=>$id])->one();
        if ($order->order_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已被承接']);
            return $this->resultInfo($data);
        }
        $group_id = $user->parent_group_id;
        $group = AppGroup::find()->where(['id'=>$group_id])->one();
        $order->order_status = 2;
        $order->deal_company = $user->group_id;
        $order->driverinfo = json_encode([['id'=>'','price'=>$order->line_price,'carnumber'=>$carnumber,'contant'=>$driver_name,'tel'=>$mobile]],JSON_UNESCAPED_UNICODE);
        $carriage = new AppCarriageList();
        $carriage->order_id = $id;
        $carriage->carriage_number = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $carriage->group_id = $group->group_id;
        $carriage->create_user_id = $user->id;
        $carriage->create_user_name = $user->name;
        $carriage->deal_company = $group->group_id;
        $carriage->contant = $driver_name;
        $carriage->tel = $mobile;
        $carriage->carriage_price = $order->line_price;
        $carriage->type = 1;

        $receive = new AppReceive();
        $receive->receivprice = $order->line_price;
        $receive->trueprice = 0;
        $receive->al_price = 0;
        $receive->order_id = $order->id;
        $receive->create_user_id = $user->id;
        $receive->create_user_name = $user->name;
        $receive->group_id = $user->group_id;
        $receive->ordernumber = $order->ordernumber;
        if($order->money_state == 'N'){
            $receive->company_type = 3;
            $receive->compay_id = $order->group_id;
            $payment = AppPayment::find()->where(['group_id'=>$order->group_id,'order_id'=>$id,'type'=>1])->one();
            $payment->carriage_id = $user->group_id;
            $payment->pay_type = 5;
            $res_p = $payment->save();
        }
        $transaction= AppOrder::getDb()->beginTransaction();
        try {
            $res = $order->save();
            $arr = $receive->save();
            $res_c = $carriage->save();
            if ($res && $arr && $res_c){
                $transaction->commit();
                $this->hanldlog($user->id,'接取订单'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'接单成功']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'接单失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 接单（系统）
     * */
    public function actionSystem_take(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $res_p = true;
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppOrder::find()->where(['id'=>$id])->one();
        if ($order->order_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已被承接']);
            return $this->resultInfo($data);
        }
        $group_id = $user->parent_group_id;
        $group = AppGroup::find()->where(['id'=>$group_id])->one();
        $order->order_status = 2;
        $order->deal_company = $user->group_id;
        $receive = new AppReceive();
        $receive->receivprice = $order->line_price;
        $receive->trueprice = $order->line_price;
        $receive->al_price = $order->line_price;
        $receive->order_id = $order->id;
        $receive->create_user_id = $user->id;
        $receive->create_user_name = $user->name;
        $receive->group_id = $user->group_id;
        $receive->ordernumber = $order->ordernumber;
        if ($order->money_state == 'N' || !$order->money_state){
            $receive->company_type = 3;
            $receive->compay_id = $order->group_id;
            $receive->trueprice = 0;
            $payment = AppPayment::find()->where(['group_id'=>$order->group_id,'order_id'=>$id])->one();
            $payment->carriage_id = $user->group_id;
            $payment->pay_type = 5;
            $res_p = $payment->save();
        }
        $transaction= AppOrder::getDb()->beginTransaction();
        try {
            $res = $order->save();
            $arr = $receive->save();

            if ($res && $arr && $res_p){
                $transaction->commit();
                $this->hanldlog($user->id,'接取订单'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'接单成功']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'接单失败']);
            return $this->resultInfo($data);
        }
    }

    /**
     * Renders the index view for the module
     * 接单列表
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
        $ordernumber = $input['ordernumber'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $chitu = $input['chitu'];

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

        $check_result = $this->check_token_list($token,$chitu);//验证令牌
        $list = AppOrder::find()
            ->alias('v')
            ->select(['v.*', 't.carparame','a.group_name'])
            ->leftJoin('app_cartype t', 'v.cartype=t.car_id')
            ->leftJoin('app_group a','a.id= v.group_id')
            ->where(['v.deal_company' => $group_id, 'v.delete_flag' => 'Y']);
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
     * 取消订单（接单）
     * 订单状态更改为已取消，删除应收
     * */
    public function actionCancel_take(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $chitu = $input['chitu'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
//        $this->check_group_auth($order->deal_company,$user);
        if (in_array($order->order_status,[3,4,5,6])){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已承运，不能取消']);
            return $this->resultInfo($data);
        }
        $payment = false;
        $res_p = true;
        if ($chitu == 3){
            $order->order_status = 1;
            $order->driverinfo = '';
            $order->deal_company = '';
            $order->deal_company_name = '';
            $order->deal_user = '';
            $receive = AppReceive::find()->where(['order_id'=>$order->id,'group_id'=>$user->group_id])->one();

            if ($order->money_state == 'N'){
                $payment = AppPayment::find()->where(['group_id'=>$order->group_id,'order_id'=>$id])->one();
                $payment->carriage_id = '';
            }
            $transaction= AppOrder::getDb()->beginTransaction();
            try {
                $res = $order->save();
                $arr = $receive->delete();
                if ($payment) {
                    $res_p = $payment->save();
                }
                if ($res && $arr && $res_p){
                    $transaction->commit();
                    $this->hanldlog($user->id,'取消接单'.$order->ordernumber);
                    $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
                    return $this->resultInfo($data);
                }
            }catch (\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'取消失败']);
                return $this->resultInfo($data);
            }
        }else{
            $order->order_status = 1;
            $order->driverinfo = '';
            $order->deal_company = '';
            $order->deal_company_name = '';
            $order->deal_user = '';
            $receive = AppReceive::find()->where(['order_id'=>$order->id,'group_id'=>$user->group_id])->one();
            $transaction= AppOrder::getDb()->beginTransaction();
            try {
                $res = $order->save();
                $receive->delete();
                $transaction->commit();
                $this->hanldlog($user->id,'取消接单'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
                return $this->resultInfo($data);
            }catch(\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'取消失败']);
                return $this->resultInfo($data);
            }
        }

    }

    /*
     * 转为内部订单
     * */
    public function actionCopy_order(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::find()->where(['id'=>$id])->asArray()->one();
        unset($order['id']);
        $order['ordernumber'] = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order['takenumber'] = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);

        if ($order['line_status'] != 2) {
            $data = $this->encrypt(['code'=>400,'msg'=>'此订单为内部订单']);
            return $this->resultInfo($data);
        }
        $order['main_order'] = 1;
        $order['company_id'] = $order['group_id'];
        $order['group_id'] = $user->group_id;
        $order['create_user_id'] = $user->id;
        $order['create_user_name'] = $user->name;
        $order['line_id'] = $id;
        $order['order_status'] = 1;
        $order['line_status'] = 1;
        $order['total_price'] = $order['line_price'];
        $order['price'] = $order['line_price'];
        $order['pickprice'] = 0;
        $order['sendprice'] = 0;
        $order['otherprice'] = 0;
        $order['more_price'] = 0;
        $order['startstr'] = $order['line_start_contant'];
        $order['endstr'] = $order['line_end_contant'];        
        $order['line_start_contant'] = '';
        $order['line_end_contant'] = '';
        $order['cargo_user'] = $order->group_id;
        if ($order['order_type'] == 8 || $order['order_type'] == 3 ||$order['order_type'] == 1){
            $order['order_type'] = 5;
        }else if($order['order_type'] == 9 || $order['order_type'] == 4 || $order['order_type'] == 2  || $order['order_type'] == 7 ||  $order['order_type'] == 10){
            $order['order_type'] = 6;
        }
        $this->check_group_auth($order['deal_company'],$user);
        $order['deal_company'] = '';
        $model = new AppOrder();
        $model->attributes = $order;
        $order_o = AppOrder::findOne($id);
        $order_o->copy = 2;
        $transaction= AppOrder::getDb()->beginTransaction();
        try{
            $res = $model->save();
            $res_o = $order_o->save();
            $transaction->commit();
            $this->hanldlog($user->id,'外部订单'.$order->ordernumber.'转为内部订单');
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }catch(\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 确认完成
     * */
    public function actionConfirm_order(){
          $input = Yii::$app->request->post();
          $token = $input['token'];
          $id = $input['id'];
          if (empty($token) || empty($id)){
              $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
              return $this->resultInfo($data);
          }
          $check_result = $this->check_token($token,true);
          $user = $check_result['user'];
          $order = AppOrder::findOne($id);
          $this->check_group_auth($order->group_id,$user);
          if (empty($order->price) || empty($order->total_price)){
              $data = $this->encrypt(['code'=>400,'msg'=>'请确认价格']);
              return $this->resultInfo($data);
          }
          if ($order->order_stage == 2){
             $data = $this->encrypt(['code'=>400,'msg'=>'已确认，请勿重复操作']);
             return $this->resultInfo($data);
          }
          $model = AppOrder::find()->where(['line_id'=>$id])->asArray()->all();
          if ($model == []){
              $data = $this->encrypt(['code'=>400,'msg'=>'请先执行...操作']);
              return $this->resultInfo($data);
          }
          $order->order_stage = 2;
          $res = $order->save();
          if ($res){
              $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
              return $this->resultInfo($data);
          }else{
              $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
              return $this->resultInfo($data);
          }
    }

    /*
     * 确认送达(接单)
     * */
    public function actionDone_arrive(){
          $input = Yii::$app->request->post();
          $token = $input['token'];
          $id = $input['id'];
          if(empty($token) || empty($id)){
              $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
              return $this->resultInfo($data);
          }
          $check_result = $this->check_token($token,false);
          $user = $check_result['user'];
          $take_order = AppOrder::findOne($id);
//          $copy_order = AppOrder::find()->where(['line_id'=>$id])->asArray()->one();

//          if ($copy_order){
//              if ($copy_order['split'] == 2){
//                  $split_order = AppOrder::find()->where(['split_id'=>$copy_order])->all();
//                  foreach($split_order as $key =>$value){
//                      if ($value['order_status'] != 6){
//                          $ids[] = $value['order_status'];
//                      }
//                  }
//                  if(count($ids)>1){
//                      $data = $this->encrypt(['code'=>400,'msg'=>'请先完成该订单的分单']);
//                      return $this->resultInfo($data);
//                  }
//              }else{
//                  if ($copy_order['order_status'] != 6){
//                      $data = $this->encrypt(['code'=>400,'msg'=>'请先完成该订单的分单']);
//                      return $this->resultInfo($data);
//                  }
//              }
//          }else{
//              $data = $this->encrypt(['code'=>400,'msg'=>'订单还未运输，不能进行此操作']);
//              return $this->resultInfo($data);
//          }
          if($take_order->order_status == 6){
              $data = $this->encrypt(['code'=>400,'msg'=>'订单已完成']);
              return $this->resultInfo($data);
          }
          if($take_order->order_status == 7){
              $data = $this->encrypt(['code'=>400,'msg'=>'订单已超时']);
              return $this->resultInfo($data);
          }
          if($take_order->order_status == 8){
              $data = $this->encrypt(['code'=>400,'msg'=>'订单已取消']);
              return $this->resultInfo($data);
          }

          $take_order->order_status = 5;
          $res =  $take_order->save();
          if ($res){
              $this->hanldlog($user->id,'订单已送达'.$take_order->ordernumber);
              $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
              return $this->resultInfo($data);
          }else{
              $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
              return $this->resultInfo($data);
          }
    }

    /* -------------- 订单列表 ---------------*/

    /**
     * Renders the index view for the module
     * 订单列表
     * @return string
     */
    public function actionOrder_index()
    {
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $ordernumber = $input['ordernumber'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $chitu = $input['chitu'];

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

        $check_result = $this->check_token_list($token,$chitu);//验证令牌
        $user = $check_result['user'];
        $list = AppOrder::find()
            ->alias('v')
            ->select(['v.*', 't.carparame','a.all_name'])
            ->leftJoin('app_cartype t', 'v.cartype=t.car_id')
            ->leftJoin('app_customer a','a.id= v.company_id')
            ->where(['v.group_id' => $group_id, 'v.delete_flag' => 'Y','v.main_order'=>1,'v.line_status'=>1]);
        $group = AppGroup::findOne($user->parent_group_id);
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
     * 取消订单（下单 整车/零担 order）
     * */
    public function actionCancel_order()
    {
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $chitu = $input['chitu'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

            $check_result = $this->check_token($token,true);
            $user = $check_result['user'];
            $order = AppOrder::findOne($id);
            $this->check_group_auth($order->group_id,$user);
            if ($order->order_status == 8){
                $data = $this->encrypt(['code'=>400,'msg'=>'订单已取消']);
                return $this->resultInfo($data);
            }
            if ($order->order_status == 6){
                $data = $this->encrypt(['code'=>400,'msg'=>'订单已完成不能取消']);
                return $this->resultInfo($data);
            }
            if ($order->order_stage != 1){
                $data = $this->encrypt(['code'=>400,'msg'=>'不可以取消']);
                return $this->resultInfo($data);
            }

            $transaction = AppOrder::getDb()->beginTransaction();
            try {
                //判断是否为复制订单，如果是删除复制订单，并修改原始订单为未确认
                if($order->line_id){
                     if($order->order_type == 5 ||$order->order_type == 8){
                         $order->delete();
                         $copy_order = AppOrder::findOne($order->line_id);
                         $copy_order->copy= 1;
                         $copy_order->save();
                     }else if($order->order_type == 6||$order->order_type == 7||$order->order_type == 9||$order->order_type == 10){
                         $order->delete();
                         $copy_order = AppBulk::findOne($order->line_id);
                         $copy_order->copy = 1;
                         $copy_order->save();
                     }
                }else{
                    if ($order->line_status == 1){
                        if($chitu == 1){
                            $list = AppPayment::find()->where(['order_id'=>$id,'group_id'=>$order->group_id])->all();
                            if ($list) {
                                AppPayment::deleteAll(['order_id'=>$id,'group_id'=>$order->group_id]);
                            }
                        }else{
                            $model = AppReceive::find()->where(['order_id'=>$id,'group_id'=>$order->group_id,'delete_flag'=>'Y'])->one();
                            $model->delete();
                        }
                    }else{
                        $list = AppPayment::find()->where(['order_id'=>$id,'group_id'=>$order->group_id])->all();
                        if ($list) {
                            AppPayment::deleteAll(['order_id'=>$id,'group_id'=>$order->group_id]);
                        }
                    }
                    $order->order_status = 8;
                    $res = $order->save();
                }

                $transaction->commit();
                $this->hanldlog($user->id,'取消订单:'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
                return $this->resultInfo($data);
        } catch(\Exception $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败,请重试！']);
            return $this->resultInfo($data);
        } catch(\Throwable $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败,请重试！']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 干线零担内部下单（取消）
     * */
    public function actionLine_order_cancel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppBulk::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->orderstate == 6){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已取消']);
            return $this->resultInfo($data);
        }
        if ($order->orderstate == 5){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已完成不能取消']);
            return $this->resultInfo($data);
        }
        $ve_order = AppOrder::find()->where(['line_id'=>$id])->one();
        if ($ve_order){
            if ($ve_order->order_stage != 1){
                $data = $this->encrypt(['code'=>400,'msg'=>'订单已开始不可以取消']);
                return $this->resultInfo($data);
            }
        }
        $transaction = AppOrder::getDb()->beginTransaction();
        try {
            //取消下单
            $order->orderstate = 6;
            $ve_order->delete();
            $res = $order->save();
            $receive = AppReceive::find()->where(['group_id'=>$user->group_id,'order_id'=>$order->id])->one()->delete();
            if ($res && $receive){
                $transaction->commit();
                $this->hanldlog($user->id,'取消订单:'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'取消失败！']);
                return $this->resultInfo($data);
            }

        } catch(\Exception $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败,请重试！']);
            return $this->resultInfo($data);
        } catch(\Throwable $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败,请重试！']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 删除订单
     * */
    public function actionDelete_order(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $order = AppOrder::find()->where(['id'=>$id])->one();
        $this->check_group_auth($order->group_id,$user);
        if ($order->line_status == 1){
            $model = AppReceive::find()->where(['order_id'=>$id,'group_id'=>$order->group_id,'delete_flag'=>'Y'])->one();
            $model->delete();
        }else{
            $list = AppPayment::find()->where(['order_id'=>$id,'group_id'=>$order->group_id])->all();
            if ($list) {
                AppPayment::deleteAll(['order_id'=>$id,'group_id'=>$order->group_id]);
            }
        }
        $order->delete_flag = 'N';
        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'删除订单:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'删除成功']);
            return $this->resultInfo($data);
        }

        $data = $this->encrypt(['code'=>400,'msg'=>'删除失败']);
        return $this->resultInfo($data);
    }

    /*
    * 确认订单
    * */
    public function actionList_confirm()
    {
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if (empty($order->price) || empty($order->total_price)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请确认价格']);
            return $this->resultInfo($data);
        }
        if($order->order_status == 8){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已取消']);
            return $this->resultInfo($data);
        }
        if($order->line_status == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'外部订单不执行此操作']);
            return $this->resultInfo($data);
        }
        if($order->order_stage == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'已确认，请勿重复操作']);
            return $this->resultInfo($data);
        }
        $order->order_stage = 2;
        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'完成订单列表操作'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 完成订单
     * */
    public function actionDone_order(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $chitu = $input['chitu'];
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true,$chitu);

        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        if ($order->main_order == 1){
            if ($order->split == 1){
                if ($order->order_stage != 4){
                    $data = $this->encrypt(['code'=>400,'msg'=>'请先完成调度']);
                    return $this->resultInfo($data);
                }
            }else{
                 $split_order = AppOrder::find()->where(['split_id'=>$id])->asArray()->all();
                 foreach($split_order as $key =>$value){
                    if ($value['order_status'] != 6){
                        $ids[] = $value['order_status'];
                    }
                 }
                 if(count($ids)>1){
                    $data = $this->encrypt(['code'=>400,'msg'=>'请先完成该订单的分单']);
                    return $this->resultInfo($data);
                 }
            }
        }else{
            if ($order->order_stage != 4){
                $data = $this->encrypt(['code'=>400,'msg'=>'请先完成调度']);
                return $this->resultInfo($data);
            }
        }
        if ($order->line_id){
            if ($order->order_type != 7 && $order->order_type != 10){
                $order->order_status = 6;
                $copy_order = AppOrder::findOne($order->line_id);
                $copy_order->order_status = 5;
            }else if($order->order_type == 7){
                $order->order_status = 6;
                $copy_order = AppBulk::findOne($order->line_id);
                $copy_order->orderstate = 4;
            }else if($order->order_type == 10){
                $order->order_status = 6;
                $copy_order = AppBulk::findOne($order->line_id);
                $copy_order->orderstate = 5;
            }
            $transaction = AppOrder::getDb()->beginTransaction();
            try {
                $res = $order->save();
                $copy_order->save();
                $transaction->commit();
                $this->hanldlog($user->id,'完成订单'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                return $this->resultInfo($data);
            }catch (\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                return $this->resultInfo($data);
            }

        }else{
            $order->order_status = 6;
            $res = $order->save();
            if ($res){
                $this->hanldlog($user->id,'完成订单'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                return $this->resultInfo($data);
            }

        }

    }

    /* --------------------         调度处理              ----------------------*/

    /*
     * 订单列表
     * */
    public function actionAll_order(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $chitu = $input['chitu'];
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
        if (empty($token) || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $check_result = $this->check_token_list($token,$chitu);//验证令牌
        $list = AppOrder::find()
            ->alias('v')
            ->select(['v.*', 't.carparame'])
            ->leftJoin('app_cartype t', 'v.cartype=t.car_id')
            ->where(['v.group_id' => $group_id, 'v.delete_flag' => 'Y','v.order_stage'=>2,'v.split'=>1]);
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
        foreach ($list as $k => $v) {
            $list[$k]['startstr'] = json_decode($v['startstr'],true);
            $list[$k]['endstr'] = json_decode($v['endstr'],true);
            $list[$k]['start_store'] = json_decode($v['start_store'],true);
            $list[$k]['end_store'] = json_decode($v['end_store'],true);
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
     * 上线
     * */
    public function actionOnline(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        $line_price = $input['line_price'];
        $startstr = $input['startstr'];
        $endstr = $input['endstr'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (!$line_price){
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写上线价格']);
            return $this->resultInfo($data);
        }
        if(empty($startstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'提货地址不能为空']);
            return $this->resultInfo($data);
        }
        if(empty($endstr)){
            $data = $this->encrypt(['code'=>400,'msg'=>'送货地址不能为空']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $order = AppOrder::find()->where(['id'=>$id])->one();
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status != 1 || $order->line_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单状态已改变，请刷新重试!']);
            return $this->resultInfo($data);
        }
        $payment = true;
        $order->money_state = 'N';
        $order->line_status = 2;
        $order->line_price = $line_price;
        $order->line_start_contant = $startstr;
        $order->line_end_contant = $endstr;
        $payment = new AppPayment();
        $payment->group_id = $order->group_id;
        $payment->order_id = $order->id;
        $payment->truepay = $line_price;
        $payment->truepay = 0;
        $payment->create_user_id = $user->id;
        $payment->pay_price = $line_price;

        $transaction= AppOrder::getDb()->beginTransaction();
        try{
            $res_p = $payment->save();
            $res_o = $order->save();
            if ($res_o && $res_p){
                $transaction->commit();
                $this->hanldlog($user->id,'上线订单:'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'上线成功']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollback();
            $data = $this->encrypt(['code'=>400,'msg'=>'上线失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 拆分订单
     * */
    public function actionSplit_order(){
          $input = Yii::$app->request->post();
          $id = $input['id'];
          $token = $input['token'];
          $arr = json_decode($input['arr'],true);
          if (empty($id) || empty($token)){
              $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
              return $this->resultInfo($data);
          }
          $check_result = $this->check_token($token,true);
          $user = $check_result['user'];
          $order = AppOrder::findOne($id);
          $this->check_group_auth($order->group_id,$user);
          if ($order->split == 2){
              $data = $this->encrypt(['code'=>400,'msg'=>'已拆订单不能再次拆分']);
              return $this->resultInfo($data);
          }
          if($order->main_order == 2){
              $data = $this->encrypt(['code'=>400,'msg'=>'分订单不能做再拆分']);
              return $this->resultInfo($data);
          }
          foreach ($arr as $key => $value){
//                 $model = new AppOrder();
                  $info['ordernumber'] = $order->ordernumber.'-'.($key+1);
                  $info['takenuber'] = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                  $info['startcity'] = $value['startcity'];
                  $info['endcity'] = $value['endcity'];
                  $info['startstr'] = $value['startstr'];
                  $info['endstr'] = $value['endstr'];
                  $info['weight'] = $value['weight'];
                  $info['volume'] = $value['volume'];
                  $info['number'] = $value['number'];
                  $info['number2'] = $value['number2'];
                  $info['name'] = $value['name'];
                  $info['time_start'] = $value['time_start'];
                  $info['time_end'] = $value['time_end'];
                  $info['price'] = $value['total_price'];
                  $info['total_price'] = $value['total_price'];
                  $info['line_start_contant'] = $value['startstr'];
                  $info['line_end_contant'] = $value['endstr'];
                  $info['temperture'] = $value['temperture'];
                  $info['picktype'] = $value['picktype'];
                  $info['sendtype'] = $value['sendtype'];
                  $info['money_state'] = 'N';
                  $info['cartype'] = 1;
                  $info['main_order'] = 2;
                  $info['split'] = 1;
                  $info['order_stage'] = 2;
                  $info['split_id'] = $id;
                  $info['group_id'] = $order->group_id;
                  $info['create_user_id'] = $user->id;
                  $info['create_user_name'] = $user->name;
                  $info['create_time'] = date('Y-m-d H:i:s',time());
                  $info['update_time'] = date('Y-m-d H:i:s',time());
                  $info_c[] = $info;
          }
          $transaction= AppOrder::getDb()->beginTransaction();
          try{
              $res = Yii::$app->db->createCommand()->batchInsert(AppOrder::tableName(), ['ordernumber','takenumber','startcity', 'endcity', 'startstr', 'endstr', 'weight', 'volume', 'number', 'number2','name','time_start', 'time_end', 'price','total_price',
                  'line_start_contant','line_end_contant','temperture','picktype','sendtype','money_state','cartype','main_order','split','order_stage','split_id','group_id','create_user_id','create_user_name','create_time','update_time'], $info_c)->execute();
              $order->split = 2;
              $res_o = $order->save();
              $transaction->commit();
              $this->hanldlog($user->id,'拆分订单'.$order->ordernumber);
              $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
              return $this->resultInfo($data);
          }catch(\Exception $e){
              $transaction->rollBack();
              $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
              return $this->resultInfo($data);
          }
    }
    /*
     *取消拆分
     * */
    public function actionCancel_split(){
           $input = Yii::$app->request->post();
           $token = $input['token'];
           $id = $input['id'];
           if (empty($token) || empty($id)){
               $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
               return $this->resultInfo($data);
           }
           $check_result = $this->check_token($token,true);
           $user = $check_result['user'];
           $order = AppOrder::find()->select('id,order_status,split_id,delete_flag,use_flag')->where(['split_id'=>$id,'delete_flag'=>'Y','use_flag'=>'Y'])->asArray()->all();
           $this->check_group_auth($order->group_id,$user);
           $split = true;
           foreach($order as $key =>$value){
               if ($value['order_stage'] == 3){
                   $split = false;
               }
           }
           if ($split == false){
               $data = $this->encrypt(['code'=>400,'msg'=>'订单已处理，不能取消']);
               return $this->resultInfo($data);
           }
           $model = AppOrder::findOne($id);
           $model->split = 1;
           $transaction= AppOrder::getDb()->beginTransaction();
           try{
               $res = AppOrder::deleteAll(['id','in',$order['id']]);
               $arr = $model->save();
               if ($res && $arr){
                   $transaction->commit();
                   $this->hanldlog($user->id,'取消拆分订单'.$order->ordernumber);
                   $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                   return $this->resultInfo($data);
               }else{
                   $transaction->rollBack();
                   $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                   return $this->resultInfo($data);
               }
           }catch(\Exception $e){
               $transaction->rollBack();
               $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
               return $this->resultInfo($data);
           }
    }


    /*
     * 编辑拆分订单
     * */
    public function actionDeal_edit(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $group_id = $input['group_id'] ?? '';
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
        $total_price = $input['total_price'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $order = AppOrder::findOne($id);
        if ($order->order_status == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已调度不可以修改']);
            return $this->resultInfo($data);
        }

        if (empty($start_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车开始时间不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($end_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车结束时间不能为空']);
            return $this->resultInfo($data);
        }
        if($cartype == 0){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择车型']);
            return $this->resultInfo($data);
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

        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);

        $order->cartype = $cartype;
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $order->line_start_contant = $startstr;
        $order->line_end_contant = $endstr;
        $arr_startstr = json_decode($startstr,true);
        foreach ($arr_startstr as $k => $v){
            $all = $v['pro'].$v['city'].$v['area'].$v['info'];

            $common_address = AppCommonAddress::find()->where(['group_id'=>$user->parent_group_id,'all'=>$all])->one();
            if ($common_address){
                @$common_address->updateCounters(['count_views'=>1]);
            }else{
                $common_address = new AppCommonAddress();
                $common_address->pro_id = $v['pro'];
                $common_address->city_id = $v['city'];
                $common_address->area_id = $v['area'];
                $common_address->address = $v['info'];
                $common_address->all = $all;
                $common_address->group_id = $group_id;
                $common_address->create_user = $user->name;
                $common_address->create_user_id = $user->id;
                @$common_address->save();
            }

            $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
            if ($common_contact){
                @$common_contact->updateCounters(['views'=>1]);
            }else{
                $common_contact = new AppCommonContacts();
                $common_contact->name = $v['contant'];
                $common_contact->tel = $v['tel'];
                $common_contact->user_id = $user->id;
                $common_contact->create_user = $user->name;
                $common_contact->create_userid = $user->id;
                @$common_contact->save();
            }
        }
        $order->endstr = $endstr;
        $arr_endstr = json_decode($endstr,true);

        foreach ($arr_endstr as $k => $v){
            $all = $v['pro'].$v['city'].$v['area'].$v['info'];
            $common_address = AppCommonAddress::find()->where(['group_id'=>$group_id,'all'=>$all])->one();
            if ($common_address){
                @$common_address->updateCounters(['count_views'=>1]);
            }else{
                $common_address = new AppCommonAddress();
                $common_address->pro_id = $v['pro'];
                $common_address->city_id = $v['city'];
                $common_address->area_id = $v['area'];
                $common_address->address = $v['info'];
                $common_address->all = $all;
                $common_address->group_id = $group_id;
                $common_address->create_user = $user->name;
                $common_address->create_user_id = $user->id;
                @$common_address->save();
            }

            $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
            if ($common_contact){
                @$common_contact->updateCounters(['views'=>1]);
            }else{
                $common_contact = new AppCommonContacts();
                $common_contact->name = $v['contant'];
                $common_contact->tel = $v['tel'];
                $common_contact->user_id = $user->id;
                $common_contact->create_user = $user->name;
                $common_contact->create_userid = $user->id;
                @$common_contact->save();
            }
        }
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $order->endstr = $endstr;
        $order->name = $cargo_name;
        $order->number = $cargo_number;
        $order->number2 = $cargo_number2;
        $order->weight = $cargo_weight;
        $order->volume = $cargo_volume;
        $order->create_user_id = $user['id'];
        $order->create_user_name = $user['name'];
        $order->temperture = $temperture;
        $order->remark = $remark;
        $order->picktype = $picktype;
        $order->sendtype = $sendtype;
        $order->time_start = $start_time;//用车时间
        $order->time_end = $end_time;//预计到达时间
        $order->price = $price;
        $order->total_price = $total_price;
        $order->line_start_contant = $startstr;
        $order->line_end_contant = $endstr;
        $res =  $order->save();
        if ($res){
            $this->hanldlog($user->id,'修改订单:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'修改成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'修改失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 确认订单
     * */
    public function actionDeal_confirm(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if($order->order_stage == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'已确认，请勿重复操作']);
            return $this->resultInfo($data);
        }
        $order->order_stage = 3;
        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'完成订单处理'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 在线订单
     * */
    public function actionOnline_index(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $chitu = $input['chitu'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $ordernumber = $input['ordernumber'] ?? '';
        $begintime = $input['begintime'] ?? '';

        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];

        if (empty($token)) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $check_result = $this->check_token_list($token,$chitu);//验证令牌
        $list = AppOrder::find()
            ->alias('v')
            ->select(['v.*','t.carparame'])
            ->leftJoin('app_cartype t','v.cartype=t.car_id')
            ->where(['v.line_status'=>2,'order_status'=>1,'v.delete_flag'=>'Y']);
        if($ordernumber){
            $list->andWhere(['like','v.ordernumber',$ordernumber]);
        }
        if ($begintime) {
            $time_s = $begintime . ' 00:00:00';
            $time_e = $begintime . ' 23:59:59';
            $list->andWhere(['between','v.time_start',$time_s,$time_e]);
        }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.time_start'=>SORT_DESC])
            ->asArray()
            ->all();
        foreach($list as $key =>$value){
            $list[$key]['startstr'] = json_decode($value['startstr'],true);
            $list[$key]['endstr'] = json_decode($value['endstr'],true);
        }
        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'auth' =>$check_result['auth'],
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }


    /*
     * 在线线路
     * */
    public function actionOnline_line(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $line_start_city = $input['line_start_city'] ?? '';
        $line_end_city = $input['line_end_city'] ?? '';
        $startarea = $input['startarea'] ?? '';
        $endarea = $input['endarea'] ?? '';
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (empty($token)) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $check_result = $this->check_token_list($token);//验证令牌
        $list = AppLine::find();
        if ($line_start_city) {
            $list->andWhere(['like','startcity',$line_start_city]);
        }

        if ($line_end_city) {
            $list->andWhere(['like','endcity',$line_end_city])
                ->orWhere(['like','transfer',$line_end_city]);
        }
        $list->andWhere(['delete_flag'=>'Y','line_state'=>2,'state'=>1]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['update_time'=>SORT_DESC,'start_time'=>SORT_DESC])
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['set_price'] = json_decode($v['weight_price'],true);
            $begin_store = json_decode($v['begin_store'],true);
            $end_store = json_decode($v['end_store'],true);
            $transfer_info = json_decode($v['transfer_info'],true);

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
            'auth'  => $check_result['auth'],
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }
    /* -------------   调度处理     -------------------*/
    /*
     * 调度列表
     * */
    public function actionDispatch_index(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $ordernumber = $input['ordernumber'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $startcity = $input['startcity'] ?? '';
        $endcity = $input['endcity'] ??'';

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
        $list = AppOrder::find()
            ->alias('v')
            ->select(['v.*', 't.carparame'])
            ->leftJoin('app_cartype t', 'v.cartype=t.car_id')
            ->where(['v.group_id' => $group_id, 'v.delete_flag' => 'Y','order_stage'=>3,'line_status'=>1]);
        if ($ordernumber) {
            $list->andWhere(['like', 'v.ordernumber', $ordernumber]);
        }

//        if($startcity && $endcity){
//            $list->andWhere(['like','v.startcity',$startcity])
//                 ->andWhere(['like','v.endcity',$endcity]);
//        }else{
//            if ($startcity){
//                $list->andWhere(['like','v.startcity',$startcity]);
//            }else if($endcity){
//                $list->andWhere(['like','v.endcity',$endcity]);
//            }
//        }
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
        foreach ($list as $k=>$v) {
            $list[$k]['startstr'] = json_decode($v['startstr'],true);
            $list[$k]['endstr'] = json_decode($v['endstr'],true);
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
     * 取消订单处理
     * */
    public function actionCancel_deal(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if($order->order_status == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已调度,不能执行此操作']);
            return $this->resultInfo($data);
        }
        $order->order_stage = 2;
        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'取消已处理的订单'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 调度(合并订单，分配车辆)
     * */
    public function actionDispatch(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $ids = $input['ids'];
        $group_id = $input['group_id'];
//         $ids =  explode(',',$ids);
        $type = $input['type'];
        $price = $input['price'];
        $carriage_info = json_decode($input['arr'],true);
        $order_type = $input['order_type'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user= $check_result['user'];

        $list = AppOrder::find()->where(['in','id',$ids])->andWhere(['group_id'=>$group_id])->asArray()->all();
        $volume =$this->get_params($ids,'volume');
        $weight = $this->get_params($ids,'weight');
        $number = $this->get_params($ids,'number');
        $number1 = $this->get_params($ids,'number2');
        $startstr = $endstr = $temperture = [];

        foreach ($list as $key =>$value){

            $startstr = array_merge($startstr,json_decode($value['startstr'],true));
            $endstr = array_merge($endstr,json_decode($value['endstr'],true));
            $order['startcity'] = $value['startcity'];
            $order['endcity']   = $value['endcity'];
        }

        $startstr = array_unique($startstr,SORT_REGULAR);
        $endstr = array_unique($endstr,SORT_REGULAR);
//        $temperture = array_unique($temperture,SORT_REGULAR);
        $transaction= AppMegerOrder::getDb()->beginTransaction();
        try {
            $pick_order = new AppMegerOrder();
            $pick_order->ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);;
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
            $pick_order->driverinfo = $input['arr'];
            $pick_order->ordertype = $order_type;
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
                        $list_c['type'] = $order_type;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s', time());
                        $info_c[] = $list_c;
                        $deal_company = '';
                    }
                    $res = Yii::$app->db->createCommand()->batchInsert(AppOrderCarriage::tableName(), ['pick_id', 'group_id', 'create_user_id', 'carriage_price', 'type', 'contant', 'carnumber', 'tel','startstr','endstr', 'create_time', 'update_time'], $pick_lists)->execute();
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
                        $list_c['type'] = $order_type;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s', time());
                        $info_c[] = $list_c;
                        $deal_company = $value['id'];
                    }
                    $res = Yii::$app->db->createCommand()->batchInsert(AppOrderCarriage::tableName(), ['pick_id', 'group_id', 'create_user_id', 'carriage_price', 'type', 'deal_company', 'contant', 'carnumber', 'tel','startstr','endstr', 'create_time', 'update_time'], $pick_lists)->execute();
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
                        $list_c['type'] = $order_type;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s', time());
                        $info_c[] = $list_c;
                        $deal_company = '';
                    }
                    $res = Yii::$app->db->createCommand()->batchInsert(AppOrderCarriage::tableName(), ['pick_id', 'group_id', 'create_user_id', 'carriage_price', 'type', 'contant', 'carnumber', 'tel','startstr','endstr', 'create_time', 'update_time'], $pick_lists)->execute();
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
            $lists = AppOrder::updateAll(['order_status'=>3,'order_stage'=>4],['in', 'id', $ids]);
            foreach($ids as $key => $value){
                 $list = AppOrder::findOne($value);
                 if ($list->main_order == 1){
                     if ($list->line_id){
                         if ($list->order_type != 7 && $list->order_type != 10){
                             $list_main = AppOrder::findOne($list->line_id);
                             $list_main->driverinfo = $input['arr'];
                             $list_main->order_status = 3;
                             $list_main->save();
                         }else{
                             $list_main = AppBulk::findOne($list->line_id);
                             $list_main->orderstate = 3;
                             $list_main->save();
                         }
                     }else{
                         $list->driverinfo = $input['arr'];
                         $list->order_status = 3;
                         $list->save();
                     }
                 }else{
                     $list_split = AppOrder::findOne($list->split_id);
                     if ($list_split->line_id){
                         if ($list->order_type != 7 && $list->order_type != 10) {
                             $list_line = AppOrder::findOne($list_split->line_id);
                             $list_line->driverinfo = $input['arr'];
                             $list_line->order_status = 3;
                             $list_line->save();
                         }else{
                             $list_line = AppBulk::findOne($list_split->line_id);
                             $list_line->orderstate = 3;
                             $list_line->save();
                         }
                     }else{
                         $list_split->driverinfo = $input['arr'];
                         $list_split->order_status = 3;
                         $list_split->save();
                     }
                 }
            }
            if ($arr && $lists && $res && $carriage && $res_pick){
                $transaction->commit();
                $this->hanldlog($user->id,'调度订单:');
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
     * 调度订单详情
     * */
    public function actionSelect_info(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $ids = $input['ids'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
//         $check_result = $this->check_token($token,false);
//        $user= $check_result['user'];
        $list = AppOrder::find()
            ->where(['in','id',$ids])
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['startstr'] = json_decode($v['startstr'],true);
            $list[$k]['endstr'] = json_decode($v['endstr'],true);
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list]);
        return $this->resultInfo($data);

    }


    /*
     * 调度完成
     * */
    public function actionDispatch_confirm(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if($order->order_stage == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'已确认，请勿重复操作']);
            return $this->resultInfo($data);
        }
        $order->order_stage = 4;
        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'完成调度'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 取消调度
     * */
    public function actionCancel_dispatch(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $pick_id = $input['pick_id'];
        if (empty($token) || empty($id) || empty($pick_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $pickorder = AppOrder::findOne($id);
        $this->check_group_auth($pickorder->group_id,$user);
        if ($pickorder->order_status == 6){
            $data = $this->encrypt(['code'=>400,'msg'=>'不能取消，订单已完成']);
            return $this->resultInfo($data);
        }
        $pickorder->order_status = 1;
        $pickorder->order_stage = 3;
        $order = AppMegerOrder::find()->where(['id'=>$pick_id])->one();
        $volume =  $order->volume - $pickorder->volume;
        $number = $order->number - $pickorder->number;
        $number1 = $order->number1 - $pickorder->number2;
        $weight = $order->weight - $pickorder->weight;
        $ids = explode(',',$order->order_ids);
        foreach($ids as $key => $value){
            if ($value == $id){
                unset($ids[$key]);
            }
        }

        if (count($ids)>=1){
            $ids = implode(',',$ids);
//            $order->state = 9;
            $order->order_ids = $ids;
            $order->volume = $volume;
            $order->number = $number;
            $order->number1 = $number1;
            $order->weight = $weight;
        }

        if(count($ids)<1){
            $order->delete();
            $payment = AppPayment::find()->where(['order_id'=>$pick_id,'group_id'=>$order->group_id])->one();
            if ($payment){
                $payment->delete();
            }
            $carriage = AppOrderCarriage::find()->where(['pick_id'=>$pick_id,'group_id'=>$order->group_id])->one();
            if ($carriage){
                $carriage->delete();
            }
        }
        $res = true;
        $transaction= AppOrder::getDb()->beginTransaction();
        try {
            if (count($ids)>=1){
                $res = $order->save();
            }
            $res_p = $pickorder->save();
            if ($res && $res_p){
                $transaction->commit();
                $this->hanldlog($user->id,'取消调度'.$order->ordernumber);
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

    /* ----------------        运输列表         -------------------*/

    /*
     * 运输列表
     * */
    public function actionCarriage_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
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
        if (empty($token) || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

       $check_result = $this->check_token_list($token);//验证令牌
        $list = AppMegerOrder::find()
            ->alias('v')
            ->select(['v.*', 't.carparame','b.name'])
            ->leftJoin('app_cartype t', 'v.cartype=t.car_id')
            ->leftJoin('app_carriage b','v.deal_company = b.cid')
            ->where(['v.group_id' => $group_id]);
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
            'auth' => $check_result['auth'],
            'data' => precaution_xss($list)
        ];
        return json_encode($data);
    }

    /*
     * 详细订单列表
     * */
    public function actionCarriage_view_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
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
        if (empty($token) || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $check_result = $this->check_token_list($token);//验证令牌
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
            'auth' => $check_result['auth'],
            'data' => precaution_xss($list)
        ];
        return json_encode($data);
    }

    /*
     * 完成订单(合单)
     * */
    public function actionOrder_done(){
          $input = Yii::$app->request->post();
          $token = $input['token'];
          $id = $input['id'];
          if (empty($token) || empty($id)){
              $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
              return $this->resultInfo($data);
          }
          $check_result = $this->check_token($token,true);
          $user = $check_result['user'];
          $merage_order = AppMegerOrder::findOne($id);
          if ($merage_order->state == 8){
              $data = $this->encrypt(['code'=>400,'msg'=>'订单已完成，请勿重复提交']);
              return $this->resultInfo($data);
          }
          $this->check_group_auth($merage_order->group_id,$user);
          $merage_order->state = 8;
          $main_id = $oid = $split_id = $id_take=[];
          $id_in = $id_info = $id_split =$split_list = [];
          $count_id = '';
          $transaction= AppMegerOrder::getDb()->beginTransaction();
          try {
              $res = $merage_order->save();
              foreach (explode(',',$merage_order->order_ids) as $key => $value){
                  $order = AppOrder::findOne($value);
                  if ($order->main_order == 1){
                       if ($order->split_id){
                           $id_take = $order->id;
                           $id_takes = $id_take;
                       }else{
                           $ids[] = $order->id;
                           $id_info = $ids;
                       }
                  }else{
                       $ids_in[] = $order->id;
                       $id_in = $ids_in;
                       $main_order = AppOrder::findOne($order->split_id);
                       if ($main_order->order_status != 4){
                           $id_split = $main_order->id;
                       }
                       //合单最后一条分单点击完成修改原始订单状态
                       $order_list = AppOrder::find()->where(['split_id'=>$order->split_id])->asArray()->all();
                       foreach ($order_list as $k =>$v){
                           if($v['order_status'] == 6){
                               $list_id[] = $v['id'];
                               $split_list = $list_id;
                           }
                       }
                       if (count($order_list) - count($split_list) == 1){
                           $count_id = $order->split_id;
                       }
                  }
              }
              if ($id_info){
                  $list = AppOrder::updateAll(['order_status'=>5],['in', 'id', $id_info]);
              }
              if ($id_takes){
                  $list_take = AppOrder::updateAll(['order_status'=>5],['in', 'id', $id_takes]);
              }
              if ($id_in){
                  $lists = AppOrder::updateAll(['order_status'=>6],['in', 'id',$id_in]);
              }
              if($id_split){
                  $split_order = AppOrder::updateAll(['order_status'=>4],['in','id',$id_split]);
              }
              if ($count_id){
                  $count_order = AppOrder::findOne($count_id);
                  $count_order->order_status = 5;
                  $count_order->save();
              }
//              $lists = AppOrder::updateAll(['order_status'=>6],['in', 'id', explode(',',$merage_order->order_ids)]);
              $transaction->commit();
              $this->hanldlog($user->id,'完成订单'.$merage_order->ordernumber);
              $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
              return $this->resultInfo($data);
          }catch(\Exception $e){
              $transaction->rollBack();
              $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
              return $this->resultInfo($data);
          }
    }

    /*
     *完成订单（分单）
     * */
    public function actionSplit_done(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $merage_order = AppOrder::findOne($id);
        $this->check_group_auth($merage_order->group_id,$user);
        if ($merage_order->main_order == 1){
            $merage_order->order_status = 5;
        }else{
            $merage_order->order_status = 6;
            $main_order = AppOrder::find($merage_order->split_id);
            if($main_order->order_status != 4){
                $main_order->order_status = 4;
                $main_order->save();
            }
        }
        $res = $merage_order->save();
        if ($res){
            $this->hanldlog($user->id,'完成订单'.$merage_order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*   -------------------------------  上线订单列表      -------------------------------*/
    /*
     * 上线列表
     * */
    public function actionOnline_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $ordernumber = $input['ordernumber'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $chitu = $input['chitu'];

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

        $check_result = $this->check_token_list($token,$chitu);//验证令牌
        $list = AppOrder::find()
            ->alias('v')
            ->select(['v.*', 't.carparame'])
            ->leftJoin('app_cartype t', 'v.cartype=t.car_id')
            ->where(['v.group_id' => $group_id,'v.line_status'=>2]);
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
     * 下线
     * */
    public function actionUnline(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已被承接，不可以下线']);
            return $this->resultInfo($data);
        }
        $order->line_status = 1;
        $res = $order->save();
        if ($res){
            $data = $this->encrypt(['code'=>200,'msg'=>'下线成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * (系统)订单完成
     * */
    public function actionOnline_done(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status != 5){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单状态错误，请刷新重试!']);
            return $this->resultInfo($data);
        }
        $split_id = [];
        $count_id = '';
        if($order->main_order==1){
            if($order->line_id){
                if($order->order_type == 5 || $order->order_type == 6){
                    $main_order = AppOrder::findOne($order->line_id);
                    $main_order->order_status = 5;
                }else if($order->order_type == 10){
                    $main_order = AppBulk::findOne($order->line_id);
                    $main_order->orderstate = 4;
                }
            }
            $order->order_status = 6;
            if ($main_order){
                $res_m = $main_order->save();
            }
            $res_o = $order->save();
            if($res_o){
                $this->hanldlog($user->id,'完成订单:'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'订单已完成']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                return $this->resultInfo($data);
            }
        }else{
            $split_list = AppOrder::find()->where(['split_id'=>$order->split_id])->asArray()->all();
            foreach($split_list as $key =>$value){
                if($value['order_status'] == 6){
                    $list_id[] = $value['id'];
                    $split_id = $list_id;
                }
            }
            if(count($split_list) - count($split_id) == 1){
                $count_id = $order->split_id;
            }
            $split_order = AppOrder::findOne($order->split_id);
            if ($count_id){
                $count_order = AppOrder::findOne($count_id);
                $count_order->order_status = 5;
                $res_c = $count_order->save();
                if($count_order->line_id){
                    if($order->order_type == 5 || $order->order_type == 6){
                        $main_order = AppOrder::findOne($order->line_id);
                        $main_order->order_status = 5;
                    }else if($order->order_type == 10){
                        $main_order = AppBulk::findOne($order->line_id);
                        $main_order->orderstate = 4;
                    }
                }
            }
            $order->order_status = 6;
            $res = $order->save();
            if($res){
                $this->hanldlog($user->id,'完成订单:'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'订单已完成']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                return $this->resultInfo($data);
            }
        }
    }

    /*
     * 订单送达
     * */
    public function actionArrive_done(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];

        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);

        if ($order->order_status == 5){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已送达，等待客户确认']);
            return $this->resultInfo($data);
        }
        $order->order_status = 5;// 订单状态 5 已送达
        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'订单已送达'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*   -----------------------     零担下单           ---------------------------*/
    /*
     * 零担干线系统内部下单
     * */
    public function actionBulk_add(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
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
        $otherprice = $input['otherprice'] ?? 0;
        $customer_id = $input['customer_id'];
        $customer_price = $input['customer_price'] ?? 0;
        $line_type = $input['line_type'];

        if (empty($token) || empty($shiftid) || empty($group_id)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($customer_id)){
            $data = $this->encrypt(['code' => '400', 'msg' => '请选择客户']);
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
            $data = $this->encrypt(['code' => '400', 'msg' => '货物名称不能为空']);
            return $this->resultInfo($data);
        }
        if ($lineprice ==''){
            $data = $this->encrypt(['code' => '400', 'msg' => '干线价格不能为空']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        if ($begin_info){
            $arr_startstr = json_decode($begin_info,true);
            foreach ($arr_startstr as $k => $v){
                $all = $v['pro'].$v['city'].$v['area'].$v['info'];

                $common_address = AppCommonAddress::find()->where(['group_id'=>$user->parent_group_id,'all'=>$all])->one();
                if ($common_address){
                    // @$common_address->updateCounters(['count_views'=>1]);
                }else{
                    $common_address = new AppCommonAddress();
                    $common_address->pro_id = $v['pro'];
                    $common_address->city_id = $v['city'];
                    $common_address->area_id = $v['area'];
                    $common_address->address = $v['info'];
                    $common_address->all = $all;
                    $common_address->group_id = $group_id;
                    $common_address->create_user = $user->name;
                    $common_address->create_user_id = $user->id;
                    @$common_address->save();
                }

                $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
                if ($common_contact){
                    // @$common_contact->updateCounters(['views'=>1]);
                }else{
                    $common_contact = new AppCommonContacts();
                    $common_contact->name = $v['contant'];
                    $common_contact->tel = $v['tel'];
                    $common_contact->user_id = $user->id;
                    $common_contact->create_user = $user->name;
                    $common_contact->create_userid = $user->id;
                    @$common_contact->save();
                }
            }
        }
        if ($end_info){
            $arr_endstr = json_decode($end_info,true);
            foreach ($arr_endstr as $k => $v){
                $all = $v['pro'].$v['city'].$v['area'].$v['info'];
                $common_address = AppCommonAddress::find()->where(['group_id'=>$group_id,'all'=>$all])->one();
                if ($common_address){
                    // @$common_address->updateCounters(['count_views'=>1]);
                }else{
                    $common_address = new AppCommonAddress();
                    $common_address->pro_id = $v['pro'];
                    $common_address->city_id = $v['city'];
                    $common_address->area_id = $v['area'];
                    $common_address->address = $v['info'];
                    $common_address->all = $all;
                    $common_address->group_id = $group_id;
                    $common_address->create_user = $user->name;
                    $common_address->create_user_id = $user->id;
                    @$common_address->save();
                }

                $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
                if ($common_contact){
                    // @$common_contact->updateCounters(['views'=>1]);
                }else{
                    $common_contact = new AppCommonContacts();
                    $common_contact->name = $v['contant'];
                    $common_contact->tel = $v['tel'];
                    $common_contact->user_id = $user->id;
                    $common_contact->create_user = $user->name;
                    $common_contact->create_userid = $user->id;
                    @$common_contact->save();
                }
            }
        }
        $bulk = new AppBulk();
        $line = AppLine::findOne($shiftid);
        $groupid = $user->parent_group_id;
        $group = AppGroup::find()->where(['id'=>$groupid])->one();
        $transaction= AppBulk::getDb()->beginTransaction();
        $arr = true;
        try {
            $bulk->customer_id = $customer_id;
            $bulk->ordernumber = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
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
            $bulk->create_user_id = $user->id;
            $bulk->remark = $remark;
            $bulk->otherprice = $otherprice;
            $bulk->total_price = $lineprice + $bulk->pickprice + $bulk->sendprice + $otherprice;
            $bulk->line_type = $line_type;
            $res = $bulk->save();

            $receive = new AppReceive();
            $time = date('Y-m-d H:i:s', time());
            $receive->compay_id = $customer_id;
            $receive->receivprice = $customer_price;
            $receive->trueprice = 0;
            $receive->order_id = $bulk->id;
            $receive->receive_info = '';
            $receive->create_user_id = $user->id;
            $receive->create_user_name = $user->name;
            $receive->group_id = $group_id;
            $receive->create_time = $time;
            $receive->update_time = $time;
            $receive->type = 2;
            $receive->ordernumber = $bulk->ordernumber;
            $arr = $receive->save();
            if ($res && $arr) {
                $transaction->commit();
                if($line_type == 1){
                    $this->copy_bulk($user,$bulk->id,$line_type);
                }
                $this->hanldlog($user->id, '干线下单:' . $bulk->id);
                $data = $this->encrypt(['code' => '200', 'msg' => '下单成功', 'data' => $bulk->id]);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>'400','msg'=>'下单失败']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>'400','msg'=>'下单失败']);
            return $this->resultInfo($data);
        }
    }

    /*
    * 复制生成内部新订单
    * */
    public function copy_bulk($user,$id){
        $input = Yii::$app->request->post();
        $bulk = AppBulk::find()
            ->alias('a')
            ->select('a.*,b.start_time,b.trunking,b.arrive_time,b.begin_store,b.end_store')
            ->leftJoin('app_line b','a.shiftid = b.id')
            ->where(['a.id'=>$id])
            ->asArray()
            ->one();
        $order = new AppOrder();
        $order->ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->takenumber = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->name = $bulk['goodsname'];
        $order->number = $bulk['number'];
        $order->number2 = $bulk['number1'];
        $order->weight = $bulk['weight'];
        $order->volume = $bulk['volume'];
        $order->temperture = $bulk['temperture'];
        $order->remark = $bulk['remark'];
        $order->group_id = $bulk['group_id'];
        $order->create_user_id = $user->id;
        $order->create_user_name = $user->name;
        $order->cartype = 1;
        $order->picktype = $bulk['picktype'];
        $order->sendtype = $bulk['sendtype'];
        $order->money_state = 'N';
        $order->startcity = $bulk['begincity'];
        $order->endcity = $bulk['endcity'];
        $order->startstr = $bulk['begin_info'];
        $order->endstr = $bulk['end_info'];
        $order->pickprice = $bulk['pickprice'];
        $order->sendprice = $bulk['sendprice'];
        $order->price = $bulk['total_price'];
        $order->total_price = $bulk['total_price'];
        $order->time_start = $bulk['start_time'];
        $order->time_end = $bulk['arrive_time'];
        $order->line_start_contant = $bulk['begin_info'];
        $order->line_end_contant = $bulk['end_info'];
        $order->start_store = $bulk['begin_store'];
        $order->end_store = $bulk['end_store'];
        $order->line_id = $bulk['id'];
        $order->remark = $bulk['remark'];
        $order->order_type = 10;
        $order->paytype = $bulk['pay_state'];
        $res =  $order->save();
        $list = AppBulk::findOne($bulk['id']);
        $list->copy = 2;
        $list->save();
        if ($res){
            $this->hanldlog($user->id,'生成内部零担订单'.$bulk->ordernumber);
            $arr = true;
//            $data = $this->encrypt(['code'=>'200','msg'=>'操作成功']);
//            return $this->resultInfo($data);
        }else{
            $arr = false;
//            $data = $this->encrypt(['code'=>'400','msg'=>'操作失败']);
//            return $this->resultInfo($data);
        }

    }

    /*
     * 零担订单完成
     * */
    public function actionBulk_done(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppBulk::find()
            ->alias('a')
            ->select(['a.*','b.group_id As groupid'])
            ->leftJoin('app_line b','a.shiftid = b.id')
            ->where(['a.id'=>$id])
            ->asArray()
            ->one();

        if ($order['orderstate'] != 4){
            $data = $this->encrypt(['code'=>'400','msg'=>'订单还在运输中']);
            return $this->resultInfo($data);
        }
        $this->check_group_auth($order['group_id'],$user);
        $bulk = AppBulk::findOne($id);
        $bulk->orderstate = 5;

        $group = AppGroup::find()->where(['id'=>$order['groupid']])->one();
        $group->balance = $group->balance + $order['total_price'];
        $balance = new AppBalance();
        $balance->pay_money = $order['total_price'];
        $balance->order_content = '零担订单收入';
        $balance->action_type = 9;
        $balance->userid = $user->id;
        $balance->create_time = date('Y-m-d H:i:s',time());
        $balance->ordertype = 2;
        $balance->orderid = $id;
        $balance->group_id = $user->group_id;
        $paymessage = new AppPaymessage();
        $paymessage->paynum = $order['total_price'];
        $paymessage->create_time = date('Y-m-d H:i:s',time());
        $paymessage->userid = $user->id;
        $paymessage->paytype = 3;
        $paymessage->type = 1;
        $paymessage->state = 5;
        $paymessage->orderid = $order['ordernumber'];
        $receive = AppReceive::find()->where(['group_id'=>$order['groupid'],'order_id'=>$id,'type'=>2])->one();
        $receive->status = 3;
        $payment = AppPayment::find()->where(['group_id'=>$order['group_id'],'order_id'=>$id,'type'=>2])->one();
        $payment->status = 3;
        $payment->al_pay = $order['total_price'];

        $transaction= AppBulk::getDb()->beginTransaction();
        try {
            $res_b = $balance->save();
            $res_pay = $paymessage->save();
            $res_g = $group->save();
            $res = $bulk->save();
            $arr = $receive->save();
            $res_p = $payment->save();
            if ($res && $arr && $res_g && $res_p &&$res_pay && $res_b){
                $transaction->commit();
                $this->hanldlog($user->id,'完成零担订单'.$order['ordernumber']);
                $data = $this->encrypt(['code'=>200,'msg'=>'已完成']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 零担干线下单(平台)
     * */
    public function actionBulk_order(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
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
        $otherprice = $input['otherprice'] ?? 0;
        $customer_id = $input['customer_id'] ?? '';
        $customer_price = $input['customer_price'] ?? 0;
        $line_type = $input['line_type'];
        if (empty($token) || empty($shiftid) || empty($group_id)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '参数错误']);
            return $this->resultInfo($data);
        }
        if ($customer_id){
            if ($customer_price == ''){
                $data = $this->encrypt(['code' => '400', 'msg' => '干线费不能为空']);
                return $this->resultInfo($data);
            }
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
            $data = $this->encrypt(['code' => '400', 'msg' => '货物名称不能为空']);
            return $this->resultInfo($data);
        }
        if ($lineprice ==''){
            $data = $this->encrypt(['code' => '400', 'msg' => '干线价格不能为空']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        if ($begin_info){
            $arr_startstr = json_decode($begin_info,true);
            foreach ($arr_startstr as $k => $v){
                $all = $v['pro'].$v['city'].$v['area'].$v['info'];

                $common_address = AppCommonAddress::find()->where(['group_id'=>$user->parent_group_id,'all'=>$all])->one();
                if ($common_address){
                    // @$common_address->updateCounters(['count_views'=>1]);
                }else{
                    $common_address = new AppCommonAddress();
                    $common_address->pro_id = $v['pro'];
                    $common_address->city_id = $v['city'];
                    $common_address->area_id = $v['area'];
                    $common_address->address = $v['info'];
                    $common_address->all = $all;
                    $common_address->group_id = $group_id;
                    $common_address->create_user = $user->name;
                    $common_address->create_user_id = $user->id;
                    @$common_address->save();
                }

                $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
                if ($common_contact){
                    // @$common_contact->updateCounters(['views'=>1]);
                }else{
                    $common_contact = new AppCommonContacts();
                    $common_contact->name = $v['contant'];
                    $common_contact->tel = $v['tel'];
                    $common_contact->user_id = $user->id;
                    $common_contact->create_user = $user->name;
                    $common_contact->create_userid = $user->id;
                    @$common_contact->save();
                }
            }
        }
        if ($end_info){
            $arr_endstr = json_decode($end_info,true);
            foreach ($arr_endstr as $k => $v){
                $all = $v['pro'].$v['city'].$v['area'].$v['info'];
                $common_address = AppCommonAddress::find()->where(['group_id'=>$group_id,'all'=>$all])->one();
                if ($common_address){
                    // @$common_address->updateCounters(['count_views'=>1]);
                }else{
                    $common_address = new AppCommonAddress();
                    $common_address->pro_id = $v['pro'];
                    $common_address->city_id = $v['city'];
                    $common_address->area_id = $v['area'];
                    $common_address->address = $v['info'];
                    $common_address->all = $all;
                    $common_address->group_id = $group_id;
                    $common_address->create_user = $user->name;
                    $common_address->create_user_id = $user->id;
                    @$common_address->save();
                }

                $common_contact = AppCommonContacts::find()->where(['user_id'=>$user->id,'name'=>$v['contant'],'tel'=>$v['tel']])->one();
                if ($common_contact){
                    // @$common_contact->updateCounters(['views'=>1]);
                }else{
                    $common_contact = new AppCommonContacts();
                    $common_contact->name = $v['contant'];
                    $common_contact->tel = $v['tel'];
                    $common_contact->user_id = $user->id;
                    $common_contact->create_user = $user->name;
                    $common_contact->create_userid = $user->id;
                    @$common_contact->save();
                }
            }
        }
        $bulk = new AppBulk();
        $line = AppLine::findOne($shiftid);
        $transaction= AppBulk::getDb()->beginTransaction();
        $res_s = $res_p = $arr = true;
        try {
            $bulk->customer_id = $group_id;
            $bulk->ordernumber = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
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
            $bulk->create_user_id = $user->id;
            $bulk->remark = $remark;
            $bulk->otherprice = $otherprice;
            $bulk->total_price = $lineprice + $bulk->pickprice + $bulk->sendprice + $otherprice;
            $bulk->line_type = $line_type;
            $res = $bulk->save();
            if ($res && $arr && $res_p && $res_s) {
                $transaction->commit();
                $this->hanldlog($user->id, '干线下单:' . $bulk->id);
                $data = $this->encrypt(['code' => '200', 'msg' => '下单成功', 'data' => $bulk->id]);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>'400','msg'=>'下单失败']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>'400','msg'=>'下单失败']);
            return $this->resultInfo($data);
        }
    }
/*
 *                                                     __----~~~~~~~~~~~------___
 *                                    .  .   ~~//====......          __--~ ~~
 *                    -.            \_|//     |||\\  ~~~~~~::::... /~
 *                 ___-==_       _-~o~  \/    |||  \\            _/~~-
 *         __---~~~.==~||\=_    -_--~/_-~|-   |\\   \\        _/~
 *     _-~~     .=~    |  \\-_    '-~7  /-   /  ||    \      /
 *   .~       .~       |   \\ -_    /  /-   /   ||      \   /
 *  /  ____  /         |     \\ ~-_/  /|- _/   .||       \ /
 *  |~~    ~~|--~~~~--_ \     ~==-/   | \~--===~~        .\
 *           '         ~-|      /|    |-~\~~       __--~~
 *                       |-~~-_/ |    |   ~\_   _-~            /\
 *                            /  \     \__   \/~                \__
 *                        _--~ _/ | .-~~____--~-/                  ~~==.
 *                       ((->/~   '.|||' -_|    ~~-/ ,              . _||
 *                                  -_     ~\      ~~---l__i__i__i--~~_/
 *                                  _-~-__   ~)  \--______________--~~
 *                                //.-~~~-~_--~- |-------~~~~~~~~
 *                                       //.-~~~--\
 *                       ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 *
 *                               神兽保佑            永无BUG
 */

}




