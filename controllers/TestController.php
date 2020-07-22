<?php
/**
 * Created by: Joker
 * Date: 2019/8/27
 * Time: 10:36
 */

namespace app\controllers;

use app\models\Test;
use yii\web\Controller;



class TestController extends Controller
{
    public $enableCsrfValidation = false;// 不验证 token
    function actionA(){
        $data = json_decode(base64_decode($_POST['param']),true);
        if(!isset($data['params']) || empty($data['signStr'])|| !is_array($data['params'])||!is_string($data['signStr'])){
            return 1003;
        }

        ksort($data['params']);
        $signStr = '';
        foreach($data['params'] as $v){
            if(is_array($v)){
                return 1004;
            }else{
                $signStr .= $v;
            }
        }
        if(md5(urlencode($signStr))!= $data['signStr']){
            return 1005;
        }
        pr($data);
    }

    function actionB(){
        ksort($_GET);
        $data['params'] = $_GET;
        $signStr = '';
        foreach($_GET as $v){
            if(is_array($v)){
                return ['retCode'=>1004];
            }else{
                $signStr .= $v;
            }
        }
        $data['signStr'] = md5(urlencode($signStr));
        return base64_encode(json_encode(($data)));
    }

    function actionC(){
        $data = json_decode(base64_decode($_POST['token']),true);
        if(empty($data['params'])|| empty($data['signStr'])|| !is_array($data['params'])||!is_string($data['signStr'])){
            return 1007;
        }

        if(empty($data['params']['member_id']) || !isset($data['params']['last_login_time'])){
            return 1007;
        }

        ksort($data['params']);

        // 獲取用戶的信息
        $memberInfo = Member::findOne(['member_id'=>$data['params']['member_id']]);
        pr($memberInfo);die;
    }

    function actionD(){
        echo  "<form style='display:none;' id='form1' name='form1' method='post' action=https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/v5><input name='MerchantID' type='text' value='2000132' /><input name='MerchantTradeNo' type='text' value='BM1OCFXP1U3WQ854G2N' /><input name='MerchantTradeDate' type='text' value='2019/08/29 12:05:53' /><input name='PaymentType' type='text' value='aio' /><input name='TotalAmount' type='text' value='300' /><input name='TradeDesc' type='text' value='Buy package' /><input name='ItemName' type='text' value='three' /><input name='ReturnURL' type='text' value='http://twbmonsteradmin.xun-ao.com/ecpay/return-url' /><input name='ChoosePayment' type='text' value='Credit' /><input name='Remark' type='text' value='joker2019-08-29 12:05:53提交購買套餐1' /><input name='ClientBackURL' type='text' value='http://www.bmonster.com/?MerchantTradeNo=BM1OCFXP1U3WQ854G2N' /><input name='Language' type='text' value='ENG' /><input name='OrderResultURL' type='text' value='http://www.bmonster.com/' /><input name='PeriodAmount' type='text' value='300' /><input name='PeriodType' type='text' value='M' /><input name='Frequency' type='text' value='1' /><input name='ExecTimes' type='text' value='99' /><input name='PeriodReturnURL' type='text' value='http://twbmonsteradmin.xun-ao.com/ecpay/period-return-url' /><input name='CheckMacValue' type='text' value='2E7A2B9DD2499C90657F244818B44C51' /></form><script type='text/javascript'>function load_submit(){document.form1.submit()}load_submit();</script>";
    }

    function actionE(){
        $record = DiscountActivityRecord::find()
            ->where(['discount_activity_record.member_id'=>1])
            ->leftJoin('discount_activity_member','discount_activity_record.dam_id=discount_activity_member.dam_id')
            ->andWhere(['>','discount_activity_member.member_id',0])
            ->leftJoin('discount_activity','discount_activity.da_id=discount_activity_member.da_id')
            ->select([
                "discount_activity_record.dam_id",
                "discount_activity_member.member_id",
                "discount_activity_member.da_id",
                "discount_activity.coupon_id_member",
            ])
            ->asArray()
            ->one();
        if ($record){
            // 給用戶發券

        }
    }

    public function actionPay(){
        echo PublicFunction::pay([])['data']['form'];die;
    }



    function actionGoWeb2(){
        Test::updateAll(['name'=>222],['id'=>1]); 
    }
}