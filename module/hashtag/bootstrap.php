<?php

Hook::getInstance()->register('app.menu.start', function() {
    if (model('user')->hasPermission('hashtag', true)) echo view('hashtag::menu/side');
});

Hook::getInstance()->register('post-editor.input', function() {
   if (model('user')->hasPermission('hashtag')) echo view('hashtag::editor');
});

Hook::getInstance()->register('footer.after.js', function() {
    echo "<script src='".assetUrl('module/hashtag/assets/script.js')."?time=".time()."'></script>";
});

$request->any('hashtags', array('uses' => 'hashtag::Hashtag@index', 'secure' => true));




