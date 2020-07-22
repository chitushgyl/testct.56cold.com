<?php

namespace app\models;

use Yii;
/**
 * This is the model class for table "ct_account".
 *
 * @property int $id 索引id
 * @property string $username 用户名
 * @property string $realname 真实姓名
 * @property string $password 密码
 * @property int $sex 性别 1 男 2 女
 * @property string $phone 电话
 * @property int $role 职位
 * @property string $email 邮箱
 * @property int $state 状态 1 在线 2 删除
 * @property string $addtime 添加时间
 * @property int $status 1货主公司23PL
 * @property string $address 公司地址
 * @property string $type 公司账户分类
 * @property int $qxz 权限组
 * @property int $admin 父级分类
 * @property int $position 职位
 * @property string $weixin
 * @property int $identity
 * @property string $company
 */
class Account extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ct_account';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sex', 'role', 'state', 'status', 'qxz', 'admin', 'position', 'identity'], 'integer'],
            [['username', 'realname', 'password'], 'string', 'max' => 32],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 40],
            [['addtime', 'type', 'weixin'], 'string', 'max' => 30],
            [['address'], 'string', 'max' => 100],
            [['company'], 'string', 'max' => 25],
            [['permissions'], 'string']
        ];
    }       

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'realname' => 'Realname',
            'password' => 'Password',
            'sex' => 'Sex',
            'phone' => 'Phone',
            'role' => 'Role',
            'email' => 'Email',
            'state' => 'State',
            'addtime' => 'Addtime',
            'status' => 'Status',
            'address' => 'Address',
            'type' => 'Type',
            'qxz' => 'Qxz',
            'admin' => 'Admin',
            'position' => 'Position',
            'weixin' => 'Weixin',
            'identity' => 'Identity',
            'company' => 'Company',
            'permissions' => 'permissions'
        ];
    }

}
