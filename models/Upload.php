<?php
namespace app\models;

use yii\web\UploadedFile;
use Yii;
use yii\base\Model;

class Upload extends Model
{
    public $file;

    public function rules()
    {
        return [
            [['file'], 'file', 'maxFiles' => 10],
        ];
    }

    public function attributeLabels()
    {
        return [
            'file'=>'文件上传'
        ];
     }
}