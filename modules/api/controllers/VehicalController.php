<?php

namespace app\modules\api\controllers;

use app\models\AppBalance;
use app\models\AppCarriageList;
use app\models\AppOrder;
use app\models\AppRefund;
use app\models\Carriage;
use app\models\AppMember;
use app\models\AppPayment;
use app\models\AppPaymessage;
use app\models\AppReceive;
use app\models\Car;
use app\models\Upload;
use app\models\District;
use app\models\AppCommonContacts;
use app\models\AppCommonAddress;
use app\models\AppVehical;
use app\models\AppGroup;
use app\models\Customer;
use app\models\AppUnusual;
use app\models\AppCartype;
use app\models\AppSetting;
use Yii;

/**
 * Default controller for the `api` module
 */
class VehicalController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    /*
     * 查看订单
     * */
    public function  actionIndex(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $ordernumber = $input['name'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $endtime = $input['endtime'] ?? '';
        $customer = $input['customer'] ?? '';
        $status = $input['order_status'] ?? '';
        $num = $input['num'] ?? '';

        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (empty($token) || !$group_id){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
//        $list = AppVehical::find()
//            ->where(['delete_flag'=>'Y','order_status'=>$status]);
        $check_result = $this->check_token_list($token);//验证令牌
        $list = AppVehical::find()
            ->alias('v')
            ->select(['v.*','c.all_name','t.carparame'])
            ->leftJoin('app_customer c','v.company_id=c.id')
            ->leftJoin('app_cartype t','v.cartype=t.car_id');
        if ($ordernumber) {
            $list->andWhere(['like','v.ordernumber',$ordernumber]);
        }
        if($customer){
            $list->andWhere(['like','v.customer',$customer]);
        }
        if ($begintime && $endtime){
            $list->andWhere(['between','v.create_time',$begintime,$endtime]);
        }
        if ($status){
            $list->andWhere(['v.order_status'=>$status]);
        }        

        if ($num){
            $list->andWhere(['like','v.ordernumber',$num]);
        }
        $list->andWhere(['v.group_id'=>$group_id,'line_status'=>1]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.update_time'=>SORT_DESC,'v.order_status'=>SORT_ASC])
            ->asArray()
            ->all();

        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'auth' => $check_result['auth'],
            'data'  => $list
        ];
        return json_encode($data);
    }

    /*
    * 添加订单
    * */
    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $company_id = $input['company_id'];
        $start_time = $input['start_time'];
        $end_time = $input['end_time'];
        $cartype = $input['cartype'];
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startstr = $input['startstr'];
        $endstr = $input['endstr'];
        $cargo_name = $input['cargo_name'];
        $cargo_number = $input['cargo_number'];
        $cargo_number2 = $input['cargo_number2'];
        $cargo_weight = $input['cargo_weight'];
        $cargo_volume = $input['cargo_volume'];
        $remark = $input['remark'];
        $temperture = $input['temperture'];
        $picktype = $input['picktype'];
        $sendtype = $input['sendtype'];
        $pickprice = $input['pickprice'] ?? 0;
        $sendprice = $input['sendprice'] ?? 0;
        $price = $input['price'] ?? 0;
        $otherprice = $input['otherprice'] ?? 0;
        $more_price = $input['more_price'] ?? 0;
        $order_own = $input['order_own'];

        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $order = new AppVehical();
        if (empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        if($order_own == 2){
            if (empty($company_id)){
                $data = $this->encrypt(['code'=>400,'msg'=>'请选择客户！']);
                return $this->resultInfo($data);
            }
            $order->company_id = $company_id;
        }
        if (empty($start_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车开始时间不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($end_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车结束时间不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($temperture)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择温度']);
            return $this->resultInfo($data);
        }

        if($cartype == 0){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择车型']);
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

        $order->order_own = $order_own;
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

        $order->ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->takenumber = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->cargo_name = $cargo_name;
        $order->cargo_number = $cargo_number;
        $order->cargo_number2 = $cargo_number2;
        $order->cargo_weight = $cargo_weight;
        $order->cargo_volume = $cargo_volume;
        $order->create_user_id = $user['id'];
        $order->create_user_name = $user['name'];
        $order->group_id = $group_id;
        $order->temperture = $temperture;
        $order->remark = $remark;
        $order->picktype = $picktype;
        $order->pickprice = $pickprice;
        $order->sendtype = $sendtype;
        $order->sendprice = $sendprice;
        $order->time_start = $start_time;//用车时间
        $order->time_end = $end_time;//预计到达时间
        $order->price = $price;
        $order->otherprice = $otherprice;
        $order->more_price = $more_price;
        $order->total_price = $pickprice - 0 + $sendprice - 0 + $price - 0 + (float)$otherprice + (float)$more_price;
        $res =  $order->save();

        $receive = new AppReceive();
        $group = AppGroup::findOne($user->parent_group_id);
        if ($group->level_id == 3 && $order_own == 2){
            $time = date('Y-m-d H:i:s',time());
            $receive->compay_id = $company_id;
            $receive->receivprice = $order->total_price;
            $receive->trueprice = 0;
            $receive->order_id = $order->id;
            $receive->receive_info = json_encode(['price'=>$price,'pickprice'=>$pickprice,'sendprice'=>$sendprice,'more_price'=>$more_price,'otherprice'=>$otherprice]);
            $receive->create_user_id = $user->id;
            $receive->create_user_name = $user->name;
            $receive->group_id = $group_id;
            $receive->create_time = $time;
            $receive->update_time = $time;
            $arr = $receive->save();
        }

        if ($res){
            $this->hanldlog($user->id,'添加订单:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 货主/普通用户添加订单
     * */
    public function actionVehical_add(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $start_time = $input['start_time'];
        $end_time = $input['end_time'];
        $cartype = $input['cartype'];
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startstr = $input['startstr'];
        $endstr = $input['endstr'];
        $cargo_name = $input['cargo_name'];
        $cargo_number = $input['cargo_number'];
        $cargo_number2 = $input['cargo_number2'];
        $remark = $input['remark'];
        $temperture = $input['temperture'];
        $picktype = $input['picktype'] ?? 1;
        $sendtype = $input['sendtype'] ?? 1;
        $price = $input['price'] ?? 0;
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $this->check_group_auth($user->group_id,$user);
        $order = new AppVehical();

        if (empty($start_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车开始时间不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($end_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车结束时间不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($temperture)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择温度']);
            return $this->resultInfo($data);
        }
        if(!$cartype){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择车型']);
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
        $order->order_own = 1;
        $order->ordernumber = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->takenumber = 'T'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $order->time_start = $start_time;
        $order->time_end = $end_time;
        $order->cargo_name = $cargo_name;
        $order->temperture = $temperture;
        $order->cartype = $cartype;
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $order->endstr = $endstr;
        $order->price = $price;
        $order->line_price = $price;
        $order->cargo_number = $cargo_number;
        $order->cargo_number2 = $cargo_number2;
        $order->picktype = $picktype;
        $order->sendtype = $sendtype;
        $order->remark = $remark;
        $order->group_id = $user->group_id;
        $order->total_price = $price;
        $order->create_user_id = $user->id;
        $order->create_user_name = $user->name;
        $res  = $order->save();

        $payment = new AppPayment();
        $payment->order_id = $order->id;
        $payment->pay_price = $price;
        $payment->group_id = $user->group_id;
        $payment->create_user_id = $user->id;
        $payment->create_user_name = $user->name;
        $payment->save();

        if ($res){
            $this->hanldlog($user->id,'添加整车订单'.$order->startcity.'->'.$order->endcity);
            $data = $this->encrypt(['code'=>'200','msg'=>'添加成功','data'=>$order->id]);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'添加失败']);
            return $this->resultInfo($data);
        }

    }

    /*
     * 修改订单
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        $group_id = $input['group_id'];
        $company_id = $input['company_id'] ?? '';
        $start_time = $input['start_time'];
        $end_time = $input['end_time'];
        $cartype = $input['cartype'];
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startstr = $input['startstr'];
        $endstr = $input['endstr'];
        $cargo_name = $input['cargo_name'];
        $cargo_number = $input['cargo_number'];
        $cargo_number2 = $input['cargo_number2'];
        $cargo_weight = $input['cargo_weight'] ?? '';
        $cargo_volume = $input['cargo_volume'] ?? '';
        $remark = $input['remark'];
        $temperture = $input['temperture'];
        $picktype = $input['picktype'] ?? 1;
        $sendtype = $input['sendtype'] ?? 1;
        $pickprice = $input['pickprice'] ?? 0;
        $sendprice = $input['sendprice'] ?? 0;
        $price = $input['price'] ?? 0;
        $otherprice = $input['otherprice'] ?? 0;
        $more_price = $input['more_price'] ?? 0;
        $order_own = $input['order_own'] ?? 1;

        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $order = AppVehical::findOne($id);
        if (empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
            return $this->resultInfo($data);
        }
        if($order_own == 2){
            if (empty($company_id)){
                $data = $this->encrypt(['code'=>400,'msg'=>'请选择客户！']);
                return $this->resultInfo($data);
            }
            $order->company_id = $company_id;
        }
        if (empty($start_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车开始时间不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($end_time)){
            $data = $this->encrypt(['code'=>400,'msg'=>'预约用车结束时间不能为空']);
            return $this->resultInfo($data);
        }

        if (empty($temperture)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择温度']);
            return $this->resultInfo($data);
        }

        if($cartype == 0){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择车型']);
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
        $this->check_group_auth($order->group_id,$user);

        $order->order_own = $order_own;
        $order->cartype = $cartype;
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $arr_startstr = json_decode($startstr,true);
        foreach ($arr_startstr as $k => $v){
            $all = $v['pro'].$v['city'].$v['area'].$v['info'];

            $common_address = AppCommonAddress::find()->where(['group_id'=>$user->parent_group_id,'all'=>$all])->one();
            if ($common_address){

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

        $order->cargo_name = $cargo_name;
        $order->cargo_number = $cargo_number;
        $order->cargo_number2 = $cargo_number2;
        $order->cargo_weight = $cargo_weight;
        $order->cargo_volume = $cargo_volume;

        $order->group_id = $group_id;
        $order->temperture = $temperture;
        $order->remark = $remark;

        $order->picktype = $picktype;
        $order->pickprice = $pickprice;

        $order->sendtype = $sendtype;
        $order->sendprice = $sendprice;

        $order->time_start = $start_time;//用车时间
        $order->time_end = $end_time;//预计到达时间
        $order->price = $price;
        $order->otherprice = $otherprice;
        $order->more_price = $more_price;
        $order->total_price = $pickprice + $sendprice + $price + (float)$otherprice + (float)$more_price;
        $res =  $order->save();

        $receive = AppReceive::find()->where(['order_id'=>$id,'group_id'=>$group_id])->one();
        $group = AppGroup::findOne($user->parent_group_id);
        if ($group->level_id == 3 && $order_own == 2){
            $time = date('Y-m-d H:i:s',time());
            if  (!$receive) {
                $receive = new AppReceive();
                $receive->create_time = $time;
                $receive->order_id = $id;
                $receive->create_user_id = $user->id;
                $receive->create_user_name = $user->name;
            }
            $receive->update_time = $time;
            $receive->compay_id = $company_id;
            $receive->receivprice =  $order->total_price;
            $receive->trueprice = 0;
            $receive->receive_info = json_encode(['price'=>$price,'pickprice'=>$pickprice,'sendprice'=>$sendprice,'more_price'=>$more_price,'otherprice'=>$otherprice]);
            $receive->group_id = $group_id;
            $receive->save();
        }

        if ($res){
            $this->hanldlog($user->id,'编辑订单:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'编辑成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'编辑失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 订单 编辑 详情
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
     * 订单 回单 详情
     * */
    public function actionView_img(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];

        $model = AppOrder::find()->select(['receipt'])->where(['id'=>$id])->asArray()->one();
        if ($model) {
            $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>json_decode($model['receipt'])]);
            return $this->resultInfo($data);
        } else {
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
    }  

    /*
     * 删除订单 回单 图片
     * */
    public function actionView_img_del(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $img = $input['img'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        $model = AppOrder::findOne($id);

        if ($model) {
            $imgs = $model->receipt;
            if ($imgs) {
                $imgs = json_decode($imgs,true);
                foreach ($imgs as $k => $v) {
                    if ($v == $img) {
                        unset($imgs[$k]);
                        break;
                    }
                }
                $model->receipt = json_encode($imgs);
                $model->update_time = date('Y-m-d H:i:s',time());
                $res = $model->save();
                if ($res) {
                    @unlink(ltrim($img,'/'));
                    $this->hanldlog($user->id,'整车订单删除回单:'.$model->ordernumber);
                    $data = $this->encrypt(['code'=>200,'msg'=>'删除成功！','data'=>$model->receipt,'img'=>$img]);
                    return $this->resultInfo($data);
                } else {
                    $data = $this->encrypt(['code'=>400,'msg'=>'删除失败！','data'=>$model,'img'=>$img]);
                    return $this->resultInfo($data);
                }
            }
        } else {
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
    }    

    /*
     * 订单详情 附带运输单、回单
     * */
    public function actionIndex_view(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        if ($id) {
            $model = AppVehical::find()
                ->alias('v')
                ->select(['v.*','c.carparame','r.all_name','g.group_name user_company'])
                ->leftJoin('app_cartype c','v.cartype=c.car_id')
                ->leftJoin('app_customer r','v.company_id=r.id')
                ->leftJoin('app_group g','v.group_id=g.id')
                ->where(['v.id'=>$id])
                ->asArray()
                ->one();
            $model['startstr'] = json_decode($model['startstr'],true);
            $model['endstr'] = json_decode($model['endstr'],true);

            $model['cargo_number'] = $model['cargo_number'] ?? '';
            $model['cargo_number2'] = $model['cargo_number2'] ?? '';
            $model['cargo_weight'] = $model['cargo_weight'] ?? '';
            $model['cargo_volume'] = $model['cargo_volume'] ?? '';
            $model['pickprice'] = $model['pickprice'] ?? '';
            $model['sendprice'] = $model['sendprice'] ?? '';
            $model['otherprice'] = $model['otherprice'] ?? '';
            $model['more_price'] = $model['more_price'] ?? '';

            $list = AppCarriageList::find()->where(['order_id'=>$id,'group_id'=>$model['group_id']])->asArray()->all();
            if ($list) {
                foreach($list as $k=>$v) {
                    //承运方
                    if ($v['type'] == 2) {
                        $carriage = Carriage::find()->select(['name'])->where(['cid'=>$v['deal_company']])->one();
                        $list[$k]['carnumber'] = $carriage->name;
                    }
                }
            }

        } else {
            $model = new AppVehical();
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'list'=>$list,'id'=>$id,'group_id'=>$model['group_id']]);
        return $this->resultInfo($data);
    }    

    /*
     * 接单订单详情 附带运输单
     * */
    public function actionTake_index_view(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        if ($id) {
            $model = AppVehical::find()
                ->alias('v')
                ->select(['v.*','c.carparame','r.all_name','g.group_name user_company'])
                ->leftJoin('app_cartype c','v.cartype=c.car_id')
                ->leftJoin('app_customer r','v.company_id=r.id')
                ->leftJoin('app_group g','v.group_id=g.id')
                ->where(['v.id'=>$id])
                ->asArray()
                ->one();
            $model['startstr'] = json_decode($model['startstr'],true);
            $model['endstr'] = json_decode($model['endstr'],true);

            $model['cargo_number'] = $model['cargo_number'] ?? '';
            $model['cargo_number2'] = $model['cargo_number2'] ?? '';
            $model['cargo_weight'] = $model['cargo_weight'] ?? '';
            $model['cargo_volume'] = $model['cargo_volume'] ?? '';
            $model['pickprice'] = $model['pickprice'] ?? '';
            $model['sendprice'] = $model['sendprice'] ?? '';

            $list = AppCarriageList::find()->where(['order_id'=>$id,'group_id'=>$model['deal_company']])->asArray()->all();
            if ($list) {
                foreach($list as $k=>$v) {
                    //承运方
                    if ($v['type'] == 2) {
                        $carriage = Carriage::find()->select(['name'])->where(['cid'=>$v['deal_company']])->one();
                        $list[$k]['carnumber'] = $carriage->name;
                    }
                }
            }

        } else {
            $model = new AppVehical();
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'list'=>$list,'id'=>$id,'group_id'=>$model['group_id']]);
        return $this->resultInfo($data);
    }

    /*
     * 订单回单详情
     * */
    public function actionReceipt_view(){
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
            $model = AppVehical::find()->select(['receipt'])->where(['id'=>$id])->asArray()->one();
        } else {
            $model = new AppVehical();
        }

        $data = $this->encrypt(['code'=>200,'msg'=>'ok','data'=>$model]);
        return $this->resultInfo($data);
    }

    /*
     * 整车导入页面 基本是公共的导入请求方法
     * */
    public function actionFile(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        $groups = AppGroup::group_list($user);
        $data = $this->encrypt(['code'=>200,'msg'=>'','groups'=>$groups]);
        return $this->resultInfo($data);
    }

    /*
     * 货主/普通用户下单支付 干线列表 整车下单 货到付款
     * */
    public function actionPay(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        $money_state = $input['money_state'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);//验证令牌
        $user = $check_result['user'];
        $order = AppVehical::find()->where(['id'=>$id])->one();
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status != 1 || $order->line_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单状态已改变，请刷新重试!']);
            return $this->resultInfo($data);
        }

        if ($money_state == 2) {
            $order->money_state = 'N';
        }
        $payment = new AppPayment();
        $payment->pay_price = $order->total_price;
        $payment->truepay = '';
        $payment->al_pay = '';
        $payment->group_id = $order->group_id;
        $payment->order_id = $order->id;
        $payment->create_user_id = $user->id;
        $order->line_status = 2;
        $transaction= AppPayment::getDb()->beginTransaction();
        try{
            $res_p = $payment->save();
            $res_o = $order->save();
            if ($res_o && $res_p){
                $transaction->commit();
                $this->hanldlog($user->id,'发布订单:'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'下单成功']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){

            $transaction->rollback();
            $data = $this->encrypt(['code'=>400,'msg'=>'下单失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 上线
     * */
    public function actionOnline(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        $line_price = $input['line_price'];
        $money_state = $input['money_state'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (!$line_price){
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写上线价格']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $order = AppVehical::find()->where(['id'=>$id])->one();
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status != 1 || $order->line_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单状态已改变，请刷新重试!']);
            return $this->resultInfo($data);
        }

        if ($money_state == 2) {
            $order->money_state = 'N';
        }
        $order->line_status = 2;
        $order->line_price = $line_price;
        $payment = new AppPayment();
        $payment->group_id = $order->group_id;
        $payment->order_id = $order->id;
        $payment->truepay = $line_price;
        if ($money_state == 2){
            $payment->truepay = 0;
        }
        $payment->create_user_id = $user->id;
        $payment->pay_price = $line_price;

        $transaction= AppPayment::getDb()->beginTransaction();
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

    // 验证是否有支付宝支付权限
    public function actionAlipay(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $order = AppVehical::find()->where(['id'=>$id])->one();
        $this->check_group_auth($order->group_id,$user);
        $data = $this->encrypt(['code'=>200,'msg'=>'ok']);
        return $this->resultInfo($data);
    }

    // 验证是否有货到付款权限
    public function actionNo_pay(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $order = AppVehical::find()->where(['id'=>$id])->one();
        $this->check_group_auth($order->group_id,$user);
        $data = $this->encrypt(['code'=>200,'msg'=>'ok']);
        return $this->resultInfo($data);
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
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);
        $money_state = $order->money_state;
        $group_id = $order->group_id;
        $this->check_group_auth($order->group_id,$user);

        if($order->line_status != 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已下线']);
            return $this->resultInfo($data);
        }
        if($order->order_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单正在进行中不能做该操作']);
            return $this->resultInfo($data);
        }
        $order->line_status = 1;
        $order->line_price = '';
        $order->money_state = '';
        $res = $order->save();
        if(!$res){
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
        if ($order->pay_status == 2 && $money_state == 'Y'){
             $payment = AppPayment::find()->where(['order_id'=>$id,'carriage_name'=>'赤途'])->one();
             if ($payment){
                 $payment->delete();
             }
             $paymessage = AppPaymessage::find()->where(['orderid'=>$order->tradenumber,'state'=>1,'pay_result'=>'SUCCESS'])->one();
             if (!$paymessage){
                 $this->hanldlog($user->id,'下线订单'.$order->ordernumber);
                 $data = $this->encrypt(['code'=>200,'msg'=>'下线成功，退款请联系客服']);
                 return $this->resultInfo($data);
             }
             if ($paymessage->paytype == 1){
                 //支付宝退款
                 $body = '下线退款';
                 $arr = $this->refund($paymessage->orderid,$paymessage->paynum,$body);
                 $res = json_decode($arr,true);
                 // $refund = $res['alipay_trade_refund_response'];
                 $refund = (array)$res;
                 if ($refund['code'] == '10000' && $refund['msg'] == 'Success'){
                     $balance = new AppBalance();
                     $pay = new AppPaymessage();
                     $balance->orderid = $id;
                     $balance->pay_money = $refund['refund_fee'];
                     $balance->order_content = '整车订单下线退款';
                     $balance->action_type = 5;
                     $balance->userid = $user->id;
                     $balance->create_time = date('Y-m-d H:i:s',time());
                     $balance->ordertype = 1;
                     $pay->orderid = $refund['out_trade_no'];
                     $pay->paynum = $refund['refund_fee'];
                     $pay->create_time = date('Y-m-d H:i:s',time());
                     $pay->userid = $user->id;
                     $pay->paytype = 1;
                     $pay->type = 1;
                     $pay->state = 3;
                     $pay->payname = $refund['buyer_logon_id'];
                     $transaction= AppPaymessage::getDb()->beginTransaction();
                     $order->pay_status = 1;

                    $order->save();
                    $res = $pay->save();
                    $res_b = $balance->save();

                    $data = $this->encrypt(['code'=>200,'msg'=>'下线成功，运费已退至付款账户']);
                    return $this->resultInfo($data);

                }else{
                    $balance = new AppBalance();
                    $pay = new AppPaymessage();
                    $balance->orderid = $id;
                    $balance->pay_money = $paymessage->paynum;
                    $balance->order_content = '整车订单下线退款失败';
                    $balance->action_type = 5;
                    $balance->userid = $user->id;
                    $balance->create_time = date('Y-m-d H:i:s',time());
                    $balance->ordertype = 1;
                    $pay->orderid = $paymessage['orderid'];
                    $pay->paynum = $paymessage->paynum;
                    $pay->create_time = date('Y-m-d H:i:s',time());
                    $pay->userid = $user->id;
                    $pay->paytype = 1;
                    $pay->type = 1;
                    $pay->state = 3;
                    $pay->pay_result = 'FAIL';
                    $balance->save();
                    $pay->save();
                    $this->hanldlog($user->id,'下线订单'.$order->ordernumber);
                    $data = $this->encrypt(['code'=>200,'msg'=>'下线成功，退款请联系客服']);
                    return $this->resultInfo($data);
                }
            }elseif($paymessage->paytype == 3){
                //余额退款
                $tradenumber = $order->tradenumber;
                $group = AppGroup::find()->where(['id'=>$order->group_id])->one();
                $paymessage = AppPaymessage::find()->where(['orderid'=>$tradenumber,'state'=>1,'paytype'=>3,'pay_result'=>'SUCCESS'])->one();
                $price = $paymessage->paynum;
                $balan_money = $paymessage->paynum + $group->balance;
                $group->balance = $balan_money;
                $balance = new AppBalance();
                $pay = new AppPaymessage();
                $balance->orderid = $order->id;
                $balance->pay_money = $price;
                $balance->order_content = '整车余额退款';
                $balance->action_type = 7;
                $balance->userid = $user->id;
                $balance->create_time = date('Y-m-d H:i:s',time());
                $balance->ordertype = 1;
                $balance->group_id = $order->group_id;
                $pay->orderid = $order->tradenumber;
                $pay->paynum = $price;
                $pay->create_time = date('Y-m-d H:i:s',time());
                $pay->userid = $user->id;
                $pay->paytype = 3;
                $pay->type = 1;
                $pay->state = 3;
                $order->pay_status = 1;
                $transaction= AppPaymessage::getDb()->beginTransaction();
                try{
                    $res = $pay->save();
                    $res_m = $group->save();
                    $res_b = $balance->save();
                    $res_o = $order->save();
                    if ($res && $res_m &&$res_b &&$res_o){
                        $transaction->commit();
                        $data = $this->encrypt(['code'=>200,'msg'=>'下线成功，运费已退至付款账户']);
                        return $this->resultInfo($data);
                    }
                }catch (\Exception $e){
                    $transaction->rollback();
                    $data = $this->encrypt(['code'=>200,'msg'=>'下线成功，退款请联系客服！']);
                    return $this->resultInfo($data);
                }
            }
        }else{
            $payment = AppPayment::find()->where(['order_id'=>$id])->one();
            if ($payment){
                $payment->delete();
            }
            $this->hanldlog($user->id,'下线订单'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }
    }

    public function actionUnlineone(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];

        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);
        $money_state = $order->money_state;
        $group_id = $order->group_id;
        $this->check_group_auth($order->group_id,$user);

        if($order->line_status != 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已下线']);
            return $this->resultInfo($data);
        }
        if($order->order_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单正在进行中不能做该操作']);
            return $this->resultInfo($data);
        }
        $order->line_status = 1;
        $order->line_price = '';
        $order->money_state = '';
        $res = $order->save();
        if(!$res){
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
        if ($order->pay_status == 2 && $money_state == 'Y'){
            $payment = AppPayment::find()->where(['order_id'=>$id,'carriage_name'=>'赤途'])->one();
            if ($payment){
                $payment->delete();
            }
            $paymessage = AppPaymessage::find()->where(['orderid'=>$order->tradenumber,'state'=>1,'pay_result'=>'SUCCESS'])->one();
            if (!$paymessage){
                $this->hanldlog($user->id,'下线订单'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'下线成功，订单所支付金额将会在24小时之后到支付账户']);
                return $this->resultInfo($data);
            }
            if ($paymessage->paytype == 1){
                $refund = new AppRefund();
                $refund->content = '下线订单退款';
                $refund->orderid = $order->id;
                $refund->type = 1;
                $refund->ordernumber = $order->tradenumber;
                $refund->price = $paymessage->paynum;
                $refund->paytype = 'ALIPAY';
                $refund->user_id = $user->id;
                $refund->group_id = $order->group_id;
                $refund->save();
            }else if($paymessage->paytype == 3){
                $refund = new AppRefund();
                $refund->content = '下线订单退款';
                $refund->orderid = $order->id;
                $refund->type = 1;
                $refund->ordernumber = $order->tradenumber;
                $refund->price = $paymessage->paynum;
                $refund->paytype = 'BALANCE';
                $refund->user_id = $user->id;
                $refund->group_id = $order->group_id;
                $refund->save();
            }

        }else{
            $this->hanldlog($user->id,'下线订单'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }
    }


    /*
     * 调度列表
     * */
    public function actionDispatch_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $ordernumber = $input['ordernumber'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $endtime = $input['endtime'] ?? '';
        $customer = $input['customer'] ?? '';

        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (empty($token) || !$group_id){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $check_result = $this->check_token_list($token);//验证令牌
        $list = AppVehical::find()
            ->alias('v')
            ->select(['v.*','c.all_name','t.carparame'])
            ->leftJoin('app_customer c','v.company_id=c.id')
            ->leftJoin('app_cartype t','v.cartype=t.car_id')
            ->where(['v.group_id'=>$group_id,'v.line_status'=>1,'v.delete_flag'=>'Y','v.carriage_id'=>null])
            ->andWhere(['in','v.order_status',[1,2]]);
        if ($ordernumber) {
            $list->andWhere(['like','v.ordernumber',$ordernumber]);
        }
        if($customer){
            $list->andWhere(['like','v.customer',$customer]);
        }
        if ($begintime) {
            $time_s = $begintime . ' 00:00:00';
            $time_e = $begintime . ' 23:59:59';
            $list->andWhere(['between','v.time_start',$time_s,$time_e]);
        }
        // if ($begintime && $endtime){
        //     $list->andWhere(['between','v.create_time',$begintime,$endtime]);
        // }
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.time_start'=>SORT_ASC])
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

    /*
     * 调度订单详情
     * */
    public function actionDispatch_view(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        $model = AppVehical::find()
            ->alias('v')
            ->select(['v.*','c.all_name','t.carparame','g.group_name get_group_name'])
            ->leftJoin('app_customer c','v.company_id=c.id')
            ->leftJoin('app_cartype t','v.cartype=t.car_id')
            ->leftJoin('app_group g','v.group_id=g.id')
            ->where(['v.id'=>$id])
            ->asArray()
            ->one();


        if (!$model || $model['delete_flag'] != 'Y' || $model['line_status'] != 1 || !in_array($model['order_status'],[1,2])) {
            $data = $this->encrypt(['code'=>200,'msg'=>'此订单不能调度，具体原因请查看订单状态','data'=>$model,'check'=>400]);
            return $this->resultInfo($data);
        }
        $model['startstr'] = json_decode($model['startstr']);
        $model['endstr'] = json_decode($model['endstr']);
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'check'=>200]);
        return $this->resultInfo($data);
    }    

    /*
     * 外部调度订单详情
     * */
    public function actionDispatch_out_view(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        $model = AppVehical::find()
            ->alias('v')
            ->select(['v.*','c.all_name','t.carparame','g.group_name get_group_name'])
            ->leftJoin('app_customer c','v.company_id=c.id')
            ->leftJoin('app_cartype t','v.cartype=t.car_id')
            ->leftJoin('app_group g','v.group_id=g.id')
            ->where(['v.id'=>$id])
            ->asArray()
            ->one();


        if (!$model || $model['delete_flag'] != 'Y' || $model['line_status'] != 2 || $model['order_status'] != 2) {
            $data = $this->encrypt(['code'=>200,'msg'=>'此订单不能调度，具体原因请查看订单状态','data'=>$model,'check'=>400]);
            return $this->resultInfo($data);
        }
        $model['startstr'] = json_decode($model['startstr']);
        $model['endstr'] = json_decode($model['endstr']);
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'check'=>200]);
        return $this->resultInfo($data);
    }

    /*
     * 内部订单调度
     * */
    public function actionDispatch_go(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $type = $input['type'];//1 自有 2 承运商 3临时
        $arr = json_decode($input['arr'],true);
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);
        $group_id = $order->group_id;
        $this->check_group_auth($group_id,$user);
        $model = new AppCarriageList();

        if($order->order_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已调度']);
            return $this->resultInfo($data);
        }
        $res = $carriage = true;
        $transaction= AppVehical::getDb()->beginTransaction();
        try {
        switch ($type){
            case '1':
                foreach($arr as $key =>$value){
                    $list['order_id'] = $id;
                    $list['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list['group_id'] = $group_id;
                    $list['create_user_id'] = $user->id;
                    $list['create_user_name'] = $user->name;
                    $list['contant'] = $value['contant'];
                    $list['tel'] = $value['tel'];
                    $list['carnumber'] = $value['carnumber'];
                    $list['type'] = $type;
                    $list['create_time'] = $list['update_time'] = date('Y-m-d H:i:s',time());
                    $info[] = $list;

                    $list_c['order_id'] = $id;
                    $list_c['pay_price'] = $value['price'];
                    $list_c['truepay'] = 0;
                    $list_c['group_id'] = $group_id;
                    $list_c['create_user_id'] = $user->id;
                    $list_c['create_user_name'] = $user->name;
                    $list_c['carriage_id'] = $value['id'];
                    $list_c['driver_name'] = $value['contant'];
                    $list_c['driver_car'] = $value['carnumber'];
                    $list_c['driver_tel'] = $value['tel'];
                    $list_c['pay_type'] = 1;
                    $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                    $info_c[] = $list_c;
                }
                $res = Yii::$app->db->createCommand()->batchInsert(AppCarriageList::tableName(), ['order_id','carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel', 'carnumber', 'type','create_time','update_time'], $info)->execute();
                $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id','pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','driver_name','driver_car','driver_tel','pay_type','create_time','update_time'], $info_c)->execute();
                break;
            case '2':
                foreach($arr as $key =>$value){
                    $list['order_id'] = $id;
                    $list['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list['group_id'] = $group_id;
                    $list['create_user_id']= $user->id;
                    $list['create_user_name'] = $user->name;
                    $list['deal_company'] = $value['id'];
                    $list['contant'] = $value['contant'];
                    $list['tel'] = $value['tel'];
                    $list['carriage_price'] = $value['price'];
                    $list['type'] = $type;
                    $list['create_time'] = $list['update_time'] = date('Y-m-d H:i:s',time());
                    $info[] = $list;
                    $list_c['order_id'] = $id;
                    $list_c['pay_price'] = $value['price'];
                    $list_c['truepay'] = 0;
                    $list_c['group_id'] = $group_id;
                    $list_c['create_user_id'] = $user->id;
                    $list_c['create_user_name'] = $user->name;
                    $list_c['carriage_id'] = $value['id'];
                    $list_c['pay_type'] = 2;
                    $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                    $info_c[] = $list_c;
                    $carriage_id = $value['id'];
                }
                $order->carriage_id = $carriage_id;
                $res = Yii::$app->db->createCommand()->batchInsert(AppCarriageList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'deal_company', 'contant', 'tel', 'carriage_price', 'type','create_time','update_time'], $info)->execute();
                $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','pay_type','create_time','update_time'], $info_c)->execute();
                break;
            case '3':
                foreach($arr as $key =>$value){
                    $list['order_id'] = $id;
                    $list['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list['group_id'] = $group_id;
                    $list['create_user_id'] = $user->id;
                    $list['create_user_name'] = $user->name;
                    $list['contant'] = $value['contant'];
                    $list['tel'] = $value['tel'];
                    $list['carnumber'] = $value['carnumber'];
                    $list['carriage_price'] = $value['price'];
                    $list['type'] = $type;
                    $list['create_time'] = $list['update_time'] = date('Y-m-d H:i:s',time());
                    $info[] = $list;
                    $list_c['order_id'] = $id;
                    $list_c['pay_price'] = $value['price'];
                    $list_c['truepay'] = 0;
                    $list_c['group_id'] = $group_id;
                    $list_c['create_user_id'] = $user->id;
                    $list_c['create_user_name'] = $user->name;
                    $list_c['driver_name'] = $value['contant'];
                    $list_c['driver_car'] = $value['carnumber'];
                    $list_c['driver_tel'] = $value['tel'];
                    $list_c['pay_type'] = 3;
                    $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                    $info_c[] = $list_c;
                }
                $res = Yii::$app->db->createCommand()->batchInsert(AppCarriageList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel', 'carnumber', 'carriage_price', 'type','create_time','update_time'], $info)->execute();
                $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'driver_name', 'driver_car','driver_tel','pay_type','create_time','update_time'], $info_c)->execute();
                break;
            default:
                break;
        }
        $order->order_status = 3;
        if ($type == 2){
            $order->order_status = 2;
        }
        $order->driverinfo = $input['arr'];
        $res_o = $order->save();
        if ($res && $carriage && $res_o && $carriage){
                $transaction->commit();
                $this->hanldlog($user->id,'调度订单:'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'调度成功']);
                return $this->resultInfo($data);
        }else{
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
     * 外部订单调度
     * */
    public function actionOut_dispatch(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $type = $input['type'];
        $arr = json_decode($input['arr'],true);
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);
        $group_id = $order->deal_company;
        // $group_id = $input['group_id'];
        $this->check_group_auth($order->deal_company,$user);
        $model = new AppCarriageList();

        if($order->order_status != 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已调度']);
            return $this->resultInfo($data);
        }
        $res = $carriage = true;
        $transaction= AppVehical::getDb()->beginTransaction();
        try {
        switch ($type){
            case '1':
                foreach($arr as $key =>$value){
                    $list['order_id'] = $id;
                    $list['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list['group_id'] = $group_id;
                    $list['create_user_id'] = $user->id;
                    $list['create_user_name'] = $user->name;
                    $list['contant'] = $value['contant'];
                    $list['tel'] = $value['tel'];
                    $list['carnumber'] = $value['carnumber'];
                    $list['type'] = $type;
                    $list['create_time'] = $list['update_time'] = date('Y-m-d H:i:s',time());
                    $info[] = $list;

                    $list_c['order_id'] = $id;
                    $list_c['pay_price'] = $value['price'];
                    $list_c['truepay'] = 0;
                    $list_c['group_id'] = $group_id;
                    $list_c['create_user_id'] = $user->id;
                    $list_c['create_user_name'] = $user->name;
                    $list_c['carriage_id'] = $value['id'];
                    $list_c['driver_name'] = $value['contant'];
                    $list_c['driver_car'] = $value['carnumber'];
                    $list_c['driver_tel'] = $value['tel'];
                    $list_c['pay_type'] = 1;
                    $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                    $info_c[] = $list_c;
                }
                $res = Yii::$app->db->createCommand()->batchInsert(AppCarriageList::tableName(), ['order_id','carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel', 'carnumber', 'type','create_time','update_time'], $info)->execute();
                $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id','pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','driver_name','driver_car','driver_tel','pay_type','create_time','update_time'], $info_c)->execute();
                break;
            case '2':
                foreach($arr as $key =>$value){
                    $list['order_id'] = $id;
                    $list['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list['group_id'] = $group_id;
                    $list['create_user_id']= $user->id;
                    $list['create_user_name'] = $user->name;
                    $list['deal_company'] = $value['id'];
                    $list['contant'] = $value['contant'];
                    $list['tel'] = $value['tel'];
                    $list['carriage_price'] = $value['price'];
                    $list['type'] = $type;
                    $list['create_time'] = $list['update_time'] = date('Y-m-d H:i:s',time());
                    $info[] = $list;
                    $list_c['order_id'] = $id;
                    $list_c['pay_price'] = $value['price'];
                    $list_c['truepay'] = 0;
                    $list_c['group_id'] = $group_id;
                    $list_c['create_user_id'] = $user->id;
                    $list_c['create_user_name'] = $user->name;
                    $list_c['carriage_id'] = $value['id'];
                    $list_c['pay_type'] = 2;
                    $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                    $info_c[] = $list_c;
                }
                $res = Yii::$app->db->createCommand()->batchInsert(AppCarriageList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'deal_company', 'contant', 'tel', 'carriage_price', 'type','create_time','update_time'], $info)->execute();
                $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id','pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','pay_type','create_time','update_time'], $info_c)->execute();
                break;
            case '3':
                foreach($arr as $key =>$value){
                    $list['order_id'] = $id;
                    $list['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list['group_id'] = $group_id;
                    $list['create_user_id'] = $user->id;
                    $list['create_user_name'] = $user->name;
                    $list['contant'] = $value['contant'];
                    $list['tel'] = $value['tel'];
                    $list['carnumber'] = $value['carnumber'];
                    $list['carriage_price'] = $value['price'];
                    $list['type'] = $type;
                    $list['create_time'] = $list['update_time'] = date('Y-m-d H:i:s',time());
                    $info[] = $list;
                    $list_c['order_id'] = $id;
                    $list_c['pay_price'] = $value['price'];
                    $list_c['truepay'] = 0;
                    $list_c['group_id'] = $group_id;
                    $list_c['create_user_id'] = $user->id;
                    $list_c['create_user_name'] = $user->name;
                    $list_c['driver_name'] = $value['contant'];
                    $list_c['driver_car'] = $value['carnumber'];
                    $list_c['driver_tel'] = $value['tel'];
                    $list_c['pay_type'] = 3;
                    $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                    $info_c[] = $list_c;
                }
                $res = Yii::$app->db->createCommand()->batchInsert(AppCarriageList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel', 'carnumber', 'carriage_price', 'type','create_time','update_time'], $info)->execute();
                $carriage = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id','pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'driver_name', 'driver_car','driver_tel','pay_type','create_time','update_time'], $info_c)->execute();
                break;
            default:
                break;
        }
        $order->order_status = 3;
        if ($type == 2){
            $order->order_status = 2;
        }
        $order->driverinfo = $input['arr'];
        $res_o = $order->save();
            if ($res_o && $res &&$carriage){
                $transaction->commit();
                $this->hanldlog($user->id,'调度订单:'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'调度成功']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'调度失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 内部订单完成操作
     * */
    public function actionVehical_success(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status != 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单状态错误，请刷新重试!']);
            return $this->resultInfo($data);
        }
        $order->order_status = 6;
        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'完成内部订单:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'订单已完成']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 内部整车订单取消
     * */
    public function actionVehical_cancel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $transaction = AppVehical::getDb()->beginTransaction();
        try {
            $check_result = $this->check_token($token,true);
            $user = $check_result['user'];
            $order = AppVehical::findOne($id);
            $this->check_group_auth($order->group_id,$user);
            if ($order->order_status == 8){

                $data = $this->encrypt(['code'=>400,'msg'=>'订单已取消']);
                return $this->resultInfo($data);
            }
            if ($order->order_status == 6){
                $data = $this->encrypt(['code'=>400,'msg'=>'订单已完成不能取消']);
                return $this->resultInfo($data);
            }
            if ($order->order_status != 1){
                $list = AppPayment::find()->where(['order_id'=>$id])->all();
                if ($list) {
                    AppPayment::deleteAll(['order_id'=>$id]);
                }
            }
            if($order->order_own == 2){
                $model = AppReceive::find()->where(['order_id'=>$id,'delete_flag'=>'Y'])->one();
                $model->delete();
            }
            $order->order_status = 8;
            $res = $order->save();
            $transaction->commit();
        } catch(\Exception $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败,请重试！']);
            return $this->resultInfo($data);
        } catch(\Throwable $e) {
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败,请重试！']);
            return $this->resultInfo($data);
        }

        if ($res){
            $this->hanldlog($user->id,'取消内部订单:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'取消失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 外部订单取消
     * */

    public function actionOnline_cancel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status == 8){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已取消']);
            return $this->resultInfo($data);
        }
        if ($order->order_status == 6){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已完成不能取消']);
            return $this->resultInfo($data);
        }
        if ($order->order_status == 4){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已提货不能取消']);
            return $this->resultInfo($data);
        }
        if($order->order_status == 5){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单运输中取消']);
            return $this->resultInfo($data);
        }
        if ($order->order_status == 1){
            $recive = AppReceive::find()->where(['order_id'=>$id])->one();
            $order->order_status = 8;
            $transaction= AppVehical::getDb()->beginTransaction();
            try {
                $res1 = $recive->delete();
                $res2 = $order->save();
                if ($res1 && $res2){
                    $transaction->commit();
                    $this->hanldlog($user->id,'取消订单'.$order->ordernumber);
                    $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
                    return $this->resultInfo($data);
                }
            }catch (\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'取消失败']);
                return $this->resultInfo($data);
            }
        }else{
            $recive = AppReceive::find()->where(['order_id'=>$id])->one();
            $payment = AppPayment::find()->where(['order_id'=>$id])->all();
            $transaction= AppVehical::getDb()->beginTransaction();
            try {
                $res1 = $recive->delete();
                $res2 = $order->save();
                if ($res1 && $res2){
                    $transaction->commit();
                    $this->hanldlog($user->id,'取消订单'.$order->ordernumber);
                    $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
                    return $this->resultInfo($data);
                }
            }catch (\Exception $e){
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'取消失败']);
                return $this->resultInfo($data);
            }
        }
    }
    /*
     * 查找车辆信息
     * */
    public function actionSelect_car(){
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        $carnumber = $input['carnumber'];
        $type = $input['type'];
        $car = Car::find()->where(['group_id'=>$group_id,'state'=>1,'delete_flag'=>'Y','use_flag'=>'Y']);
        if ($carnumber){
            $car->andWhere(['like','carnumber',$carnumber]);
        }
        if ($type){
            $car->andWhere(['type'=>$type]);
        }
        $list = $car->select(['carnumber','driver_name','mobile'])->asArray()->all();

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list]);
        return $this->resultInfo($data);
    }
    /*
     * 检索地址
     * */
    public function actionSelect()
    {
        $input = Yii::$app->request->post();
        $group_id = $input['group_id'];
        $pro_id = $input['pro'];
        $city_id = $input['city'];
        $area_id = $input['area'];
        $address = $input['address'];
        $list = AppCommonAddress::find()
            ->where(['like', 'all', $address]);
        if ($pro_id) {
            $list->andWhere(['pro_id' => $pro_id]);
        }
        if ($city_id) {
            $list->andWhere(['city_id' => $city_id]);
        }
        if ($area_id) {
            $list->andWhere(['area_id' => $area_id]);
        }

        $list->andWhere(['group_id' => $group_id]);

        $l = json_encode($list);
        $list = $list
            ->select(['pro_id', 'city_id', 'area_id', 'address','all'])
            ->orderBy(['count_views' => SORT_DESC, 'update_time' => SORT_DESC])
            ->limit(20)
            ->asArray()
            ->all();

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list,'input'=>$input,'l'=>$l]);
        return $this->resultInfo($data);
    }


    /*
     * 检索联系人
     * */
    public function actionSelect_contact(){
        $input = Yii::$app->request->post();
        $name = $input['name'];
        $tel = $input['tel'];
        $user_id = $input['user_id'];
        $list = AppCommonContacts::find()
            ->where(['user_id'=>$user_id]);
        if ($name){
            $list->andWhere(['like','name',$name]);
        }
        if ($tel){
            $list->andWhere(['like','tel',$tel]);
        }
        $list->andWhere(['!=','tel','']);
        $list = $list
            ->select(['name','tel'])
            ->orderBy(['views'=>SORT_DESC,'update_time'=>SORT_DESC])
            ->limit(20)
            ->asArray()
            ->all();

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list]);
        return $this->resultInfo($data);
    }

    /*
     * 计算公里数
     * */
    public function actionCount_time(){
        $input = Yii::$app->request->post();
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        // 起点城市经纬度
        $start_action = bd_local($type='1',$startcity,$area='');//经纬度
        // 终点城市经纬度
        $end_action = bd_local($type='1',$endcity,$area='');//经纬度
        $list = direction($start_action['lat'], $start_action['lng'], $end_action['lat'], $end_action['lng']);
        $kilo = $list['distance']/1000;
        $time = round($kilo/60 + 10);
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$time]);
        return $this->resultInfo($data);
    }

    /*
     * 上线订单列表
     * */
    public function actionLine_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $status = $input['status'];
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
        if (empty($token) || !$group_id){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $check_result = $this->check_token_list($token);//验证令牌
        $list = AppVehical::find()
            ->alias('v')
            ->select(['v.*','c.all_name','t.carparame'])
            ->leftJoin('app_customer c','v.company_id=c.id')
            ->leftJoin('app_cartype t','v.cartype=t.car_id')
            // ->leftJoin('app_unusual u','v.id=u.orderid')
            ->where(['v.group_id'=>$group_id,'v.line_status'=>2,'v.delete_flag'=>'Y']);
        if($ordernumber){
            $list->andWhere(['like','v.ordernumber',$ordernumber]);
        }
        if ($begintime) {
            $time_s = $begintime . ' 00:00:00';
            $time_e = $begintime . ' 23:59:59';
            $list->andWhere(['between','v.time_start',$time_s,$time_e]);
        }

        if ($status == 1) {
            $list->andWhere(['=','v.order_status',1]);
        } else if ($status == 2) {
            $list->andWhere(['=','v.order_status',2]);
        } else if ($status == 3) {
            $list->andWhere(['in','v.order_status',[3,4]]);
        } else if ($status == 4) {
            $list->andWhere(['=','v.order_status',5]);
        } else if ($status == 5) {
            $list->andWhere(['=','v.order_status',6]);
        } else if ($status == 6) {
            $list->andWhere(['in','v.order_status',[7,8]]);
        }

        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.time_start'=>SORT_ASC])
            ->asArray()
            ->all();

        foreach ($list as $k => $v) {
            $one = AppUnusual::find()->select(['content'])->where(['orderid'=>$v['id']])->one();
            if ($one) {
                $list[$k]['content'] = $one->content;
            } else {
                $list[$k]['content'] = '';
            }
        }

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
    /*
     * 已接单列表
     * */
    public function actionVehical_take(){
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
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        if (empty($token) || !$group_id){
            $data['msg'] = '参数错误';
            return json_encode($data);
        }

        $check_result = $this->check_token_list($token);//验证令牌
        $list = AppVehical::find()
            ->alias('v')
            ->select(['v.*','t.carparame'])
            ->leftJoin('app_cartype t','v.cartype=t.car_id')
            ->where(['v.deal_company'=>$group_id,'v.line_status'=>2,'v.delete_flag'=>'Y']);
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
            ->orderBy(['v.time_start'=>SORT_ASC])
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
    /*
     * 整车接单
     * */
    public function actionOrder_take(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $res_p = true;
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>40000003,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppVehical::find()->where(['id'=>$id])->one();
        if ($order->order_status != 1){
            $data = $this->encrypt(['code'=>4000001,'msg'=>'订单已被承接']);
            return $this->resultInfo($data);
        }
        $group_id = $user->parent_group_id;

        $group = AppGroup::find()->where(['id'=>$group_id])->one();

        if ($group->level_id == 1){
            $has_car = Car::find()->where(['group_id'=>$group->id])->one();
            if (!$has_car){
                $data = $this->encrypt(['code'=>400,'msg'=>'请先认证车辆']);
                return $this->resultInfo($data);
            }
            $has_order = AppVehical::find()->where(['group_id'=>$id])->andWhere(['not in','order_status',[1,6,7,8]])->all();
            if ($has_order){
                $data = $this->encrypt(['code'=>400000,'msg'=>'请先完成当前订单']);
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
            if($order->money_state == 'Y'){
                $receive->company_type = 2;
                $receive->compay_id = 25;
            }else{
                $receive->company_type = 1;
                $receive->compay_id = $order->compay_id;
                $payment = AppPayment::find()->where(['group_id'=>$order->group_id,'order_id'=>$id,'type'=>1])->one();
                $payment->carriage_id = $user->group_id;
                $payment->pay_type = 4;
                $res_p = $payment->save();
            }
            $transaction= AppVehical::getDb()->beginTransaction();
            try {
                $res = $order->save();
                $arr = $receive->save();
                $res_c = $carriage->save();
                if ($res && $arr && $res_c){
                    $transaction->commit();
                    $this->hanldlog($user->id,'接取外部订单'.$order->ordernumber);
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
            if($order->money_state == 'Y'){
                $receive->company_type = 2;
                $receive->compay_id = 25;
            }else{
                $receive->company_type = 2;
                $receive->compay_id = $order->group_id;
                $receive->trueprice = 0;
            }
            if ($order->money_state == 'N'){
                $payment = AppPayment::find()->where(['group_id'=>$order->group_id,'order_id'=>$id])->one();
                $payment->carriage_id = $user->group_id;
                $payment->pay_type = 4;
                $res_p = $payment->save();
            }
            $transaction= AppVehical::getDb()->beginTransaction();
            try {
                $res = $order->save();
                $arr = $receive->save();

                if ($res && $arr && $res_p){
                    $transaction->commit();
                    $this->hanldlog($user->id,'接取外部订单'.$order->ordernumber);
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
     * 确认送达
     * */
    public function actionConfirm_service(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];

        $order = AppVehical::findOne($id);
        $this->check_group_auth($order->deal_company,$user);

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

    /*
     * 确认完成
     * */
    public function actionConfirm_done(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status == 6){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已完成']);
            return $this->resultInfo($data);
        }
        if ($order->unusual == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'请处理完异常信息']);
            return $this->resultInfo($data);
        }
        $order->order_status = 6;
        $group = AppGroup::find()->where(['id'=>$order->deal_company])->one();
        $group->balance = $group->balance + $order->line_price;
        $balance = new AppBalance();
        $balance->pay_money = $order->line_price;
        $balance->order_content = '整车订单收入';
        $balance->action_type = 9;
        $balance->userid = $user->id;
        $balance->create_time = date('Y-m-d H:i:s',time());
        $balance->ordertype = 1;
        $balance->orderid = $order->id;
        $balance->group_id = $order->deal_company;
        $paymessage = new AppPaymessage();
        $paymessage->paynum = $order->line_price;
        $paymessage->create_time = date('Y-m-d H:i:s',time());
        $paymessage->userid = $user->id;
        $paymessage->paytype = 3;
        $paymessage->type = 1;
        $paymessage->state = 5;
        $paymessage->orderid = $order->ordernumber;
        $receive = AppReceive::find()->where(['group_id'=>$order->deal_company,'order_id'=>$id])->one();
        $receive->status = 3;
        $receive->trueprice = $receive->al_price = $order->line_price;
        $payment = AppPayment::find()->where(['group_id'=>$order->group_id,'order_id'=>$id])->one();
        $payment->status = 3;
        $payment->al_pay = $payment->truepay = $order->line_price ;

        $transaction= AppVehical::getDb()->beginTransaction();
        try {
            $res_b = $balance->save();
            $res_pay = $paymessage->save();
            $res_g = $group->save();
            $res = $order->save();
            $arr = $receive->save();
            $res_p = $payment->save();
            if ($res && $arr && $res_g && $res_p &&$res_pay && $res_b){
                $transaction->commit();
                $this->hanldlog($user->id,'完成订单'.$order->ordernumber);
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
     * 上传回单
     * */
    public function actionUpload_receipt(){
         $input = Yii::$app->request->post();
         $token  = $input['token'];
         $id = $input['id'];
         $file = $_FILES['file'];
//         $file = $input['tyd'];
         if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
         }
         $check_result = $this->check_token($token,true);
         $user = $check_result['user'];
         $order = AppVehical::findOne($id);
         $this->check_group_auth($order->deal_company,$user);
         $order->receipt = $this->more_upload('receipt',$file);
         $res = $order->save();
         if ($res){
             $this->hanldlog($user->id,'上传回单'.$order->ordernumber);
             $data = $this->encrypt(['code'=>200,'msg'=>'上传成功']);
             return $this->resultInfo($data);
         }else{
             $data = $this->encrypt(['code'=>400,'msg'=>'上传失败']);
             return $this->resultInfo($data);
         }
    }

    /*
     * 接单上传回单
     * */
    public function actionUpload(){
        $input = Yii::$app->request->post();
        $token  = $input['token'];
        $id = $input['id'];
        $file = $input['tyd'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->deal_company,$user);
        $imgs = json_decode($this->base64($file),true);
         $old_imgs = $order->receipt;
         if ($old_imgs && count(json_decode($old_imgs,true)) >= 1) {
            $imgs = array_merge(json_decode($old_imgs,true),$imgs);
         }
        $order->receipt = json_encode($imgs);
        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'上传回单'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'上传成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'上传失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     *内部订单上传回单
     * */
    public function actionUpload_order(){
        $input = Yii::$app->request->post();
        $token  = $input['token'];
        $id = $input['id'];
        $file = $input['tyd'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        $imgs = json_decode($this->base64($file),true);
        $old_imgs = $order->receipt;
        if ($old_imgs && count(json_decode($old_imgs,true)) >= 1) {
            $imgs = array_merge(json_decode($old_imgs,true),$imgs);
        }
        $order->receipt = json_encode($imgs);
        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'上传回单'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'上传成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'上传失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 申报异常
     * */
    public function actionUnusual(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $content = $input['content'];
        // $type = $input['type'];
        if (empty($token) && !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($content)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请填写异常信息']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        $model = AppUnusual::find()->where(['orderid'=>$order->id])->one();
        if (!$model) {
            $model = new AppUnusual();
            $model->orderid = $id;
        }
        $model->group_id = $order->group_id;
        $model->content = $content;
        $model->type = 1;
        // if ($type){
        //     $unusual->charging = $input['charging'];
        // }else{
        //     $unusual->charging = 0;
        // }
        $res = $model->save();
        // $order->unusual = 2;
        // $order->unususl_id = $unusual->id;
        // $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'申报异常:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>'200','msg'=>'提交成功!']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'网络出错，请稍后再试']);
            return $this->resultInfo($data);
        }
    }

    /*
     *查看异常标签
     * */
    public function actionSelect_remark(){
        $remark = AppRemark::find()->where(['delete_flag'=>'Y'])->asArray()->all();
        $data =$this->encrypt(['code'=>'200','msg'=>'','data'=>$remark]);
        return $this->resultInfo($data);
    }
    
    /*
     * 查看group表level
     * */
    public function actionSelect_level(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        //临时付费为0
        $arr = [];
        // $arr['price'] = 0.01;
        // $data = $this->encrypt(['code'=>200,'msg'=>'操作成功','data'=>$arr,'type'=>1]);
        // return $this->resultInfo($data);

        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];

        $group = AppGroup::find()->where(['id'=>$user->group_id])->one();
        $order = AppVehical::find()->select(['order_status','line_price'])->where(['id'=>$id])->one();
        $time = time();
        
        if ($group->level == 3) {
            //包月
            if (strtotime($group->now_level_expire) < $time) {
                // 过期
                $group->level = 1;//修改为一单一付
                $group->save();
                $setting = AppSetting::find()->select(['value'])->where(['key'=>'order_fixed_price'])->one();
                $arr['price'] = $setting->value;
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功','data'=>$arr,'type'=>1]);
                return $this->resultInfo($data);
            } else {
                // 未过期
                if ($order->order_status == 2){
                    $data = $this->encrypt(['code'=>400,'msg'=>'订单已被承接']);
                    return $this->resultInfo($data);
                }
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功','data'=>$arr,'type'=>3]);
                return $this->resultInfo($data);
            }
        } else {
            // 需要先付
            if ($group->level == 1) {
                $setting = AppSetting::find()->select(['value'])->where(['key'=>'order_fixed_price'])->one();
                $arr['price'] = $setting->value;
            } else if ($group->level == 2) {
                $setting = AppSetting::find()->select(['value'])->where(['key'=>'order_percent'])->one();
                $arr['price'] = round($order->line_price*($setting->value-0)/100,2);
            }
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功','data'=>$arr,'type'=>$group->level]);
            return $this->resultInfo($data);
        }

    }

    /*
     * 修改group表level
     * */
    public function actionEdit_level(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $type = $input['type'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);
        $group = AppGroup::find()->where(['id'=>$group_id])->one();
        if ($type == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
        if ($group->level == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
        if ($type == 1){
            $group->level = 1;
        }else{
            $group->level = 2;
        }
        $res = $group->save();
        if ($res){
            $this->hanldlog($user->id,'修改接单支付方式'.$group->group_name);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     *查询订单状态
     * */
    public function actionCheck_state(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $order = AppVehical::find()->select(['order_status'])->where(['id'=>$id])->one();
        if ($order->order_status == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已被承接']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>200,'msg'=>'']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 接单取消
     * */
    public function actionTake_cancel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);

//        $this->check_group_auth($order->deal_company,$user);
        $group = AppGroup::find()->where(['id'=>$user->group_id])->one();
        if (in_array($order->order_status,[3,4,5,6])){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已承运，不能取消']);
            return $this->resultInfo($data);
        }
        $payment = false;
        $res_p = true;
        if ($group->level_id == 3){
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


            $transaction= AppVehical::getDb()->beginTransaction();
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
            $res = $order->save();
            if ($res){
                $this->hanldlog($user->id,'取消接单'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'取消成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'取消失败']);
                return $this->resultInfo($data);
            }
        }
    }


    /*
     * 整车计费规则
     * */
    public function actionCount_role(){
        $input = Yii::$app->request->post();
        $carid = $input['car_id'];
        $car = AppCartype::find()->select('lowprice,costkm')->where(['car_id'=>$carid])->asArray()->one();
        if ($car){
            $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$car]);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'暂无数据']);
            return $this->resultInfo($data);
        }

    }

    /*
     * 确认接单（接客户下单）
     * */
    public function actionCustomer_order(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppVehical::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->order_status != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'请勿重复操作']);
            return $this->resultInfo($data);
        }
        $order->order_status = 2;
        $res = $order->save();
        if ($res){
             $this->hanldlog($user->id,'确认订单：'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功，请尽快安排车辆']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'网络出错']);
            return $this->resultInfo($data);
        }
    }

}