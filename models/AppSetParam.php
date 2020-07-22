<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_set_param".
 *
 * @property int $id 卸货费用系数
 * @property int $type 订单类型: 1 城配 2整车
 * @property double $scale_startprice 起步价系数
 * @property double $scale_km 里程偏离系数1-100
 * @property double $scale_km_two 里程偏离系数100-300
 * @property double $scale_km_three 里程偏离系数300-1000
 * @property double $scale_km_four 里程偏离系数1000以上
 * @property double $scale_price_km 单公里价格系数
 * @property double $scale_pickgood 装货费用系数
 * @property double $scale_sendgood 卸货费用系数
 * @property double $scale_multistore 多点提配系数
 * @property double $scale_sameday 当日配送费用系数配送比例
 * @property double $scale_seconday 下单第二天配送费用百分比
 * @property double $scale_moreday 超出下单日两天后费用百分比
 * @property double $scale_discount 优惠折扣 供促销使用 
 * @property double $goback 返程计费标准
 * @property double $earnest
 */
class AppSetParam extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_set_param';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'type'], 'integer'],
            [['type'], 'required'],
            [['scale_startprice', 'scale_km', 'scale_km_two', 'scale_km_three', 'scale_km_four', 'scale_price_km', 'scale_pickgood', 'scale_sendgood', 'scale_multistore', 'scale_sameday', 'scale_seconday', 'scale_moreday', 'scale_discount', 'goback', 'earnest'], 'number'],
            [['type'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'scale_startprice' => 'Scale Startprice',
            'scale_km' => 'Scale Km',
            'scale_km_two' => 'Scale Km Two',
            'scale_km_three' => 'Scale Km Three',
            'scale_km_four' => 'Scale Km Four',
            'scale_price_km' => 'Scale Price Km',
            'scale_pickgood' => 'Scale Pickgood',
            'scale_sendgood' => 'Scale Sendgood',
            'scale_multistore' => 'Scale Multistore',
            'scale_sameday' => 'Scale Sameday',
            'scale_seconday' => 'Scale Seconday',
            'scale_moreday' => 'Scale Moreday',
            'scale_discount' => 'Scale Discount',
            'goback' => 'Goback',
            'earnest' => 'Earnest',
        ];
    }
}
