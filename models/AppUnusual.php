<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_unusual".
 *
 * @property int $id
 * @property int $orderid
 * @property int $group_id
 * @property int $create_user_id
 * @property string $content 异常内容
 * @property int $status 异常状态 1 未处理 2处理中 3已解决
 * @property string $create_time
 * @property string $update_time
 * @property string $charging 扣费
 */
class AppUnusual extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_unusual';
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
            [['orderid', 'group_id', 'create_user_id', 'status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['charging'], 'number'],
            [['content'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orderid' => 'Orderid',
            'group_id' => 'Group ID',
            'create_user_id' => 'Create User ID',
            'content' => 'Content',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'charging' => 'Charging',
        ];
    }
}
