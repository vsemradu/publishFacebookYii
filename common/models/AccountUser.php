<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "accounts_users".
 *
 * @property integer $id
 * @property integer $account_id
 * @property integer $user_id
 * @property integer $created_at
 *
 * @property Accounts $account
 * @property Users $user
 */
class AccountUser extends \yii\db\ActiveRecord
{

    const SCENARIO_INSERT = 'insert';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'accounts_users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'user_id'], 'required'],
            [['account_id', 'user_id', 'created_at'], 'integer'],
            [['account_id'], 'exist', 'skipOnError' => true, 'targetClass' => Account::className(), 'targetAttribute' => ['account_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            ['created_at', 'default', 'value' => time(), 'on' => [self::SCENARIO_INSERT]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'user_id' => 'User ID',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(Account::className(), ['id' => 'account_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public static function cleanUserRelation($user_id)
    {
        self::deleteAll(['user_id' => $user_id]);
    }

    public static function setUsersRelation($usersAccounts)
    {
        foreach ($usersAccounts as $user_id => $accounts) {
            self::cleanUserRelation($user_id);
            foreach ($accounts as $account) {
                $model = new self();
                $model->setScenario(self::SCENARIO_INSERT);
                $model->account_id = $account->id;
                $model->user_id = $user_id;
                $model->save();
            }
        }
    }
}
