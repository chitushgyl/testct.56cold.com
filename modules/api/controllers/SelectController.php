<?php
namespace app\modules\api\controllers;

use app\models\AppCartype;
use app\models\AppLine;
use app\models\AppOrder;
use app\models\AppSetParam;
use app\models\District;
use Yii;

/**
 * Default controller for the `api` module
 */
class SelectController extends CommonController
{
    /**
     * Renders the index view for the module
     * 查询线路
     * @return string
     */
    public function actionSelect_line()
    {
        $input = Yii::$app->request->post();
        $startcity = $input['startcity']??'';//起点城市
        $endcity = $input['endcity']?? '';//终点城市
        $startarea = $input['startarea'] ?? '';
        $endarea = $input['endarea'] ?? '';
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        $list = AppLine::find();
        if ($startcity) {
            $list->andWhere(['like','startcity',$startcity]);
        }

        if ($endcity) {
            $list->andWhere(['like','endcity',$endcity])
                ->orWhere(['like','transfer',$endcity]);
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
        if (empty($startcity) && empty($endcity)){
            $list = [];
        }
        if ($startcity && $endcity){

            $address = District::find()->where(['like','name',$startcity])->select('id,name,level')->andWhere(['level'=>2])->one();
            $address1 = District::find()->where(['like','name',$endcity])->select('id,name,level')->andWhere(['level'=>2])->one();
            $cache = \Yii::$app->cache;
            $vehical = $cache->get($address->id.'_'.$address1->id);
            if (!$vehical){
                $vehical = $this->vehical_line($startcity,$endcity,$startarea,$endarea);//获取整车线路
                $cache->set($address->id.'_'.$address1->id,$vehical);
            }
            $arr = array_merge($list,$vehical);
            $count = count($arr);
            $list = array_slice($arr,($page-1)*$limit,$limit);
        }

        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'status'=>200,
            'count' => $count,
            'data'  => precaution_xss($list)
        ];
        return  $arr = json_encode($data);
    }
    /*
     * 整车线路
     * */
    public function vehical_line($startcity,$endcity,$startarea,$endarea){
        // 整车
        $vehicle = array();
        $car = AppCartype::find()->select('car_id')->asArray()->all();
        unset($car[0]);
        foreach ($car as $k => $v){
            $res = $this->vehical_count($startcity,$endcity, $startarea,$endarea,$v['car_id']);
                $vehicle['id'] = $k + 1;
                $vehicle['startcity'] = $startcity;
                $vehicle['endcity'] = $endcity;
                $vehicle['start_time'] = $res['hour'];
                $vehicle['line_price'] = $res['countprice'];
                $vehicle['carname'] = $res['carname'];
                $vehicle['km'] = $res['km'];
                $vehical[] = $vehicle;
        }
        return $vehical;
    }

    /*
     * 整车计算公里数，价格
     * */
    public function vehical_count($startcity,$endcity,$startarea,$endarea,$carid){
        // 查询系数类型 2 整车
        $type = 2;
        // 总运费
        $allmoney = 0;
        // 起步价
        $startPrice = 0;
        // 运费
        $freight = 0;
        // 每公里单价
        $onePrice = 0;
        // 行车时长
        $gethour = 0;
        // 里程数
        $km = 0;
        // 起步价系数
        $scale_startprice = 1;
        // 里程偏离系数
        $scale_km = 1;
        // 单公里价格系数
        $scale_price_km = 1;
        // 查找选定的车型
        $car_type = AppCartype::find()->select('car_id,lowprice,costkm,carparame')->where(['car_id'=>$carid])->asArray()->one();
        // 查找系数比例
        $scale = AppSetParam::find()->select('scale_startprice,scale_km,scale_price_km,type')->where(['type'=>$type])->asArray()->one();
        // 如果有系数值则写入
        if($scale['type']){
            $scale_startprice = $scale['scale_startprice'];
            $scale_km = $scale['scale_km'];
            $scale_price_km = $scale['scale_price_km'];
        }
        // 起点城市经纬度
        $start_action = bd_local($type='1',$startcity,$area=$startarea);//经纬度
        // 终点城市经纬度
        $end_action = bd_local($type='1',$endcity,$area=$endarea);//经纬度
        // 获取百度返回的结果
        $list = direction($start_action['lat'], $start_action['lng'], $end_action['lat'], $end_action['lng']);
        // 解析结果得到公里数和行车时长
        $finally = $list['distance']/1000;
        // $gethour = $list['duration']/60/60;
        $gethour = round($finally/65);
        $gethour = $gethour < 1 ? 1 : $gethour;
        // 乘以公里系数后的公里数
        $km = $this->mileage_interval(2,(int)$finally);
        // 计算起步价
        $startPrice = $car_type['lowprice']*$scale_startprice;
        // 运费 公里数*单价
        $freight = $km*$car_type['costkm']*$scale_price_km;
        // 总运费
        $allmoney = $startPrice+$freight;

        // 行车小时
        $data['hour'] = round($gethour);
        // 预计费用
        $data['countprice'] = round($allmoney);
        // 预计费用
        $data['carname'] = $car_type['carparame'];
        $data['km'] =round($km);

        return $data;
    }
    /*
     * 零担线路
     * */
    public function bulk_line($startcity,$endcity,$startarea,$endarea){
         $line = AppLine::find()
             ->select('id,startcity,endcity,startarea,endarea,price,eprice,start_time,trunking')
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
                 ->select('id,startcity,endcity,startarea,endarea,price,eprice,start_time,trunking')
                 ->where(['startcity'=>$startcity,'endcity'=>$endcity,'delete_flag'=>'Y'])
                 ->asArray()
                 ->all();
         }
         return $line;
    }

    /*
     * 上线订单列表
     * */
    public function actionOnline_index(){
        $request = Yii::$app->request;
        $input = $request->post();
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
            'data'  => precaution_xss($list)
        ];
        return json_encode($data);
    }



}

