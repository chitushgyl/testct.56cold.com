<?php
/**
 * Created by pysh.
 * Date: 2019/7/3
 * Time: 19:19
 */
namespace app\controllers;
use Yii,
    yii\web\Controller,
    app\models\District;
/**
 *
 */
class CommonController extends Controller
{
    public function actions(){
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    // 获取地址
    public function actionGetAddress(){
        $id = $_POST['id'];
        $list = District::find()->where(['parent_id'=>$id])->asArray()->all();
        return pj($list);
    }

}