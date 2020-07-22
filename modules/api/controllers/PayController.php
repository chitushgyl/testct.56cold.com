<?php

namespace app\modules\api\controllers;

use AlipayTradePagePayContentBuilder;
use AlipayTradeService;
use app\models\AppBalance;
use app\models\AppBulk;
use app\models\AppGroup;
use app\models\AppLine;
use app\models\AppMemberOrder;
use app\models\AppPayment;
use app\models\AppPaymessage;
use app\models\AppPayOrder;
use app\models\AppReceive;
use app\models\AppVehical;
use app\models\User;
use NativePay;
use WxPayConfig;
use WxPayUnifiedOrder;
use Yii;

/**
 * Default controller for the `api` module
 */
class PayController extends CommonController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionWechat()
    {
        return $this->render('wechat');
    }

    /*
     * 微信支付
     * */
    public function actionWechatpay()
    {
        require_once(Yii::getAlias('@vendor') . '/wxpay/lib/WxPay.Data.php');
        require_once(Yii::getAlias('@vendor') . '/wxpay/NativePay.php');
        $notify = new \NativePay;
        $input = new \WxPayUnifiedOrder;
        $input->SetBody("信息支付");//商品描述
        $input->SetAttach("user_id");//设置附加数据，在查询API和支付通知中原样返回
        $input->SetOut_trade_no(WxPayConfig::MCHID . date("YmdHis"));//订单ID
        $input->SetTotal_fee("1");//支付金额
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");//设置商品标记，代金券或立减优惠功能的参数 */
        $input->SetNotify_url("https://chitu.56cold.com/api/pay/notify");//回调地址
        $input->SetTrade_type("NATIVE");//支付类型
        $input->SetProduct_id("123456789");//商品ID
        $result = $notify->GetPayUrl($input);
        $url = $result["code_url"];

        return $this->render('pay', ['url' => $url]);
    }

    /*
     * 支付宝支付
     * */
    public function actionAlipay()
    {
        $input = Yii::$app->request->get();
        $price = $input['price'];
        $id = $input['id'];
//        $user_id = $input['user_id'];
        $token = $input['token'];
        $result = $this->check_token($token, false);
        $user_id = $result['user']->id;
        $order = AppVehical::findOne($id);
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php');
        $out_trade_no = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);//商户订单号，商户网站订单系统中唯一订单号，必填
        $order->tradenumber = $out_trade_no;
        $order->save();
        $subject = '整车订单支付'; //订单名称，必填
        $total_amount = $price; //付款金额，必填
        $body = '';//商品描述，可空
        $timeExpress = '15m';
        $notify_url = "https://chitu.56cold.com/api/pay/notify_url";//异步通知地址
        $return_url = "https://user.56cold.com";//同步跳转
        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder;
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setPassBack_params($user_id);
        $payRequestBuilder->setTimeExpress($timeExpress);
        $config = Yii::$app->params['configpay'];
        $aop = new \AlipayTradeService($config);
        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = $aop->pagePay($payRequestBuilder, $return_url, $notify_url);
        //输出表单
        var_dump($response);
        // echo json_encode($response);
    }    

    /*
     * 整车支付宝支付异步回调
     * */
    public function actionNotify_url()
    {
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        $arr = $_POST;
        $config = Yii::$app->params['configpay'];
        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($arr);
        if ($arr['trade_status'] == 'TRADE_SUCCESS') {
            $order = AppVehical::find()->where(['tradenumber' => $arr['out_trade_no']])->one();
            $pay = new AppPaymessage();
            $pay->orderid = $arr['out_trade_no'];
            $pay->paynum = $arr['total_amount'];
            $pay->platformorderid = $arr['trade_no'];
            $pay->create_time = date('Y-m-d H:i:s', time());
            $pay->userid = $arr['passback_params'];
            $pay->paytype = 1;
            $pay->type = 1;
            $pay->state = 1;
            $pay->group_id = $order->group_id;


            $order->pay_status = 2;
            $order->line_price = $arr['total_amount'];
            $order->money_state = 'Y';
            $order->line_status = 2;

            $balance = new AppBalance();
            $balance->pay_money = $arr['total_amount'];
            $balance->order_content = '整车支付';
            $balance->action_type = 3;
            $balance->userid = $arr['passback_params'];
            $balance->create_time = date('Y-m-d H:i:s', time());
            $balance->ordertype = 1;
            $balance->orderid = $order->id;
            $balance->group_id = $order->group_id;
            $transaction = Yii::$app->db->beginTransaction();

            $payment = new AppPayment();
            $payment->group_id = $order->group_id;
            $payment->order_id = $order->id;
            $payment->pay_type = 4;
            $payment->status = 3;
            $payment->al_pay = $arr['total_amount'];
            $payment->truepay = $arr['total_amount'];
            $payment->create_user_id = $arr['passback_params'];
            $payment->carriage_name = '赤途';
            $payment->carriage_id = 25;
            $payment->pay_price = $arr['total_amount'];
            try {
                $res = $pay->save();
                $res_o = $order->save();
                $res_b = $balance->save();
                $res_p = $payment->save();
                if ($res && $res_o && $res_b && $res_p) {
                    $transaction->commit();
                    echo 'success';
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
            }
        } else {
            echo 'fail';
        }
    }

    public function actionReturn_url()
    {

    }

    public function actionQrcode($data)
    {
        error_reporting(E_ERROR);
        require_once(Yii::getAlias('@vendor') . '/wxpay/example/phpqrcode/phpqrcode.php');
        $url = urldecode($data);
        $qrcode = new \QRcode();
//        QRcode::png($url);
        $qrcode->png($url);
    }

    /*
     * 支付宝充值
     * */
    public function actionAli_regchange()
    {
        $input = Yii::$app->request->get();
        $token = $input['token'];
        $price = $input['price'];
        $check_result = $this->check_token($token, false);
        $user = $check_result['user'];
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php');
        $ordernumber = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $out_trade_no = trim($ordernumber);//商户订单号，商户网站订单系统中唯一订单号，必填
        $subject = trim('充值'); //订单名称，必填
        $total_amount = trim($price); //付款金额，必填
        $body = trim('余额充值');//商品描述，可空
        $timeExpress = '15m';
        $notify_url = "https://chitu.56cold.com/api/pay/alipay_notify";//异步通知地址
        $return_url = "https://user.56cold.com";//同步跳转
        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder;
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setPassBack_params($user->id);
        $payRequestBuilder->setTimeExpress($timeExpress);
        $config = Yii::$app->params['configpay'];
        $aop = new \AlipayTradeService($config);
        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = $aop->pagePay($payRequestBuilder, $return_url, $notify_url);
        //输出表单
        var_dump($response);
    }

    /*
     * 充值异步回调
     * */
    public function actionAlipay_notify()
    {
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        $arr = $_POST;
        $config = Yii::$app->params['configpay'];
        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($arr);
        if ($arr['trade_status'] == 'TRADE_SUCCESS') {
            $pay = new AppPaymessage();
            $user = User::findOne($arr['passback_params']);
            $pay->orderid = $arr['out_trade_no'];
            $pay->paynum = $arr['total_amount'];
            $pay->platformorderid = $arr['trade_no'];
            $pay->create_time = date('Y-m-d H:i:s', time());
            $pay->userid = $arr['passback_params'];
            $pay->paytype = 1;
//             $pay->payname = $arr['buyer_login_id'];
            $pay->type = 1;
            $pay->state = 2;
            $pay->group_id = $user->group_id;
            $model = AppGroup::findOne($user->group_id);
            $model->balance = $model->balance + $arr['total_amount'];
            $balance = new AppBalance();
            $balance->pay_money = $arr['total_amount'];
            $balance->order_content = '充值';
            $balance->action_type = 1;
            $balance->userid = $arr['passback_params'];
            $balance->create_time = date('Y-m-d H:i:s', time());
            $balance->ordertype = 4;
            $balance->group_id = $user->group_id;
            $transaction = Yii::$app->db->transaction;
            try {
                $res = $pay->save();
                $res_m = $model->save();
                $res_b = $balance->save();
                if ($res && $res_m && $res_b) {
                    $transaction->commit();
                    echo 'success';
                }
            } catch (\Exception $e) {
                $transaction->rollback();
            }
        } else {
            echo 'fail';
        }
    }

    public function actionAlipay_return()
    {
        $config = Yii::$app->params['configpay'];
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        $arr = $_GET;
        $alipaySevice = new AlipayTradeService($config);
        $result = $alipaySevice->check($arr);
        if ($result) {
            $out_trade_no = htmlspecialchars($_GET['out_trade_no']);
            //支付宝交易号
            $trade_no = htmlspecialchars($_GET['trade_no']);
            return $this->render('alipay_return', ['trade_no' => $trade_no]);
        } else {
            //验证失败
            $error = '支付失败';
            return $this->render('alipay_return', ['trade_no' => $error]);
        }
    }

    /*
     * 微信充值
     * */
    public function actionWe_regchange()
    {
        require_once(Yii::getAlias('@vendor') . '/wxpay/lib/WxPay.Data.php');
        require_once(Yii::getAlias('@vendor') . '/wxpay/NativePay.php');
        $notify = new \NativePay;
        $input = new \WxPayUnifiedOrder;
        $input->SetBody("信息支付");//商品描述
        $input->SetAttach("user_id");//设置附加数据，在查询API和支付通知中原样返回
        $input->SetOut_trade_no(WxPayConfig::MCHID . date("YmdHis"));//订单ID
        $input->SetTotal_fee("1");//支付金额
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");//设置商品标记，代金券或立减优惠功能的参数 */
        $input->SetNotify_url("https://chitu.56cold.com/api/pay/wx_notify");//回调地址
        $input->SetTrade_type("NATIVE");//支付类型
        $input->SetProduct_id("123456789");//商品ID
        $result = $notify->GetPayUrl($input);
        $url = $result["code_url"];

        return $this->render('pay', ['url' => $url]);
    }

    public function actionWx_notify()
    {

    }

    /*
     * 余额支付
     * */
    public function actionBalance_pay()
    {
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $price = $input['line_price'];
        if (empty($token)) {
            $data = $this->encrypt(['code' => 400, 'msg' => '参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($price)) {
            $data = $this->encrypt(['code' => 400, 'msg' => '请填写价格']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token, true);
        $user = $check_result['user'];
        $order = AppVehical::find()->where(['id' => $id])->one();
        $groups = $this->check_group_auth($order->group_id, $user);
        $group = AppGroup::find()->where(['id' => $user->group_id])->one();
        if ($order->line_status == 2) {
            $data = $this->encrypt(['code' => 400, 'msg' => '订单已上线']);
            return $this->resultInfo($data);
        }
        if ($order->order_status != 1) {
            $data = $this->encrypt(['code' => 400, 'msg' => '订单已调度，不能上线']);
            return $this->resultInfo($data);
        }
        $order->line_status = 2;
        $order->money_state = 'Y';
        $order->line_price = $price;
        $order->pay_status = 2;
        $tradenumber = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $order->tradenumber = $tradenumber;
        $balan_money = $group->balance - $price;
        if ($balan_money < 0) {
            $data = $this->encrypt(['code' => 400, 'msg' => '余额不足，请充值']);
            return $this->resultInfo($data);
        }

        $group->balance = $balan_money;
        $balance = new AppBalance();
        $pay = new AppPaymessage();
        $balance->orderid = $id;
        $balance->pay_money = $price;
        $balance->order_content = '余额支付';
        $balance->action_type = 2;
        $balance->userid = $user->id;
        $balance->create_time = date('Y-m-d H:i:s', time());
        $balance->ordertype = 1;
        $balance->group_id = $order->group_id;
        $pay->orderid = $tradenumber;
        $pay->paynum = $price;
        $pay->create_time = date('Y-m-d H:i:s', time());
        $pay->userid = $user->id;
        $pay->paytype = 3;
        $pay->type = 1;
        $pay->state = 1;
        $pay->group_id = $order->id;

        $payment = new AppPayment();
        $payment->group_id = $order->group_id;
        $payment->order_id = $order->id;
        $payment->pay_type = 4;
        $payment->status = 3;
        $payment->al_pay = $price;
        $payment->truepay = $price;
        $payment->create_user_id = $user->id;
        $payment->carriage_name = '赤途';
        $payment->carriage_id = 25;
        $payment->pay_price = $price;
        $transaction = AppPaymessage::getDb()->beginTransaction();
        try {
            $res = $pay->save();
            $res_m = $group->save();
            $res_b = $balance->save();
            $res_o = $order->save();
            $res_p = $payment->save();
            if ($res && $res_m && $res_b && $res_o && $res_p) {
                $transaction->commit();
                $data = $this->encrypt(['code' => 200, 'msg' => '上线成功']);
                return $this->resultInfo($data);
            }
        } catch (\Exception $e) {
            $transaction->rollback();
            $data = $this->encrypt(['code' => 400, 'msg' => '上线失败！']);
            return $this->resultInfo($data);
        }
    }

    public function actionRefund()
    {
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/buildermodel/AlipayTradeRefundContentBuilder.php');
        //商户订单号，商户网站订单系统中唯一订单号
        $out_trade_no = trim($_POST['WIDTRout_trade_no']);

        //支付宝交易号
        $trade_no = trim($_POST['WIDTRtrade_no']);
        //请二选一设置

        //需要退款的金额，该金额不能大于订单金额，必填
        $refund_amount = trim($_POST['WIDTRrefund_amount']);

        //退款的原因说明
        $refund_reason = trim($_POST['WIDTRrefund_reason']);

        //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
        $out_request_no = trim($_POST['WIDTRout_request_no']);

        //构造参数
        $RequestBuilder = new \AlipayTradeRefundContentBuilder();
        $RequestBuilder->setOutTradeNo($out_trade_no);
        $RequestBuilder->setTradeNo($trade_no);
        $RequestBuilder->setRefundAmount($refund_amount);
        $RequestBuilder->setOutRequestNo($out_request_no);
        $RequestBuilder->setRefundReason($refund_reason);
        $config = Yii::$app->params['configpay'];
        $aop = new AlipayTradeService($config);

        /**
         * alipay.trade.refund (统一收单交易退款接口)
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @return $response 支付宝返回的信息
         */
        $response = $aop->Refund($RequestBuilder);
        $res = json_encode($response);
//        $res = json_decode($res,true);
//        $refund = $res['alipay_trade_refund_response'];
        return $res;
    }

    /*
     * 支付宝充值会员
     * */
    public function actionPay_member()
    {
        $input = Yii::$app->request->get();
        $token = $input['token'];
        $price = $input['price'];
        $month = $input['month'];
        $check_result = $this->check_token($token, false);
        $user = $check_result['user'];
        $member = new AppMemberOrder();
        $member->ordernumber = 'M' . date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $member->month = $month;
        $member->user_id = $user->id;
        $member->group_id = $user->group_id;
        $res = $member->save();
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php');
        $ordernumber = $member->ordernumber;
        $out_trade_no = trim($ordernumber);//商户订单号，商户网站订单系统中唯一订单号，必填
        $subject = trim('包月'); //订单名称，必填
        $total_amount = trim($price); //付款金额，必填
        $body = trim('接单包月，一个月内不限次数');//商品描述，可空
        $timeExpress = '15m';
        $notify_url = "https://chitu.56cold.com/api/pay/member_notify";//异步通知地址
        $return_url = "https://user.56cold.com";//同步跳转
        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder;
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setPassBack_params($user->id);
        $payRequestBuilder->setTimeExpress($timeExpress);
        $config = Yii::$app->params['configpay'];
        $aop = new \AlipayTradeService($config);
        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = $aop->pagePay($payRequestBuilder, $return_url, $notify_url);
        //输出表单
        var_dump($response);
    }

    /*
     *包月回调
     * */
    public function actionMember_notify()
    {
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        $arr = $_POST;
        $config = Yii::$app->params['configpay'];
        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($arr);
        if ($arr['trade_status'] == 'TRADE_SUCCESS') {
            $pay = new AppPaymessage();
            $pay->orderid = $arr['out_trade_no'];
            $pay->paynum = $arr['total_amount'];
            $pay->platformorderid = $arr['trade_no'];
            $pay->create_time = date('Y-m-d H:i:s', time());
            $pay->userid = $arr['passback_params'];
            $pay->paytype = 1;
            $pay->type = 1;
            $pay->state = 4;
            $user = User::findOne($arr['passback_params']);
            $group = AppGroup::findOne($user->group_id);
            $count = AppMemberOrder::find()->where(['ordernumber' => $arr['out_trade_no']])->one();
            $count->state = 2;
            $group->level = 3;
            $time = time();
            if ($group->now_level_expire && $group->now_level_expire != '0000-00-00 00:00:00') {
                if (strtotime($group->now_level_expire) > $time) {
                    // 正在包月
                    $endtime = strtotime(" +$count->month month", strtotime($group->now_level_expire));
                    $group->now_level_expire = date('Y-m-d H:i:s', $endtime);
                } else {
                    // 包月过期
                    $endtime = date('Y-m-d H:i:s', strtotime(" +$count->month month", $time));
                    $group->now_level_expire = $endtime;
                }
            } else {
                $endtime = date('Y-m-d H:i:s', strtotime(" +$count->month month", $time));
                $group->now_level_expire = $endtime;
            }
            $balance = new AppBalance();
            $balance->pay_money = $arr['total_amount'];
            $balance->order_content = '包月';
            $balance->action_type = 8;
            $balance->userid = $arr['passback_params'];
            $balance->create_time = date('Y-m-d H:i:s', $time);
            $balance->ordertype = 1;
            $balance->group_id = $user->group_id;
            $pay->group_id = $user->group_id;
            $transaction = AppPaymessage::getDb()->beginTransaction();
            try {
                $res = $pay->save();
                $res_m = $group->save();
                $res_b = $balance->save();
                $res_c = $count->save();
                if ($res && $res_m && $res_b && $res_c) {
                    $transaction->commit();
                    echo 'success';
                }
            } catch (\Exception $e) {
                $transaction->rollback();
                echo 'fail';
            }
        } else {
            echo 'fail';
        }
    }

    /*
     * 余额充值会员
     * */
    public function actionBalance_member()
    {
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $month = $input['month'];
        $price = $input['price'];
        $check_result = $this->check_token($token, false);
        $user = $check_result['user'];
        $member = new AppMemberOrder();
        $member->ordernumber = 'M' . date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $member->month = $month;
        $member->user_id = $user->id;
        $member->group_id = $user->group_id;
        $res = $member->save();
        if (empty($token)) {
            $data = $this->encrypt(['code' => 400, 'msg' => '参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($price)) {
            $data = $this->encrypt(['code' => 400, 'msg' => '请填写价格']);
            return $this->resultInfo($data);
        }
//         $this->check_group_auth($order->group_id,$user);
        $group = AppGroup::find()->where(['id' => $user->group_id])->one();
        $group->level = 3;
        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        if ($group->now_level_expire && $group->now_level_expire != '0000-00-00 00:00:00') {
            if (strtotime($group->now_level_expire) > $time) {
                // 正在包月
                $endtime = strtotime(" +" . $month . " month", strtotime($group->now_level_expire));
                $group->now_level_expire = date('Y-m-d H:i:s', $endtime);
            } else {
                // 包月过期
                $endtime = date('Y-m-d H:i:s', strtotime(" +" . $month . " month", $time));
                $group->now_level_expire = $endtime;
            }
        } else {
            $endtime = date('Y-m-d H:i:s', strtotime(" +" . $month . " month", $time));
            $group->now_level_expire = $endtime;
        }

        $member->state = 2;
        $balan_money = $group->balance - $price;
        if ($balan_money < 0) {
            $data = $this->encrypt(['code' => 400, 'msg' => '余额不足，请充值']);
            return $this->resultInfo($data);
        }
        $group->balance = $balan_money;
        $balance = new AppBalance();
        $pay = new AppPaymessage();
        $balance->orderid = $member->id;
        $balance->pay_money = $price;
        $balance->order_content = '余额包月';
        $balance->action_type = 8;
        $balance->userid = $user->id;
        $balance->create_time = $date;
        $balance->ordertype = 1;
        $balance->group_id = $member->group_id;
        $pay->orderid = $member->ordernumber;
        $pay->paynum = $price;
        $pay->create_time = $date;
        $pay->userid = $user->id;
        $pay->paytype = 3;
        $pay->type = 1;
        $pay->state = 4;
        $pay->group_id = $member->group_id;
        $transaction = AppPaymessage::getDb()->beginTransaction();

        try {
            $res = $pay->save();
            $res_m = $group->save();
            $res_b = $balance->save();
            $res_o = $member->save();
            if ($res && $res_m && $res_b && $res_o) {
                $transaction->commit();
                $data = $this->encrypt(['code' => 200, 'msg' => '操作成功']);
                return $this->resultInfo($data);
            }
        } catch (\Exception $e) {
            $transaction->rollback();
            $data = $this->encrypt(['code' => 400, 'msg' => '操作失败！']);
            return $this->resultInfo($data);
        }
    }

    /*
     * 接单支付宝支付（单条）
     * */
    public function actionAlipay_take()
    {
        $input = Yii::$app->request->get();
        $token = $input['token'];
        $price = $input['price'];
        $id = $input['id'];
        $result = $this->check_token($token, false);
        $user = $result['user'];
        $user_id = $user->id;
        $order = AppVehical::findOne($id);
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php');
        $pay_order = new AppPayOrder();
        $pay_order->group_id = $user->group_id;
        $pay_order->order_id = $order->takenumber;
        $pay_order->pay_id = $id;
        $pay_order->user_id = $user->id;
        $pay_order->price = $price;
        $pay_order->save();
        $subject = '整车接单支付'; //订单名称，必填
        $total_amount = $price; //付款金额，必填
        $body = '';//商品描述，可空
        $timeExpress = '15m';
        $notify_url = "https://chitu.56cold.com/api/pay/take_notify";//异步通知地址
        $return_url = "https://user.56cold.com";//同步跳转
        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder;
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($pay_order->order_id);
        $payRequestBuilder->setPassBack_params($user_id);
        $payRequestBuilder->setTimeExpress($timeExpress);
        $config = Yii::$app->params['configpay'];
        $aop = new \AlipayTradeService($config);
        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = $aop->pagePay($payRequestBuilder, $return_url, $notify_url);
        //输出表单
        var_dump($response);
        // echo json_encode($response);
    }

    /*
     * 接单支付回调
     * */
    public function actionTake_notify()
    {
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        $arr = $_POST;
        $config = Yii::$app->params['configpay'];
        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($arr);
        if ($arr['trade_status'] == 'TRADE_SUCCESS') {
            $user = User::findOne($arr['passback_params']);
            $pay = new AppPaymessage();
            $pay->orderid = $arr['out_trade_no'];
            $pay->paynum = $arr['total_amount'];
            $pay->platformorderid = $arr['trade_no'];
            $pay->create_time = date('Y-m-d H:i:s', time());
            $pay->userid = $arr['passback_params'];
            $pay->paytype = 1;
            $pay->type = 1;
            $pay->state = 1;
            $pay->group_id = $user->group_id;

            $count = AppPayOrder::find()->where(['order_id' => $arr['out_trade_no']])->one();
            $count->paystate = 2;
            $balance = new AppBalance();
            $balance->orderid = $count->id;
            $balance->pay_money = $arr['total_amount'];
            $balance->order_content = '整车接单支付宝支付';
            $balance->action_type = 3;
            $balance->userid = $arr['passback_params'];
            $balance->create_time = date('Y-m-d H:i:s', time());
            $balance->ordertype = 1;
            $balance->group_id = $user->group_id;
            $pay->group_id = $user->group_id;
            $order = AppVehical::find()->where(['takenumber' => $arr['out_trade_no']])->one();
            $group = AppGroup::find()->where(['id' => $user->group_id])->one();
            $res_p = $arr = $res_p = true;
            if ($group->level_id == 3) {
                $order->order_status = 2;
                $order->deal_company = $user->group_id;
                $receive = new AppReceive();
                $receive->receivprice = $order->line_price;
                $receive->trueprice = $order->line_price;
                $receive->al_price = $order->line_price;
                $receive->order_id = $order->id;
                $receive->create_user_id = $user->id;
                $receive->create_user_name = $user->name;
                $receive->group_id = $user->group_id;
                if ($order->money_state == 'Y') {
                    $receive->company_type = 2;
                    $receive->compay_id = 25;
                    $receive->status = 3;
                    $receive->al_price = $order->line_price;
                } else {
                    $receive->company_type = 1;
                    $receive->compay_id = $order->group_id;
                    $payment = AppPayment::find()->where(['group_id' => $order->group_id, 'order_id' => $order->id])->one();
                    $payment->carriage_id = $user->group_id;
                    $res_p = $payment->save();
                }
            }
            $transaction = AppPaymessage::getDb()->beginTransaction();
            try {
                $res_o = $order->save();
                $arr = $receive->save();
                $res = $pay->save();
                $res_b = $balance->save();
                $res_c = $count->save();
//                if ($payment){
//                    $res_p = $payment->save();
//                }
                if ($res && $res_b && $res_c && $res_o && $arr && $res_p) {
                    $transaction->commit();
                    echo 'success';
                }
            } catch (\Exception $e) {
//                var_dump($e);
                $transaction->rollback();
                echo 'fail';
            }
        } else {
            echo 'fail';
        }
    }

    /*
     * 接单余额支付
     * */
    public function actionTake_balance()
    {
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $price = $input['price'];
        $res_r = true;
        if (empty($token)) {
            $data = $this->encrypt(['code' => 400, 'msg' => '参数错误']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token, false);
        $user = $check_result['user'];
        $order = AppVehical::find()->where(['id' => $id])->one();
        if ($order->order_status == 2) {
            $data = $this->encrypt(['code' => 400, 'msg' => '订单已被接单']);
            return $this->resultInfo($data);
        }
//        $this->check_group_auth($order->group_id,$user);
        $group = AppGroup::find()->where(['id' => $user->group_id])->one();
        $pay_order = new AppPayOrder();
        $pay_order->group_id = $user->group_id;
        $pay_order->order_id = $order->takenumber;
        $pay_order->pay_id = $id;
        $pay_order->user_id = $user->id;
        $pay_order->price = $price;
        $pay_s = $pay_order->save();
        if (!$pay_s) {
            $data = $this->encrypt(['code' => 400, 'msg' => '网络出错']);
            return $this->resultInfo($data);
        }
        $balan_money = $group->balance - $price;
        if ($balan_money < 0) {
            $data = $this->encrypt(['code' => 400, 'msg' => '余额不足，请充值']);
            return $this->resultInfo($data);
        }

        $group->balance = $balan_money;
        $balance = new AppBalance();
        $pay = new AppPaymessage();
        $balance->orderid = $pay_order->id;
        $balance->pay_money = $price;
        $balance->order_content = '余额接单支付';
        $balance->action_type = 2;
        $balance->userid = $user->id;
        $balance->create_time = date('Y-m-d H:i:s', time());
        $balance->ordertype = 1;
        $balance->group_id = $user->group_id;
        $pay->orderid = $pay_order->order_id;
        $pay->paynum = $price;
        $pay->create_time = date('Y-m-d H:i:s', time());
        $pay->userid = $user->id;
        $pay->paytype = 3;
        $pay->type = 1;
        $pay->state = 1;
        $pay->group_id = $user->group_id;
        if ($group->level_id == 3) {
            $order->order_status = 2;
            $order->deal_company = $user->group_id;
            $receive = new AppReceive();
            $receive->receivprice = $order->line_price;
            $receive->trueprice = $order->line_price;
            $receive->order_id = $order->id;
            $receive->create_user_id = $user->id;
            $receive->create_user_name = $user->name;
            $receive->group_id = $user->group_id;
            if ($order->money_state == 'Y') {
                $receive->company_type = 2;
                $receive->compay_id = 25;
                $receive->status = 3;
                $receive->al_price = $order->line_price;
            } else {
                $receive->company_type = 1;
                $receive->compay_id = $order->group_id;
                $payment = AppPayment::find()->where(['group_id' => $order->group_id, 'order_id' => $order->id])->one();
                $payment->carriage_id = $user->group_id;
                $payment->save();
            }
        }
        $member_g = AppPayOrder::findOne($pay_order->id);
        $member_g->paystate = 2;
        $transaction = AppPaymessage::getDb()->beginTransaction();
        try {
            $res = $pay->save();
            $res_m = $group->save();
            $res_b = $balance->save();
            $res_o = $order->save();
//            if ($payment){
//                $res_p = $payment->save();
//            }
            $res_a = $member_g->save();
            $res_r = $receive->save();
            if ($res && $res_m && $res_b && $res_o && $res_a && $res_r) {
                $transaction->commit();
                $data = $this->encrypt(['code' => 200, 'msg' => '接单成功']);
                return $this->resultInfo($data);
            }
        } catch (\Exception $e) {
            $transaction->rollback();
            $data = $this->encrypt(['code' => 400, 'msg' => '网络错误！']);
            return $this->resultInfo($data);
        }
    }



    /*
     * 零担在线下单支付宝支付
     * */
    public function actionBulk_alipay()
    {
        $input = Yii::$app->request->get();
        $price = $input['price'];
        $id = $input['id'];
        $token = $input['token'];
        $user_id = $input['user_id'];
        $order = AppBulk::findOne($id);
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php');
        $out_trade_no = $order->ordernumber;//商户订单号，商户网站订单系统中唯一订单号，必填
        $subject = '干线下单支付'; //订单名称，必填
        $total_amount = $price; //付款金额，必填
        $body = '';//商品描述，可空
        $timeExpress = '15m';
        $notify_url = "https://chitu.56cold.com/api/pay/bulk_notify";//异步通知地址
        $return_url = "https://user.56cold.com";//同步跳转
        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder;
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setPassBack_params($user_id);
        $payRequestBuilder->setTimeExpress($timeExpress);
        $config = Yii::$app->params['configpay'];
        $aop = new \AlipayTradeService($config);
        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = $aop->pagePay($payRequestBuilder, $return_url, $notify_url);
        //输出表单
        var_dump($response);
        // echo json_encode($response);
    }

    /*
     * 在线下单支付回调
     * */
    public function actionBulk_notify()
    {
        require_once(Yii::getAlias('@vendor') . '/alipay/pagepay/service/AlipayTradeService.php');
        $arr = $_POST;
        $config = Yii::$app->params['configpay'];
        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($arr);
        if ($arr['trade_status'] == 'TRADE_SUCCESS') {
            $order = AppBulk::find()->where(['ordernumber' => $arr['out_trade_no']])->one();
            $pay = new AppPaymessage();
            $pay->orderid = $arr['out_trade_no'];
            $pay->paynum = $arr['total_amount'];
            $pay->platformorderid = $arr['trade_no'];
            $pay->create_time = date('Y-m-d H:i:s', time());
            $pay->userid = $arr['passback_params'];
            $pay->paytype = 1;
            $pay->type = 1;
            $pay->state = 1;
            $pay->group_id = $order->group_id;
            $order->orderstate = 2;
            $order->paystate = 2;

            $balance = new AppBalance();
            $balance->pay_money = $arr['total_amount'];
            $balance->order_content = '干线下单支付宝支付';
            $balance->action_type = 3;
            $balance->userid = $arr['passback_params'];
            $balance->create_time = date('Y-m-d H:i:s', time());
            $balance->ordertype = 2;
            $balance->orderid = $order->id;
            $balance->group_id = $order->group_id;
            $transaction = Yii::$app->db->beginTransaction();

            $payment = new AppPayment();
            $payment->group_id = $order->group_id;
            $payment->order_id = $order->id;
            $payment->pay_type = 4;
            $payment->status = 3;
            $payment->al_pay = $arr['total_amount'];
            $payment->truepay = $arr['total_amount'];
            $payment->create_user_id = $arr['passback_params'];
            $payment->carriage_name = '赤途';
            $payment->carriage_id = 25;
            $payment->pay_price = $arr['total_amount'];
            $payment->type = 2;
            $line = AppLine::findOne($order->shiftid);
            $receive = new AppReceive();
            $time = date('Y-m-d H:i:s',time());
            $receive->compay_id = 25;
            $receive->company_type= 2;
            $receive->receivprice = $arr['total_amount'];
            $receive->trueprice = 0;
            $receive->order_id = $order->id;
            $receive->receive_info = '';
            $receive->create_user_id = $arr['passback_params'];
            $receive->group_id = $line->group_id;
            $receive->create_time = $time;
            $receive->update_time = $time;
            $receive->ordernumber = $arr['out_trade_no'];
            $receive->type = 2;
            try {
                $res = $pay->save();
                $res_o = $order->save();
                $res_b = $balance->save();
                $res_p = $payment->save();
                if ($res && $res_o && $res_b && $res_p) {
                    $transaction->commit();
                    echo 'success';
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
            }
        } else {
            echo 'fail';
        }
    }

    /*
     * 零担在线下单余额支付
     * */
    public function actionBulk_balance(){
        $input = Yii::$app->request->post();
        $token = $input['token'];
        $id = $input['id'];
        $price = $input['price'];
        if (empty($token)) {
            $data = $this->encrypt(['code' => 400, 'msg' => '参数错误']);
            return $this->resultInfo($data);
        }
        if (empty($price)) {
            $data = $this->encrypt(['code' => 400, 'msg' => '请填写价格']);
            return $this->resultInfo($data);
        }
        $check_result = $this->check_token($token, false);
        $user = $check_result['user'];
        $order = AppBulk::find()->where(['id' => $id])->one();
        $this->check_group_auth($order->group_id, $user);
        $group = AppGroup::find()->where(['id' => $user->group_id])->one();
        if ($order->orderstate !=1) {
            $data = $this->encrypt(['code' => 400, 'msg' => '订单已开始运输']);
            return $this->resultInfo($data);
        }
        if ($order->paystate != 1) {
            $data = $this->encrypt(['code' => 400, 'msg' => '订单已支付']);
            return $this->resultInfo($data);
        }
        $order->orderstate = 2;
        $order->paystate = 2;
        $balan_money = $group->balance - $price;
        if ($balan_money < 0) {
            $data = $this->encrypt(['code' => 400, 'msg' => '余额不足，请充值']);
            return $this->resultInfo($data);
        }

        $group->balance = $balan_money;
        $balance = new AppBalance();
        $pay = new AppPaymessage();
        $balance->orderid = $id;
        $balance->pay_money = $price;
        $balance->order_content = '余额支付';
        $balance->action_type = 2;
        $balance->userid = $user->id;
        $balance->create_time = date('Y-m-d H:i:s', time());
        $balance->ordertype = 2;
        $balance->group_id = $order->group_id;
        $pay->orderid = $order->ordernumber;
        $pay->paynum = $price;
        $pay->create_time = date('Y-m-d H:i:s', time());
        $pay->userid = $user->id;
        $pay->paytype = 3;
        $pay->type = 1;
        $pay->state = 1;
        $pay->group_id = $order->group_id;
        $payment = new AppPayment();
        $payment->group_id = $order->group_id;
        $payment->order_id = $order->id;
        $payment->pay_type = 4;
        $payment->status = 3;
        $payment->al_pay = $price;
        $payment->truepay = $price;
        $payment->create_user_id = $user->id;
        $payment->carriage_name = '赤途';
        $payment->carriage_id = 25;
        $payment->pay_price = $price;
        $payment->type = 2;
        $line = AppLine::findOne($order->shiftid);
        $receive = new AppReceive();
        $time = date('Y-m-d H:i:s',time());
        $receive->compay_id = 25;
        $receive->company_type = 2;
        $receive->receivprice = $price;
        $receive->trueprice = 0;
        $receive->order_id = $order->id;
        $receive->receive_info = '';
        $receive->create_user_id = $user->id;
        $receive->create_user_name = $user->name;
        $receive->group_id = $line->group_id;
        $receive->create_time = $time;
        $receive->update_time = $time;
        $receive->ordernumber = $order->ordernumber;
        $receive->type = 2;

        $transaction = AppPaymessage::getDb()->beginTransaction();
        try {
            $res = $pay->save();
            $res_m = $group->save();
            $res_b = $balance->save();
            $res_o = $order->save();
            $res_p = $payment->save();
            $arr = $receive->save();
            if ($res && $res_m && $res_b && $res_o && $res_p && $arr) {
                $transaction->commit();
                $data = $this->encrypt(['code' => 200, 'msg' => '干线下单支付成功']);
                return $this->resultInfo($data);
            }
        } catch (\Exception $e) {
            $transaction->rollback();
            $data = $this->encrypt(['code' => 400, 'msg' => '干线下单支付失败！']);
            return $this->resultInfo($data);
        }
    }

}
