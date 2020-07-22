<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_pickorder".
 *
 * @property string $id
 * @property int $order_id
 * @property string $startcity
 * @property string $endcity
 * @property string $startstr_pick
 * @property string $endstr_pick
 * @property string $create_time
 * @property string $update_time
 * @property string $goodsname
 * @property string $pick_volume
 * @property string $pick_number
 * @property string $pick_number1
 * @property string $pick_weight
 * @property string $pick_price 价格
 * @property int $state 1 未调度 2 已调度 
 * @property int $group_id
 * @property int $carriage_list_id
 * @property int $pick_id 合单ID
 * @property string $temperture
 * @property int $order_state 1未配送 2已配送 3已取消
 */
class AppPickorder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_pickorder';
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
            [['order_id', 'state', 'group_id', 'carriage_list_id', 'pick_id', 'order_state'], 'integer'],
            [['startstr_pick', 'endstr_pick'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['pick_volume', 'pick_number', 'pick_number1', 'pick_weight', 'pick_price'], 'number'],
            [['startcity', 'endcity'], 'string', 'max' => 20],
            [['goodsname'], 'string', 'max' => 30],
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
            'order_id' => 'Order ID',
            'startcity' => 'Startcity',
            'endcity' => 'Endcity',
            'startstr_pick' => 'Startstr Pick',
            'endstr_pick' => 'Endstr Pick',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'goodsname' => 'Goodsname',
            'pick_volume' => 'Pick Volume',
            'pick_number' => 'Pick Number',
            'pick_number1' => 'Pick Number1',
            'pick_weight' => 'Pick Weight',
            'pick_price' => 'Pick Price',
            'state' => 'State',
            'group_id' => 'Group ID',
            'carriage_list_id' => 'Carriage List ID',
            'pick_id' => 'Pick ID',
            'temperture' => 'Temperture',
            'orderstate' => 'Order State',
        ];
    }
}
