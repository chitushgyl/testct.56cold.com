<?php
namespace app\modules\api\controllers;

use Yii;
use app\models\AppCarriageList;
use app\models\AppPayment;
use app\models\AppReceive;
use app\models\AppVehical;
use app\models\AppGroup;

/**
 * Default controller for the `api` module
 */
class HallController extends CommonController
{
    /*
     * 整车大厅列表
     * */
    public function actionVehical_index(){
        $request = Yii::$app->request;
        $input = $request->post();
        $page = $input['page'] ?? 1;
        $limit = $input['limit'] ?? 10;
        $begintime = $input['begintime'] ?? '';
        $startcity = $input['startcity'];
        $endcity = $input['endcity'];
        $data = [
            'code' => 200,
            'msg'   => '',
            'status'=>400,
            'count' => 0,
            'data'  => []
        ];
        $list = AppVehical::find()
            ->alias('v')
            ->select(['v.*','c.all_name','t.carparame'])
            ->leftJoin('app_customer c','v.company_id=c.id')
            ->leftJoin('app_cartype t','v.cartype=t.car_id')
            ->where(['v.line_status'=>2,'v.order_status'=>1,'v.delete_flag'=>'Y']);

        if ($begintime) {
            $time_s = $begintime . ' 00:00:00';
            $time_e = $begintime . ' 23:59:59';
            $list->andWhere(['between','v.time_start',$time_s,$time_e]);
        }
        if ($startcity){
            $list->andWhere(['like','v.startcity',$startcity]);
        }
        if ($endcity){
            $list->andWhere(['like','v.endcity',$endcity]);
        }

        $count = $list->count();
        $list = $list->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy(['v.time_start'=>SORT_ASC])
            ->asArray()
            ->all();
        foreach($list as $k => $v) {
            $list[$k]['startstr'] = json_decode($v['startstr'],true);
            $list[$k]['endstr'] = json_decode($v['endstr'],true);
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
   





























 