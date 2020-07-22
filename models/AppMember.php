<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_member".
 *
 * @property string $id
 * @property string $level
 * @property string $name
 * @property string $price
 * @property string $create_time
 * @property string $delete_flag
 */
class AppMember extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_member';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['price'], 'number'],
            [['create_time'], 'safe'],
            [['level', 'name'], 'string', 'max' => 255],
            [['delete_flag'], 'string', 'max' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'level' => 'Level',
            'name' => 'Name',
            'price' => 'Price',
            'create_time' => 'Create Time',
            'delete_flag' => 'Delete Flag',
        ];
    }
}
