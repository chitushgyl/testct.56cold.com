<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_carriage_list".
 *
 * @property int $id
 * @property int $order_id 订单号ID
 * @property string $carriage_number 运单号
 * @property int $group_id 所属公司名称
 * @property string $group_name 公司名称
 * @property int $create_user_id
 * @property string $create_user_name
 * @property string $carriage_price
 * @property string $driver_info
 * @property int $type
 * @property string $create_time
 * @property string $update_time
 * @property int $deal_company 承运公司
 * @property string $deal_company_name
 * @property string $contant
 * @property string $carnumber
 * @property string $tel
 */
class AppCarriageList extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_carriage_list';
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
            [['order_id', 'group_id', 'create_user_id', 'type', 'deal_company'], 'integer'],
            [['carriage_price'], 'number'],
            [['driver_info'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['carriage_number', 'group_name', 'create_user_name', 'deal_company_name'], 'string', 'max' => 30],
            [['contant', 'carnumber', 'tel'], 'string', 'max' => 20],
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
            'carriage_number' => 'Carriage Number',
            'group_id' => 'Group ID',
            'group_name' => 'Group Name',
            'create_user_id' => 'Create User ID',
            'create_user_name' => 'Create User Name',
            'carriage_price' => 'Carriage Price',
            'driver_info' => 'Driver Info',
            'type' => 'Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'deal_company' => 'Deal Company',
            'deal_company_name' => 'Deal Company Name',
            'contant' => 'Contant',
            'carnumber' => 'Carnumber',
            'tel' => 'Tel',
        ];
    }
}
