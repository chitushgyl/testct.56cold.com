<?php

namespace app\models;

use  Yii;
use  yii\behaviors\TimestampBehavior;
use  yii\db\Expression;

/**
 * This is the model class for table "app_carriage_contact".
 *
 * @property string $id
 * @property string $name
 * @property string $tel
 * @property string $create_time
 * @property string $update_time
 * @property int $carriage_id 所属承运公司
 * @property int $group_id 归属公司
 */
class CarriageContact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_carriage_contact';
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
            [['create_time', 'update_time'], 'safe'],
            [['carriage_id', 'group_id'], 'integer'],
            [['name'], 'string', 'max' => 30],
            [['tel'], 'string', 'max' => 11],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'tel' => 'Tel',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'carriage_id' => 'Carriage ID',
            'group_id' => 'Group ID',
        ];
    }
}
