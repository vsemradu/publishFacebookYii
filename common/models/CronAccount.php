<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "cron_accounts".
 *
 * @property integer $id
 * @property integer $account_id
 * @property string $status
 * @property string $is_new
 * @property integer $created_at
 * @property integer $update_at
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $do_time
 *
 * @property Accounts $account
 */
class CronAccount extends \yii\db\ActiveRecord
{

    const STATUS_CREATE = 'create';
    const STATUS_UPDATE = 'update';
    const STATUS_INPROGRESS = 'inprogress';
    const TIME_LIMIT = 3600;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cron_accounts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'created_at', 'update_at', 'start_time', 'end_time', 'do_time'], 'integer'],
            [['account_id'], 'required'],
            [['status', 'is_new'], 'string', 'max' => 255],
            [['account_id'], 'exist', 'skipOnError' => true, 'targetClass' => Account::className(), 'targetAttribute' => ['account_id' => 'id']],
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
            'status' => 'Status',
            'is_new' => 'Is New',
            'created_at' => 'Created At',
            'update_at' => 'Update At',
            'do_time' => 'Do time',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(Account::className(), ['id' => 'account_id']);
    }


    public function setStartCronTask()
    {
        $this->update_at = time();
        $this->start_time = time();
        $this->status = self::STATUS_INPROGRESS;
        $this->save();
    }

    public function setEndCronTask()
    {
        $this->update_at = time();
        $this->end_time = time();
        $this->status = self::STATUS_UPDATE;
        $this->save();
    }

    public static function setAccountsToCron($accounts)
    {
        foreach ($accounts as $account) {
            $model = new self();
            $model->account_id = $account->id;
            $model->status = self::STATUS_CREATE;
            $model->is_new = !empty($account->is_new) ? Account::ACCOUNT_NEW : Account::ACCOUNT_OLD;
            $model->created_at = time();
            $model->update_at = time();
            $model->do_time = (time() + self::TIME_LIMIT);
            $model->save();
        }
        return;
    }

    public static function getAccountToGetPost()
    {
        $model = self::find()->where('do_time < :time', ['time' => time()])->orderBy('id ASC')->one();
        return $model;
    }
}
