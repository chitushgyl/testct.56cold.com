<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_balance".
 *
 * @property int $id
 * @property string $pay_money 操作金额
 * @property string $order_content 订单内容: 充值，下单支付，信息费、退款
 * @property int $action_type 操作类型： 1 充值2 扣除余额或信用额度3支付宝4微信
 * @property int $userid 用户ID
 * @property string $create_time 操作时间
 * @property int $orderid 记录的ID
 * @property int $ordertype 1整车 2零担 3 微信充值 4支付宝充值
 */
class AppBalance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_balance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pay_money'], 'number'],
            [['action_type', 'userid', 'orderid', 'ordertype'], 'integer'],
            [['create_time'], 'safe'],
            [['order_content'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pay_money' => 'Pay Money',
            'order_content' => 'Order Content',
            'action_type' => 'Action Type',
            'userid' => 'Userid',
            'create_time' => 'Create Time',
            'orderid' => 'Orderid',
            'ordertype' => 'Ordertype',
        ];
    }
}
