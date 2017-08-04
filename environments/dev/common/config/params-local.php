<?php
return [
    'fb_app_id' => '1597821863879960',
    'fb_app_secret' => '8ca5e35fa43311302ef38f8df4a3a397',
    'fb_graph_version' => 'v2.6',
    'fb_login_redirect_url' => 'site/facebook-login',
    'fb_user_permissions' => ['email', 'user_likes', 'read_insights', 'pages_show_list'],

    'fb_since_for_old_posts' => date('Y-m-d\TH:i:s', strtotime('-6month midnight')),
    'fb_since_for_new_posts' => date('Y-m-d\TH:i:s', strtotime('-4year midnight')),
];