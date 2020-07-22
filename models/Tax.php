<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ct_tax".
 *
 * @property string $id
 * @property int $com_type 抬头类型1公司2个人
 * @property string $title 公司抬头
 * @property string $tax_number 税号
 * @property string $bank 开户银行
 * @property string $bank_number
 * @property string $com_address 企业地址
 * @property string $com_tel 企业电话
 * @property string $name 收件人
 * @property string $phone 联系人电话
 * @property string $address 收票地址
 * @property string $detail_address 联系人详细地址
 * @property int $userid 用户ID
 * @property int $orderid 订单ID
 * @property int $ordertype 订单分类1城际2市内3零担
 */
class Tax extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ct_tax';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['com_type', 'userid', 'orderid', 'ordertype'], 'integer'],
            [['title', 'tax_number', 'userid', 'orderid'], 'required'],
            [['title', 'bank', 'com_tel', 'name', 'phone'], 'string', 'max' => 30],
            [['tax_number', 'bank_number', 'com_address', 'address'], 'string', 'max' => 100],
            [['detail_address'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'com_type' => 'Com Type',
            'title' => 'Title',
            'tax_number' => 'Tax Number',
            'bank' => 'Bank',
            'bank_number' => 'Bank Number',
            'com_address' => 'Com Address',
            'com_tel' => 'Com Tel',
            'name' => 'Name',
            'phone' => 'Phone',
            'address' => 'Address',
            'detail_address' => 'Detail Address',
            'userid' => 'Userid',
            'orderid' => 'Orderid',
            'ordertype' => 'Ordertype',
        ];
    }
}
