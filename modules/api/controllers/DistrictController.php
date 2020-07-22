<?php

namespace app\modules\api\controllers;

use app\models\User; 
use app\models\District;
use Yii;
use yii\web\Request;

/**
 * Customer controller for the `api` module
 */
class DistrictController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex() {
        $request = Yii::$app->request;
        $input = $request->post();
        $pid = $input['pid'];
        $where = [];
        if ($pid) {
            $where[] =['=','parent_id',$pid];
        }
        if ($pid == 0 || !$pid) {
            $cache = \Yii::$app->cache;
            $list = $cache->get('district_index');
            if (!$list) {
                $list = District::find()->where(['parent_id'=>0])->asArray()->all();
                $cache->set('district_index',$list,86400);
            }
        } else {
            $list = District::find()->where(['parent_id'=>$pid])->asArray()->all();
        }

        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'data'  => precaution_xss($list)
        ];
        $data = $this->encrypt($data);
        return json_encode($data);
    }     

    public function actionIndex_name() {
        $request = Yii::$app->request;
        $input = $request->post();
        $pid = $input['pid'];
        $name = $input['name'];
        $type = $input['type'];
        $where = [];
        if ($pid == 0 && !$name) {
            $where[] =['=','parent_id',0];
            $cache = \Yii::$app->cache;
            $list = $cache->get('district_index');
            if (!$list) {
                $list = District::find()->where(['parent_id'=>0])->asArray()->all();
                $cache->set('district_index',$list,86400);
            }
        } else {
            if ($type == 1) {//省份下
                $obj_name = District::find()->where(['name'=>$name])->one();
            } else if($type == 2) {//城市下
                $obj_name = District::find()->where(['name'=>$name])->orderBy(['id'=>SORT_DESC])->one();
            }
            $list = District::find()->where(['parent_id'=>$obj_name->id])->asArray()->all();
        }

        $data = [
            'code' => 200,
            'msg'   => '正在请求中...',
            'data'  => precaution_xss($list)
        ];
        $data = $this->encrypt($data);
        return json_encode($data);
    }   

}
