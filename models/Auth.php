<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "app_auth".
 *
 * @property string $id
 * @property string $display_name 显示名称
 * @property string $route 路由名称 例如 
 * @property int $parent_id 父级权限对应的 id
 * @property int $sort 排序
 * @property string $update_time
 * @property int $status 是否是主公司显示，1：是，2：否
 * @property string $use_flag Y:使用中，N:禁用中
 */
class Auth extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parent_id', 'sort', 'status'], 'integer'],
            [['update_time'], 'safe'],
            [['display_name'], 'string', 'max' => 30],
            [['route'], 'string', 'max' => 50],
            [['use_flag'], 'string', 'max' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'display_name' => 'Display Name',
            'route' => 'Route',
            'parent_id' => 'Parent ID',
            'sort' => 'Sort',
            'update_time' => 'Update Time',
            'status' => 'Status',
            'use_flag' => 'Use Flag',
        ];
    }
}
