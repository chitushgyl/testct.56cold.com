<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_refund".
 *
 * @property string $id
 * @property string $content 退款原因
 * @property int $orderid 订单ID
 * @property string $create_time
 * @property string $update_time
 * @property int $state 1待审核 2审核通过 3审核失败
 * @property int $type 1整车 2零担
 */
class AppRefund extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_refund';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['orderid', 'state', 'type'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['content'], 'string', 'max' => 200],
        ];
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
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'content' => 'Content',
            'orderid' => 'Orderid',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'state' => 'State',
            'type' => 'Type',
        ];
    }
}
