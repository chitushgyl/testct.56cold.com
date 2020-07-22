<?php

namespace app\modules\api\controllers;

use app\models\AppCartype;
use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use app\models\AppLine;
use app\models\AppGroup;
use app\models\AppBulk;
use app\models\AppList;
use app\models\AppOrder;
use app\models\AppPayment;
use app\models\AppReceive;
use app\models\AppSendorder;
use app\models\AppSetParam;
use app\models\Carriage;
use Yii;

/**
 * Default controller for the `api` module
 */
class LineController extends CommonController
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
        $line_city = $input['line_city'] ?? '';

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
        $list = AppLine::find();
        if ($line_city) {
            $list->orWhere(['like','startcity',$line_city])
                ->orWhere(['like','endcity',$line_city])
                ->orWhere(['like','transfer',$line_city]);
        }
        $list->andWhere(['group_id'=>$group_id,'delete_flag'=>'Y']);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['update_time'=>SORT_DESC])
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['set_price'] = json_decode($v['weight_price'],true);
            $list[$k]['startstr'] = json_decode($v['begin_store'],true);
            $list[$k]['endstr'] = json_decode($v['end_store'],true);
            $id = $v['id'];
            $list[$k]['count'] = AppBulk::find()->where(['paystate'=>2,'line_type'=>2])->orWhere(['in','line_type',[1,3]])->andWhere(['shiftid'=>$id])->count();
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
     *创建线路
     * */
    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $shiftnumber = $input['shiftnumber'] ?? '';//班次号
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startarea = $input['startarea'] ?? '';
        $endarea = $input['endarea'] ?? '';
        $begin_store = $input['begin_store'];//起始仓地址
        $end_store = $input['end_store'];//目的仓地址
        $picktype = $input['picktype'];
        $sendtype = $input['sendtype'];
        $trunking = $input['trunking'];//时效
        $all_weight = $input['all_weight'] ?? '';
        $all_volume = $input['all_volume'] ?? '';
        $freeweight = $input['freeweight'] ?? '';
        $weight_price = $input['weight_price'];//区间价格
        $group_id = $input['group_id'];
        $line_price = $input['line_price'] ?? '';//干线最低收费
        $transfer_info = $input['transfer_info'] ?? '';
        $transfer = $input['transfer'] ?? '';
        $start_time = $input['start_time'];//发车时间
        $arrive_time = $input['arrive_time'];
        $carriage_id = $input['carriage_id'] ?? '';
        if (empty($token) && !$group_id){
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
        if (empty($start_time)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选发车时间']);
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

        if (empty($weight_price)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写重量区间价格']);
            return $this->resultInfo($data);
        }
        if (empty($arrive_time)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写预计到达时间']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $this->check_group_auth($group_id,$user);
        $arr_startstr = json_decode($begin_store,true);
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
        if($transfer_info){
            $transfer_info = json_decode($transfer_info,true);
            foreach ($transfer_info as $k => $v){
                $all = $v['pro'].$v['city'].$v['area'].$v['info'];
                if ($all) {
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
            }
        }
        $arr_endstr = json_decode($end_store,true);
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
        $group =
        $model = new AppLine();
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
        $model->shiftnumber = $shiftnumber;
        $model->startcity = $startcity;
        $model->endcity = $endcity;
        $model->weight_price =$weight_price;
        $model->line_price = $line_price;
        $model->picktype = $picktype;
        $model->start_time = $start_time;
        $model->arrive_time = $arrive_time;
        $model->sendtype = $sendtype;
        $model->create_user_id = $user->id;
        $model->freeweight = $freeweight;
        $model->group_id = $group_id;
        $model->all_weight = $all_weight;
        $model->all_volume = $all_volume;
        $model->trunking = $trunking;
        $model->transfer = $transfer;
        $model->transfer_info = $input['transfer_info'] ?? '';
        $model->carriage_id = $carriage_id;
        //获取最低单价
        $price = json_decode($weight_price,true);
        foreach($price as $key =>$value){
            $price_a[] = $value['price'];
        }

        $model->price = min($price_a);
        $model->eprice = min($price_a)*1000/2.5;

        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'添加线路:'.$model->id.$model->startcity.'->'.$model->endcity);
            $data = $this->encrypt(['code'=>'200','msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'添加失败']);
            return $this->resultInfo($data);
        }

    }

    /*
     * 查看线路
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
        $groups = AppGroup::group_list($user);
        if ($id) {
            $model = AppLine::find()
                ->where(['id'=>$id])
                ->asArray()
                ->one();
        } else {
            $model = new AppLine();
        }
        $carriage = Carriage::find()->select('cid,name')->where(['group_id'=>$user->group_id,'delete_flag'=>'Y','use_flag'=>'Y'])->asArray()->all();
        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$model,'groups'=>$groups,'carriage'=>$carriage]);
        return $this->resultInfo($data);

    }

    /*
     * 修改线路
     * */
    public function actionEdit(){
        $input = Yii::$app->request->post();
        $id = $input['id'];
        $token = $input['token'];
        $shiftnumber = $input['shiftnumber'] ?? '';//班次号
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $startarea = $input['startarea'] ?? '';
        $endarea = $input['endarea'] ?? '';
        $begin_store = $input['begin_store'];//起始仓地址
        $end_store = $input['end_store'];//目的仓地址
        $picktype = $input['picktype'];
        $sendtype = $input['sendtype'];
        $trunking = $input['trunking'];//时效
        $all_weight = $input['all_weight'] ?? '';
        $all_volume = $input['all_volume'] ?? '';
        $freeweight = $input['freeweight'] ?? '';
        $weight_price = $input['weight_price'];//区间价格
        $group_id = $input['group_id'];
        $line_price = $input['line_price'] ?? '';//干线最低收费
        $transfer_info = $input['transfer_info'] ?? '';
        $transfer = $input['transfer'] ?? '';
        $start_time = $input['start_time'];//发车时间
        $arrive_time = $input['arrive_time'];
        $carriage_id = $input['carriage_id'];
        if (empty($token) || !$group_id || !$id){
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
        if (empty($start_time)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填选发车时间']);
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

        if (empty($weight_price)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写重量区间价格']);
            return $this->resultInfo($data);
        }
        if (empty($arrive_time)){
            $data = $this->encrypt(['code'=>'400','msg'=>'请填写预计到达时间']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $model = AppLine::findOne($id);
        $this->check_group_auth($model->group_id,$user);
        $arr_startstr = json_decode($begin_store,true);
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
       if ($transfer_info){
           $transfer_info = json_decode($transfer_info,true);
           foreach ($transfer_info as $k => $v){
               $all = $v['pro'].$v['city'].$v['area'].$v['info'];
               if ($all) {
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
       }
        $arr_endstr = json_decode($end_store,true);
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
        $model->shiftnumber = $shiftnumber;
        $model->startcity = $startcity;
        $model->endcity = $endcity;
        $model->weight_price =$weight_price;
        $model->line_price = $line_price;
        $model->picktype = $picktype;
        $model->start_time = $start_time;
        $model->arrive_time = $arrive_time;
        $model->sendtype = $sendtype;
        $model->create_user_id = $user->id;
        $model->freeweight = $freeweight;
        $model->group_id = $group_id;
        $model->all_weight = $all_weight;
        $model->all_volume = $all_volume;
        $model->trunking = $trunking;
        $model->transfer = $transfer;
        $model->transfer_info = $input['transfer_info'] ?? '';
        $model->carriage_id = $carriage_id;
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'编辑线路:'.$model->id.$model->startcity.'->'.$model->endcity);
            $data = $this->encrypt(['code'=>'200','msg'=>'编辑成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'编辑失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 删除线路
     * */
    public function actionDelete(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = AppLine::find()->where(['id'=>$id])->one();
        $line = AppBulk::find()->where(['shiftid'=>$id])->asArray()->all();
        if (count($line) > 0){
            $data = $this->encrypt(['code'=>'400','msg'=>'该线路下有订单不能删除！']);
            return $this->resultInfo($data);
        }
        $this->check_group_auth($model->group_id,$user);
        $model->delete_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'删除线路:'.$model->id.$model->startcity.'->'.$model->endcity);
            $data = $this->encrypt(['code'=>200,'msg'=>'删除成功']);
            return $this->resultInfo($data);
        }

        $data = $this->encrypt(['code'=>400,'msg'=>'删除失败']);
        return $this->resultInfo($data);
    }

    /*
     * 上线
     * */
    public function actionOnline(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = AppLine::find()->where(['id'=>$id])->one();
        if($model->state == 5){
            $data = $this->encrypt(['code'=>400,'msg'=>'线路已超时，不可以上线']);
            return $this->resultInfo($data);
        }
        if($model->state == 4){
            $data = $this->encrypt(['code'=>400,'msg'=>'线路已取消，不可以上线']);
            return $this->resultInfo($data);
        }
        if($model->state == 3){
            $data = $this->encrypt(['code'=>400,'msg'=>'线路已完成，不可以上线']);
            return $this->resultInfo($data);
        }
        if($model->state == 2){
            $data = $this->encrypt(['code'=>400,'msg'=>'线路已发车，不可以上线']);
            return $this->resultInfo($data);
        }
        $this->check_group_auth($model->group_id,$user);
        $model->line_state = 2;
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'上线线路：'.$model->id);
            $data = $this->encrypt(['code'=>200,'msg'=>'上线成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'上线失败']);
            return $this->resultInfo($data);
        }
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
        $model = AppLine::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->line_state = 1;
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,$user->name.'下线线路：'.$model->id);
            $data = $this->encrypt(['code'=>200,'msg'=>'下线成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'下线失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 上线列表
     * */
    public function actionOnline_index(){
        $request = Yii::$app->request;
        $input = $request->post();
        $token = $input['token'];
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $line_city = $input['line_city'] ?? '';
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
        $list = AppLine::find();
        if ($line_city) {
            $list->orWhere(['like','startcity',$line_city])
                ->orWhere(['like','endcity',$line_city])
                ->orWhere(['like','transfer',$line_city]);
        }
        $list->andWhere(['group_id'=>$group_id,'delete_flag'=>'Y']);
        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['update_time'=>SORT_DESC])
            ->asArray()
            ->all();

        foreach ($list as $k => $v) {
            $list[$k]['set_price'] = json_decode($v['weight_price'],true);   
            $id = $v['id'];
            $list[$k]['count'] = AppBulk::find()->where(['paystate'=>2,'line_type'=>2])->orWhere(['line_type'=>1])->andWhere(['shiftid'=>$id])->count();
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
     * 在线线路
     * */
    public function actionOnline_line(){
        $request = Yii::$app->request;
        $input = $request->post();
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
            ->orderBy(['update_time'=>SORT_DESC])
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
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }

    /*
     * 零担线路
     * */
    public function bulk_line($startcity,$endcity,$startarea,$endarea){
        $line = AppLine::find()
//            ->select('id,startcity,endcity,startarea,endarea,price,eprice,start_time,trunking')
            ->where(['startcity'=>$startcity,'endcity'=>$endcity,'delete_flag'=>'Y']);
        if ($startarea){
            $line->andWhere(['startarea'=>$startarea]);
        }
        if ($endarea){
            $line->andWhere(['endarea'=>$endarea]);
        }
        $line = $line->asArray()->all();
        if ($startarea && $endarea && $line == [[]]){
            $line = AppLine::find()
                ->where(['startcity'=>$startcity,'endcity'=>$endcity,'delete_flag'=>'Y'])
                ->asArray()
                ->all();
        }

        foreach ($line as $k => $v) {
            $line[$k]['set_price'] = json_decode($v['weight_price'],true);
            $begin_store = json_decode($v['begin_store'],true);
            $end_store = json_decode($v['end_store'],true);
            $transfer_info = json_decode($v['transfer_info'],true);

            $line[$k]['begin_store_pro'] = $begin_store[0]['pro']. ' '. $begin_store[0]['city'] . ' ' . $begin_store[0]['area'];
            $line[$k]['begin_store_info'] = $begin_store[0]['info'];

            $line[$k]['end_store_pro'] = $end_store[0]['pro']. ' '. $end_store[0]['city'] . ' ' . $end_store[0]['area'];
            $line[$k]['end_store_info'] = $end_store[0]['info'];

            if ($transfer_info[0]['pro']) {
                $line[$k]['transfer_pro'] = $transfer_info[0]['pro']. ' '. $transfer_info[0]['city'] . ' ' . $transfer_info[0]['area'];
                $line[$k]['transfer_info'] = $transfer_info[0]['info'];
            } else {
                $line[$k]['transfer_pro'] = '';
                $line[$k]['transfer_info'] = '';
            }
        }
        return $line;
    }


    /*
     * 支付前判断线路
     * */
    public function actionCheck_line(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $shiftid = $input['shiftid'];
        if (empty($token) || empty($shiftid)){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $line = AppLine::findOne($shiftid);
        if ($line->line_state == 1){
            $data = $this->encrypt(['code'=>'400','msg'=>'线路已下线']);
            return $this->resultInfo($data);
        }
        if ($line->state ==2){
            $data = $this->encrypt(['code'=>'400','msg'=>'线路已发车']);
            return $this->resultInfo($data);
        }
        if ($line->state ==3){
            $data = $this->encrypt(['code'=>'400','msg'=>'线路已完成']);
            return $this->resultInfo($data);
        }
        if ($line->state ==4){
            $data = $this->encrypt(['code'=>'400','msg'=>'线路已取消']);
            return $this->resultInfo($data);
        }
        if ($line->state ==5){
            $data = $this->encrypt(['code'=>'400','msg'=>'线路已过期']);
            return $this->resultInfo($data);
        }
        $data = $this->encrypt(['code'=>'200','msg'=>'快去支付吧']);
        return $this->resultInfo($data);
    }

    /*
     * 干线发车
     * */
    public function actionDispatch_car(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $shiftid = $input['shiftid'];
        if (empty($token) || empty($shiftid)){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);
        $user = $check_result['user'];
        $line = AppLine::findOne($shiftid);
        $this->check_group_auth($line->group_id,$user);
        if ($line->state != 1){
            $data = $this->encrypt(['code'=>'400','msg'=>'请确认线路状态']);
            return $this->resultInfo($data);
        }
        $line->state = 2;
        $res = $line->save();
        if ($res){
            $data = $this->encrypt(['code'=>'200','msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'操作失败']);
            return $this->resultInfo($data);
        }

    }
    /*
     * 转换线路(转为自有)
     * */
    public function actionCopy_line(){
          $input = Yii::$app->request->post();
          $token = $input['token'];
          $id = $input['id'];
          $price = $input['price'];
          $lowprice = $input['lowprice'];
          if (empty($token) || empty($id)){
              $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
              return $this->resultInfo($data);
          }
          $check_result = $this->check_token($token,false);
          $user = $check_result['user'];
          $line = AppLine::find()->where(['id'=>$id])->asArray()->one();
          unset($line['id']);
          $line['weight_price'] = $price;
          $line['price'] = $lowprice;
          $line_log = new AppLine();
          $line_log->attributes = $line;
          $res =  $line_log->save();
          if($res){
              $data = $this->encrypt(['code'=>'200','msg'=>'操作成功']);
              return $this->resultInfo($data);
          }else{
              $data = $this->encrypt(['code'=>'400','msg'=>'操作失败']);
              return $this->resultInfo($data);
          }

    }

    /*
     * 干线接单转内部订单
     * */
    public function actionCopy_bulk(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>'400','msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
        $bulk = AppBulk::find()
            ->alias('a')
            ->select('a.*,b.start_time,b.trunking,b.arrive_time')
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
        $order->group_id = $user->group_id;
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
        $order->line_id = $bulk['id'];
        $order->order_type = 7;
        $order->start_store = $bulk['begin_store'];
        $order->end_store = $bulk['end_store'];
        $list = AppBulk::findOne($bulk['id']);
        $list->copy = 2;
        $arr = $list->save();
        $res =  $order->save();
        if ($res && $arr){
            $this->hanldlog($user->id,'生成内部零担订单'.$bulk->ordernumber);
            $data = $this->encrypt(['code'=>'200','msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'200','msg'=>'操作成功']);
            return $this->resultInfo($data);
        }
    }
}
