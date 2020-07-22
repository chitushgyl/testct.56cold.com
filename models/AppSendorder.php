<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_sendorder".
 *
 * @property string $id
 * @property int $order_id
 * @property string $startcity
 * @property string $endcity
 * @property string $startstr_send
 * @property string $endstr_send
 * @property string $create_time
 * @property string $update_time
 * @property string $goodsname
 * @property string $send_volume
 * @property string $send_number
 * @property string $send_number1
 * @property string $send_weight
 * @property string $send_price
 * @property int $status 1 未调度 2 已调度
 * @property int $group_id
 * @property int $carriage_list_id
 * @property string $temperture
 * @property int $order_state 1未配送 2 已配送 3 已取消
 */
class AppSendorder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_sendorder';
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
            [['order_id', 'status', 'group_id', 'carriage_list_id', 'order_state'], 'integer'],
            [['startstr_send', 'endstr_send'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['send_volume', 'send_number', 'send_number1', 'send_weight', 'send_price'], 'number'],
            [['startcity', 'endcity'], 'string', 'max' => 20],
            [['goodsname'], 'string', 'max' => 30],
            [['temperture'], 'string', 'max' => 10],
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
            'startcity' => 'Startcity',
            'endcity' => 'Endcity',
            'startstr_send' => 'Startstr Send',
            'endstr_send' => 'Endstr Send',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'goodsname' => 'Goodsname',
            'send_volume' => 'Send Volume',
            'send_number' => 'Send Number',
            'send_number1' => 'Send Number1',
            'send_weight' => 'Send Weight',
            'send_price' => 'Send Price',
            'status' => 'Status',
            'group_id' => 'Group ID',
            'carriage_list_id' => 'Carriage List ID',
            'temperture' => 'Temperture',
            'orderstatus' => 'Order State',
        ];
    }
}
