<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_ip".
 *
 * @property int $id
 * @property string $url 域名
 * @property string $update_time 修改时间
 * @property string $content 后台首页内容
 * @property string $file 文件
 * @property string $use_flag Y:使用，N：未使用
 * @property string $name 公司名称（简称）
 * @property string $full_name 公司全称
 * @property int $group_id 公司id
 * @property string $logo
 * @property string $index
 */
class AppIp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_ip';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['update_time'], 'safe'],
            [['group_id','apply_id'], 'integer'],
            [['logo', 'index'], 'string'],
            [['url', 'name'], 'string', 'max' => 30],
            [['content', 'full_name'], 'string', 'max' => 100],
            [['file'], 'string', 'max' => 20],
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
            'url' => 'Url',
            'update_time' => 'Update Time',
            'content' => 'Content',
            'file' => 'File',
            'use_flag' => 'Use Flag',
            'name' => 'Name',
            'full_name' => 'Full Name',
            'group_id' => 'Group ID',
            'logo' => 'Logo',
            'index' => 'Index',
            'apply_id' => 'apply_id',
        ];
    }
}
