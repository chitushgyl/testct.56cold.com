<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_line".
 *
 * @property int $id
 * @property string $shiftnumber 线路班次
 * @property string $startcity 起始地
 * @property string $endcity 目的地
 * @property string $price 最低单价
 * @property string $eprice 抛货价
 * @property string $line_price 干线最低收费
 * @property int $group_id 所属公司ID
 * @property int $create_user_id
 * @property string $trunking 时效
 * @property string $pickprice 提货费
 * @property string $sendprice 配送费
 * @property int $picktype 1提货 2自送
 * @property int $sendtype 1 配送 2自提
 * @property string $begin_store 起始仓库地址json
 * @property string $end_store 目的地仓库json
 * @property string $create_time
 * @property string $update_time
 * @property string $start_time 发车时间
 * @property string $time_week 发车时间周
 * @property int $state 状态 1未发车 2 已发车 3已完成 4已取消
 * @property string $delete_flag 'Y'  'N'删除
 * @property string $discount 折扣
 * @property int $line_state 1内部 2上线
 * @property string $all_weight 总重量
 * @property string $all_volume 总体积
 * @property string $weight_price 阶梯价格详情
 * @property string $transfer 中转站
 * @property string $freeweight 免提重量
 * @property string $arrive_time 预计到达时间
 * @property int $line_id 线路模板ID
 * @property string $transfer_info
 * @property int $carriage_id 承运公司
 */
class AppLine extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_line';
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
            [['price', 'eprice', 'line_price', 'pickprice', 'sendprice', 'discount', 'all_weight', 'all_volume', 'freeweight'], 'number'],
            [['group_id', 'create_user_id','carriage_id', 'picktype', 'sendtype', 'state', 'line_state', 'line_id'], 'integer'],
            [['begin_store', 'end_store', 'weight_price', 'transfer_info'], 'string'],
            [['create_time', 'update_time', 'start_time', 'arrive_time'], 'safe'],
            [['shiftnumber', 'startcity', 'endcity'], 'string', 'max' => 30],
            [['trunking', 'time_week'], 'string', 'max' => 10],
            [['delete_flag'], 'string', 'max' => 1],
            [['transfer'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shiftnumber' => 'Shiftnumber',
            'startcity' => 'Startcity',
            'endcity' => 'Endcity',
            'price' => 'Price',
            'eprice' => 'Eprice',
            'line_price' => 'Line Price',
            'group_id' => 'Group ID',
            'create_user_id' => 'Create User ID',
            'trunking' => 'Trunking',
            'pickprice' => 'Pickprice',
            'sendprice' => 'Sendprice',
            'picktype' => 'Picktype',
            'sendtype' => 'Sendtype',
            'begin_store' => 'Begin Store',
            'end_store' => 'End Store',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'start_time' => 'Start Time',
            'time_week' => 'Time Week',
            'state' => 'State',
            'delete_flag' => 'Delete Flag',
            'discount' => 'Discount',
            'line_state' => 'Line State',
            'all_weight' => 'All Weight',
            'all_volume' => 'All Volume',
            'weight_price' => 'Weight Price',
            'transfer' => 'Transfer',
            'freeweight' => 'Freeweight',
            'arrive_time' => 'Arrive Time',
            'line_id' => 'Line ID',
            'transfer_info' => 'Transfer Info',
            'carriage_id' => 'Carriage ID'
        ];
    }
}
