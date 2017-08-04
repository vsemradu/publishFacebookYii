<?php

namespace common\components;

use common\models\Account;
use common\models\AccountUser;
use common\models\CronAccount;
use Facebook\PersistentData\FacebookSessionPersistentDataHandler;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use Facebook\Facebook;

class FacebookComponent extends Component
{
    public $facebook;
    public $accessToken;

    public function __construct()
    {
        session_start();
        $this->facebook = new Facebook([
            'app_id' => Yii::$app->params['fb_app_id'],
            'app_secret' => Yii::$app->params['fb_app_secret'],
            'default_graph_version' => Yii::$app->params['fb_graph_version'],
            'persistent_data_handler' => new FacebookSessionPersistentDataHandler(),
        ]);

    }

    public function getAccountPosts($accountCron)
    {
        $posts = [];
        $this->facebook->setDefaultAccessToken($accountCron->account->fb_access_token);
        $since = ($accountCron->is_new == Account::ACCOUNT_OLD) ? Yii::$app->params['fb_since_for_old_posts'] : Yii::$app->params['fb_since_for_new_posts'];
        try {
            $endpoint = '/' . $accountCron->account->fb_inside_id . '/feed/?fields=name,story,message,caption,description,type,full_picture,link,parent_id,updated_time,created_time&limit=100';
            if ($since) {
                $endpoint .= ('&since=' . $since);
            }
            $response = $this->facebook->get($endpoint);
            $graphEdge = $response->getGraphEdge();
            foreach ($graphEdge as $graphNode) {
                $posts[] = $graphNode;
            }
//            do {
//                foreach ($graphEdge as $graphNode) {
//                    $posts[] = $graphNode;
//                }
//            } while ($graphEdge = $this->facebook->next($graphEdge));
        } catch (\Components\Exceptions\FacebookAccessTokenException $e) {
            Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebookCron');
            return false;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebookCron');
            return false;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebookCron');
            return false;
        }

        return $posts;
    }

    public function getAccountsInsight($accounts)
    {
        foreach ($accounts as $account) {


            try {
                $this->facebook->setDefaultAccessToken($account->fb_access_token);
                $response = $this->facebook->get('/' . $account->fb_inside_id . '/insights');
                $graphEdge = $response->getGraphEdge();
                print_r($graphEdge);
            } catch (\Components\Exceptions\FacebookAccessTokenException $e) {
                $user->setNotActive();
                Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebookCron');
                return false;
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                $user->setNotActive();
                Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebookCron');
                return false;
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                $user->setNotActive();
                Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebookCron');
                return false;
            }

        }
    }

    public function getUsersAccounts($users)
    {

        $usersAccounts = [];
        $accounts = [];

        foreach ($users as $user) {

            try {
                $this->facebook->setDefaultAccessToken($user->fb_access_token);
                $response = $this->facebook->get('/me/accounts/?fields=category,name,about,link,picture.type(large),cover,access_token&limit=100');
                $graphEdge = $response->getGraphEdge();
                foreach ($graphEdge as $graphNode) {
                    $account = Account::updateAccountFromFacebook($graphNode);
                    $usersAccounts[$user->id][] = $account;

                    if (empty($accounts[$account->id])) {
                        $accounts[$account->id] = $account;
                    }

                }
            } catch (\Components\Exceptions\FacebookAccessTokenException $e) {
                $user->setNotActive();
                Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebookCron');
                return false;
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                $user->setNotActive();
                Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebookCron');
                return false;
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                $user->setNotActive();
                Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebookCron');
                return false;
            }
        }

        AccountUser::setUsersRelation($usersAccounts);
        return $accounts;
    }

    public function getUrlForLogin()
    {
        $helper = $this->facebook->getRedirectLoginHelper();
        $permissions = Yii::$app->params['fb_user_permissions']; // Optional permissions
        $loginUrl = $helper->getLoginUrl(Yii::$app->urlManager->createAbsoluteUrl(Yii::$app->params['fb_login_redirect_url']), $permissions);
        return $loginUrl;
    }

    public function getUserInfo()
    {
        $this->facebook->setDefaultAccessToken($this->accessToken);
        try {
            $response = $this->facebook->get('/me?fields=email,picture.type(large),first_name,last_name,timezone');
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebook');
            return false;
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            Yii::error('Facebook SDK returned an error: ' . $e->getMessage(), 'facebook');
            return false;
        }

        $user = $response->getGraphUser();
        return $user;
    }

    public function fbLoginCallBack()
    {

        $helper = $this->facebook->getRedirectLoginHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            Yii::error('Graph returned an error: ' . $e->getMessage(), 'facebook');
            return false;
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            Yii::error('Facebook SDK returned an error: ' . $e->getMessage(), 'facebook');
            return false;
        }

        if (!isset($accessToken)) {
            if ($helper->getError()) {
                Yii::error("Error Description: " . $helper->getErrorDescription(), 'facebook');
            } else {
                Yii::error('Bad request', 'facebook');
            }
            return false;
        }


        $oAuth2Client = $this->facebook->getOAuth2Client();

        if (!$accessToken->isLongLived()) {
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                Yii::error("Error getting long-lived access token: " . $helper->getMessage(), 'facebook');
                return false;
            }
            $this->accessToken = $accessToken->getValue();
            return $this->accessToken;
        }
        return false;
    }
}
