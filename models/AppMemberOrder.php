<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_member_order".
 *
 * @property string $id
 * @property string $ordernumber
 * @property string $month
 * @property int $user_id
 * @property int $price
 * @property string $create_time
 * @property int $state 1未支付2支付
 * @property int $group_id
 */
class AppMemberOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_member_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'price', 'state', 'group_id'], 'integer'],
            [['create_time'], 'safe'],
            [['ordernumber'], 'string', 'max' => 64],
            [['month'], 'string', 'max' => 30],
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
            'month' => 'Month',
            'user_id' => 'User ID',
            'price' => 'Price',
            'create_time' => 'Create Time',
            'state' => 'State',
            'group_id' => 'Group ID',
        ];
    }
}
