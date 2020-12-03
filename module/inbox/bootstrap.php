<?php

Hook::getInstance()->register('app.menu.top', function() {
    echo view('inbox::menu');
});


$request->any('inbox', array('uses' => 'inbox::Inbox@index', 'secure' => true));
$request->any('inbox/load/threads', array('uses' => 'inbox::Inbox@loadThreads', 'secure' => true));
$request->any('inbox/load/chat', array('uses' => 'inbox::Inbox@loadChat', 'secure' => true));
$request->any('inbox/send/chat', array('uses' => 'inbox::Inbox@sendChat', 'secure' => true));

$request->any('inbox/cron', array('uses' => 'inbox::Inbox@cron', 'secure' => false));


