<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_ip_apply".
 *
 * @property int $id
 * @property string $name 公司名称简称
 * @property string $full_name 公司全称
 * @property string $url 申请域名
 * @property string $create_time
 * @property string $update_time
 * @property int $status 状态，1：通过，2：待处理，3：失败,4:取消
 * @property int $group_id 公司id
 * @property string $remark 备注：失败说明
 * @property string $logo logo图片
 * @property string $index 首页logo图片
 */
class AppIpApply extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_ip_apply';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['create_time', 'update_time'], 'safe'],
            [['status', 'group_id'], 'integer'],
            [['logo', 'index'], 'string'],
            [['name', 'url'], 'string', 'max' => 30],
            [['full_name'], 'string', 'max' => 100],
            [['remark'], 'string', 'max' => 64],
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
            'full_name' => 'Full Name',
            'url' => 'Url',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'status' => 'Status',
            'group_id' => 'Group ID',
            'remark' => 'Remark',
            'logo' => 'Logo',
            'index' => 'Index',
        ];
    }
}
