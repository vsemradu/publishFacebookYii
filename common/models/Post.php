<?php

namespace common\models;

use common\components\QueryComponent;
use Yii;

/**
 * This is the model class for table "posts".
 *
 * @property integer $id
 * @property string $fb_inside_id
 * @property integer $account_id
 * @property integer $domain_id
 * @property string $name
 * @property string $message
 * @property string $story
 * @property string $caption
 * @property string $description
 * @property string $type
 * @property string $picture
 * @property string $link
 * @property string $source
 * @property integer $updated_at
 * @property integer $created_at
 * @property integer $fb_created_at
 * @property integer $fb_update_at
 *
 * @property Accounts $account
 * @property Domains $domain
 */
class Post extends \yii\db\ActiveRecord
{
    const POST_TYPE_LINK = 'link';
    const POST_TYPE_STATUS = 'status';
    const POST_TYPE_PHOTO = 'photo';
    const POST_TYPE_VIDEO = 'video';
    const POST_TYPE_OFFER = 'offer';
    const SOURCE_UPLOADED = 'uploaded';
    const SOURCE_SHARED = 'shared';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'posts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fb_inside_id', 'account_id'], 'required'],
            [['account_id', 'domain_id', 'updated_at', 'created_at', 'fb_created_at', 'fb_update_at'], 'integer'],
            [['message', 'description', 'picture', 'link'], 'string'],
            [['fb_inside_id', 'name', 'story', 'caption', 'type', 'source'], 'string', 'max' => 255],
            [['account_id'], 'exist', 'skipOnError' => true, 'targetClass' => Account::className(), 'targetAttribute' => ['account_id' => 'id']],
            [['domain_id'], 'exist', 'skipOnError' => true, 'targetClass' => Domain::className(), 'targetAttribute' => ['domain_id' => 'id']],
            ['type', 'in', 'range' => [self::POST_TYPE_LINK, self::POST_TYPE_STATUS, self::POST_TYPE_PHOTO, self::POST_TYPE_VIDEO, self::POST_TYPE_OFFER]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fb_inside_id' => 'Fb Inside ID',
            'account_id' => 'Account ID',
            'domain_id' => 'Domain ID',
            'name' => 'Name',
            'message' => 'Message',
            'story' => 'Story',
            'caption' => 'Caption',
            'description' => 'Description',
            'type' => 'Type',
            'picture' => 'Picture',
            'link' => 'Link',
            'source' => 'Source',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
            'fb_created_at' => 'Fb Created At',
            'fb_update_at' => 'Fb Update At',
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
    public function getDomain()
    {
        return $this->hasOne(Domain::className(), ['id' => 'domain_id']);
    }



    public static function updatePostFromFacebook($posts, $account)
    {
        $dataFbInsideId = [];
        $dataInsert = [];
        $dataUpdate = [];
        $attributes = self::attributes();

        /*delete id*/
        unset($attributes[0]);
        $attributes = array_values($attributes);
        /*delete id*/

        $oldPosts = $account->postsArrayInsideIdKeys;

        foreach ($posts as $post) {
            $model = !empty($oldPosts[$post->getField('id')]) ? $oldPosts[$post->getField('id')] : new self();
            if (!empty($model->id)) {
                $data['id'] = $model->id;
            }
            $dataFbInsideId[] = $post->getField('id');
            $data['fb_inside_id'] = $post->getField('id');
            $data['account_id'] = $account->id;
            $data['domain_id'] = null;
            $data['name'] = $post->getField('name');
            $data['message'] = $post->getField('message');
            $data['story'] = $post->getField('story');
            $data['caption'] = $post->getField('caption');
            $data['description'] = $post->getField('description');
            $data['type'] = $post->getField('type');
            $data['picture'] = $post->getField('full_picture');
            $data['link'] = $post->getField('link');
            $data['source'] = $post->getField('parent_id') ? self::SOURCE_SHARED : self::SOURCE_UPLOADED;
            $data['updated_at'] = time();
            $data['created_at'] = empty($model->id) ? time() : $model->created_at;
            $data['fb_created_at'] = $post->getField('created_time')->getTimestamp();
            $data['fb_update_at'] = $post->getField('updated_time')->getTimestamp();

            if (!empty($model->id)) {
                $dataUpdate[] = $data;
            } else {
                $dataInsert[] = $data;
            }


        }

        if (!empty($dataInsert)) {
            Yii::$app->db->createCommand()->batchInsert(self::tableName(), $attributes, $dataInsert)->execute();
        }
        if (!empty($dataUpdate)) {
            QueryComponent::batchUpdate(self::tableName(), $attributes, $dataUpdate);
        }
        return $dataFbInsideId;
    }
}
