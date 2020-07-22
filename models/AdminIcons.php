<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "admin_icons".
 *
 * @property int $id
 * @property string $unicode unicode 字符
 * @property string $class 类名
 * @property string $created_at 添加时间
 * @property string $updated_at 修改时间
 */
class AdminIcons extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'admin_icons';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'safe'],
            [['unicode', 'class'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'unicode' => 'Unicode',
            'class' => 'Class',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
