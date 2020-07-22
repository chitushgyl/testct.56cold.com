<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_vehical".
 *
 * @property int $id
 * @property string $ordernumber
 * @property string $tradenumber
 * @property int $company_id 业务公司ID
 * @property string $company_name 业务公司
 * @property string $cargo_name 货物名称
 * @property string $cargo_number 货物件数
 * @property string $cargo_number2 冷藏数量
 * @property string $cargo_weight 货物重量
 * @property string $cargo_volume 货物体积
 * @property string $temperture 温度
 * @property string $remark 特别说明：备注
 * @property int $create_user_id
 * @property string $create_user_name
 * @property string $create_time
 * @property string $update_time
 * @property string $delete_flag 删除标记（正常Y,删除N）
 * @property int $group_id 当前公司ID
 * @property string $group_name
 * @property string $receivable 应收总计
 * @property string $receivable_info 应收详情，json
 * @property string $receivable_true 实际应收
 * @property string $payment_true 实际应付
 * @property int $deal_company 处理运单的公司
 * @property string $deal_company_name 处理人id
 * @property string $deal_user 处理运单的人
 * @property string $line_price 上线价格
 * @property int $order_type 订单类型1:整车2：特价
 * @property int $cartype 车辆类型
 * @property int $picktype 1客户装货,2司机装货
 * @property int $sendtype 1客户卸货,2司机卸货
 * @property int $where 1:PC端，2：APP端
 * @property string $payment_info 应付账款详情json
 * @property string $cargo_user 货主姓名
 * @property string $cargo_tel 货主电话
 * @property string $money_state 现付 Y 货到付款 N
 * @property int $carriage_status 运输状态 1
 * @property int $order_status 订单状态 1未接单/待调度 2已接单 3已调度 4已提货 5运输中 6已完成 7已超时 8已取消 
 * @property int $pay_status 支付状态 1未支付 2已支付
 * @property int $line_status 是否上线 1内部 2上线
 * @property int $address_id
 * @property string $startcity 起始城市
 * @property string $endcity 终点城市
 * @property string $startstr 起始地集合
 * @property string $endstr 目的地集合
 * @property string $pickprice 装货费
 * @property string $sendprice 卸货费
 * @property string $otherprice 其他费用
 * @property string $price 运费
 * @property string $oil_card
 * @property string $total_price 费用总计
 * @property int $oil_number
 * @property string $time_start 装车时间
 * @property string $time_end 预计到达时间
 * @property string $time_done 订单完成时间
 * @property string $receipt 回单
 * @property int $order_own 1自有货物 2外来货物
 * @property string $more_price 多点费用
 * @property string $driverinfo 车辆信息
 * @property string $carriage_price 承运价格
 */
class AppVehical extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_vehical';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'create_user_id', 'group_id', 'deal_company', 'order_type', 'cartype', 'picktype', 'sendtype', 'where', 'carriage_status', 'order_status', 'pay_status', 'line_status', 'address_id', 'oil_number', 'order_own'], 'integer'],
            [['cargo_number', 'cargo_number2', 'cargo_weight', 'cargo_volume', 'receivable', 'receivable_true', 'payment_true', 'line_price', 'pickprice', 'sendprice', 'otherprice', 'price', 'oil_card', 'total_price', 'more_price', 'carriage_price'], 'number'],
            [['create_time', 'update_time', 'time_start', 'time_end', 'time_done','paytype'], 'safe'],
            [['receivable_info','receipt','payment_info', 'startstr', 'endstr', 'driverinfo'], 'string'],
            [['price'], 'required'],
            [['ordernumber', 'tradenumber', 'company_name', 'cargo_name', 'group_name'], 'string', 'max' => 50],
            [['temperture', 'startcity', 'endcity'], 'string', 'max' => 20],
            [['remark'], 'string', 'max' => 200],
            [['create_user_name', 'deal_company_name'], 'string', 'max' => 30],
            [['delete_flag', 'money_state'], 'string', 'max' => 1],
            [['deal_user', 'cargo_tel'], 'string', 'max' => 64],
            [['cargo_user'], 'string', 'max' => 25],
        ];
    }
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
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ordernumber' => 'Ordernumber',
            'tradenumber' => 'Tradenumber',
            'company_id' => 'Company ID',
            'company_name' => 'Company Name',
            'cargo_name' => 'Cargo Name',
            'cargo_number' => 'Cargo Number',
            'cargo_number2' => 'Cargo Number2',
            'cargo_weight' => 'Cargo Weight',
            'cargo_volume' => 'Cargo Volume',
            'temperture' => 'Temperture',
            'remark' => 'Remark',
            'create_user_id' => 'Create User ID',
            'create_user_name' => 'Create User Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'delete_flag' => 'Delete Flag',
            'group_id' => 'Group ID',
            'group_name' => 'Group Name',
            'receivable' => 'Receivable',
            'receivable_info' => 'Receivable Info',
            'receivable_true' => 'Receivable True',
            'payment_true' => 'Payment True',
            'deal_company' => 'Deal Company',
            'deal_company_name' => 'Deal Company Name',
            'deal_user' => 'Deal User',
            'line_price' => 'Line Price',
            'order_type' => 'Order Type',
            'cartype' => 'Cartype',
            'picktype' => 'Picktype',
            'sendtype' => 'Sendtype',
            'where' => 'Where',
            'payment_info' => 'Payment Info',
            'cargo_user' => 'Cargo User',
            'cargo_tel' => 'Cargo Tel',
            'money_state' => 'Money State',
            'carriage_status' => 'Carriage Status',
            'order_status' => 'Order Status',
            'pay_status' => 'Pay Status',
            'line_status' => 'Line Status',
            'address_id' => 'Address ID',
            'startcity' => 'Startcity',
            'endcity' => 'Endcity',
            'startstr' => 'Startstr',
            'endstr' => 'Endstr',
            'pickprice' => 'Pickprice',
            'sendprice' => 'Sendprice',
            'otherprice' => 'Otherprice',
            'price' => 'Price',
            'oil_card' => 'Oil Card',
            'total_price' => 'Total Price',
            'oil_number' => 'Oil Number',
            'time_start' => 'Time Start',
            'time_end' => 'Time End',
            'time_done' => 'Time Done',
            'receipt' => 'Receipt',
            'order_own' => 'Order Own',
            'more_price' => 'More Price',
            'driverinfo' => 'Driverinfo',
            'carriage_price' => 'Carriage Price',
            'paytype' => 'paytype',
        ];
    }
}
