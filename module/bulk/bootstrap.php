<?php

Hook::getInstance()->register('app.menu.start', function() {
    if (model('user')->hasPermission('bulk-schedule', true)) echo view('bulk::menu/side');
});

Hook::getInstance()->register('folder-menu-extend', function($file) {
    echo view('bulk::extend-folder', array('file' => $file));
});

Hook::getInstance()->register('cronjob.finished', function() {
   model('bulk::bulk')->runCron();
});
Hook::getInstance()->register('package.add.extend.form', function() {
   echo view('bulk::admin/package', array('permissions' => array()));
});
Hook::getInstance()->register('package.edit.extend.form', function($permissions) {
    echo view('bulk::admin/package', array('permissions' => $permissions));
});
$request->any('bulk/schedule', array('uses' => 'bulk::Bulk@index', 'secure' => true));
$request->any('bulk/create/folder', array('uses' => 'bulk::Bulk@create', 'secure' => true));

$request->any('bulk/schedule/{id}', array('uses' => 'bulk::Bulk@page', 'secure' => true))->where(array('id' => '[0-9]+'));





