<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "admin_permissions".
 *
 * @property string $id
 * @property string $display_name 显示名称
 * @property string $route 路由名称 例如 admin.index.index
 * @property int $icon_id 图标ID
 * @property int $parent_id 父级权限对应的 id
 * @property int $sort 排序
 * @property string $created_at
 * @property string $updated_at
 * @property int $status 是否是开发者显示，1：是，0：全部显示
 */
class AdminPermissions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'admin_permissions';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['icon_id', 'parent_id', 'sort', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['display_name', 'route'], 'string', 'max' => 255],
        ];
    }
    /**
     * Desc: 獲取登錄用戶的左邊側欄
     * Created by pysh
     * Date: 2020/2/2
     * Time: 16:46
     * @return array
     */
    public static function getList($auth = 1){
        // 获取登入账户信息
        $session = Yii::$app->session;
        $admin_id = $session->get('admin_id');
        $user = Account::findOne(['id' => $admin_id]);
        $list = [];
        if ($user->username == 'admin') {
            Yii::$app->session->set('role_name','超级管理员');
            $list = AdminPermissions::find()
                ->select(['admin_permissions.id','admin_permissions.display_name','admin_permissions.route','admin_permissions.icon_id','admin_permissions.parent_id','admin_icons.class','admin_icons.unicode'])
                ->leftJoin('admin_icons','`admin_icons`.`id`=`admin_permissions`.`icon_id`')
                ->orderBy(['admin_permissions.sort'=> SORT_ASC,'admin_permissions.id'=> SORT_ASC])
                ->asArray()
                ->all();
            //开发者
        } else {
            // 普通子账号
            // 获取登入账户的 id
            $role_id = $user['position'];
            $role_pre = AdminRole::find()->where(['id'=>$role_id])
                ->select(['permissions','role'])
                ->asArray()->one();
            if(!$role_pre){
                return [];
            }
            Yii::$app->session->set('role_name',$role_pre['role']);
            $id = explode(',',$role_pre['permissions']);
            $list = AdminPermissions::find()
                ->select(['admin_permissions.id','admin_permissions.display_name','admin_permissions.route','admin_permissions.icon_id','admin_permissions.parent_id','admin_icons.class','admin_icons.unicode'])
                ->leftJoin('admin_icons','`admin_icons`.`id`=`admin_permissions`.`icon_id`')
                ->where(['admin_permissions.id'=>$id])
                ->orderBy(['admin_permissions.sort'=> SORT_ASC,'admin_permissions.id'=> SORT_ASC])
                ->asArray()
                ->all();
        }
        return $list;
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
            'icon_id' => 'Icon ID',
            'parent_id' => 'Parent ID',
            'sort' => 'Sort',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'status' => 'Status',
        ];
    }
}
