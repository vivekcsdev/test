<?php
$request->any('/', array('uses' => 'Home@index', 'secure' => false));
$request->any('login', array('uses' => 'Home@login', 'secure' => false));
$request->any('logout', array('uses' => 'Home@logout', 'secure' => false));
$request->any('signup', array('uses' => 'Home@signup', 'secure' => false));
$request->any('forgot', array('uses' => 'Home@forgot', 'secure' => false));
$request->any('change/language', array('uses' => 'Home@changeLanguage', 'secure' => false));


$request->any('facebook/auth', array('uses' => 'Home@facebookAuth', 'secure' => false));
$request->any('twitter/auth', array('uses' => 'Home@twitterAuth', 'secure' => false));
$request->any('google/auth', array('uses' => 'Home@googleAuth', 'secure' => false));

$request->any('open/menu', array('uses' => 'Home@openMenu', 'secure' => false));
$request->any('go/back/admin', array('uses' => 'Home@goBackAdmin', 'secure' => false));
$request->any('close/menu', array('uses' => 'Home@closeMenu', 'secure' => false));


$request->any('activate/{code}', array('uses' => 'Home@activate', 'secure' => false))->where(array('code' => '[a-zA-Z0-9]+'));
$request->any('reset/{code}', array('uses' => 'Home@reset', 'secure' => false))->where(array('code' => '[a-zA-Z0-9]+'));
$request->any('post/accounts', array('uses' => 'Post@accounts_index', 'secure' => true));
$request->any('post', array('uses' => 'Post@index', 'secure' => true));
$request->any('post/engagement',array('uses' => 'Post@engagement','secure' => true));
$request->any('post/compose',array('uses' => 'Post@compose','secure' => true));
$request->any('post/details',array('uses' => 'Post@details','secure' => true));
$request->any('post/confirm',array('uses' => 'Post@confirm','secure' => true));
$request->any('post/fetch/link', array('uses' => 'Post@fetchLink', 'secure' => true));
$request->any('drafts', array('uses' => 'Post@drafts', 'secure' => true));
$request->any('collection/access/accept/{code}', array('uses' => 'Post@acceptCollection', 'secure' => false))->where(array('code' => '[a-zA-Z0-9]+'));

$request->any('drafts/{id}', array('uses' => 'Post@drafts', 'secure' => true))->where(array('id' => '[0-9]+'));
$request->any('captions', array('uses' => 'Caption@index', 'secure' => true));
$request->any('file-manager', array('uses' => 'Filemanager@index', 'secure' => true));
$request->any('file/open/folder', array('uses' => 'Filemanager@openFolder', 'secure' => true));

$request->any('image/editor', array('uses' => 'Filemanager@imageEditor', 'secure' => true));
$request->any('onedrive', array('uses' => 'Filemanager@onedriveCallback', 'secure' => true));
$request->any('groups', array('uses' => 'Group@index', 'secure' => true));
$request->any('accounts', array('uses' => 'Account@index', 'secure' => true));
$request->any('account/action', array('uses' => 'Account@action', 'secure' => true));
$request->any('accounts/{type}', array('uses' => 'Account@accounts', 'secure' => true))->where(array('type' => '[a-zA-Z0-9]+'));

$request->any('schedules', array('uses' => 'Schedule@index', 'secure' => true));
$request->any('schedules/{type}', array('uses' => 'Schedule@index', 'secure' => true))->where(array('type' => '[a-zA-Z0-9]+'));;
$request->any('schedules/{type}/{social}', array('uses' => 'Schedule@index', 'secure' => true))->where(array('type' => '[a-zA-Z0-9]+', 'social' => '[a-zA-Z0-9]+'));;
$request->any('schedule/posts/{type}/{social}', array('uses' => 'Schedule@posts', 'secure' => true))->where(array('type' => '[a-zA-Z0-9]+', 'social' => '[a-zA-Z0-9]+'));;
$request->any('post/action', array('uses' => 'Post@action', 'secure' => true));

$request->any('reports', array('uses' => 'Report@index', 'secure' => true));
$request->any('reports/{social}', array('uses' => 'Report@index', 'secure' => true))->where(array('social' => '[a-zA-Z0-9]+'));

$request->any('rss', array('uses' => 'Rss@index', 'secure' => true));
$request->any('rss/{id}', array('uses' => 'Rss@page', 'secure' => true))->where(array('id' => '[a-zA-Z0-9]+'));

$request->any('analytics/{id}', array('uses' => 'Analytics@index', 'secure' => true))->where(array('id' => '[a-zA-Z0-9]+'));

$request->any('profile', array('uses' => 'Profile@index', 'secure' => true));
$request->any('manage/team', array('uses' => 'Profile@team', 'secure' => true));$request->any('manage/team', array('uses' => 'Profile@team', 'secure' => true));
$request->any('switch/account', array('uses' => 'Profile@switchAccount', 'secure' => true));
$request->any('activate/invite/{code}', array('uses' => 'Profile@activateInvite', 'secure' => false))->where(array('code' => '[a-zA-Z0-9]+'));
$request->any('delete/account', array('uses' => 'Profile@deleteAccount', 'secure' => true));
$request->any('remove/watermark', array('uses' => 'Profile@removeWatermark', 'secure' => true));


$request->any('admin', array('uses' => 'Admin@index', 'secure' => true));
$request->any('admin/settings', array('uses' => 'Admin@settings', 'secure' => true));
$request->any('admin/social/integration', array('uses' => 'Admin@socialIntegration', 'secure' => true));
$request->any('admin/modules', array('uses' => 'Admin@modules', 'secure' => true));
$request->any('admin/users', array('uses' => 'Admin@users', 'secure' => true));
$request->any('admin/user/edit/{id}', array('uses' => 'Admin@userEdit', 'secure' => true))->where(array('id' => '[0-9]+'));
$request->any('admin/languages', array('uses' => 'Admin@languages', 'secure' => true));
$request->any('admin/language/{id}', array('uses' => 'Admin@languageEdit', 'secure' => true))->where(array('id' => '[0-9a-zA-Z]+'));
$request->any('admin/pages', array('uses' => 'Admin@pages', 'secure' => true));
$request->any('admin/page/edit/{id}', array('uses' => 'Admin@pageEdit', 'secure' => true))->where(array('id' => '[0-9]+'));

$request->any('admin/proxy', array('uses' => 'Admin@proxy', 'secure' => true));
$request->any('admin/design', array('uses' => 'Admin@design', 'secure' => true));
$request->any('admin/design/{id}', array('uses' => 'Admin@design', 'secure' => true))->where(array('id' => '[0-9]+'));

$request->any('admin/update', array('uses' => 'Admin@update', 'secure' => true));

$request->any('cron/run', array('uses' => 'Cron@run', 'secure' => false));

$request->any('{slug}', array('uses' => 'Home@page', 'secure' => false))->where(array('slug' => '[a-zA-Z0-9\-\_]+'));

