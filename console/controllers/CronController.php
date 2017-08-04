<?php

namespace console\controllers;

use common\components\FacebookComponent;
use common\models\CronAccount;
use common\models\CronPost;
use common\models\Post;
use common\models\User;
use yii\console\Controller;
use yii;

/**
 * Test controller
 */
class CronController extends Controller
{

    public function actionAccounts()
    {
        $users = User::getActiveUsers();
        if (empty($users)) {
            return false;
        }
        $facebookComponent = new FacebookComponent();
        $accounts = $facebookComponent->getUsersAccounts($users);


        if (empty($accounts)) {
            return false;
        }
        $insightAccounts = $facebookComponent->getAccountsInsight($accounts);
        CronAccount::setAccountsToCron($accounts);
    }

    public function actionPostsFromAccount()
    {
        $accountCron = CronAccount::getAccountToGetPost();
        $accountCron->setStartCronTask();

        $facebookComponent = new FacebookComponent();
        $posts = $facebookComponent->getAccountPosts($accountCron);
        $dataFbInsideId = Post::updatePostFromFacebook($posts, $accountCron->account);
        if (!empty($dataFbInsideId)) {

            CronPost::generatePostToCron($dataFbInsideId, $accountCron->account);
        }

        $accountCron->setEndCronTask();
    }

    public function actionInsightsFromPosts()
    {
        $postsCron = CronPost::getPostsToGetInsights();
        $postsCron->setStartCronTask();

        $facebookComponent = new FacebookComponent();
//        $posts = $facebookComponent->getAccountPosts($accountCron);
//        $dataFbInsideId = Post::updatePostFromFacebook($posts, $accountCron->account);
//        if (!empty($dataFbInsideId)) {
//
//            CronPost::generatePostToCron($dataFbInsideId, $accountCron->account);
//        }

        $postsCron->setEndCronTask();
    }
}