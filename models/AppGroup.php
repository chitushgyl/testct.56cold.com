<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "app_group".
 *
 * @property int $id
 * @property string $group_name 公司/门店名称
 * @property string $name 联系人
 * @property string $tel 对外联系电话
 * @property string $address 地址
 * @property int $level_id
 * @property int $create_id
 * @property string $create_name
 * @property string $create_time
 * @property string $update_time
 * @property string $use_flag 使用标记（正常Y,删除N，待审核W，审核不通过X）
 * @property string $delete_flag 删除标记（正常Y,删除N）
 * @property int $pro_id 省ID
 * @property int $city_id 区域（市）
 * @property int $area_id 区ID
 * @property int $main_id 主公司ID 默认1主公司 2分公司
 * @property string $expire_time 会员过期时间
 * @property int $group_id 主账号id
 * @property string $balance 余额
 * @property string $creditmoney 信用额度
 * @property int $level 接单信息费支付：1 一单一付 2：每单抽成，3：包月接单
 * @property string $code
 * @property string $now_level_expire 包月支付接单信息费过期时间
 */
class AppGroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_group';
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
            [['group_name'], 'required'],
            [['level_id', 'create_id', 'pro_id', 'city_id', 'area_id', 'main_id', 'group_id', 'level'], 'integer'],
            [['create_time', 'update_time', 'expire_time', 'now_level_expire'], 'safe'],
            [['balance', 'creditmoney'], 'number'],
            [['group_name', 'name', 'create_name'], 'string', 'max' => 30],
            [['tel'], 'string', 'max' => 11],
            [['address'], 'string', 'max' => 100],
            [['use_flag', 'delete_flag'], 'string', 'max' => 1],
            [['code'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_name' => 'Group Name',
            'name' => 'Name',
            'tel' => 'Tel',
            'address' => 'Address',
            'level_id' => 'Level ID',
            'create_id' => 'Create ID',
            'create_name' => 'Create Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'use_flag' => 'Use Flag',
            'delete_flag' => 'Delete Flag',
            'pro_id' => 'Pro ID',
            'city_id' => 'City ID',
            'area_id' => 'Area ID',
            'main_id' => 'Main ID',
            'expire_time' => 'Expire Time',
            'group_id' => 'Group ID',
            'balance' => 'Balance',
            'creditmoney' => 'Creditmoney',
            'level' => 'Level',
            'code' => 'Code',
            'now_level_expire' => 'Now Level Expire',
        ];
    }

    public static function group_list($user){
        $list = [];
        if ($user) {
            if ($user->admin_id == 1 && $user->com_type == 1) {
                $parent_group_id = $user->parent_group_id;
                $list = AppGroup::find()
                    ->select(['id','group_name'])
                    ->where(['id'=>$parent_group_id,'delete_flag'=>'Y'])
                    ->asArray()
                    ->all();
                $list2 = AppGroup::find()
                    ->select(['id','group_name'])
                    ->where(['group_id'=>$parent_group_id,'delete_flag'=>'Y'])
                    ->asArray()
                    ->all();
                $list = array_merge($list,$list2);
                $arr = [];
                $arr_data = [];
                foreach ($list as $k => $v) {
                    if (!in_array($v['id'],$arr)) {
                        $arr[] = $v['id'];
                        $arr_data[] = $v; 
                    }
                }
                $list = $arr_data;
            } else {
                $authority_id = $user->authority_id;
                $role = AppRole::find()->where(['role_id'=>$authority_id])->one();
                $group_id = explode(',',$role->group_id);
                $list = AppGroup::find()
                    ->select(['id','group_name'])
                    ->where(['in','id',$group_id])
                    ->andWhere(['delete_flag'=>'Y'])
                    ->asArray()
                    ->all();
            }
        }
        return $list;
    }

    public static function group_list_arr($user) {
        $list = self::group_list($user);
        $arr = [];
        foreach ($list as $k => $v) {
            $arr[] = $v['id'];
        }
        return $arr;
    }
    /*
     * 查看公司等级
     * */
    public static function group_level($group_id){
        $list = AppGroup::find()->select(['group_name','main_id','group_id','level_id'])->where(['id'=>$group_id])->one();
        if ($list->main_id == 1){
            $data = $list->level_id;
        }else{
            $group_main = AppGroup::find()->select(['group_name','level_id'])->where(['id'=>$list->group_id])->one();
            $data = $group_main->level_id;
        }
        return $data;
    }

}
