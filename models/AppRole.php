<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_role".
 *
 * @property int $role_id
 * @property string $name 角色名称
 * @property string $group_id 公司id
 * @property string $update_time 时间
 * @property string $role_auth 权限
 */
class AppRole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_role';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_id', 'role_auth','top_auth'], 'string'],
            [['update_time'], 'safe'],
            [['name'], 'string', 'max' => 30],
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
                'createdAtAttribute' => false,
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
            'role_id' => 'Role ID',
            'name' => 'Name',
            'group_id' => 'Group ID',
            'update_time' => 'Update Time',
            'role_auth' => 'Role Auth',
            'top_auth' => 'Role Auth',
        ];
    }
}
