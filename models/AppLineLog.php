<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_line_log".
 *
 * @property int $id
 * @property string $startcity
 * @property string $endcity
 * @property string $time 发车时间
 * @property string $begin_store 起始仓或临时停靠地
 * @property string $end_store 目的仓或临时停靠站json
 * @property string $line_price 最低收费
 * @property int $group_id 归属公司
 * @property string $trunking 时效
 * @property int $picktype 1自送 2上门提货
 * @property int $sendtype 1自提 送货上门
 * @property string $pickprice 提货费
 * @property string $sendprice 配送价
 * @property string $use_flag 禁用N
 * @property string $delete_flag 删除N
 * @property int $create_user_id
 * @property string $create_time
 * @property string $update_time
 * @property string $weight_price 重量区间价格
 * @property string $all_weight 总重量
 * @property string $all_volume 总体积
 * @property string $freepick 免提标准
 * @property resource $time_week
 * @property string $centercity 中转城市
 * @property string $center_store 中转仓库
 * @property string $temperture 温度
 * @property int $carriage_id 承运公司
 */
class AppLineLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_line_log';
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
            [['time', 'create_time', 'update_time'], 'safe'],
            [['begin_store', 'end_store', 'trunking', 'weight_price', 'center_store'], 'string'],
            [['line_price', 'pickprice', 'sendprice', 'all_weight', 'all_volume', 'freepick'], 'number'],
            [['group_id', 'picktype', 'sendtype', 'create_user_id','carriage_id'], 'integer'],
            [['startcity', 'endcity', 'centercity'], 'string', 'max' => 10],
            [['use_flag', 'delete_flag'], 'string', 'max' => 1],
            [['time_week'], 'string', 'max' => 30],
            [['temperture'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'startcity' => 'Startcity',
            'endcity' => 'Endcity',
            'time' => 'Time',
            'begin_store' => 'Begin Store',
            'end_store' => 'End Store',
            'line_price' => 'Line Price',
            'group_id' => 'Group ID',
            'trunking' => 'Trunking',
            'picktype' => 'Picktype',
            'sendtype' => 'Sendtype',
            'pickprice' => 'Pickprice',
            'sendprice' => 'Sendprice',
            'use_flag' => 'Use Flag',
            'delete_flag' => 'Delete Flag',
            'create_user_id' => 'Create User ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'weight_price' => 'Weight Price',
            'all_weight' => 'All Weight',
            'all_volume' => 'All Volume',
            'freepick' => 'Freepick',
            'time_week' => 'Time Week',
            'centercity' => 'Centercity',
            'center_store' => 'Center Store',
            'temperture' => 'Temperture',
            'carriage_id' => 'Carriage ID'
        ];
    }
}
