<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_goods".
 *
 * @property string $id
 * @property string $startstr
 * @property string $endstr
 * @property int $ordertype 1整车 2零担
 * @property string $goodsname
 * @property string $volume
 * @property string $number
 * @property string $weight
 * @property string $price
 * @property string $contact_name
 * @property string $contact_tel
 * @property string $create_time
 * @property string $update_time
 * @property string $remark
 * @property string $use_flag
 * @property string $delete_flag
 * @property int $group_id
 * @property string $startcity
 * @property string $endcity
 */
class AppGoods extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_goods';
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
            [['startstr', 'endstr'], 'string'],
            [['ordertype','group_id'], 'integer'],
            [['volume', 'weight', 'price'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['goodsname', 'contact_name','startcity','endcity'], 'string', 'max' => 20],
            [['number'], 'string', 'max' => 10],
            [['contact_tel'], 'string', 'max' => 11],
            [['remark'], 'string', 'max' => 255],
            [['use_flag', 'delete_flag'], 'string', 'max' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'startstr' => 'Startstr',
            'endstr' => 'Endstr',
            'ordertype' => 'Ordertype',
            'goodsname' => 'Goodsname',
            'volume' => 'Volume',
            'number' => 'Number',
            'weight' => 'Weight',
            'price' => 'Price',
            'contact_name' => 'Contact Name',
            'contact_tel' => 'Contact Tel',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'remark' => 'Remark',
            'use_flag' => 'Use Flag',
            'delete_flag' => 'Delete Flag',
            'group_id' => 'Group Id',
            'startcity' => 'Startcity',
            'endcity' => 'Endcity',
        ];
    }
}
