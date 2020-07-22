<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_common_contacts".
 *
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string $tel
 * @property string $use_flag
 * @property string $delete_flag
 * @property string $create_user
 * @property int $create_userid
 * @property string $create_time
 * @property string $update_time
 * @property int $views
 */
class AppCommonContacts extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_common_contacts';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'create_userid', 'views'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name', 'create_user'], 'string', 'max' => 30],
            [['tel'], 'string', 'max' => 30],
            [['use_flag', 'delete_flag'], 'string', 'max' => 2],
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
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'name' => 'Name',
            'tel' => 'Tel',
            'use_flag' => 'Use Flag',
            'delete_flag' => 'Delete Flag',
            'create_user' => 'Create User',
            'create_userid' => 'Create Userid',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'views' => 'Views',
        ];
    }
}
