<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_cartype".
 *
 * @property int $car_id
 * @property string $carparame 车型参数
 * @property string $avatar 车型图片
 * @property string $allweight 承载重量（KG）
 * @property string $allvolume 承载体积（M³）
 * @property string $dimensions 车辆长*宽*高
 * @property string $costkm 每公里价格RMB/KM
 * @property string $lowprice 出车最低价
 * @property string $chartered 市内包车价8h/150km
 * @property string $pickup 装货费用
 * @property string $unload 卸货费用
 * @property string $morepickup 多点提货费用
 * @property string $klio 车速
 * @property int $com_type 公司类型1 3pl 2赤途
 * @property int $typeid 添加公司
 */
class AppCartype extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_cartype';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['costkm', 'lowprice', 'chartered', 'pickup', 'unload', 'morepickup'], 'number'],
            [['com_type', 'typeid'], 'integer'],
            [['carparame'], 'string', 'max' => 100],
            [['avatar'], 'string', 'max' => 255],
            [['allweight', 'allvolume'], 'string', 'max' => 10],
            [['dimensions'], 'string', 'max' => 50],
            [['klio'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'car_id' => 'Car ID',
            'carparame' => 'Carparame',
            'avatar' => 'Avatar',
            'allweight' => 'Allweight',
            'allvolume' => 'Allvolume',
            'dimensions' => 'Dimensions',
            'costkm' => 'Costkm',
            'lowprice' => 'Lowprice',
            'chartered' => 'Chartered',
            'pickup' => 'Pickup',
            'unload' => 'Unload',
            'morepickup' => 'Morepickup',
            'klio' => 'Klio',
            'com_type' => 'Com Type',
            'typeid' => 'Typeid',
        ];
    }

    public static function get_list(){
        $list = AppCartype::find()->select('car_id,carparame,dimensions,allweight,allvolume')->asArray()->all();
        return $list;
    }
}
