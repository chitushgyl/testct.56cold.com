<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "count_receive".
 *
 * @property int $id
 * @property int $group_id
 * @property string $day
 * @property string $create_time
 * @property string $price 应付
 * @property string $price_true 实际应f付
 * @property string $receivprice 1应付 2实付 3应收 4实收
 * @property string $trueprice
 */
class CountReceive extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'count_receive';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_id'], 'integer'],
            [['day', 'create_time'], 'safe'],
            [['price', 'price_true', 'receivprice', 'trueprice'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_id' => 'Group ID',
            'day' => 'Day',
            'create_time' => 'Create Time',
            'price' => 'Price',
            'price_true' => 'Price True',
            'receivprice' => 'Receivprice',
            'trueprice' => 'Trueprice',
        ];
    }
}
