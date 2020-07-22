<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_bulk".
 *
 * @property string $id
 * @property int $ordernumber
 * @property string $goodsname 货品名称
 * @property string $number 件数
 * @property string $weight 重量
 * @property string $volum 体积
 * @property int $shiftid 线路ID
 * @property string $temperture 温度
 * @property string $begincity 起点城市
 * @property string $endcity 目的城市
 * @property string $lineprice 干线价格
 * @property string $pickprice 提货价格
 * @property string $sendprice 配送价格
 * @property int $picktype 1提货 2自送
 * @property int $sendtype 1 配送 2自提
 * @property string $begin_info 发货地址json
 * @property string $end_info 配送地址json
 * @property int $group_id 归属公司
 * @property int $create_user_id
 * @property int $customer_id
 * @property string $create_time
 * @property string $update_time
 * @property string $receipt 回单json
 * @property string $remark 备注
 * @property int $orderstate 订单状态 1未支付 2已接单 
 * @property int $paystate 支付状态 1 未支付 2 已支付
 * @property string $total_price 总价
 * @property string $otherprice 其他费用
 * @property string $line_price 上线价格
 * @property string $edit_price 修改价格
 * @property string $pay_state 结算方式
 * @property string $line_type 1内部干线下单 2外部干线下单
 * @property string $copy 1未复制 2已复制
 */
class AppBulk extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_bulk';
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
            [['shiftid', 'picktype', 'sendtype', 'group_id', 'create_user_id', 'customer_id', 'orderstate', 'copy','paystate','line_type'], 'integer'],
            [['number', 'weight', 'volume', 'lineprice', 'pickprice', 'sendprice', 'total_price', 'otherprice', 'line_price', 'edit_price'], 'number'],
            [['begin_info', 'end_info', 'receipt'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['goodsname','ordernumber'], 'string', 'max' => 30],
            [['temperture', 'begincity', 'endcity'], 'string', 'max' => 20],
            [['remark'], 'string', 'max' => 255],
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
            'goodsname' => 'Goodsname',
            'number' => 'Number',
            'weight' => 'Weight',
            'volume' => 'Volume',
            'shiftid' => 'Shiftid',
            'temperture' => 'Temperture',
            'begincity' => 'Begincity',
            'endcity' => 'Endcity',
            'lineprice' => 'Lineprice',
            'pickprice' => 'Pickprice',
            'sendprice' => 'Sendprice',
            'picktype' => 'Picktype',
            'sendtype' => 'Sendtype',
            'begin_info' => 'Begin Info',
            'end_info' => 'End Info',
            'group_id' => 'Group ID',
            'create_user_id' => 'Create User ID',
            'customer_id' => 'Customer ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'receipt' => 'Receipt',
            'remark' => 'Remark',
            'orderstate' => 'Orderstate',
            'paystate' => 'Paystate',
            'total_price' => 'Total Price',
            'otherprice' => 'Otherprice',
            'line_price' => 'Line Price',
            'edit_price' => 'Edit Price',
            'pay_state' => 'Pay State',
            'line_type' => 'Line Type',
            'copy' => 'Copy'
        ];
    }
}
