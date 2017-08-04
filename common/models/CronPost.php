<?php

namespace common\models;

use Yii;
use yii\helpers\Json;

/**
 * This is the model class for table "cron_posts".
 *
 * @property integer $id
 * @property integer $account_id
 * @property string $fb_posts_inside_id_json
 * @property string $status
 * @property integer $created_at
 * @property integer $update_at
 * @property integer $do_time
 * @property integer $start_time
 * @property integer $end_time
 *
 * @property Accounts $account
 */
class CronPost extends \yii\db\ActiveRecord
{
    const STATUS_CREATE = 'create';
    const STATUS_UPDATE = 'update';
    const STATUS_INPROGRESS = 'inprogress';
    const TIME_LIMIT = 4000;
    const LIMIT_POST = 3000;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cron_posts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'fb_posts_inside_id_json'], 'required'],
            [['account_id', 'created_at', 'update_at', 'do_time', 'start_time', 'end_time'], 'integer'],
            [['fb_posts_inside_id_json'], 'string'],
            [['status'], 'string', 'max' => 255],
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
            'fb_posts_inside_id_json' => 'Fb Posts Inside Id Json',
            'status' => 'Status',
            'created_at' => 'Created At',
            'update_at' => 'Update At',
            'do_time' => 'Do Time',
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

    public static function generatePostToCron($dataFbInsideId, $account)
    {
        if (empty($dataFbInsideId)) {
            return;
        }
        $posts = array_chunk($dataFbInsideId, self::LIMIT_POST);//slice
        $timeDo = time();
        foreach ($posts as $post) {
            $model = new self();
            $model->account_id = $account->id;
            $model->fb_posts_inside_id_json = Json::encode($post);
            $model->status = self::STATUS_CREATE;
            $model->created_at = time();
            $model->update_at = time();
            $model->do_time = $timeDo;
            $model->save();
            $timeDo = $timeDo + self::TIME_LIMIT;
        }

        return;
    }

    public static function getPostsToGetInsights()
    {
        $model = self::find()->where('do_time < :time', ['time' => time()])->orderBy('id ASC')->one();
        return $model;
    }
}
