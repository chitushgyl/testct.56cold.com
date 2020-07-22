<?php

namespace app\modules\api\controllers;

use app\models\AppCommonAddress;
use app\models\AppCommonContacts;
use app\models\AppLine;
use app\models\AppLineLog;
use app\models\AppGroup;
use app\models\AppOrder;
use app\models\Carriage;
use Yii;


/**
 * Default controller for the `api` module
 */
class LineLogController extends CommonController
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
        $city = $input['city'] ?? '';
        $group_id = $input['group_id'];
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;

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
        $list = AppLineLog::find();

        if ($city) {
            $list->orWhere(['like','startcity',$city])
                ->orWhere(['like','endcity',$city])
                ->orWhere(['like','centercity',$city]);
        }

        $list->andWhere(['group_id'=>$group_id,'delete_flag'=>'Y']);
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
            'auth' => $check_result['auth'],
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }

    /*
     *创建线路模型
     * */
    public function actionAdd(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
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
        $carriage_id = $input['carriage_id'] ?? '';
        $time = $input['time'];
        if (empty($token) || !$group_id){
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

        $check_result = $this->check_token($token,false);
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
        if ($input['center_store']){
            $arr_centerstr = json_decode($input['center_store'],true);
            foreach ($arr_centerstr as $k => $v){
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
        $model->create_user_id = $user->id;
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
            $this->line_auto($model->id,'add');
            $this->hanldlog($user->id,'添加线路模型：'.$model->id.$startcity.'->'.$endcity);
            $data = $this->encrypt(['code'=>'200','msg'=>'添加成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>'400','msg'=>'添加失败']);
            return $this->resultInfo($data);
        }
    }
    /*
     * 自动生成线路
     * */
    private function line_auto($id,$type){
        if ($type == 'edit'){
//            $line_a = AppLine::find()->where(['line_id'=>$id,'delete_flag'=>'Y'])->all();
//            foreach ($line_a as $k =>$v){
//                $line_b = AppLine::findOne($v['id']);
//                $line_b->delete_flag = 'N';
//                $line_b ->save();
//            }
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
                    $line_e->line_state = 1;
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
        $token = $input['token'];
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
        $carriage_id = $input['carriage_id'] ?? '';
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
        $check_result = $this->check_token($token,false);
        $user = $check_result['user'];
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
        if ($input['center_store']){
            $arr_centerstr = json_decode($input['center_store'],true);
            foreach ($arr_centerstr as $k => $v){
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
        $model = AppLineLog::findOne($id);
        $this->check_group_auth($model->group_id,$user);
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
        $model->create_user_id = $user->id;
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
            $this->line_auto($model->id,'edit');
            $this->hanldlog($user->id,'编辑线路模型：'.$model->id.$startcity.'->'.$endcity);
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
            $model = AppLineLog::find()
                ->where(['id'=>$id])
                ->asArray()
                ->one();
        } else {
            $model = new AppLineLog();
        }
        $carriage = Carriage::find()->select('cid,name')->where(['group_id'=>$user->group_id,'delete_flag'=>'Y','use_flag'=>'Y'])->asArray()->all();

        $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$model,'groups'=>$groups,'carriage'=>$carriage]);
        return $this->resultInfo($data);

    }

    /*
     * 删除线路模型
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
        $model = AppLineLog::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->delete_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'删除线路模型:'.$model->id.$model->startcity.'->'.$model->endcity);
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
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = AppLineLog::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'N';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'禁用线路模型:'.$model->id.$model->startcity.'->'.$model->endcity);
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
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];
        $model = AppLineLog::find()->where(['id'=>$id])->one();
        $this->check_group_auth($model->group_id,$user);
        $model->use_flag = 'Y';
        $res = $model->save();
        if ($res){
            $this->hanldlog($user->id,'启用线路模型：'.$model->id.$model->startcity.'->'.$model->endcity);
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }else{
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 转为自有模型
     * */
    public function actionCopy_line(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        if (empty($token) || empty($id)){
            $data = $this->encrypt(['code'=>400,'msg'=>'参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token,true);//验证令牌
        $user = $check_result['user'];

        $model = AppLineLog::find()->where(['id'=>$id])->asArray()->one();
        $this->check_group_auth($model['group_id'],$user);
        unset($model['id']);
        $model['carriage_id'] = '';
        $line = new AppLineLog();
        $line->attributes = $model;
        $line->create_user_id = $user->id;
        $order_o = AppLineLog::findOne($id);
        $order_o->copy = 2;

        $transaction= AppLineLog::getDb()->beginTransaction();
        try{
            $res = $line->save();
            $res_o = $order_o->save();
            $transaction->commit();
            $this->line_auto($line->id,'add');
            $this->hanldlog($user->id,'线路模板'.$model['startcity'].'->'.$model['endcity'].'转为内部线路模板');
            $data = $this->encrypt(['code'=>200,'msg'=>'操作成功']);
            return $this->resultInfo($data);
        }catch(\Exception $e){
            $transaction->rollBack();
            $data = $this->encrypt(['code'=>400,'msg'=>'操作失败']);
            return $this->resultInfo($data);
        }
    }
}
