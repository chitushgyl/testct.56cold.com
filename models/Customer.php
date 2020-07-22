<?php
namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "app_customer".
 *
 * @property int $id 索引id
 * @property string $all_name 客户全称
 * @property string $address 详细地址
 * @property string $business 营业执照
 * @property string $use_flag 状态 Y 启用 N禁用
 * @property string $delete_flag
 * @property int $province_id
 * @property int $city_id
 * @property int $area_id
 * @property int $group_id 所属公司
 * @property string $create_time 添加日期
 * @property string $update_time
 * @property string $title 开票抬头
 * @property string $bank 开户银行
 * @property string $bank_number 银行账号
 * @property string $tax_number 税号
 * @property string $com_address 企业地址
 * @property string $com_tel 企业电话
 * @property string $username 账号
 * @property string $password 密码
 */
class Customer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_customer';
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
            [['province_id', 'city_id', 'area_id', 'group_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['all_name', 'address','password'], 'string', 'max' => 32],
            [['business'], 'string', 'max' => 255],
            [['use_flag', 'delete_flag'], 'string', 'max' => 2],
            [['title', 'com_address'], 'string', 'max' => 50],
            [['bank', 'bank_number', 'tax_number','username'], 'string', 'max' => 30],
            [['com_tel'], 'string', 'max' => 11],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'all_name' => 'All Name',
            'address' => 'Address',
            'business' => 'Business',
            'use_flag' => 'Use Flag',
            'delete_flag' => 'Delete Flag',
            'province_id' => 'Province ID',
            'city_id' => 'City ID',
            'area_id' => 'Area ID',
            'group_id' => 'Group ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'title' => 'Title',
            'bank' => 'Bank',
            'bank_number' => 'Bank Number',
            'tax_number' => 'Tax Number',
            'com_address' => 'Com Address',
            'com_tel' => 'Com Tel',
            'username' => 'Username',
            'password' => 'Password',
        ];
    }

    public static function get_list($group_id){
        $list = Customer::find()
            ->where(['use_flag'=>'Y','delete_flag'=>'Y','group_id'=>$group_id])
            ->asArray()
            ->all();
        return $list;
    }
}
