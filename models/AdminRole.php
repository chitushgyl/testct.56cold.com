<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ct_admin_role".
 *
 * @property int $id
 * @property int $admin admin主账号的id
 * @property string $role 职位/角色
 * @property int $addtime
 * @property string $permissions 职位权限,使用 , 分割
 */
class AdminRole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ct_admin_role';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'role', 'addtime', 'permissions'], 'required'],
            [[ 'addtime'], 'integer'],
            [['permissions'], 'string'],
            [['role'], 'string', 'max' => 25],
        ];
    }

        /**
     * {@inheritdoc}
     */
    public static function getList()
    {
        $list = self::find()
            ->select(['id','role'])
            ->asArray()
            ->all();
        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'role' => 'Role',
            'addtime' => 'Addtime',
            'permissions' => 'Permissions',
        ];
    }
}
