<?php
namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "app_template".
 *
 * @property string $id
 * @property string $title 模板类别
 * @property string $content
 * @property string $create_time
 * @property int $type 1验证类
 */
class AppTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_template';
    }

    /**
     * @inheritdoc
     */
    // public function behaviors()
    // {
    //     return [
    //         [
    //             'class' => TimestampBehavior::className(),
    //             'createdAtAttribute' => 'create_time',
    //             'updatedAtAttribute' => flase,
    //             //'value'   => new Expression('NOW()'),
    //             'value'   => function(){return date('Y-m-d H:i:s',time());},
    //         ],
    //     ];
    // }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['create_time'], 'safe'],
            [['type'], 'integer'],
            [['title'], 'string', 'max' => 30],
            [['content'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'content' => 'Content',
            'create_time' => 'Create Time',
            'type' => 'Type',
        ];
    }
}
