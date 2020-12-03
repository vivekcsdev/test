<?php

Hook::getInstance()->register('app.menu.start', function() {
    if (model('user')->hasPermission('automations', true) and (config('enable-instagram-activity', true))) echo view('automation::menu/side');
});

Hook::getInstance()->register('admin.settings.menu', function() {
    echo view('automation::admin/menu');
});
Hook::getInstance()->register('cronjob.finished', function() {
  model('automation::automation')->runCron();
});
Hook::getInstance()->register('package.add.extend.form', function() {
   echo view('automation::admin/package', array('permissions' => array()));
});
Hook::getInstance()->register('package.edit.extend.form', function($permissions) {
    echo view('automation::admin/package', array('permissions' => $permissions));
});
$request->any('automations', array('uses' => 'automation::Automation@index', 'secure' => true));
$request->any('automations/{id}', array('uses' => 'automation::Automation@index', 'secure' => true))->where(array('id' => '[0-9]+'));
$request->any('automation/get/counts', array('uses' => 'automation::Automation@getCount', 'secure' => true));
$request->any('automations/{type}/fetch/{id}', array('uses' => 'automation::Automation@fetch', 'secure' => true))->where(array('type' => '[a-zA-Z0-9\-]+','id' => '[0-9]+'));

$request->any('admin/automation', array('uses' => 'automation::Admin@index', 'secure' => true));






