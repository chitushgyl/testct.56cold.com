<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_car".
 *
 * @property string $id
 * @property string $carnumber 车牌号
 * @property string $weight 承重
 * @property string $volam 体积
 * @property int $cartype 车型
 * @property string $create_time
 * @property string $update_time
 * @property string $delete_flag
 * @property string $use_flag
 * @property string $group_name 所属公司名称
 * @property int $group_id 所属公司
 * @property string $control 温控类型1冷藏2冷冻3双温
 * @property string $check_time 验车日期
 * @property string $create_name
 * @property string $create_id
 * @property int $status 车辆状态 1 未审核 2 已审核 3未通过
 * @property string $license 行驶证
 * @property string $medallion 营运证
 * @property string $board_time 初始上牌日期
 * @property string $carimage 车辆45度照片
 * @property string $driver_name 联系人
 * @property string $mobile 联系人电话
 * @property int $type 公司类型 1自有车辆 2 承运商车辆 3临时车辆
 * @property int $state 1空闲 2在途
 */
class Car extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_car';
    }

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
            [['cartype', 'group_id', 'status', 'type', 'state','create_id'], 'integer'],
            [['create_time', 'update_time', 'check_time', 'board_time'], 'safe'],
            [['carnumber'], 'string', 'max' => 20],
            [['weight', 'volam', 'delete_flag', 'use_flag'], 'string', 'max' => 10],
            [['group_name', 'control'], 'string', 'max' => 30],
            [['create_name', 'driver_name'], 'string', 'max' => 25],
            [['license', 'medallion', 'carimage'], 'string', 'max' => 200],
            [['mobile'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'carnumber' => 'Carnumber',
            'weight' => 'Weight',
            'volam' => 'Volam',
            'cartype' => 'Cartype',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'delete_flag' => 'Delete Flag',
            'use_flag' => 'Use Flag',
            'group_name' => 'Group Name',
            'group_id' => 'Group ID',
            'control' => 'Control',
            'check_time' => 'Check Time',
            'create_name' => 'Create Name',
            'create_id' => 'Create ID',
            'status' => 'Status',
            'license' => 'License',
            'medallion' => 'Medallion',
            'board_time' => 'Board Time',
            'carimage' => 'Carimage',
            'driver_name' => 'Driver Name',
            'mobile' => 'Mobile',
            'type' => 'Type',
            'state' => 'State',
        ];
    }
}
