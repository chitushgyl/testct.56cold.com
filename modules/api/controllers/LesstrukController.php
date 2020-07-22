<?php
namespace app\modules\api\controllers;

use app\models\AppCartype;
use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use app\models\AppGroup;
use app\models\AppOrder;
use app\models\AppPayment;
use app\models\AppReceive;
use app\models\Customer;
use Yii;

class LesstrukController extends CommonController{
     /*
      * 添加订单
      * */
    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $company_id = $input['company_id'];
        $start_time = $input['start_time'];
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
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $order = new AppOrder();
        if (empty($group_id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择所属公司！']);
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

        if (empty($temperture)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择温度']);
            return $this->resultInfo($data);
        }


        if (empty($name)){
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

        $check_result = $this->check_token($token,false);//验证令牌
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);
        $group = AppGroup::findOne($user->parent_group_id);
        if ($group->level_id != 3) {
            $order->company_id = $group_id;
        } else {
            if ($company_id) {
                $order->company_id = $company_id;
            } else {
                $order->company_id = $group_id;
            }
        }
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
        $order->name = $cargo_name;
        $order->number = $cargo_number;
        $order->number2 = $cargo_number2;
        $order->weight = $cargo_weight;
        $order->volume = $cargo_volume;
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
        $order->total_price = $total_price;
        $order->order_type = $order_type;
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
        $arr = $receive->save();


        if ($res){
            $this->hanldlog($user->id,'添加零担订单:'.$order->startcity.'->'.$order->endcity);
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 普通用户下单
     * */
    public function actionBulk_add(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $start_time = $input['start_time'];
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
        $price = $input['price'] ?? '';
        $order_type = $input['order_type'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }

        $order = new AppOrder();
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


        if (empty($name)){
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
        $check_result = $this->check_token($token,false);//验证令牌
        $user = $check_result['user'];
        $group = AppGroup::findOne($user->parent_group_id);

        $order->company_id = $user->group_id;
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
        $order->endstr = $endstr;
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
        $order->name = $cargo_name;
        $order->number = $cargo_number;
        $order->number2 = $cargo_number2;
        $order->weight = $cargo_weight;
        $order->volume = $cargo_volume;
        $order->create_user_id = $user['id'];
        $order->create_user_name = $user['name'];
        $order->group_id = $user->group_id;
        $order->temperture = $temperture;
        $order->remark = $remark;
        $order->picktype = $picktype;
        $order->pickprice = $pickprice;
        $order->sendtype = $sendtype;
        $order->sendprice = $sendprice;
        $order->time_start = $start_time;//用车时间
        $order->time_end = $end_time;//预计到达时间
        $order->price = $price;
        $order->total_price = $price;
        $order->order_type = $order_type;
        $res =  $order->save();

        $payment = new AppPayment();
        $payment->order_id = $order->id;
        $payment->pay_price = $price;
        $payment->group_id = $user->group_id;
        $payment->create_user_id = $user->id;
        $payment->create_user_name = $user->name;
        $payment->save();

        if ($res){
            $this->hanldlog($user->id,'添加零担订单:'.$order->startcity.'->'.$order->endcity);
            $data = $this->encrypt(['code'=>200,'msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 订单详情(编辑)
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

        if ($id) {
            $group_id = $model['group_id'];
        } else {
            $group_id = $groups[0]['id'];
        }
        $customer = Customer::get_list($group_id);
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'groups'=>$groups,'customer'=>$customer,'group_id'=>$group_id]);
        return $this->resultInfo($data);
    }

    /*
     * 编辑订单
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $token = $input['toekn'];
        $id = $input['id'];
        $group_id = $input['group_id'];
        $company_id = $input['company_id'];
        $start_time = $input['start_time'];
        $end_time = $input['end_time'];
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
        $total_price = $input['total_price'] ?? '';
        if (empty($token) || empty($id)){
             $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
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

        if (empty($temperture)){
            $data = $this->encrypt(['code'=>400,'msg'=>'请选择温度']);
            return $this->resultInfo($data);
        }


        if (empty($name)){
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
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $order->startcity = $startcity;
        $order->endcity = $endcity;
        $order->startstr = $startstr;
        $order->endstr = $endstr;
        $order->time_start = $start_time;
        $order->time_end = '';
        $order->name = $cargo_name;
        $order->weight = $cargo_weight;
        $order->number = $cargo_number;
        $order->number2 = $cargo_number2;
        $order->volume = $cargo_volume;
        $order->total_price = $total_price;
        $order->picktype = $picktype;
        $order->sendtype = $sendtype;
        $order->temperture = $temperture;
        $order->remark = $remark;

        $res = $order->save();
        $group = AppGroup::findOne($user->parent_group_id);
        if ($group->level_id == 3 && $company_id){
            $receive = AppReceive::find()->where(['order_id'=>$id,'group_id'=>$user->group_id])->one();
            $time = date('Y-m-d H:i:s',time());
            $receive->receivprice = $order->total_price;
            $receive->trueprice = 0;
            $receive->receive_info = json_encode(['price'=>$price,'pickprice'=>$pickprice,'sendprice'=>$sendprice,'more_price'=>$more_price,'otherprice'=>$otherprice]);
            $receive->create_time = $time;
            $receive->update_time = $time;
            $arr = $receive->save();
        }else if($group->level_id == 1 || $group->level_id == 2){
            $payment = AppPayment::find()->where(['order_id'=>$id,'group_id'=>$user->group_id])->one();
            $payment->pay_price = $price;
            $payment->save();
        }

        if ($res){
            $this->hanldlog($user->id,'修改订单:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'修改成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'修改成功']);
            return $this->resultInfo($data);
        }


    }


    /*
     * 是否拆分订单
     * */
    public function actionSplit_order(){
        $input = Yii::$app->request->post();
        $token = $input['toekn'];
        $id = $input['id'];
        if(empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppOrder::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->split == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单已拆分，请勿重复提交']);
            return $this->resultInfo($data);
        }

        if($order->picktype == 2 && $order->sendtype == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'该订单不能拆分']);
            return $this->resultInfo($data);
        }
        $pick_store = $input['pick_store'];//起始仓库地址
        $send_store = $input['send_store'];//目的仓库地址
        if($order->picktype == 1){

        }
        if ($order->sendtype == 1){
            
        }

    }
}
