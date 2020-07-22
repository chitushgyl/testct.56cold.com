<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_tel_check".
 *
 * @property int $id
 * @property string $user_id
 * @property string $send_type 类型：verify验证码，qita其他
 * @property string $tel 手机号
 * @property string $create_time 发送时间
 * @property string $send_day 发送日
 * @property string $message 发送信息的内容
 * @property string $expired_time 过期时间
 */
class TelCheck extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_tel_check';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [];
    }
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'send_day',
                'updatedAtAttribute' => false,
                //'value'   => new Expression('NOW()'),
                'value'   => function(){return date('Y-m-d',time());},
            ],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'send_type' => 'Send Type',
            'tel' => 'Tel',
            'create_time' => 'Create Time',
            'send_day' => 'Send Day',
            'message' => 'Message',
            'expired_time' => 'Expired Time',
        ];
    }
}
