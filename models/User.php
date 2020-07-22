<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "app_admin".
 *
 * @property string $id
 * @property string $login
 * @property string $pwd
 * @property string $name
 * @property string $tel
 * @property string $email
 * @property string $create_user_id
 * @property string $create_user_name
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property int $authority_id 角色ID
 * @property string $use_flag 使用标记（正常Y,删除N）
 * @property string $delete_flag 删除标记（正常Y,删除N）
 * @property int $group_id 默认管理的公司
 * @property string $section_name 部门
 * @property string $section_id 部门id
 * @property string $device_id 设备号
 * @property int $user_id 对应前端的用户ID
 * @property string $true_name 真实姓名
 * @property string $userimage 用户头像
 * @property string $sex 性别:B男 G女
 * @property int $level_id 公司类型ID
 * @property string $balance 余额
 * @property string $salt 密码加密
 * @property string $position 职位
 * @property int $admin_id 是否主账号默认1主账号2子账号
 * @property int $parent_group_id 所属公司ID
 * @property string $creditmoney 信用额度
 * @property string $expire_time 会员过期时间
 * @property int $com_type 1主公司 2 子公司 3职员
 */
class User extends \yii\db\ActiveRecord
{
//    public $token;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_admin';
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
            [['login', 'pwd', 'name', 'authority_id'], 'required'],
            [['create_time', 'update_time', 'expire_time'], 'safe'],
            [['authority_id', 'group_id', 'user_id', 'level_id', 'admin_id', 'parent_group_id'], 'integer'],
            [['balance', 'creditmoney'], 'number'],
            [['login', 'true_name'], 'string', 'max' => 30],
            [['pwd'], 'string', 'max' => 32],
            [['name', 'section_name', 'section_id', 'position'], 'string', 'max' => 20],
            [['tel'], 'string', 'max' => 11],
            [['email', 'create_user_name'], 'string', 'max' => 50],
            [['create_user_id'], 'integer'],
            [['use_flag', 'delete_flag'], 'string', 'max' => 1],
            [['device_id', 'userimage'], 'string', 'max' => 200],
            [['sex'], 'string', 'max' => 2],
            [['salt'], 'string', 'max' => 6],
            [['com_type'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'login' => 'Login',
            'pwd' => 'Pwd',
            'name' => 'Name',
            'tel' => 'Tel',
            'email' => 'Email',
            'create_user_id' => 'Create User ID',
            'create_user_name' => 'Create User Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'authority_id' => 'Authority ID',
            'use_flag' => 'Use Flag',
            'delete_flag' => 'Delete Flag',
            'group_id' => 'Group ID',
            'section_name' => 'Section Name',
            'section_id' => 'Section ID',
            'device_id' => 'Device ID',
            'user_id' => 'User ID',
            'true_name' => 'True Name',
            'userimage' => 'Userimage',
            'sex' => 'Sex',
            'level_id' => 'Level ID',
            'balance' => 'Balance',
            'salt' => 'Salt',
            'position' => 'Position',
            'admin_id' => 'Admin ID',
            'parent_group_id' => 'Parent Group ID',
            'creditmoney' => 'Creditmoney',
            'expire_time' => 'Expire Time',
            'com_type' => 'Com Type',
        ];
    }

    public static function set_admin_id($group_id){
        $flag = User::find()->select(['id'])->where(['group_id'=>$group_id,'admin_id'=>1])->asArray()->all();
        if ($flag) {
            $ids = [];
            foreach($flag as $v) {
                $ids[] = $v['id'];
            }
            User::updateAll(['admin_id'=>2,'update_time'=>date('Y-m-d H:i:s')],['in','id',$ids]);
        }
    }
}
