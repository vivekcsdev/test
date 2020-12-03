<?php

Hook::getInstance()->register('app.menu.top', function() {
 echo view('dashboard::menu');
});

Hook::getInstance()->register('header.after.css', function() {
    echo "<link href='".assetUrl('module/dashboard/css/style.css')."?time=".time()."' rel='stylesheet'/>";
});


Hook::getInstance()->register('user.home.url', function($url) {
    $url = url('dashboard');
    return $url;
});



$request->any('dashboard', array('uses' => 'dashboard::Dashboard@index', 'secure' => true));




