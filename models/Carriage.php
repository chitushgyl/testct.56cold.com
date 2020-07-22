<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_carriage".
 *
 * @property int $cid
 * @property string $name 公司名称
 * @property int $provinceid 省ID
 * @property int $areaid 区ID
 * @property int $cityid 市ID
 * @property string $address 详细地址
 * @property string $avatar 营业执照
 * @property string $delete_flag Y开通N删除
 * @property string $use_flag Y启用N禁用
 * @property string $create_time 添加时间
 * @property string $update_time 项目客户余额
 * @property int $group_id 所属公司
 */
class Carriage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_carriage';
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
            [['provinceid', 'areaid', 'cityid', 'group_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name'], 'string', 'max' => 30],
            [['address'], 'string', 'max' => 50],
            [['avatar'], 'string', 'max' => 250],
            [['delete_flag', 'use_flag'], 'string', 'max' => 2],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'cid' => 'Cid',
            'name' => 'Name',
            'provinceid' => 'Provinceid',
            'areaid' => 'Areaid',
            'cityid' => 'Cityid',
            'address' => 'Address',
            'avatar' => 'Avatar',
            'delete_flag' => 'Delete Flag',
            'use_flag' => 'Use Flag',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'group_id' => 'Group ID',
        ];
    }
}
