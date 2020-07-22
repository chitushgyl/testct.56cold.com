<?php
namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "app_auth_left".
 *
 * @property int $id
 * @property string $display_name 显示名称
 * @property string $route 路由名称 例如 /center/index
 * @property int $parent_id 父级权限对应的 id
 * @property int $sort 排序
 * @property string $update_time
 * @property int $status 是否是分公司显示，1：是，2：否
 * @property string $use_flag Y:使用中，N:禁用中
 * @property string $icon 左边栏一级图标
 */
class AppAuthLeft extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_auth_left';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parent_id', 'sort', 'status'], 'integer'],
            [['update_time'], 'safe'],
            [['display_name'], 'string', 'max' => 30],
            [['route'], 'string', 'max' => 50],
            [['use_flag'], 'string', 'max' => 1],
            [['icon'], 'string', 'max' => 25],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'display_name' => 'Display Name',
            'route' => 'Route',
            'parent_id' => 'Parent ID',
            'sort' => 'Sort',
            'update_time' => 'Update Time',
            'status' => 'Status',
            'use_flag' => 'Use Flag',
            'icon' => 'Icon',
        ];
    }



     /**
     * Desc: 獲取用戶的左邊側欄 后台管理
     * Created by pysh
     * Date: 2020/2/2
     * Time: 16:46
     * @return array
     */ 

     public static function admin_get_list(){
        $list = AppAuthLeft::find()
            ->where(['use_flag'=>'Y'])
            ->andWhere(['!=','route','/group_account/index'])
            ->orderBy(['sort'=> SORT_ASC])
            ->asArray()
            ->all();
         return $list;
     }

         /**
     * Desc: 獲取用戶的左邊側欄 系统
     * Created by pysh
     * Date: 2020/2/2
     * Time: 16:46
     * @return array
     */
    public static function getList(){
        $tree = [];
        // $list = AppAuthLeft::find()
        //     ->where(['use_flag'=>'Y'])
        //     ->andWhere(['!=','route','/group_account/index'])
        //     ->orderBy(['sort'=> SORT_ASC])
        //     ->asArray()
        //     ->all();
        $level = AppLevel::find()->where(['level_id'=>3])->one();
        $auth = $level->auth;
        $role_ids = explode(',',$auth);
        $auth = AppAuthLeft::find()
            ->select(['id','display_name','route','parent_id','icon','num'])
            ->where(['use_flag'=>'Y'])
            ->andWhere(['in','id',$role_ids])
            ->andWhere(['!=','route','/group_account/index'])
            ->orderBy(['sort'=>SORT_ASC])
            ->asArray()
            ->all();
        if ($auth) {
            $tree = list_to_tree($auth);
        }
        return $tree;
    }
}
