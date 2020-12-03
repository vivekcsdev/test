<?php
Hook::getInstance()->register('app.menu.bottom.start', function() {
    if (model('user')->isOriginalOwner()) echo view('referral::menu/app');
});
Hook::getInstance()->register('header.after.css', function() {
   echo "<link href='".assetUrl('module/referral/css/style.css')."?time=".time()."' rel='stylesheet'/>";
});

Hook::getInstance()->register('footer.after.js', function() {
   echo "<script src='".assetUrl('module/referral/js/script.js')."?time=".time()."'></script>";
});

Hook::getInstance()->register('controller.loaded', function($c) {
    if ($ref = Request::instance()->input('ref')) {
        $c->model('referral::referral')->processRef($c,$ref);
    }
});

Hook::getInstance()->register('user.signup.finished', function($userid) {
    if (isset($_COOKIE['referral_ref'])) {
        $ref = $_COOKIE['referral_ref'];
        model('referral::referral')->addUser($userid, $ref);
    }
});

Hook::getInstance()->register('extend.admin.other.settings', function() {
   echo view('referral::admin/settings');
});

Hook::getInstance()->register('transaction.added', function($val) {
    $user = model('referral::referral')->findUnProcessed($val['userid']);
    if ($user) {
        $amount = $val['amount'];
        $commission = (config('referral-commission-type', 1) == 1) ? ($amount * config('referral-commission', 30)) / 100 : config('referral-commission', 30);
        Database::getInstance()->query("UPDATE referral_tracking SET package=?,status=?,commission=? WHERE id=?", $val['type'], 1, $commission, $user['id']);

        model('referral::referral')->processPayout($user, $commission);
    }
});

Hook::getInstance()->register('admin.settings.others', function() {
   //echo view('referral::settings');
});

$request->any('referral', array('uses' => 'referral::Referral@index', 'secure' => true));




