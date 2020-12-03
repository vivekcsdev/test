<?php

Hook::getInstance()->register('header.after.css', function() {
   echo "<link href='".assetUrl('module/help/assets/style.css')."?time=".time()."' rel='stylesheet'/>";
});

Hook::getInstance()->register('footer.after.js', function() {
   echo "<script src='".assetUrl('module/help/assets/script.js')."?time=".time()."'></script>";
   echo view('help::modal');
});

Hook::getInstance()->register('header.icons.extend', function() {
   echo view('help::icon');
});

Hook::getInstance()->register('admin.manage.menu', function() {
    echo view('help::admin/menu');
});
$request->any('help/load', array('uses' => 'help::Help@index', 'secure' => true));

$request->any('help/content', array('uses' => 'help::Help@content', 'secure' => true));

$request->any('admin/tutorials', array('uses' => 'help::Admin@index', 'secure' => true));
$request->any('admin/tutorial/edit/{id}', array('uses' => 'help::Admin@helpEdit', 'secure' => true))->where(array('id' => '[0-9]+'));





