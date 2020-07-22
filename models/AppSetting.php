<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_setting".
 *
 * @property int $id
 * @property string $name 名称
 * @property string $key
 * @property string $value
 */
class AppSetting extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'string', 'max' => 32],
            [['key'], 'string', 'max' => 30],
            [['value'], 'string', 'max' => 255],
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
            'key' => 'Key',
            'value' => 'Value',
        ];
    }

    // 一单一付
    public static function order_fixed_price(){
        $model = AppSetting::find()->select(['value'])->where(['key'=>'order_fixed_price'])->one();
        return $model->value;
    }    

    // 抽成百分比
    public static function order_percent(){
        $model = AppSetting::find()->select(['value'])->where(['key'=>'order_percent'])->one();
        return $model->value;
    }
}
