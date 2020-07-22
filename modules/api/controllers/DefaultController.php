<?php

namespace app\modules\api\controllers;

use app\models\Account;

/**
 * Default controller for the `api` module
 */
class DefaultController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $list = Account::find()->asArray()->all();
        // print_r($list);
    	$data = $this->encrypt(['code'=>200,'msg'=>'请在输入框内输入：密码不能为空的啊']);
        return $this->resultInfo($data);
        // return $this->render('index',['data'=>$data,'list'=>$list]);
    }   

    public function actionTest(){
        Topic::updateAllCounters();
    }
}
