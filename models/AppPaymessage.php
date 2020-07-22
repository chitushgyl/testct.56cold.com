<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_paymessage".
 *
 * @property string $id
 * @property int $userid 用户ID
 * @property string $create_time 支付时间
 * @property string $paynum 支付金额
 * @property string $orderid 支付订单号
 * @property string $platformorderid 平台支付交易号
 * @property int $paytype 支付类型（1支付宝2微信3余额
 * @property string $payname 充值账号 
 * @property int $type 1.PC 2.APP
 * @property int $state 1支付，2充值 3退款
 * @property string $pay_result 成功‘SUCCESS’ 失败'FAIL'
 */
class AppPaymessage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_paymessage';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userid', 'paytype', 'type', 'state'], 'integer'],
            [['create_time'], 'safe'],
            [['paynum'], 'number'],
            [['orderid'], 'string', 'max' => 80],
            [['platformorderid', 'payname'], 'string', 'max' => 100],
            [['pay_result'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'Userid',
            'create_time' => 'Create Time',
            'paynum' => 'Paynum',
            'orderid' => 'Orderid',
            'platformorderid' => 'Platformorderid',
            'paytype' => 'Paytype',
            'payname' => 'Payname',
            'type' => 'Type',
            'state' => 'State',
            'pay_result' => 'Pay Result',
        ];
    }
}
