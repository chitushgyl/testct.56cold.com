<?php
namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "app_log".
 *
 * @property int $id
 * @property string $content 操作日志
 * @property string $update_time 时间
 * @property int $admin_id 操作人id
 */
class AppLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_log';
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['update_time'], 'safe'],
            [['admin_id'], 'integer'],
            [['content'], 'string', 'max' => 100],
        ];
    }



    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'content' => 'Content',
            'update_time' => 'Update Time',
            'admin_id' => 'Admin ID',
        ];
    }
}
