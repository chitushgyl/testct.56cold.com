<?php
/**
 * Created by pysh.
 * Date: 2020/2/2
 * Time: 01:01
 */

namespace app\controllers\admin;
use app\models\AppBalance;
use app\models\AppGroup;
use app\models\AppPaymessage;
use app\models\AppWithdraw;
use yii\web\Controller,
    yii\base\Module,
    yii\web\Response,
    app\models\Account,
    Yii;

class AdminBaseController extends Controller
{
    public $request;
    public $admin_id;
    public $admin_name;
    public $now_auth;
    public function __construct($id, Module $module, array $config = [])
    {
        $this->layout = false;
        parent::__construct($id, $module, $config);
        $session = Yii::$app->session;
        $this->admin_id = $session->get('admin_id');
        $this->admin_name = $session->get('admin_name');
        if(!$session->get('admin_id')) {
            header('location:/admin/login');exit;
        }
        // 判断该用户是否有现在访问的路由的权限
        $this->now_auth = $this->checkPermissions();
        $this->request = Yii::$app->request;

    }  

    /**
     * Desc:
     * Created by pysh
     * Date: 2020/2/2
     * Time: 19:15
     */
    public function checkPermissions(){
        $route = str_replace('/','.',Yii::$app->request->getPathInfo());
        $flag = can($route);
        if($flag){
            return true;
            // throw new \yii\web\NotFoundHttpException("您沒有该操作权限!",403);
        } else {
            return false;
        }
    }    

    // 主账号以及子账号权限（子公司）
    public function getCompany_ids(){
        $companys = Account::find()->select(['id'])->where(['admin'=>$this->root_id,'status'=>9])->asArray()->all();
        $ids = [$this->root_id];
        if ($companys) {
            foreach ($companys as $v) {
                $ids[] = $v['id'];
            }
        }
        return $ids;
    }

    public function withSuccess($msg)
    {
        $session = Yii::$app->session;
        $session->set('success',$msg);
        return $this;
    }

    public function withErrors($msg)
    {
        $session = Yii::$app->session;
        $session->set('error',$msg);
        return $this;
    }

    public function resultInfo($data){
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $data;
    }

    /*
    * 支付宝提现
    * */
    public function Alipay_withdraw($ordernumber,$account,$name,$price)
    {
        require_once(Yii::getAlias('@vendor') . '/alipay/aop/AopCertClient.php');
        require_once(Yii::getAlias('@vendor') . '/alipay/aop/request/AlipayFundTransUniTransferRequest.php');

        $aop = new \AopCertClient();
        $appCertPath =Yii::getAlias('@vendor') . '/alipay/cert/appCertPublicKey.crt';
        $alipayCertPath = Yii::getAlias('@vendor') . '/alipay/cert/alipayCertPublicKey.crt';
        $rootCertPath = Yii::getAlias('@vendor') . '/alipay/cert/alipayRootCert.crt';

        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2021001162635598';
        $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAj/iU9joUtd0h88zMbIgtbFp82NsUyTkfL9AQ+DqlWIDYo0oan6ecUtC3YOrD8Nxy7fVOainN2t6/+PA8k636Yo6Ls6+RobTmswkQbYcPSMtPlj8FHFg3CueOEB1FDXbz0XgfboMu6giopSqvN9Poy/2d0PPd8c4WOTIN0BSRYFJ4+W1suejG/pGittT4zo0OXXkGHL1mU/I2ocZCn7d+mvfD3jnHV88sfg5AVsSQ7/BGEeAEuUlXK1IoPOKpjsyfXnb9fgR2PRYLhktZ9fImHH1Lvq367S9/FY7NxlgnE8N/UB7KO+y4094h72bBlNiEvUu+gJ4V9b3AYZhG3e8ZZQIDAQABAoIBAEIJpuJOf+NvnDxFK1t5F0TFONELpLwsDGcVDEOgOumeqEA2JIIpEqZWAFdfOCNKKxmFVMOTi04isHHSWCbxPZFpiyEPnkBLRyrGNJfYxKUCRO5I1+JJgG3rCpnPozXq4ymo7Nn3KFTHRfwE2TxFYln3aiVHRv28JytzDyzr0kcYtxNETQwUDkzMjYvy1Uw6Ibs4dRPgMe+mNn9OYJAdFwM5pL4Xo0cg3AJZDnDw+ZLPD5tEDNsAA/LR6AgjAn/sKQ0oakI6wFQ1z6d8s8IrN72MRoGJ9NBjIGetq9cOjS+jP4cvGfNw2AqPKW0Op8CBIvaw6GKBPLyb/t7phQxm7CECgYEA+afCBDMRgZCUOdnLBQlEI98Ua2Y3wk/gVdE9r+uq1r64gbWLUjzS+jZQoAz56wqrIIObmxqaYimeSo+t5QRu5OksXIsnWjdtdO95Z1TtgoKNTnGvIV0dCh+7UF1aiMjqMgYvpkdhr5Bm3ytatWKd9grdFcYhntWdZk8m+XMNqLkCgYEAk6E/oH2iW6QCG4Z0CuzYufDfSJcWS5zJS3+zVUXqLFu+Ssmy98Bqh1ysK4rMoLyZyKsy9lRfrpBFPn6sm7TunJBx876TRVVo7HQfcQH9frHkk9k3YEzfaeQe5RHELPwzIKCLke0tsk/C4hcRq3p6xdBrEr2oSWVSQGoAPn1VyA0CgYAssQ9WWR4FJ7ChOo/RcvszwLeTElVg/5OVSUPVvkZy8ulsrucl7aWHDToZrLkAjoRb6bNtbLG+aNzhVB85JDYF3IgIeRCuYcXCbSw6h0WNW3mYVVmYi2arbUrG9C9E1VK3acwV5ClkmGESClzOo7zLUt6JC1LxcCQhMII1nZcQYQKBgGc99Uk/kzOTVwX42V5qlIY0tXIGd0kZtIxgGgIgisvKvSGAPPCWV+miHaW1w7UFMGbtkw5Bo0hpDIPQAtZBij1jps1XEZcDTAVQkExvn9/ieIANAHUQTY24QwLfkdoD5Z2DqRe8TqDMtvV2PJ03YnTEdJz+lZn+ia8ScmlBLaRhAoGBAOo7SJMkVzqzUKZB6Q5yLK5ULpYR0n+IWRJ9OVZTPGWjWdTf2nvFWfvJZTzZaw8Xv20woTM9gjq/TigOHNgvdqi3YjTSxPnxkKZwp+dfJJAjQZX8fWPCoFyOVmKRTcnRtg3/XHI0t1bYcydhwVd2IKTvMus021zfVrkC8XzMIhLb';
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAj/iU9joUtd0h88zMbIgtbFp82NsUyTkfL9AQ+DqlWIDYo0oan6ecUtC3YOrD8Nxy7fVOainN2t6/+PA8k636Yo6Ls6+RobTmswkQbYcPSMtPlj8FHFg3CueOEB1FDXbz0XgfboMu6giopSqvN9Poy/2d0PPd8c4WOTIN0BSRYFJ4+W1suejG/pGittT4zo0OXXkGHL1mU/I2ocZCn7d+mvfD3jnHV88sfg5AVsSQ7/BGEeAEuUlXK1IoPOKpjsyfXnb9fgR2PRYLhktZ9fImHH1Lvq367S9/FY7NxlgnE8N/UB7KO+y4094h72bBlNiEvUu+gJ4V9b3AYZhG3e8ZZQIDAQAB';//调用getPublicKey从支付宝公钥证书中提取公钥
        $aop->alipayrsaPublicKey = $aop->getPublicKey($alipayCertPath);
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'UTF-8';
        $aop->format = 'json';
        $aop->isCheckAlipayPublicCert = true;//是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $aop->appCertSN = $aop->getCertSN($appCertPath);//调用getCertSN获取证书序列号
        $aop->alipayRootCertSN = $aop->getRootCertSN($rootCertPath);//调用getRootCertSN获取支付宝根证书序列号

        $request = new \AlipayFundTransUniTransferRequest();
        $arrData = [
            'out_biz_no' => $ordernumber, // 商户端的唯一订单号，对于同一笔转账请求，商户需保证该订单号唯一。
            // 单笔无密转账到支付宝账户固定
            'trans_amount' => $price, // 订单总金额，单位为元，精确到小数点后两位,
            'product_code' => 'TRANS_ACCOUNT_NO_PWD',
            'biz_scene' => 'DIRECT_TRANSFER', // B2C现金红包、单笔无密转账到支付宝/银行卡
            'order_title' => '测试提现', // 转账业务的标题，用于在支付宝用户的账单里显示
            'payee_info' => [
                'identity' => $account,// 参与方的唯一标识
                'identity_type' => 'ALIPAY_LOGON_ID', // 支付宝登录号，支持邮箱和手机号格式
                'name' => $name, // 参与方真实姓名，如果非空，将校验收款支付宝账号姓名一致性。当identity_type=ALIPAY_LOGON_ID时，本字段必填。
            ],
            'remark' => '测试提现'
        ];
        $request->setBizContent(json_encode($arrData, JSON_UNESCAPED_UNICODE));
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            $withdraw = AppWithdraw::find()->where(['ordernumber'=>$ordernumber])->one();
            $withdraw->state = 2;
            $res = $withdraw->save();
            return $res;
//            $paymessage = AppPaymessage::find()->where(['']);

//            $balance = new AppBalance();

//            echo "成功";
        } else {
//            echo "失败";
            $withdraw = AppWithdraw::find()->where(['ordernumber'=>$ordernumber])->one();
            $withdraw->state = 3;

            $pay = new AppPaymessage();
            $pay->orderid = $withdraw->ordernumber;
            $pay->paynum = $price;
            $pay->create_time = date('Y-m-d H:i:s', time());
            $pay->paytype = 1;
            $pay->type = 1;
            $pay->state = 3;
            $pay->group_id = $withdraw->group_id;

            $balance = new AppBalance();
            $balance->pay_money = $price;
            $balance->order_content = '提现失败退款';
            $balance->action_type = 7;

            $balance->create_time = date('Y-m-d H:i:s', time());
            $balance->ordertype = 2;
            $balance->orderid = $withdraw->id;
            $balance->group_id = $withdraw->group_id;


            $group = AppGroup::find()->where(['id'=>$withdraw->group_id])->one();
            $money = $group->balance;
            $group->balance = $money + $price;

            $transaction = Yii::$app->db->beginTransaction();
            try{
                $res = $withdraw->save();
                $group->save();
                $pay->save();
                $balance->save();
                $transaction->commit();
                return $res;
            }catch (\Exception $e){
                $transaction->rollBack();
                return $res = false;
            }


        }
    }
}