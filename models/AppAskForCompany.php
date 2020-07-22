<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_ask_for_company".
 *
 * @property string $id
 * @property string $name
 * @property string $group_name 公司名称
 * @property string $address 企业地址
 * @property string $tel 联系电话
 * @property string $image 企业资质
 * @property string $create_time
 * @property string $update_time
 * @property int $state 1认证中 2认证成功 3认证失败
 * @property int $email 邮箱
 * @property int $group_id 归属公司
 * @property int $account_id 归属公司
 */
class AppAskForCompany extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_ask_for_company';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['create_time', 'update_time'], 'safe'],
            [['state','group_id','account_id'], 'integer'],
            [['name'], 'string', 'max' => 20],
            [['group_name', 'address'], 'string', 'max' => 50],
            [['tel'], 'string', 'max' => 11],
            [['image'], 'string', 'max' => 200],
            [['email'], 'string', 'max' => 100],
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
            'group_name' => 'Group Name',
            'address' => 'Address',
            'tel' => 'Tel',
            'image' => 'Image',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'state' => 'State',
            'email' => 'Email',
            'group_id' => 'Group ID',
            'account_id' => 'Account ID'
        ];
    }
}
