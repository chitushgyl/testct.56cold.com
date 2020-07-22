<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_withdraw".
 *
 * @property string $id
 * @property string $ordernumber 订单号
 * @property string $account 支付宝账号
 * @property string $name 收款人真实姓名
 * @property string $price 金额
 * @property int $state 1提现中 2 提现成功 3提现失败
 * @property int $group_id 归属公司
 * @property string $create_time
 * @property string $update_time
 */
class AppWithdraw extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_withdraw';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['price'], 'number'],
            [['state', 'group_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['ordernumber', 'account'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 30],
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
            'account' => 'Account',
            'name' => 'Name',
            'price' => 'Price',
            'state' => 'State',
            'group_id' => 'Group ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
