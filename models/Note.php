<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ct_note".
 *
 * @property int $id
 * @property int $admin 主id
 * @property int $uid 操作人
 * @property int $addtime
 * @property string $c 模块
 * @property string $a 操作内容
 */
class Note extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ct_note';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['admin', 'uid', 'addtime'], 'integer'],
            [['c'], 'string', 'max' => 25],
            [['a'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'admin' => 'Admin',
            'uid' => 'Uid',
            'addtime' => 'Addtime',
            'c' => 'C',
            'a' => 'A',
        ];
    }
}
