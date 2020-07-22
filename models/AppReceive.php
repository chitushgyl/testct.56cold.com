<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_receive".
 *
 * @property string $id
 * @property int $order_id 订单ID
 * @property string $receivprice 应收
 * @property string $trueprice 实际应收
 * @property int $compay_id 应收客户公司ID
 * @property string $create_time
 * @property string $update_time
 * @property string $company_name 应收客户公司名称
 * @property string $receive_info 应收详情
 * @property int $group_id 公司ID
 * @property int $create_user_id 创建人ID
 * @property string $create_user_name 创建人名称
 * @property string $name
 * @property string $al_price 已收费用
 * @property string $ul_price 异常扣费
 * @property string $cause 扣费原因
 * @property int $status 状态：1.待收，2.部分收款，3.完成,4.已取消，5.已删除
 * @property string $delete_flag 删除'N’，'Y'
 * @property int $company_type 1 客户 2 赤途 3承运商
 */
class AppReceive extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_receive';
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
            [['order_id', 'compay_id', 'group_id', 'create_user_id', 'status', 'company_type'], 'integer'],
            [['receivprice', 'trueprice', 'al_price', 'ul_price'], 'number'],
            [['create_time', 'update_time','remark'], 'safe'],
            [['receive_info'], 'string'],
            [['company_name', 'create_user_name', 'name'], 'string', 'max' => 30],
            [['cause'], 'string', 'max' => 255],
            [['delete_flag'], 'string', 'max' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'receivprice' => 'Receivprice',
            'trueprice' => 'Trueprice',
            'compay_id' => 'Compay ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'company_name' => 'Company Name',
            'receive_info' => 'Receive Info',
            'group_id' => 'Group ID',
            'create_user_id' => 'Create User ID',
            'create_user_name' => 'Create User Name',
            'name' => 'Name',
            'al_price' => 'Al Price',
            'ul_price' => 'Ul Price',
            'cause' => 'Cause',
            'status' => 'Status',
            'delete_flag' => 'Delete Flag',
            'company_type' => 'Company Type',
            'remark' => 'remark',
        ];
    }
}
