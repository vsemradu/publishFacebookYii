<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "accounts".
 *
 * @property integer $id
 * @property string $access_token
 * @property string $inside_id
 * @property integer $status
 * @property string $name
 * @property string $link
 * @property string $category
 * @property string $picture
 * @property string $about
 * @property string $cover
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property AccountsUsers[] $accountsUsers
 * @property Posts[] $posts
 */
class Account extends \yii\db\ActiveRecord
{
    const ACCOUNT_NEW = 'new';
    const ACCOUNT_OLD = 'old';

    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_NOT_ACTIVE = 2;

    const SCENARIO_UPDATE = 'update';
    const SCENARIO_INSERT = 'insert';
    public $is_new = false;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'accounts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fb_inside_id'], 'unique'],
            [['fb_access_token', 'fb_inside_id'], 'required'],
            [['status', 'created_at'], 'integer'],
            [['link', 'picture', 'about', 'cover'], 'string'],
            [['fb_access_token', 'fb_inside_id', 'name', 'category'], 'string', 'max' => 255],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED, self::STATUS_NOT_ACTIVE]],
            [['updated_at', 'created_at'], 'default', 'value' => time(), 'on' => [self::SCENARIO_INSERT]],
            ['updated_at', 'default', 'value' => time(), 'on' => [self::SCENARIO_UPDATE]],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fb_access_token' => 'Access Token',
            'fb_inside_id' => 'Inside ID',
            'status' => 'Status',
            'name' => 'Name',
            'link' => 'Link',
            'category' => 'Category',
            'picture' => 'Picture',
            'about' => 'About',
            'cover' => 'Cover',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'is_new' => 'is new',
        ];
    }

    public static function updateAccountFromFacebook($graphNode)
    {
        $account = self::getAccountByFacebookInsightId($graphNode->getField('id'));
        $model = !empty($account) ? $account : new self();
        $model->setScenario(self::SCENARIO_UPDATE);
        if (empty($model->id)) {
            $model->setScenario(self::SCENARIO_INSERT);
            $model->is_new = true;
        }

        $model->fb_access_token = $graphNode->getField('access_token');
        $model->category = $graphNode->getField('category');
        $model->name = $graphNode->getField('name');
        $model->about = $graphNode->getField('about');
        $model->link = $graphNode->getField('link');
        $model->picture = !empty($graphNode->getField('picture')['url']) ? $graphNode->getField('picture')['url'] : '';
        $model->cover = !empty($graphNode->getField('cover')['source']) ? $graphNode->getField('cover')['source'] : '';
        $model->fb_inside_id = $graphNode->getField('id');
        $model->updated_at = time();
        if ($model->save()) {
            return $model;
        }
        return false;
    }

    public static function getAccountByFacebookInsightId($id)
    {
        $model = self::find()->where(['fb_inside_id' => $id])->one();
        return $model;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccountsUsers()
    {
        return $this->hasMany(AccountUser::className(), ['account_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosts()
    {
        return $this->hasMany(Post::className(), ['account_id' => 'id']);
    }

    public function getPostsArrayInsideIdKeys()
    {
        $data = [];
        foreach ($this->posts as $post) {
            $data[$post->fb_inside_id] = $post;
        }
        return $data;
    }
}
