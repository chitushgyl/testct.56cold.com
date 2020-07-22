<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_common_address".
 *
 * @property int $id
 * @property int $group_id
 * @property string $pro_id
 * @property string $city_id
 * @property string $area_id
 * @property string $address
 * @property string $create_user
 * @property int $create_user_id
 * @property string $use_flag
 * @property string $delete_flag
 * @property string $create_time
 * @property string $update_time
 * @property int $count_views
 * @property string $all 完整地址
 */
class AppCommonAddress extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_common_address';
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
            [['group_id', 'create_user_id', 'count_views'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['pro_id'], 'string', 'max' => 20],
            [['city_id', 'area_id'], 'string', 'max' => 40],
            [['address'], 'string', 'max' => 100],
            [['create_user'], 'string', 'max' => 30],
            [['use_flag', 'delete_flag'], 'string', 'max' => 2],
            [['all'], 'string', 'max' => 255],
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
            'pro_id' => 'Pro ID',
            'city_id' => 'City ID',
            'area_id' => 'Area ID',
            'address' => 'Address',
            'create_user' => 'Create User',
            'create_user_id' => 'Create User ID',
            'use_flag' => 'Use Flag',
            'delete_flag' => 'Delete Flag',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'count_views' => 'Count Views',
            'all' => 'All',
        ];
    }
}
