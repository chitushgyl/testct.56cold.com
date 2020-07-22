<?php

namespace app\modules\api\controllers;

use yii;
use app\models\AppIp;

/**
 * Default controller for the `api` module
 */
class IpController extends CommonController
{
    //获取公司域名对应信息
    public function actionInfo(){
        $request = Yii::$app->request;
        $input = $request->post();
        $url = $input['url'];
   
        $list = AppIp::find()->where(['url'=>$url,'use_flag'=>'Y'])->asArray()->one();
        if ($list){
            $data = $this->encrypt(['code'=>200,'msg'=>'查询成功','data'=>$list]);
        }else{
            $data = $this->encrypt(['code'=>200,'msg'=>'暂无数据！','data'=>[]]);
        }
        return $this->resultInfo($data);
    }

    public function actionTest(){

        $list = AppIp::find();
        $count = $list->count();
        $list = $list->asArray()->all();
        $data = [
            'code' => 0,
            'msg'   => '正在請求中...',
            'count' => $count,
            'data'  => $list,
            'get' => $_POST
        ];
        return json_encode($data);
    }
}
