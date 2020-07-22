<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_token".
 *
 * @property string $token_id
 * @property int $user_id
 * @property string $last_time
 * @property string $token
 */
class UserToken extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['token'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'token_id' => 'Token ID',
            'user_id' => 'User ID',
            'last_time' => 'Last Time',
            'token' => 'Token',
        ];
    }
}
