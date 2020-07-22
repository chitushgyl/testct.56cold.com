<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_pay_order".
 *
 * @property string $id
 * @property string $order_id
 * @property string $create_time
 * @property string $update_time
 * @property int $user_id
 * @property int $pay_id
 * @property int $paystate 支付状态1未支付 2支付
 * @property int $group_id
 * @property string $price
 */
class AppPayOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_pay_order';
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => 'update_time',
                //'value'   => new Expression('NOW()'),
                'value'   => function(){return date('Y-m-d H:i:s',time());},
            ],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['create_time', 'update_time'], 'safe'],
            [['paystate', 'group_id','user_id','pay_id'], 'integer'],
            [['price'], 'number'],
            [['order_id'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'user_id' => 'User ID',
            'pay_id' => 'Pay ID',
            'paystate' => 'Paystate',
            'group_id' => 'Group ID',
            'price' => 'Price',
        ];
    }
}
