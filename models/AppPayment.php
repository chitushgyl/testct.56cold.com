<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_payment".
 *
 * @property string $id
 * @property int $order_id 订单ID
 * @property string $pay_price 应付总价
 * @property string $payment_info 应付详情
 * @property int $group_id 所属公司ID
 * @property int $carriage_id 应付公司ID
 * @property string $carriage_name 应付公司名称
 * @property int $create_user_id 创建人ID
 * @property string $create_user_name 创建人名称
 * @property string $create_time
 * @property string $update_time
 * @property string $name 联系人
 * @property string $truepay 实付总费用
 * @property string $al_pay 已付费用
 * @property string $ul_pay 异常扣费
 * @property string $driver_name
 * @property string $driver_tel
 * @property string $driver_car 车牌号
 * @property int $status 1:待付，2：部分支付，3：完成，4：取消
 * @property int $pay_type 应付对象，2:承运方，3：临时司机 4赤途
 * @property string $case 扣费原因
 */
class AppPayment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_payment';
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
            [['order_id', 'group_id', 'carriage_id', 'create_user_id', 'status', 'pay_type'], 'integer'],
            [['pay_price', 'payment_info', 'truepay', 'al_pay', 'ul_pay'], 'number'],
            [['create_time', 'update_time','remark'], 'safe'],
            [['carriage_name', 'create_user_name', 'name', 'driver_car'], 'string', 'max' => 30],
            [['driver_name', 'driver_tel'], 'string', 'max' => 20],
            [['case'], 'string', 'max' => 255],
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
            'pay_price' => 'Pay Price',
            'payment_info' => 'Payment Info',
            'group_id' => 'Group ID',
            'carriage_id' => 'Carriage ID',
            'carriage_name' => 'Carriage Name',
            'create_user_id' => 'Create User ID',
            'create_user_name' => 'Create User Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'name' => 'Name',
            'truepay' => 'Truepay',
            'al_pay' => 'Al Pay',
            'ul_pay' => 'Ul Pay',
            'driver_name' => 'Driver Name',
            'driver_tel' => 'Driver Tel',
            'driver_car' => 'Driver Car',
            'status' => 'Status',
            'pay_type' => 'Pay Type',
            'case'=> 'Case'
        ];
    }
}
