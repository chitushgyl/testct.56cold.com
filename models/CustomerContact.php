<?php
namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "app_customer_contact".
 *
 * @property string $id
 * @property string $name
 * @property string $phone
 * @property string $position 职能
 * @property string $create_time
 * @property string $update_time
 * @property int $group_id 所属客户公司
 */
class CustomerContact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_customer_contact';
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
            [['create_time', 'update_time'], 'safe'],
            [['group_id'], 'integer'],
            [['name', 'position'], 'string', 'max' => 30],
            [['phone'], 'string', 'max' => 11],
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
            'phone' => 'Phone',
            'position' => 'Position',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'group_id' => 'Group ID',
        ];
    }
}
