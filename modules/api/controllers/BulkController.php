<?php

namespace app\modules\api\controllers;


use app\models\AppBalance;
use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use app\models\AppGroup;
use app\models\AppLine;
use app\models\AppPayment;
use app\models\AppPaymessage;
use app\models\AppPickorder;
use app\models\AppReceive;
use app\models\AppSendorder;
use app\models\AppUnusual;
use app\models\AppList;
use app\models\Carriage;
use Yii;
use app\models\AppBulk;

/**
 * Default controller for the `api` module
 */
class BulkController extends CommonController
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
        $customer = $input['customer'] ?? '';
        $status = $input['orderstate'] ?? '';

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
        $list = AppBulk::find()
            ->alias('v')
            ->select(['v.*', 'c.all_name'])
            ->leftJoin('app_customer c', 'v.customer_id=c.id');

        if ($ordernumber) {
            $list->andWhere(['like', 'v.ordernumber', $ordernumber]);
        }
        if ($customer) {
            $list->andWhere(['like', 'v.customer_id', $customer]);
        }
        if ($begintime && $endtime) {
            $list->andWhere(['between', 'v.create_time', $begintime, $endtime]);
        }
        if ($status) {
            $list->andWhere(['v.orderstate' => $status]);
        }
        $list->andWhere(['v.group_id' => $group_id]);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.update_time' => SORT_DESC, 'v.orderstate' => SORT_ASC])
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
     *添加订单
     * */
    public function actionAdd()
    {
        $input = Yii::$app->request->post();
        $token = $input['token'];
        if (empty($token)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '参数错误']);
            return $this->resultInfo($data);
        }
        $goodsname = $input['goodsname'];
        $weight = $input['weight'];
        $volume = $input['volume'];
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $temperture = $input['temperture'];
        $shiftid = $input['shiftid'];
        if (empty($goodsname)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '请填写货品名称']);
            return $this->resultInfo($data);
        }
        if (empty($weight)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '请填写货品重量']);
            return $this->resultInfo($data);
        }
        if (empty($volume)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '请填写货品体积']);
            return $this->resultInfo($data);
        }
        if (empty($startcity)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '请填写起始城市']);
            return $this->resultInfo($data);
        }
        if (empty($endcity)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '请填写目的城市']);
            return $this->resultInfo($data);
        }
        if (empty($temperture)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '请填写温度']);
            return $this->resultInfo($data);
        }
        if (empty($shiftid)) {
            $data = $this->encrypt(['code' => '400', 'msg' => '请选择线路']);
            return $this->resultInfo($data);
        }
        $bulk = new AppBulk();
    }

    /*
     * 在线干线 下单
     * */
    public function actionBulk_addd()
    {
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
        $groupid = $user->parent_group_id;
        $group = AppGroup::find()->where(['id'=>$groupid])->one();
        $transaction= AppBulk::getDb()->beginTransaction();
        $res_s = $res_p = $arr = true;
        try {
            if ($group->level_id != 3) {
                $bulk->customer_id = $group_id;
            } else {
                if ($customer_id) {
                    $bulk->customer_id = $customer_id;
                } else {
                    $bulk->customer_id = $group_id;
                }
            }
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
                $pickorder->order_type = 1;
                $pickorder->trunking = $line->start_time;
                $res_p = $pickorder->save();
            }
            if ($sendtype == 1) {
                $sendorder = new AppPickorder();
                $sendorder->order_id = $bulk->id;
                $sendorder->startcity = $line->endcity;
                $sendorder->endcity = $line->endcity;
                $sendorder->startstr_pick = $line->end_store;
                $sendorder->endstr_pick = $end_info;
                $sendorder->goodsname = $goodsname;
                $sendorder->pick_volume = $volume;
                $sendorder->pick_number = $number;
                $sendorder->pick_number1 = $number1;
                $sendorder->pick_weight = $weight;
                $sendorder->temperture = $temperture;
                $sendorder->pick_price = $bulk->sendprice;
                $sendorder->group_id = $group_id;
                $sendorder->order_type = 2;
                $pickorder->trunking = date('Y-m-d H:i',strtotime($line->start_time) + $line->trunking * 24 *3600);
                $res_s = $sendorder->save();
            }
            if ($group->level_id == 3) {
                if ($customer_id) {
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
                }
            }
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
     *零担下单（系统）
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
     * 编辑订单
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
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
        if (empty($token) || empty($shiftid) || empty($group_id) || empty($id)) {
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
        $line = AppLine::findOne($shiftid);
        if ($line->dispatch_state == 2){
            $data = $this->encrypt(['code' => '400', 'msg' => '该线路已调度，不能修改']);
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
        $bulk = AppBulk::findOne($id);

        $groupid = $user->parent_group_id;
        $group = AppGroup::find()->where(['id'=>$groupid])->one();
        $transaction= AppBulk::getDb()->beginTransaction();
        $res_s = $res_p = $arr = true;
        try {
            if ($group->level_id != 3) {
                $bulk->customer_id = $group_id;
            } else {
                if ($customer_id) {
                    $bulk->customer_id = $customer_id;
                } else {
                    $bulk->customer_id = $group_id;
                }
            }
            $bulk->goodsname = $goodsname;
            $bulk->number = $number;
            $bulk->number1 = $number1;
            $bulk->weight = $weight;
            $bulk->volume = $volume;
            $bulk->temperture = $temperture;
            $bulk->lineprice = $lineprice;
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
            $bulk->remark = $remark;
            $bulk->otherprice = $otherprice;
            $bulk->total_price = $lineprice + $bulk->pickprice + $bulk->sendprice + $otherprice;
            $bulk->line_type = $line_type;
            $res = $bulk->save();
            $pickorder = AppPickorder::find()->where(['group_id'=>$group_id,'order_id'=>$id,'order_type'=>1])->one();
            if ($pickorder){
                if ($picktype == 1){
                    $pickorder->startstr_pick = $begin_info;
                    $pickorder->goodsname = $goodsname;
                    $pickorder->pick_volume = $volume;
                    $pickorder->pick_number = $number;
                    $pickorder->pick_weight = $weight;
                    $pickorder->temperture = $temperture;
                    $pickorder->pick_number1 = $number1;
                    $pickorder->pick_price = $bulk->pickprice;
                    $res_p = $pickorder->save();
                }
            }else{
                if ($picktype == 1){
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
                    $pickorder->order_type = 1;
                    $res_p = $pickorder->save();
                }
            }
            $sendorder = AppPickorder::find()->where(['group_id'=>$group_id,'order_id'=>$id,'order_type'=>2])->one();
            if ($sendorder){
                if ($sendtype == 1) {
                    $sendorder->endstr_pick = $end_info;
                    $sendorder->goodsname = $goodsname;
                    $sendorder->pick_volume = $volume;
                    $sendorder->pick_number = $number;
                    $sendorder->pick_number1 = $number1;
                    $sendorder->pick_weight = $weight;
                    $sendorder->temperture = $temperture;
                    $sendorder->pick_price = $bulk->sendprice;
                    $res_s = $sendorder->save();
                }
            }else{
                if ($sendtype == 1) {
                    $sendorder = new AppPickorder();
                    $sendorder->order_id = $bulk->id;
                    $sendorder->startcity = $line->endcity;
                    $sendorder->endcity = $line->endcity;
                    $sendorder->startstr_pick = $line->end_store;
                    $sendorder->endstr_pick = $end_info;
                    $sendorder->goodsname = $goodsname;
                    $sendorder->pick_volume = $volume;
                    $sendorder->pick_number = $number;
                    $sendorder->pick_number1 = $number1;
                    $sendorder->pick_weight = $weight;
                    $sendorder->temperture = $temperture;
                    $sendorder->pick_price = $bulk->sendprice;
                    $sendorder->group_id = $group_id;
                    $sendorder->order_type = 2;
                    $res_s = $sendorder->save();
                }
            }
            $receive = AppReceive::find()->where(['order_id'=>$id,'group_id'=>$group_id])->one();
            if ($receive){
                if ($group->level_id == 3) {
                    if ($customer_id) {
                        $time = date('Y-m-d H:i:s', time());
                        $receive->compay_id = $customer_id;
                        $receive->receivprice = $customer_price;
                        $receive->receive_info = '';
                        $receive->create_user_id = $user->id;
                        $receive->create_user_name = $user->name;
                        $receive->group_id = $group_id;
                        $receive->update_time = $time;
                        $arr = $receive->save();
                    }
                }
            }else{
                if ($group->level_id == 3) {
                    if ($customer_id) {
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
                        $arr = $receive->save();
                    }
                }
            }

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
     * 零担计价
     * */
    public function actionCount_price(){
        $input = Yii::$app->request->post();
        $shiftid = $input['shiftid'];
        $line = AppLine::findOne($shiftid);
        $shift_weight = json_decode($line->weight_price,true);
        $weight = $input['weight'];
        $volume = $input['volume'];
        $weight_v = $volume*1000/$weight;
        if ($weight_v <2.5){
            $scale = 5;
            foreach ($shift_weight as $key => $value) {
                if($value['price'] < $scale){
                    $scale = $value['price'];
                }
                if($weight>= $value['min'] && $weight<= $value['max']){
                    $lineprice = $value['price'] * $weight;
                }
            }
            if(!isset($lineprice)){
                $lineprice = $scale == 5 ? $line->line_price: $scale* $weight;
            }
        }else{
            foreach ($shift_weight as $key => $value) {
                if ($weight >= $value['min']){
                    $volume_price = $value['price'] * 1000/2.5;
                }
                if($weight>= $value['min'] && $weight<= $value['max']){
                    $volume_price = $value['price'] * 1000/2.5;
                }
            }

            $lineprice = $volume * $volume_price;
        }
        $lineprice = ($lineprice>$line->line_price)? $lineprice:$line->line_price;
        $lineprice = round($lineprice,2);
        $data = $this->encrypt(['code'=>'200','msg'=>'查询成功','data'=>$lineprice]);
        return $this->resultInfo($data);
    }
    /*
     * 零担计费规则
     * */
    public function actionCount_role(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $line = AppLine::find()->select('weight_price')->where(['id'=>$id])->asArray()->one();
        if ($line){
            $data = $this->encrypt(['code'=>'200','msg'=>'查询成功','data'=>$line]);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'暂无数据']);
            return $this->resultInfo($data);
        }
    }

    /*
    * 取消下单
    * */
    public function actionBulk_cancel(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
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
        if($order->orderstate == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'订单运输中不能取消']);
            return $this->resultInfo($data);
        }
        $line = AppLine::findOne($order->shiftid);
        $time = strtotime($line->start_time);
        if ((time()-2*3600)>= $time){
            $data = $this->encrypt(['code'=>400,'msg'=>'发车前两个小时内不可取消订单']);
            return $this->resultInfo($data);
        }

        $order->orderstate = 6;
        $order_state = $order->save();
        $payment = AppPayment::find()->where(['group_id'=>$order->group_id,'order_id'=>$order->id,'type'=>2])->one();
        $pay_state =$payment->delete();
        $receive = AppReceive::find()->where(['group_id'=>$line->group_id,'order_id'=>$order->id,'type'=>2])->one();
        $receive->delete();

        $paymessage = AppPaymessage::find()->where(['orderid'=>$order->ordernumber,'state'=>1,'pay_result'=>'SUCCESS'])->one();
        if (!$paymessage){
            $this->hanldlog($user->id,'取消干线下单'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'取消成功，退款请联系客服']);
            return $this->resultInfo($data);
        }
        if ($paymessage->paytype == 1){
            //支付宝退款
            $body = '取消干线下单退款';
            $arr = $this->refund($paymessage->orderid,$paymessage->paynum,$body);
            $res = json_decode($arr,true);
            $refund = (array)$res;
            if ($refund['code'] == '10000' && $refund['msg'] == 'Success'){
                $balance = new AppBalance();
                $pay = new AppPaymessage();
                $balance->orderid = $id;
                $balance->pay_money = $refund['refund_fee'];
                $balance->order_content = '取消干线下单退款';
                $balance->action_type = 5;
                $balance->userid = $user->id;
                $balance->create_time = date('Y-m-d H:i:s',time());
                $balance->ordertype = 2;
                $balance->group_id = $user->group_id;
                $pay->orderid = $refund['out_trade_no'];
                $pay->paynum = $refund['refund_fee'];
                $pay->create_time = date('Y-m-d H:i:s',time());
                $pay->userid = $user->id;
                $pay->paytype = 1;
                $pay->type = 1;
                $pay->state = 3;
                $pay->payname = $refund['buyer_logon_id'];
                $pay->group_id = $user->group_id;
                $transaction= AppPaymessage::getDb()->beginTransaction();
                $res = $pay->save();
                $res_b = $balance->save();
                $data = $this->encrypt(['code'=>200,'msg'=>'取消成功，运费已退至付款账户']);
                return $this->resultInfo($data);

            }else{
                $balance = new AppBalance();
                $pay = new AppPaymessage();
                $balance->orderid = $id;
                $balance->pay_money = $paymessage->paynum;
                $balance->order_content = '取消干线下单退款失败';
                $balance->action_type = 5;
                $balance->userid = $user->id;
                $balance->create_time = date('Y-m-d H:i:s',time());
                $balance->ordertype = 2;
                $balance->group_id = $user->group_id;
                $pay->orderid = $paymessage['orderid'];
                $pay->paynum = $paymessage->paynum;
                $pay->create_time = date('Y-m-d H:i:s',time());
                $pay->userid = $user->id;
                $pay->paytype = 1;
                $pay->type = 1;
                $pay->state = 3;
                $pay->pay_result = 'FAIL';
                $pay->group_id = $user->group_id;
                $balance->save();
                $pay->save();
                $this->hanldlog($user->id,'取消干线下单'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'取消成功，退款请联系客服']);
                return $this->resultInfo($data);
            }
        }elseif($paymessage->paytype == 3){
            //余额退款
            $group = AppGroup::find()->where(['id'=>$order->group_id])->one();
            $paymessage = AppPaymessage::find()->where(['orderid'=>$order->ordernumber,'state'=>1,'paytype'=>3,'pay_result'=>'SUCCESS'])->one();
            $price = $paymessage->paynum;
            $balan_money = $paymessage->paynum + $group->balance;
            $group->balance = $balan_money;
            $balance = new AppBalance();
            $pay = new AppPaymessage();
            $balance->orderid = $order->id;
            $balance->pay_money = $price;
            $balance->order_content = '干线下单余额退款';
            $balance->action_type = 7;
            $balance->userid = $user->id;
            $balance->create_time = date('Y-m-d H:i:s',time());
            $balance->ordertype = 2;
            $balance->group_id = $user->group_id;
            $pay->orderid = $order->ordernumber;
            $pay->paynum = $price;
            $pay->create_time = date('Y-m-d H:i:s',time());
            $pay->userid = $user->id;
            $pay->paytype = 3;
            $pay->type = 1;
            $pay->state = 3;
            $pay->group_id = $user->group_id;
            $transaction= AppPaymessage::getDb()->beginTransaction();
            try{
                $res = $pay->save();
                $res_m = $group->save();
                $res_b = $balance->save();
                if ($res && $res_m &&$res_b){
                    $transaction->commit();
                    $this->hanldlog($user->id,'取消干线下单:'.$order->ordernumber);
                    $data = $this->encrypt(['code'=>200,'msg'=>'取消成功，运费已退至付款账户']);
                    return $this->resultInfo($data);
                }
            }catch (\Exception $e){
                $transaction->rollback();
                $this->hanldlog($user->id,'取消干线下单:'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'取消成功，退款请联系客服！']);
                return $this->resultInfo($data);
            }
        }

    }

    /*
     *客户 干线下单列表
     * */
    public function actionLine_index(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $ordernumber = $input['name'] ?? '';
        $begintime = $input['begintime'] ?? '';
        $endtime = $input['endtime'] ?? '';
        $line_city = $input['line_city'] ?? '';
        $status = $input['orderstate'] ?? '';
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
        $list->andWhere(['a.line_type'=>2,'a.group_id' => $group_id]);
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
     * 零担订单详情
     * */
    public function actionBulk_view(){
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
        $groups = AppGroup::group_list($user);
        if ($id) {
            $group_id = $model['group_id'];
        } else {
            $group_id = $groups[0]['id'];
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'groups'=>$groups,'group_id'=>$group_id]);
        return $this->resultInfo($data);
    }      

    /*
     * 零担订单 调度详情
     * */
    public function actionBulk_payment_view(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || !$id){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];

        $model = AppBulk::find()
            ->alias('a')
            ->select(['a.*','b.begin_store','b.carriage_id','b.start_time','b.trunking','b.group_id','b.end_store','b.transfer_info','c.group_name'])
            ->leftJoin('app_line b','a.shiftid=b.id')
            ->leftJoin('app_group c','b.group_id = c.id')
            ->where(['a.id'=>$id])->asArray()->one();
        if ($model['carriage_id']) {
            $carriage = Carriage::find()->where(['cid'=>$model['carriage_id']])->asArray()->one();
        }
        $model['begin_info'] = json_decode($model['begin_info'],true);
        $model['end_info'] = json_decode($model['end_info'],true);

        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'carriage'=>$carriage]);
        return $this->resultInfo($data);
    }    

    /*
     * 零担订单 回单详情
     * */
    public function actionBulk_view_img(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token);//验证令牌
        $user = $check_result['user'];
        $model = AppBulk::findOne($id);
        if ($model) {
            $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>json_decode($model->receipt,true)]);
            return $this->resultInfo($data);
        } else {
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
    }

     /*
     * 删除订单 回单 图片
     * */
    public function actionBulk_view_img_del(){
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
        $model = AppBulk::findOne($id);

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
                    $this->hanldlog($user->id,'干线订单删除回单:'.$model->ordernumber);
                    $data = $this->encrypt(['code'=>200,'msg'=>'删除成功！','data'=>$model->receipt]);
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
     * 干线下单确认送达
     * */
    public function actionBulk_arrive(){
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
            ->leftJoin('app_line b',' b.id = a.shiftid')
            ->where(['a.id'=>$id])
            ->asArray()
            ->one();
        if($order['orderstate'] == 4){
            $data = $this->encrypt(['code'=>'400','msg'=>'订单已送达']);
            return $this->resultInfo($data);
        }
        if($order['orderstate'] == 5){
            $data = $this->encrypt(['code'=>'400','msg'=>'订单已完成']);
            return $this->resultInfo($data);
        }
        if($order['orderstate'] == 6){
            $data = $this->encrypt(['code'=>'400','msg'=>'订单已取消']);
            return $this->resultInfo($data);
        }
        $this->check_group_auth($order['groupid'],$user);
        $res = Yii::$app->db->createCommand()->update('app_bulk',['orderstate'=>'4','update_time'=>date('Y-m-d H:i:s')],['id'=> $id])->execute();
        if ($res){
            $this->hanldlog($user->id,'订单已送达'.$order['ordernumber']);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     *干线下单确认完成
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

            if ($order->sendtype == 1){
                if ($order['orderstate'] != 9){
                    $data = $this->encrypt(['code'=>'400','msg'=>'订单运输中']);
                    return $this->resultInfo($data);
                }
            }else{
                if ($order['orderstate'] != 4){
                    $data = $this->encrypt(['code'=>'400','msg'=>'订单运输中']);
                    return $this->resultInfo($data);
                }
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
        $balance->group_id = $order['groupid'];
        $paymessage = new AppPaymessage();
        $paymessage->paynum = $order['total_price'];
        $paymessage->create_time = date('Y-m-d H:i:s',time());
        $paymessage->userid = $user->id;
        $paymessage->paytype = 3;
        $paymessage->type = 1;
        $paymessage->state = 5;
        $paymessage->orderid = $order['ordernumber'];
        $paymessage->group_id = $order['groupid'];
        $receive = AppReceive::find()->where(['group_id'=>$order['groupid'],'order_id'=>$id,'type'=>2])->one();
        $receive->status = 3;
        $receive->trueprice = $order['total_price'];
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
     *已接零担订单列表
     * */
    public function actionBulk_take(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $id = $input['id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;

        $data = [
            'code' => 200,
            'msg' => '',
            'status' => 400,
            'count' => 0,
            'data' => []
        ];
        if (empty($token) || !$id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $check_result = $this->check_token_list($token);//验证令牌
        $line = AppLine::find()->where(['id'=>$id])->asArray()->one();
        $order = AppBulk::find()->orWhere(['line_type'=>2,'paystate'=>2])->orWhere(['in','line_type',[1,3]])->andWhere(['shiftid'=>$id]);

        $count = $order->count();
        $order = $order->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['update_time' => SORT_DESC])
            ->asArray()
            ->all();

        foreach ($order as $k => $v) {
            $order[$k]['begin_info'] = json_decode($v['begin_info'],true);
            $order[$k]['end_info'] = json_decode($v['end_info'],true);
        }

        $data = [
            'code' => 200,
            'msg' => '正在请求中...',
            'status' => 200,
            'count' => $count,
            'line'=>$line,
            'auth' => $check_result['auth'],
            'data' => precaution_xss($order)
        ];
        return json_encode($data);
    }
    /*
     * 上传回单
     * */
    public function upload_receipt($token,$id,$file){
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $order = AppBulk::findOne($id);
        $list = AppBulk::find()
            ->alias('a')
            ->select(['a.*','b.group_id As groupid'])
            ->leftJoin('app_line b','a.shiftid = b.id')
            ->where(['a.id'=>$id])
            ->asArray()
            ->one();
        $this->check_group_auth($list['groupid'],$user);
        // $order->receipt = $this->base64($file);

        $imgs = json_decode($this->base64($file),true);
        $old_imgs = $order->receipt;
        if ($old_imgs && count(json_decode($old_imgs,true)) >= 1) {
            $imgs = array_merge(json_decode($old_imgs,true),$imgs);
        }
        $order->receipt = json_encode($imgs);

        $res = $order->save();
        if ($res){
            $this->hanldlog($user->id,'干线订单上传回单：'.$order->ordernumber);
            $data = $this->encrypt(['code'=>200,'msg'=>'上传成功']);
            $arr = $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'上传失败']);
            $arr = $this->resultInfo($data);
        }
        return $arr;
    }

    /*
     * 零担专线上传回单
     * */
    public function actionUpload_receipt(){
        $input = Yii::$app->request->post();
        $token  = $input['token'];
        $id = $input['id'];
        $file = $input['tyd'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $res =  $this->upload_receipt($token,$id,$file);
        return $res;
    }

    /*
     * 上报异常
     * */
    public function actionUnuausl(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $content = $input['content'];
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
        $order = AppBulk::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        $model = AppUnusual::find()->where(['orderid'=>$order->id])->one();
        if (!$model) {
            $model = new AppUnusual();
            $model->orderid = $id;
        }
        $model->group_id = $order->group_id;
        $model->create_user_id = $user->id;
        $model->content = $content;
        $model->type = 2;
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'干线订单异常:'.$order->ordernumber);
            $data = $this->encrypt(['code'=>'200','msg'=>'提交成功!']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'网络出错，请稍后再试']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 零担应付（无效）
     * */
    public function actionPayment(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        $picktype = $input['picktype'];
        $sendtype = $input['sendtype'];
        $type_p = $input['type_p'] ?? '';//提货: 1自有车辆 2承运公司 3临时车辆
        $type_s = $input['type_s'] ?? '';//配送: 1自有车辆 2承运公司 3临时车辆
        $type_l = $input['type_l']; //干线: 1自有车辆 2承运公司 3临时车辆
        $arr_p = json_decode($input['arr_p'],true);
        $arr_s = json_decode($input['arr_s'],true);
        $arr_l = json_decode($input['arr_l'],true);
        if (empty($id) || empty($token)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $order = AppBulk::findOne($id);
        $line = AppLine::findOne($order->shiftid);
//        $this->check_group_auth($line->group_id,$user);
        $carriage_l = $res_s = $res_p = $res_l = $carriage_p = $carriage_s = true;
        $transaction= AppBulk::getDb()->beginTransaction();
        try {
        if ($picktype == 1){
            switch ($type_p){
                case '1':
                    foreach($arr_p as $key =>$value){
                        $list['order_id'] = $id;
                        $list['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                        $list['group_id'] = $line->group_id;
                        $list['create_user_id'] = $user->id;
                        $list['create_user_name'] = $user->name;
                        $list['contant'] = $value['contant'];
                        $list['tel'] = $value['tel'];
                        $list['carriage_price'] = $value['price'];
                        $list['carnumber'] = $value['carnumber'];
                        $list['type'] = $type_p;
                        $list['startstr'] = $order->begin_info;
                        $list['endstr'] = $line->begin_store;
                        $list['create_time'] = $list['update_time'] = date('Y-m-d H:i:s',time());
                        $info[] = $list;

                        $list_c['order_id'] = $id;
                        $list_c['pay_price'] = $value['price'];
                        $list_c['truepay'] = 0;
                        $list_c['group_id'] = $line->group_id;
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
                    $res_p = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id','carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel','carriage_price', 'carnumber', 'type','startstr','endstr','create_time','update_time'], $info)->execute();
                    $carriage_p = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id','pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','driver_name','driver_car','driver_tel','pay_type','create_time','update_time'], $info_c)->execute();
                    break;
                case '2':
                    foreach($arr_p as $key =>$value){
                        $list['order_id'] = $id;
                        $list['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                        $list['group_id'] = $line->group_id;
                        $list['create_user_id']= $user->id;
                        $list['create_user_name'] = $user->name;
                        $list['deal_company'] = $value['id'];
                        $list['contant'] = $value['contant'];
                        $list['tel'] = $value['tel'];
                        $list['carriage_price'] = $value['price'];
                        $list['type'] = $type_p;
                        $list['startstr'] = $order->begin_info;
                        $list['endstr'] = $line->begin_store;
                        $list['create_time'] = $list['update_time'] = date('Y-m-d H:i:s',time());
                        $info[] = $list;
                        $list_c['order_id'] = $id;
                        $list_c['pay_price'] = $value['price'];
                        $list_c['truepay'] = 0;
                        $list_c['group_id'] = $line->group_id;
                        $list_c['create_user_id'] = $user->id;
                        $list_c['create_user_name'] = $user->name;
                        $list_c['carriage_id'] = $value['id'];
                        $list_c['pay_type'] = 2;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                        $info_c[] = $list_c;
                    }

                    $res_p = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'deal_company', 'contant', 'tel', 'carriage_price', 'type','startstr','endstr','create_time','update_time'], $info)->execute();
                    $carriage_p = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','pay_type','create_time','update_time'], $info_c)->execute();
                    break;
                case '3':
                    foreach($arr_p as $key =>$value){
                        $list['order_id'] = $id;
                        $list['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                        $list['group_id'] = $line->group_id;
                        $list['create_user_id'] = $user->id;
                        $list['create_user_name'] = $user->name;
                        $list['contant'] = $value['contant'];
                        $list['tel'] = $value['tel'];
                        $list['carnumber'] = $value['carnumber'];
                        $list['carriage_price'] = $value['price'];
                        $list['type'] = $type_p;
                        $list['startstr'] = $order->begin_info;
                        $list['endstr'] = $line->begin_store;
                        $list['create_time'] = $list['update_time'] = date('Y-m-d H:i:s',time());
                        $info[] = $list;
                        $list_c['order_id'] = $id;
                        $list_c['pay_price'] = $value['price'];
                        $list_c['truepay'] = 0;
                        $list_c['group_id'] = $line->group_id;
                        $list_c['create_user_id'] = $user->id;
                        $list_c['create_user_name'] = $user->name;
                        $list_c['driver_name'] = $value['contant'];
                        $list_c['driver_car'] = $value['carnumber'];
                        $list_c['driver_tel'] = $value['tel'];
                        $list_c['pay_type'] = 3;
                        $list_c['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                        $info_c[] = $list_c;
                    }
                    $res_p = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel', 'carnumber', 'carriage_price', 'type','startstr','endstr','create_time','update_time'], $info)->execute();
                    $carriage_p = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'driver_name', 'driver_car','driver_tel','pay_type','create_time','update_time'], $info_c)->execute();
                    break;
                default:
                    break;
            }
        }
        if ($sendtype == 1){
            switch ($type_s){
                case '1':
                    foreach($arr_s as $key =>$value){
                        $list_send['order_id'] = $id;
                        $list_send['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                        $list_send['group_id'] = $line->group_id;
                        $list_send['create_user_id'] = $user->id;
                        $list_send['create_user_name'] = $user->name;
                        $list_send['contant'] = $value['contant'];
                        $list_send['tel'] = $value['tel'];
                        $list_send['carriage_price'] = $value['price'];
                        $list_send['carnumber'] = $value['carnumber'];
                        $list_send['type'] = $type_p;
                        $list_send['startstr'] = $line->end_store;
                        $list_send['endstr'] = $order->end_info;
                        $list_send['create_time'] = $list_send['update_time'] = date('Y-m-d H:i:s',time());
                        $info_send[] = $list_send;

                        $list_cend['order_id'] = $id;
                        $list_cend['pay_price'] = $value['price'];
                        $list_cend['truepay'] = 0;
                        $list_cend['group_id'] = $line->group_id;
                        $list_cend['create_user_id'] = $user->id;
                        $list_cend['create_user_name'] = $user->name;
                        $list_cend['carriage_id'] = $value['id'];
                        $list_cend['driver_name'] = $value['contant'];
                        $list_cend['driver_car'] = $value['carnumber'];
                        $list_cend['driver_tel'] = $value['tel'];
                        $list_cend['pay_type'] = 1;
                        $list_cend['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                        $info_cend[] = $list_cend;
                    }
                    $res_s = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id','carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel', 'carriage_price','carnumber', 'type','startstr','endstr','create_time','update_time'], $info_send)->execute();
                    $carriage_s = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id','pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','driver_name','driver_car','driver_tel','pay_type','create_time','update_time'], $info_cend)->execute();
                    break;
                case '2':
                    foreach($arr_s as $key =>$value){
                        $list_send['order_id'] = $id;
                        $list_send['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                        $list_send['group_id'] = $line->group_id;
                        $list_send['create_user_id']= $user->id;
                        $list_send['create_user_name'] = $user->name;
                        $list_send['deal_company'] = $value['id'];
                        $list_send['contant'] = $value['contant'];
                        $list_send['tel'] = $value['tel'];
                        $list_send['carriage_price'] = $value['price'];
                        $list_send['type'] = $type_p;
                        $list_send['startstr'] = $line->end_store;
                        $list_send['endstr'] = $order->end_info;
                        $list_send['create_time'] = $list_send['update_time'] = date('Y-m-d H:i:s',time());
                        $info_send[] = $list_send;
                        $list_cend['order_id'] = $id;
                        $list_cend['pay_price'] = $value['price'];
                        $list_cend['truepay'] = 0;
                        $list_cend['group_id'] = $line->group_id;
                        $list_cend['create_user_id'] = $user->id;
                        $list_cend['create_user_name'] = $user->name;
                        $list_cend['carriage_id'] = $value['id'];
                        $list_cend['pay_type'] = 2;
                        $list_cend['create_time'] = $list_c['update_time'] = date('Y-m-d H:i:s',time());
                        $info_cend[] = $list_cend;
                    }
                    $res_s = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'deal_company', 'contant', 'tel', 'carriage_price', 'type','startstr','endstr','create_time','update_time'], $info_send)->execute();
                    $carriage_s = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','pay_type','create_time','update_time'], $info_cend)->execute();
                    break;
                case '3':
                    foreach($arr_s as $key =>$value){
                        $list_send['order_id'] = $id;
                        $list_send['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                        $list_send['group_id'] = $line->group_id;
                        $list_send['create_user_id'] = $user->id;
                        $list_send['create_user_name'] = $user->name;
                        $list_send['contant'] = $value['contant'];
                        $list_send['tel'] = $value['tel'];
                        $list_send['carnumber'] = $value['carnumber'];
                        $list_send['carriage_price'] = $value['price'];
                        $list_send['type'] = $type_p;
                        $list_send['startstr'] = $line->end_store;
                        $list_send['endstr'] = $order->end_info;
                        $list_send['create_time'] = $list_send['update_time'] = date('Y-m-d H:i:s',time());
                        $info_send[] = $list_send;
                        $list_cend['order_id'] = $id;
                        $list_cend['pay_price'] = $value['price'];
                        $list_cend['truepay'] = 0;
                        $list_cend['group_id'] = $line->group_id;
                        $list_cend['create_user_id'] = $user->id;
                        $list_cend['create_user_name'] = $user->name;
                        $list_cend['driver_name'] = $value['contant'];
                        $list_cend['driver_car'] = $value['carnumber'];
                        $list_cend['driver_tel'] = $value['tel'];
                        $list_cend['pay_type'] = 3;
                        $list_cend['create_time'] = $list_cend['update_time'] = date('Y-m-d H:i:s',time());
                        $info_cend[] = $list_cend;
                    }
                    $res_s = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel', 'carnumber', 'carriage_price', 'type','startstr','endstr','create_time','update_time'], $info_send)->execute();
                    $carriage_s = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'driver_name', 'driver_car','driver_tel','pay_type','create_time','update_time'], $info_cend)->execute();
                    break;
                default:
                    break;
            }
        }
        switch ($type_l){
            case '1':
                 foreach ($arr_l as $key =>$value){
                     $list_l['order_id'] = $id;
                     $list_l['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                     $list_l['group_id'] = $line->group_id;
                     $list_l['create_user_id'] = $user->id;
                     $list_l['create_user_name'] = $user->name;
                     $list_l['contant'] = $value['contant'];
                     $list_l['tel'] = $value['tel'];
                     $list_l['carriage_price'] = $value['price'];
                     $list_l['carnumber'] = $value['carnumber'];
                     $list_l['type'] = $type_l;
                     $list_l['startstr'] = $line->begin_store;
                     $list_l['endstr'] = $line->end_store;
                     $list_l['create_time'] = $list_l['update_time'] = date('Y-m-d H:i:s',time());
                     $info_l[] = $list_l;

                     $list_b['order_id'] = $id;
                     $list_b['pay_price'] = $value['price'];
                     $list_b['truepay'] = 0;
                     $list_b['group_id'] = $line->group_id;
                     $list_b['create_user_id'] = $user->id;
                     $list_b['create_user_name'] = $user->name;
                     $list_b['carriage_id'] = $value['id'];
                     $list_b['driver_name'] = $value['contant'];
                     $list_b['driver_car'] = $value['carnumber'];
                     $list_b['driver_tel'] = $value['tel'];
                     $list_b['pay_type'] = 1;
                     $list_b['create_time'] = $list_b['update_time'] = date('Y-m-d H:i:s',time());
                     $info_p[] = $list_b;
                 }
                $res_l = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id','carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel','carriage_price', 'carnumber', 'type','startstr','endstr','create_time','update_time'], $info_l)->execute();
                $carriage_l = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id','pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','driver_name','driver_car','driver_tel','pay_type','create_time','update_time'], $info_p)->execute();

                break;
            case '2':
                foreach($arr_l as $key =>$value){
                    $list_l['order_id'] = $id;
                    $list_l['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list_l['group_id'] = $line->group_id;
                    $list_l['create_user_id']= $user->id;
                    $list_l['create_user_name'] = $user->name;
                    $list_l['deal_company'] = $value['id'];
                    $list_l['contant'] = $value['contant'];
                    $list_l['tel'] = $value['tel'];
                    $list_l['carriage_price'] = $value['price'];
                    $list_l['type'] = $type_l;
                    $list_l['startstr'] = $line->begin_store;
                    $list_l['endstr'] = $line->end_store;
                    $list_l['create_time'] = $list_l['update_time'] = date('Y-m-d H:i:s',time());
                    $info_l[] = $list_l;
                    $list_b['order_id'] = $id;
                    $list_b['pay_price'] = $value['price'];
                    $list_b['truepay'] = 0;
                    $list_b['group_id'] = $line->group_id;
                    $list_b['create_user_id'] = $user->id;
                    $list_b['create_user_name'] = $user->name;
                    $list_b['carriage_id'] = $value['id'];
                    $list_b['pay_type'] = 2;
                    $list_b['create_time'] = $list_b['update_time'] = date('Y-m-d H:i:s',time());
                    $info_p[] = $list_b;
                }
                $res_l = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'deal_company', 'contant', 'tel', 'carriage_price', 'type','startstr','endstr','create_time','update_time'], $info_l)->execute();
                $carriage_l = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','pay_type','create_time','update_time'], $info_p)->execute();
                break;
            case '3':
                foreach($arr_l as $key =>$value){
                    $list_l['order_id'] = $id;
                    $list_l['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list_l['group_id'] = $line->group_id;
                    $list_l['create_user_id'] = $user->id;
                    $list_l['create_user_name'] = $user->name;
                    $list_l['contant'] = $value['contant'];
                    $list_l['tel'] = $value['tel'];
                    $list_l['carnumber'] = $value['carnumber'];
                    $list_l['carriage_price'] = $value['price'];
                    $list_l['type'] = $type_l;
                    $list_l['startstr'] = $line->begin_store;
                    $list_l['endstr'] = $line->end_store;
                    $list_l['create_time'] = $list_l['update_time'] = date('Y-m-d H:i:s',time());
                    $info_l[] = $list_l;
                    $list_b['order_id'] = $id;
                    $list_b['pay_price'] = $value['price'];
                    $list_b['truepay'] = 0;
                    $list_b['group_id'] = $line->group_id;
                    $list_b['create_user_id'] = $user->id;
                    $list_b['create_user_name'] = $user->name;
                    $list_b['driver_name'] = $value['contant'];
                    $list_b['driver_car'] = $value['carnumber'];
                    $list_b['driver_tel'] = $value['tel'];
                    $list_b['pay_type'] = 3;
                    $list_b['create_time'] = $list_b['update_time'] = date('Y-m-d H:i:s',time());
                    $info_p[] = $list_b;
                }
                $res_l = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel', 'carnumber', 'carriage_price', 'type','startstr','endstr','create_time','update_time'], $info_l)->execute();
                $carriage_l = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'driver_name', 'driver_car','driver_tel','pay_type','create_time','update_time'], $info_p)->execute();
                break;
            default:
                break;
        }
            $order->orderstate = 3;
            $res_o = $order->save();
            if ($res_o && $res_s && $res_p && $carriage_p && $carriage_l &&$res_l && $carriage_s){
                $transaction->commit();
                $this->hanldlog($user->id,'零担订单'.$order->ordernumber);
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                return $this->resultInfo($data);
            }else{
                $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                return $this->resultInfo($data);
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 内部订单
     * */
    public function actionLine_list(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
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
        if (empty($token) || !$group_id) {
            $data['msg'] = '参数错误';
            return json_encode($data);
        }
        $check_result = $this->check_token_list($token);//验证令牌
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
        $list->andWhere(['a.line_type'=>1,'a.group_id' => $group_id]);
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
        }

        $data = [
            'code' => 200,
            'msg' => '正在请求中...',
            'status' => 200,
            'count' => $count,
            'auth' => $check_result['auth'],
            '$check_result' => $check_result,
            'data' => precaution_xss($list)
        ];
        return json_encode($data);
    }

    /*
     * 调度详情
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
        $model = AppBulk::find()
            ->alias('m')
            ->select('m.*,n.carriage_id')
            ->leftJoin('app_line n','m.shiftid = n.id')
            ->where(['m.id'=>$id])
            ->asArray()
            ->one();

        if ($model['line_type'] == 1){
            if ($model['carriage_id']){

                $model =AppBulk::find()
                    ->alias('v')
                    ->select(['v.*','c.all_name','l.start_time','l.carriage_id','a.name As carriage'])
                    ->leftJoin('app_customer c','v.customer_id=c.id')
                    ->leftJoin('app_line l','v.shiftid=l.id')
                    ->leftJoin('app_carriage a','l.carriage_id = a.cid')
                    ->where(['v.id'=>$id])
                    ->asArray()
                    ->one();
            }else{

                $model =AppBulk::find()
                    ->alias('v')
                    ->select(['v.*','c.all_name','l.start_time','l.group_id','a.group_name As carriage'])
                    ->leftJoin('app_customer c','v.customer_id=c.id')
                    ->leftJoin('app_line l','v.shiftid=l.id')
                    ->leftJoin('app_group a','l.group_id = a.id')
                    ->where(['v.id'=>$id])
                    ->asArray()
                    ->one();
            }

        }else{
            if ($model['carriage_id']){
                $model = AppBulk::find()
                    ->alias('v')
                    ->select(['v.*','c.group_name all_name','a.name As carriage','l.carriage_id','l.start_time'])
                    ->leftJoin('app_group c','v.group_id=c.id')
                    ->leftJoin('app_line l','v.shiftid=l.id')
                    ->leftJoin('app_carriage a','l.carriage_id = a.cid')
                    ->where(['v.id'=>$id])
                    ->asArray()
                    ->one();
            }else{
                $model = AppBulk::find()
                    ->alias('v')
                    ->select(['v.*','c.group_name all_name','a.group_name As carriage','l.start_time','l.group_id'])
                    ->leftJoin('app_group c','v.group_id=c.id')
                    ->leftJoin('app_line l','v.shiftid=l.id')
                    ->leftJoin('app_group a','l.group_id = a.id')
                    ->where(['v.id'=>$id])
                    ->asArray()
                    ->one();
            }

        }
        $model['begin_info'] = json_decode($model['begin_info'],true);
        $model['end_info'] = json_decode($model['end_info'],true);
        $model['receipt'] = json_decode($model['receipt'],true);
        $list = AppList::find()->where(['order_id'=>$id])->asArray()->all();
        foreach ($list as $k => $v) {
            if ($v['type'] == 2) {
                $one = Carriage::find()->where(['cid'=>$v['deal_company']])->one();
                if ($one) {
                    $list[$k]['carnumber'] = $one->name;
                }
            }
        }
        $data = $this->encrypt(['code'=>200,'msg'=>'','data'=>$model,'list'=>$list]);
        return $this->resultInfo($data);
    }

    /*
     * 确认接单（客户下单）
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
        $order = AppBulk::findOne($id);
        $this->check_group_auth($order->group_id,$user);
        if ($order->orderstate != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'请勿重复操作']);
            return $this->resultInfo($data);
        }
        $order->orderstate = 2;
        $res = $order->save();
        if ($res){
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功，请尽快安排车辆']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'网络出错']);
            return $this->resultInfo($data);
        }
    }



    /*
     * 调度
     * */
    public function actionDispatch_order(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $carriage_info = json_decode($input['arr'],true);//调度信息
        $type = $input['type'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);

        $line = AppLine::findOne($id);
        $this->check_group_auth($line->group_id,$check_result['user']);
        $user = $check_result['user'];
        if ($line->dispatch_state == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'该班次已调度']);
            return $this->resultInfo($data);
        }
        if ($line->state != 1){
            $data = $this->encrypt(['code'=>400,'msg'=>'该班次已发车']);
            return $this->resultInfo($data);
        }
        $order = AppBulk::find()->where(['shiftid'=>$id])->andWhere(['!=','orderstate',6])->asArray()->all();
        if (!$order){
            $data = $this->encrypt(['code'=>400,'msg'=>'线路订单不存在！']);
            return $this->resultInfo($data);
        }
        $res_l = $carriage_l = true;
        switch ($type){
            case '1':
                foreach ($carriage_info as $key =>$value){
                    $list_l['order_id'] = $id;
                    $list_l['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list_l['group_id'] = $line->group_id;
                    $list_l['create_user_id'] = $user->id;
                    $list_l['create_user_name'] = $user->name;
                    $list_l['contant'] = $value['contant'];
                    $list_l['tel'] = $value['tel'];
                    $list_l['carriage_price'] = $value['price'];
                    $list_l['carnumber'] = $value['carnumber'];
                    $list_l['type'] = $type;
                    $list_l['startstr'] = $line->begin_store;
                    $list_l['endstr'] = $line->end_store;
                    $list_l['create_time'] = $list_l['update_time'] = date('Y-m-d H:i:s',time());
                    $info_l[] = $list_l;

                    $list_b['order_id'] = $id;
                    $list_b['pay_price'] = $value['price'];
                    $list_b['truepay'] = 0;
                    $list_b['group_id'] = $line->group_id;
                    $list_b['create_user_id'] = $user->id;
                    $list_b['create_user_name'] = $user->name;
                    $list_b['carriage_id'] = $value['id'];
                    $list_b['driver_name'] = $value['contant'];
                    $list_b['driver_car'] = $value['carnumber'];
                    $list_b['driver_tel'] = $value['tel'];
                    $list_b['pay_type'] = 1;
                    $list_b['create_time'] = $list_b['update_time'] = date('Y-m-d H:i:s',time());
                    $list_b['type'] = 2;
                    $info_p[] = $list_b;
                }
                $res_l = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id','carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel','carriage_price', 'carnumber', 'type','startstr','endstr','create_time','update_time'], $info_l)->execute();
                $carriage_l = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id','pay_price', 'truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','driver_name','driver_car','driver_tel','pay_type','create_time','update_time','type'], $info_p)->execute();

                break;
            case '2':
                foreach($carriage_info as $key =>$value){
                    $list_l['order_id'] = $id;
                    $list_l['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list_l['group_id'] = $line->group_id;
                    $list_l['create_user_id']= $user->id;
                    $list_l['create_user_name'] = $user->name;
                    $list_l['deal_company'] = $value['id'];
                    $list_l['contant'] = $value['contant'];
                    $list_l['tel'] = $value['tel'];
                    $list_l['carriage_price'] = $value['price'];
                    $list_l['type'] = $type;
                    $list_l['startstr'] = $line->begin_store;
                    $list_l['endstr'] = $line->end_store;
                    $list_l['create_time'] = $list_l['update_time'] = date('Y-m-d H:i:s',time());
                    $info_l[] = $list_l;
                    $list_b['order_id'] = $id;
                    $list_b['pay_price'] = $value['price'];
                    $list_b['truepay'] = 0;
                    $list_b['group_id'] = $line->group_id;
                    $list_b['create_user_id'] = $user->id;
                    $list_b['create_user_name'] = $user->name;
                    $list_b['carriage_id'] = $value['id'];
                    $list_b['pay_type'] = 2;
                    $list_b['create_time'] = $list_b['update_time'] = date('Y-m-d H:i:s',time());
                    $list_b['type'] = 2;
                    $info_p[] = $list_b;
                }
                $res_l = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'deal_company', 'contant', 'tel', 'carriage_price', 'type','startstr','endstr','create_time','update_time'], $info_l)->execute();
                $carriage_l = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'carriage_id','pay_type','create_time','update_time','type'], $info_p)->execute();
                break;
            case '3':
                foreach($carriage_info as $key =>$value){
                    $list_l['order_id'] = $id;
                    $list_l['carriage_number'] = 'C'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                    $list_l['group_id'] = $line->group_id;
                    $list_l['create_user_id'] = $user->id;
                    $list_l['create_user_name'] = $user->name;
                    $list_l['contant'] = $value['contant'];
                    $list_l['tel'] = $value['tel'];
                    $list_l['carnumber'] = $value['carnumber'];
                    $list_l['carriage_price'] = $value['price'];
                    $list_l['type'] = $type;
                    $list_l['startstr'] = $line->begin_store;
                    $list_l['endstr'] = $line->end_store;
                    $list_l['create_time'] = $list_l['update_time'] = date('Y-m-d H:i:s',time());
                    $info_l[] = $list_l;
                    $list_b['order_id'] = $id;
                    $list_b['pay_price'] = $value['price'];
                    $list_b['truepay'] = 0;
                    $list_b['group_id'] = $line->group_id;
                    $list_b['create_user_id'] = $user->id;
                    $list_b['create_user_name'] = $user->name;
                    $list_b['driver_name'] = $value['contant'];
                    $list_b['driver_car'] = $value['carnumber'];
                    $list_b['driver_tel'] = $value['tel'];
                    $list_b['pay_type'] = 3;
                    $list_b['create_time'] = $list_b['update_time'] = date('Y-m-d H:i:s',time());
                    $list_b['type'] = 2;
                    $info_p[] = $list_b;
                }
                $res_l = Yii::$app->db->createCommand()->batchInsert(AppList::tableName(), ['order_id', 'carriage_number', 'group_id', 'create_user_id', 'create_user_name', 'contant', 'tel', 'carnumber', 'carriage_price', 'type','startstr','endstr','create_time','update_time'], $info_l)->execute();
                $carriage_l = Yii::$app->db->createCommand()->batchInsert(AppPayment::tableName(), ['order_id', 'pay_price','truepay', 'group_id', 'create_user_id', 'create_user_name', 'driver_name', 'driver_car','driver_tel','pay_type','create_time','update_time','type'], $info_p)->execute();
                break;
            default:
                break;
        }

        $lists = AppBulk::updateAll(['orderstate'=>3],['and',['shiftid'=>$id],['<>','orderstate',6]]);
        $line->dispatch_state = 2;
        $transaction= AppBulk::getDb()->beginTransaction();
        try {
            $arr = $line->save();
            if ($lists && $res_l && $carriage_l && $arr){
                $transaction->commit();
                $data = $this->encrypt(['code'=>200,'msg'=>'调度成功']);
                return $this->resultInfo($data);
            }else{
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'调度失败']);
                return $this->resultInfo($data);
            }
        }catch(\Exception $e ){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'调度失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 干线调度完成(不使用)
     * */
    public function actionLine_doneo(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user =$check_result['user'];
        $line = AppLine::findOne($id);
        $this->check_group_auth($line->group_id,$user);
        if ($line->state != 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'请确认线路已经发车']);
            return $this->resultInfo($data);
        }
        $order = AppBulk::find()->where(['shiftid'=>$id])->andWhere(['<>','orderstate',6])->select('id')->asArray()->all();
        foreach ($order as $key =>$value){
            $bulk = AppBulk::findOne($value['id']);
            $ids[$key] = $value['id'];
        }
        $line->state = 3;
        $transaction= AppBulk::getDb()->beginTransaction();
        try {
            $arr = $line->save();
            $lists = AppBulk::updateAll(['orderstate'=>4],['and',['in','shiftid',$ids],['<>','orderstate',6]]);
            if ($lists>=1 && $arr){
                $transaction->commit();
                $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
                return $this->resultInfo($data);
            }else{
                $transaction->rollBack();
                $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
                return $this->resultInfo($data);
            }
        }catch(\Exception $e ){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }
    public function actionLine_done(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user =$check_result['user'];
        $line = AppLine::findOne($id);
        $this->check_group_auth($line->group_id,$user);
        if ($line->state != 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'请确认线路已经发车']);
            return $this->resultInfo($data);
        }
        $line->state = 3;
        $arr = $line->save();
        if ($arr){
            $this->hanldlog($user->id,'线路已完成'.$line->startcity.'->'.$line->endcity);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }

    }
    /*
     * 承运商取消
     * */
    public function actionCancel_order(){
        $startcity = 34.774578.','. 113.666868;
        $endcity = 31.231339.','. 121.302854;
//        $ak ="SdRptW2rs3xsjHhVhQOy17QzP6Gexbp6";
//        $url ="http://api.map.baidu.com/logistics_direction/v1/truck?origin=".$startcity."&destination=".$endcity."&ak=".$ak;
//        $renderOption = file_get_contents($url);
//        preg_match("/.*\((.*)\)/",$renderOption,$result);
//        $res = json_decode($result[1],true);


//        $key = '063ec2a27a0417d6d76236da149bfbef';
        $key = 'a7275a257c876fe63140da89e9c13308';
//        $url = 'https://restapi.amap.com/v4/direction/truck?origin='.$startcity."destination=".$endcity."size=2"."key=".$key;
        $url =  'https://restapi.amap.com/v4/direction/truck?width=2.5&strategy=5&size=2&weight=10&axis=2&origin=116.481008,39.989625&destination=116.414217,40.061741&height=1.6&load=0.9&key='.$key;
        $renderOption = file_get_contents($url);
//        preg_match("/.*\((.*)\)/",$renderOption,$result);
          $res = json_decode($renderOption,true);
          if ($res){
              $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$res]);
              return $this->resultInfo($data);
          }else{
              $data = $this->encrypt(['code'=>400,'msg'=>'暂无数据']);
              return $this->resultInfo($data);
          }
    }
}
