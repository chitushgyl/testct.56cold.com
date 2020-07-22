<?php
/**
 * Created by Joker.
 * Date: 2019/7/25
 * Time: 11:39
 */

namespace app\controllers;

use yii\web\Controller,
    Yii,
    Monolog\Logger;
use app\controllers\api\AddLogController;
use app\libs\ucloud\UcloudApiClient;
use Da\QrCode\QrCode;
use yii\web\Response;

class PublicFunction extends Controller
{
    public $memberInfo;

    /**
     * Desc: 請求外部 api 接口的 curl 方法
     * Created by: Joker
     * Date: 2019/8/12
     * Time: 17:16
     * @param $url
     * @param $param
     * @param bool $post_file
     * @return bool|string
     */
    public static function post($url, $param, $post_file = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }

        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        $data = json_decode($strPOST);
        if ($data && (is_object($data)) || (is_array($data) && !empty($data))) {
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8'
                )
            );
        }
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);

        curl_close($oCurl);

        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * Desc: 導出為excel文件
     * Created by: pysh
     * Date: 2019/9/24
     * Time: 10:37
     * @param $title: 头部信息
     * @param $data: 内容
     * @param $filename: 文件名
     * @param array $w: 列宽
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function excelOut2003($title,$data,$filename,$w=array()){
        $word = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO");
        $objExcel = new \PHPExcel();
        //設置title
        foreach ($title as $k => $v) {
            $objExcel->getActiveSheet()->setCellValue($word[$k].'1',$v);
        }
        //設置内容
        foreach ($data as $k => $v) {
            $i = $k+2;
            foreach ($v as $key => $value) {
                $objExcel->getActiveSheet()->setCellValue($word[$key].$i, $value);
            }
        }
        //設置寬
        if ($w) {
            foreach ($w as $k => $v) {
                $objExcel->getActiveSheet()->getColumnDimension($word[$k])->setWidth($v);
            }
        }
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * Desc: 记录后台的日志
     * Created by: Joker
     * Date: 2019/9/10
     * Time: 12:35
     * @param $data
     * @throws \Exception
     */
    public static function addAdminLog($data){
        $log = new Logger($data['logger']);
        $log->pushHandler(new \Monolog\Handler\StreamHandler(Yii::$app->basePath."/logs/admin/".date('Ymd')."/{$data['logName']}.log", Logger::ERROR));
        $log->error($data['msg'],$data['arr']);
    }

    /**
     * Desc: 记录定时脚本的日志
     * Created by: Joker
     * Date: 2019/9/10
     * Time: 12:35
     * @param $data
     * @throws \Exception
     */
    public static function addCrontabLog($data){
        $log = new Logger($data['logger']);
        $log->pushHandler(new \Monolog\Handler\StreamHandler(Yii::$app->basePath."/logs/crontab/".date('Ymd')."/{$data['logName']}.log", Logger::ERROR));
        $log->error($data['msg'],$data['arr']);
    }

    /**
     * Desc: 记录共通的日志
     * Created by: Joker
     * Date: 2019/9/10
     * Time: 12:35
     * @param $data
     * @throws \Exception
     */
    public static function addLog($data){
        $log = new Logger($data['logger']);
        $log->pushHandler(new \Monolog\Handler\StreamHandler(Yii::$app->basePath."/logs/common/".date('Ymd')."/{$data['logName']}.log", Logger::ERROR));
        $log->error($data['msg'],$data['arr']);
    }

    /**
     * Desc: 公共的返回结果函数
     * Created by: Joker
     * Date: 2019/9/16
     * Time: 15:49
     * @param int $retCode
     * @param array $retData
     * @param array $retMsg
     * @return array
     */
    private static function resultInfo($retCode = 1000,$retData = [],$retMsg = []){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(empty($retMsg['retMsg']) || empty($retMsg['retMsgEn'])){
            $retMsg = Yii::$app->params['retCode'][$retCode];
        }else{
            $retMsg = [
                'cn'=>$retMsg['retMsg'],
                'en'=>$retMsg['retMsgEn'],
            ];
        }
        return ['retCode'=>$retCode,'retMsg'=>$retMsg['cn']??'','retMsgEn'=>$retMsg['en']??'','data'=>$retData];
    }

    /**
     * Desc: 生成签到加密字符串
     * Created by: Joker
     * Date: 2019/9/16
     * Time: 15:50
     * @param $data
     * @return string
     */
    public static function createSignVerifyCode($data){
        ksort($data);
        // 生成 signStr
        $verifyCode = base64_encode(json_encode(['params'=>$data,'signStr'=>create_sign_str($data)]));
        return $verifyCode;
    }

    /**
     * Desc: 公共的生成签到二维码并返回二维码图片地址的接口
     * Created by: Joker
     * Date: 2019/9/16
     * Time: 16:27
     * @param $bc_id
     * @param $member_id
     * @return array
     */
    public  static function signQrCode($bc_id,$member_id){
        $bookInfo = MemberBookClass::findOne(['bc_id'=>$bc_id,'member_id'=>$member_id]);
        if (!$bookInfo){
            return self::resultInfo(2056);
        }

        // 生成簽到信息加密字符串
        $signStr = self::createSignVerifyCode(['bc_id'=>$bc_id]);
        if (!is_dir('image/qrcode')){
            @mkdir('image/qrcode',0777);
        }
        $imagePath = 'image/qrcode/'.date("Ym",strtotime($bookInfo->class_date)).'/'.$signStr;
        if (!is_dir('image/qrcode/'.date("Ym",strtotime($bookInfo->class_date)))){
            @mkdir('image/qrcode/'.date("Ym",strtotime($bookInfo->class_date)),0777);
        }

        if (file_exists($imagePath)){
            return self::resultInfo(1000,['image'=>\Yii::$app->params['PORT_URL'].$imagePath]);
        }else{
            // 將該字符串生成二維碼
            $qrCode = (new QrCode($signStr))
                ->useForegroundColor(0, 0, 0)
                ->useBackgroundColor(255, 255, 255)
                ->useEncoding('UTF-8')
                ->setSize(300)
                ->setMargin(5);

            $flag = $qrCode->writeFile($imagePath);
            if ($flag){
                return self::resultInfo(1000,['image'=>\Yii::$app->params['PORT_URL'].$imagePath]);
            }else{
                return self::resultInfo(2078);
            }
        }
    }

      /**
     * Desc: 发送邮件公共方法
     * Created by: pysh
     * Date: 2020
     * Time: 17:08
     * @param $data
     * @param string $key_word: 
     * @return array
     * @throws \Exception
     */
    public static function sendEmail($data,$key_word=''){
        if(empty($data['to'])){
            return ['code'=>400,"msg"=>"接收方邮箱不能为空!"];
        }
        if($key_word){
            // 替換模板中的內容關鍵字
            $template = 'hahaha';
            if(!$template){
                return ['code'=>400,"msg"=>"邮件模板不存在!"];
            }
            $data['content']['index_url'] = \Yii::$app->params['WEB_URL'];
            // 發送html格式的郵件還是普通文本格式的郵件
//            $body = createContent('woyebuzhidao',$data['content']);
            $body = 'tianxieshenme';
            $subject = '测试发送邮件';

            $from = Yii::$app->params['EMAIL']['senderEmail'];

            try{
                $mailer = Yii::$app->mailer->compose();
                $mailer->setFrom($from);
                $mailer->setTo($data['to']);
                $mailer->setSubject($subject);
                $mailer->setHtmlBody($body);
                $status = $mailer->send();
            }catch(\Exception $e){
                $status = 0;
                $data['error'] = $e->getMessage();
            }

        }else{
            // 按照传过来的信息发送邮件
            $subject = $data['subject'];
            $body = $data['body'];
            $et_id = 0;

            $from = Yii::$app->params['EMAIL']['senderEmail_Info'];

            try{
                $mailer = Yii::$app->mailerInfo->compose();
                $mailer->setFrom($from);
                $mailer->setTo($data['to']);
                $mailer->setSubject($subject);
                $mailer->setHtmlBody($body);
                $status = $mailer->send();
            }catch(\Exception $e){
                $status = 0;
                $data['error'] = $e->getMessage();
            }
        }

        // 添加郵件發送記錄
        if ($status){
            return ['code'=>200,"msg"=>"发送成功!"];
        }else{
            return ['code'=>400,'msg'=>'发送失败'];
        }
    }


}