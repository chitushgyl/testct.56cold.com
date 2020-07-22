<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ct_district".
 *
 * @property string $id
 * @property string $name
 * @property int $level
 * @property string $parent_id
 */
class District extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ct_district';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['level', 'parent_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
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
            'level' => 'Level',
            'parent_id' => 'Parent ID',
        ];
    }
}
