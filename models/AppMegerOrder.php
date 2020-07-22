<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_meger_order".
 *
 * @property string $id
 * @property string $startcity
 * @property string $endcity
 * @property string $startstr
 * @property string $endstr
 * @property int $cartype
 * @property string $volume
 * @property string $number1
 * @property string $number
 * @property string $weight
 * @property int $deal_company
 * @property string $driverinfo
 * @property string $order_ids
 * @property int $group_id
 * @property string $create_time
 * @property string $update_time
 * @property int $state 订单状态 1未接单/待调度 2已接单 3已调度 4已提货 5运输中 6已送达 7已配送 8完成 9取消 0超时
 * @property string $price
 * @property int $type 1自有 2承运商 3临时
 * @property int $ordertype 1 提货单 2配送单
 * @property int $line_state 1 内部 2 上线
 * @property string $temperture
 * @property int $carriage_state 承运商接单 1 未接单 2接单
 */
class AppMegerOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_meger_order';
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
            [['startstr', 'endstr', 'driverinfo', 'order_ids'], 'string'],
            [['cartype', 'deal_company', 'group_id', 'state', 'type', 'ordertype', 'line_state', 'carriage_state'], 'integer'],
            [['volume', 'number1', 'number', 'weight', 'price'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['startcity', 'endcity'], 'string', 'max' => 20],
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
            'startcity' => 'Startcity',
            'endcity' => 'Endcity',
            'startstr' => 'Startstr',
            'endstr' => 'Endstr',
            'cartype' => 'Cartype',
            'volume' => 'Volume',
            'number1' => 'Number1',
            'number' => 'Number',
            'weight' => 'Weight',
            'deal_company' => 'Deal Company',
            'driverinfo' => 'Driverinfo',
            'order_ids' => 'Order Ids',
            'group_id' => 'Group ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'state' => 'State',
            'price' => 'Price',
            'type' => 'Type',
            'ordertype' => 'Ordertype',
            'line_state' => 'Line State',
            'temperture' => 'Temperture',
            'carriage_state' => 'Carriage State',
        ];
    }
}
