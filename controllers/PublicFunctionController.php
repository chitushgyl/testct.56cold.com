<?php
/**
 * Created by Joker.
 * Date: 2019/7/10
 * Time: 16:25
 */

namespace app\controllers;

use Monolog\Logger;
use yii\web\Controller,
    Yii,
    yii\web\Response,
    yii\web\UploadedFile;

class PublicFunctionController extends Controller
{
    public $enableCsrfValidation = false;// 不验证 token

    public function resultInfo($retCode = 1000,$retData = [],$retMsg = []){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(empty($retMsg['retMsg']) || empty($retMsg['retMsgEn'])){
            $retMsg = Yii::$app->params['retCode'][$retCode];
        }else{
            $retMsg = [
                'cn'=>$retMsg['retMsg'],
                'en'=>$retMsg['retMsgEn'],
            ];
        }
        return ['retCode'=>$retCode,'retMsg'=>$retMsg['cn']?$retMsg['cn']:'','retMsgEn'=>$retMsg['en']?$retMsg['en']:'','data'=>$retData];
    }

    public function actionUploadTrue(){
        return ['retCode'=>1000,'retMsg'=>''];
    }
    /**
     * Desc: 公共的上傳圖片文件方法
     * Created by Joker
     * Date: 2019/7/11
     * Time: 17:05
     * @return array
     */
    public function actionUploadImg(){
        header("Access-Control-Allow-Origin: *");
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isPost) {
            $type = Yii::$app->request->get("type");
            if ($type){
                $path = 'uploads/image/'.$type;
                // 保存文件
                if(!is_dir($path)){
                    @mkdir($path,0777);
                }
            }else{
                $path = 'uploads/image';
            }
            $imageFile = UploadedFile::getInstanceByName('file');
            if($imageFile->error){
                return (['retCode'=>1001,'retMsg'=>'圖片文件過大!','retMsgEn'=>"Object file too large!"]);
            }
            if(!in_array($imageFile->getExtension(),['jpg','jpng','png'])){
                return (['retCode'=>1002,'retMsg'=>'文件格式不支持!','retMsgEn'=>"File format not supported!"]);
            }
            // 保存文件
            if(!is_dir($path.'/'.date("Ym"))){
                @mkdir($path.'/'.date("Ym"),0777);
            }

            $imageName = $path.'/'.date("Ym").'/' . time() . uniqid() . '.' . $imageFile->extension;
            if($imageFile->saveAs($imageName)){
                return ['retCode'=>1000,'retMsg'=>'保存成功!','data'=>$imageName,'retMsgEn'=>"Fail to save!"];
            }else{
                return ['retCode'=>1004,'retMsg'=>'保存失敗!','retMsgEn'=>"Upload successful!"];
            }
        }else{
            return ['retCode'=>1003,'retMsg'=>'滾犢子,王八犢子!'];
        }
    }
}