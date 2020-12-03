<?php
class AdminController extends Controller {

    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->setSideLayout('admin/layout/menu');
        $this->setActiveIconMenu('settings');
        $this->subMenuIcon = 'la la-cog';
    }

    public function index() {
        if (!moduleExists('dashboard')) return $this->settings();
        $this->setActiveSubMenu('dashboard');
        $stats = array(

        );

        if ($this->model('user')->isAdmin()) {
            $stats['total-users'] = $this->model('admin')->countStatistics('all');
            $stats['total-users-today'] = $this->model('admin')->countByTime('today');
            if (date('w', time()) == 1) {
                $time = 'today';
            } else {
                $time = 'last monday';
            }
            $stats['total-users-this-week'] = $this->model('admin')->countByTime($time);
            $stats['total-users-this-month'] = $this->model('admin')->countByTime('first day of '.date( 'F Y'));
            $stats['total-users-this-year'] = $this->model('admin')->countByTime('first day of january '.date( 'Y'));
            $stats['total-active-users'] = $this->model('admin')->countStatistics(1);
            $stats['total-inactive-users'] = $this->model('admin')->countStatistics('0');
            $stats['total-monthly-users'] = $this->model('admin')->monthlyStatistics();
        }
        return $this->render(view('dashboard::admin', array('stats' => $stats)), true);
    }

    public function users() {
        $this->setTitle(l('users-manager'));
        $this->setActiveSubMenu('users-manager')->setActiveIconMenu('users');

        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'full_name' => 'required',
                    'password' => 'required',
                    'email' => 'required|email|unique:users'
                ));

                if ($validator->passes()) {
                    if ($val['password'] != $val['confirm']) {
                        return json_encode(array(
                            'message' => l('password-does-not-match'),
                            'type' => 'error'
                        ));
                    }
                    $userid = $this->model('user')->addUser($val, true);

                    return json_encode(array(
                        'message' => l('user-created-successful'),
                        'type' => 'url',
                        'value' => url('admin/users')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }

            if (isset($val['bulk_action'])) {
                $ids = $val['id'];
                $this->defendDemo();
                foreach($ids as $id) {
                    $this->model('user')->deleteUser($id);
                }
                return json_encode(array(
                    'message' => l('bulk-action-successful'),
                    'type' => 'url',
                    'value' => url('admin/users')
                ));
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            $this->defendDemo();
            switch($action) {
                case 'delete':
                    $this->model('user')->deleteUser($id);
                    break;
                case 'enable':
                    $this->model('user')->enableUser($id);
                    break;
                case 'disable':
                    $this->model('user')->disableUser($id);
                    break;
                case 'access':
                    session_put('shadow_userid', $id);
                    return json_encode(array(
                        'type' => 'normal-url',
                        'value' => url('post'),
                        'message' => l('you-now-viewing-user')
                    ));
                    break;
            }

            return json_encode(array(
                'message' => l('user-action-successful'),
                'type' => 'url',
                'value' => url('admin/users')
            ));
        }

        $users = $this->model('admin')->getUsers($this->request->input('term'));

        if (isset($_GET['term']) and is_ajax() and !isFullSearch()) {
            return json_encode(array(
                'content' => $this->view('admin/users/display', array('users' => $users)),
                'title' => $this->getTitle(),
            ));
        }

        return $this->render($this->view('admin/users/index', array('users' => $users)),true);
    }

    public function userEdit() {
        $this->setTitle(l('users-manager'));
        $this->setActiveSubMenu('users-manager')->setActiveIconMenu('users');

        $id = $this->request->segment(3);
        $user = $this->model('user')->getUser($id);

        if (!$user) return $this->request->redirect(url('admin/users'));

        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            $validator = Validator::getInstance()->scan($val, array(
                'full_name' => 'required',
                'email' => 'required'
            ));

            if ($validator->passes()) {
                if ($val['password'] and $val['password'] != $val['confirm']) {
                    return json_encode(array(
                        'message' => l('password-does-not-match'),
                        'type' => 'error'
                    ));
                }
                $this->model('user')->adminEditUser($val, $id);

                return json_encode(array(
                    'message' => l('user-save-successful'),
                    'type' => 'url',
                    'value' => url('admin/users')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
        return $this->render($this->view('admin/users/edit', array('user' => $user)),true);
    }

    public function proxy() {
        $this->setTitle(l('proxy-manager'))->setActiveSubMenu('proxy-manager');

        if ($val = $this->request->input('val')) {
            if (isset($val['create'])) {
                $this->defendDemo();
                $validator = Validator::getInstance()->scan($val, array(
                    'address' => 'required|unique:proxies',
                ));

                if ($validator->passes()) {
                    if (!isValidProxy($val['address'])) {
                        return json_encode(array(
                            'message' => l('the-proxy-not-valid'),
                            'type' => 'error'
                        ));
                    }
                    $this->model('proxy')->addProxy($val);

                    return json_encode(array(
                        'message' => l('proxy-created-successful'),
                        'type' => 'url',
                        'value' => url('admin/proxy')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }

            if (isset($val['edit']) and $proxyId = $val['edit']) {
                $this->defendDemo();
                if (!isValidProxy($val['address'])) {
                    return json_encode(array(
                        'message' => l('the-proxy-not-valid'),
                        'type' => 'error'
                    ));
                }
                $this->model('proxy')->adminEditProxy($val, $proxyId);

                return json_encode(array(
                    'message' => l('proxy-save-successful'),
                    'type' => 'modal-url',
                    'content' => '#proxyEditModal'.$proxyId,
                    'value' => url('admin/proxy')
                ));
            }

            if (isset($val['bulk_action'])) {
                $ids = $val['id'];
                $this->defendDemo();
                foreach($ids as $id) {
                    $this->model('proxy')->deleteProxy($id);
                }
                return json_encode(array(
                    'message' => l('bulk-action-successful'),
                    'type' => 'url',
                    'value' => url('admin/proxy')
                ));
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            $this->defendDemo();
            switch($action) {
                case 'delete':
                    $this->model('proxy')->deleteProxy($id);
                    break;
                case 'enable':
                    $this->model('proxy')->enableProxy($id);
                    break;
                case 'disable':
                    $this->model('proxy')->disableProxy($id);
                    break;
            }

            return json_encode(array(
                'message' => l('proxy-action-successful'),
                'type' => 'url',
                'value' => url('admin/proxy')
            ));
        }

        $proxies = $this->model('proxy')->getProxies($this->request->input('term'));

        if (isset($_GET['term']) and is_ajax() and !isFullSearch()) {
            return json_encode(array(
                'content' => $this->view('admin/proxy/display', array('proxies' => $proxies)),
                'title' => $this->getTitle(),
            ));
        }

        return $this->render($this->view('admin/proxy/index', array('proxies' => $proxies)),true);
    }

    public function languages() {
        $this->setTitle(l('languages'))->setActiveSubMenu('languages');

        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'name' => 'required|unique:languages',
                    'code' => 'required|alpha'
                ));

                if ($validator->passes()) {

                    $this->model('admin')->addLanguage($val);

                    return json_encode(array(
                        'message' => l('language-added-successful'),
                        'type' => 'url',
                        'value' => url('admin/languages')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }

            if (isset($val['edit']) and $id = $val['edit']) {

                $this->model('admin')->saveLanguage($val, $id);

                return json_encode(array(
                    'message' => l('language-save-successful'),
                    'type' => 'modal-url',
                    'content' => '#languageEditModal'.$id,
                    'value' => url('admin/languages')
                ));
            }

            if (isset($val['bulk_action'])) {
                $this->defendDemo();
                $ids = $val['id'];
                foreach($ids as $id) {
                    $this->model('admin')->deleteLanguage($id);
                }
                return json_encode(array(
                    'message' => l('bulk-action-successful'),
                    'type' => 'url',
                    'value' => url('admin/languages')
                ));
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            $this->defendDemo();
            switch($action) {
                case 'delete':
                    $this->model('admin')->deleteLanguage($id);
                    break;
            }

            return json_encode(array(
                'message' => l('language-delete-successful'),
                'type' => 'url',
                'value' => url('admin/languages')
            ));
        }

        $languages = $this->model('admin')->getLanguages($this->request->input('term'));
        if (isset($_GET['term']) and is_ajax() and !isFullSearch()) {
            return json_encode(array(
                'content' => $this->view('admin/languages/display-languages', array('languages' => $languages)),
                'title' => $this->getTitle(),
            ));
        }
        return $this->render($this->view('admin/languages/index', array('languages' => $languages)),true);
    }

    public function languageEdit() {
        $this->setTitle(l('languages'))->setActiveSubMenu('languages');

        $id = $this->request->segment(2);

        if( !$this->model('admin')->languageExists($id)) return $this->request->redirect(url('admin/languages'));

        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'lang_key' => 'required|alphadash',
                    'word' => 'required'
                ));

                if ($validator->passes()) {

                   if (!$this->model('admin')->languageWordExists($id, $val['lang_key'])) {
                       $this->model('admin')->addLanguageWord($val, $id);

                       return json_encode(array(
                           'message' => l('word-added-successful'),
                           'type' => 'url',
                           'value' => url('admin/language/'.$id)
                       ));
                   } else {
                       return json_encode(array(
                           'message' => l('word-already-exists'),
                           'type' => 'error'
                       ));
                   }
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }

            if (isset($val['edit']) and $dId = $val['edit']) {
                $this->defendDemo();
                $this->model('admin')->saveLanguageWord($val, $dId);

                return json_encode(array(
                    'message' => l('word-save-successful'),
                    'type' => 'function',
                ));
            }


        }

        if($action = $this->request->input('action') ) {
            $this->defendDemo();
            switch($action) {
                case 'update':
                    $code = $this->request->segment(2);
                    $file = path('languages/'.$code.'.php');

                    if (file_exists($file)) {
                        //incase the user deleted the file so we should do nothing then
                        $translations = include $file;
                        $inserts = '';
                        foreach($translations as $key => $value) {
                            if (!$this->model('admin')->languageWordExists($code, $key)){
                                $value = str_replace("'", "\'", $value);
                                $inserts .= ($inserts) ? ",('$code','$key', '$value','$value')" : "('$code','$key', '$value','$value')";
                            }
                        }
                        if ($inserts) $this->db->query("INSERT INTO translations (lang,lang_key,original,translated) VALUES $inserts ");
                    }
                    break;
            }

            return json_encode(array(
                'message' => l('language-words-updated'),
                'type' => 'url',
                'value' => getFullUrl()
            ));
        }

        $languages = $this->model('admin')->getLanguageWords($id, $this->request->input('term'));
        if (isset($_GET['term']) and is_ajax() and !isFullSearch()) {
            return json_encode(array(
                'content' => $this->view('admin/languages/display-translate', array('languages' => $languages)),
                'title' => $this->getTitle(),
            ));
        }
        return $this->render($this->view('admin/languages/translate', array('languages' => $languages)),true);

    }

    public function pages() {
        $this->setTitle(l('custom-pages'))->setActiveSubMenu('pages');

        if ($val = $this->request->input('val', null, false)) {
            $this->defendDemo();
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'name' => 'required|unique:pages',
                    'slug' => 'required|alphadash|unique:pages',
                    'content' => 'required',
                ));

                if ($validator->passes()) {

                    $this->model('admin')->addPage($val);

                    return json_encode(array(
                        'message' => l('page-added-successful'),
                        'type' => 'url',
                        'value' => url('admin/pages')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }



            if (isset($val['bulk_action'])) {
                $this->defendDemo();
                $ids = $val['id'];
                foreach($ids as $id) {
                    $this->model('admin')->deletePage($id);
                }
                return json_encode(array(
                    'message' => l('bulk-action-successful'),
                    'type' => 'url',
                    'value' => url('admin/pages')
                ));
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            $this->defendDemo();
            switch($action) {
                case 'delete':
                    $this->model('admin')->deletePage($id);
                    break;
                case 'enable':
                    $this->model('admin')->enablePage($id);
                    break;
                case 'disable':
                    $this->model('admin')->disablePage($id);
                    break;
            }

            return json_encode(array(
                'message' => l('page-action-successful'),
                'type' => 'url',
                'value' => url('admin/pages')
            ));
        }


        $pages = $this->model('admin')->getPages($this->request->input('term'));
        if (isset($_GET['term']) and is_ajax() and !isFullSearch()) {
            return json_encode(array(
                'content' => $this->view('admin/pages/display', array('pages' => $pages)),
                'title' => $this->getTitle(),
            ));
        }

        return $this->render($this->view('admin/pages/index', array('pages' => $pages)),true);
    }

    public function pageEdit() {
        $this->setTitle(l('custom-pages'));
        $this->setActiveSubMenu('pages');

        $id = $this->request->segment(3);
        $page = $this->model('admin')->findPage($id);

        if (!$page) return $this->request->redirect(url('admin/pages'));

        if ($val = $this->request->input('val', null, false)) {
            $this->defendDemo();
            $validator = Validator::getInstance()->scan($val, array(
                'name' => 'required',
                'content' => 'required'
            ));

            if ($validator->passes()) {

                $this->model('admin')->savePage($val, $id);

                return json_encode(array(
                    'message' => l('page-save-successful'),
                    'type' => 'url',
                    'value' => url('admin/pages')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
        return $this->render($this->view('admin/pages/edit', array('page' => $page)),true);
    }

    public function modules() {
        $this->setTitle(l('modules'));
        $this->setActiveSubMenu('modules')->setActiveIconMenu('modules');

        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if (isset($val['upload'])) {
                if ($file = $this->request->inputFile('file')) {
                    $uploader = new Uploader($file, 'file');
                    $filepath = '';
                    $uploader->setPath('tmp/modules/');
                    if ($uploader->passed()) {
                        $filepath = $uploader->uploadFile()->result();
                    } else {
                        //there is problem
                        $message  = $uploader->getError();
                        return json_encode(array(
                            'type' => 'error',
                            'message' => $message
                        ));
                    }
                    if ($filepath) {
                        $zip = new ZipArchive;

                        $plugin_dir = path('module');
                        if ($zip->open($filepath) !== TRUE) {
                            $zip->close();
                            return false;
                        }
                        $zip->extractTo($plugin_dir);
                        $zip->close();
                    }

                    return json_encode(array(
                        'type' => 'url',
                        'value' => url('admin/modules'),
                        'message' => l('module-uploaded-success')
                    ));
                } else {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('choose-zip-file-upload')
                    ));
                }

            }
            if ($val['license_required']) {
                if (!$val['activated']) {
                    if (empty($val['license'])) {
                        return json_encode(array(
                            'type' => 'error-function',
                            'value' => 'resetPluginForm',
                            'content' => '#admin-plugins-'.$val['id'],
                            'message' => l('provide-module-license-key')
                        ));
                    } else {
                        if (!$this->model($val['id'].'::'.$val['id'])->validate($val['license'])) {
                            return json_encode(array(
                                'type' => 'error-function',
                                'value' => 'resetPluginForm',
                                'content' => '#admin-plugins-'.$val['id'],
                                'message' => l('invalide-license-key')
                            ));
                        }
                    }
                }
            }
            $this->model('admin')->savePlugins($val['id']);
            return json_encode(array(
                'type' => 'url',
                'message' => l('plugins-list-updated'),
                'value' => url('admin/modules')
            ));
        }

        return $this->render($this->view('admin/modules/index'),true);
    }
    public function socialIntegration() {
        $this->setTitle(l('social-integration'));
        $this->setActiveSubMenu('social')->setActiveIconMenu('settings');

        $message = null;

        if ($val = $this->request->input("val", null)) {
            $this->defendDemo();
            $this->model('admin')->saveSettings($val);
            return json_encode(array(
                'type' => 'success',
                'message' => l('social-integration-saved')
            ));
        }
        return $this->render($this->view('admin/social/index'),true);
    }

    public function settings() {
        $this->setTitle(l('site-settings'));
        $this->setActiveSubMenu('settings')->setActiveIconMenu('settings');

        $message = null;

        if ($val = $this->request->input("val", null, false)) {
            $this->defendDemo();
            $images = $this->request->input('img');
            if ($images) {
                foreach ($images as $image => $value) {
                    $val[$image] = $value;
                    if ($imageFile = $this->request->inputFile($image)) {
                        $uploader = new Uploader($imageFile);
                        $uploader->setPath("settings/");
                        if ($uploader->passed()) {
                            $val[$image] = $uploader->uploadFile()->result();
                        } else {
                            //there is problem
                            $message  = $uploader->getError();
                            return json_encode(array(
                                'type' => 'error',
                                'message' => $message
                            ));
                        }
                    }
                }
            }

            $this->model('admin')->saveSettings($val);
            return json_encode(array(
                'type' => 'success',
                'message' => l('settings-saved')
            ));
        }
        return $this->render($this->view('admin/settings/index'),true);
    }

    public function design() {
        $this->setTitle(l('custom-design'));
        $this->setActiveSubMenu('design')->setActiveIconMenu('settings');

        $design = null;
        if ($designId = $this->request->segment(2)) {
            $design = $this->model('admin')->findDesign($designId);
            if (!$design) return $this->request->redirect(url('admin/design'));
        }

        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if ($design) {
                $this->model('admin')->saveDesign($val, $design['id']);
                return json_encode(array(
                    'type' => 'normal-url',
                    'value' => url('admin/design'),
                    'message' => l('design-saved')
                ));
            } else {
                $this->model('admin')->saveDesign($val);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('admin/design'),
                    'message' => l('new-design-created')
                ));
            }
        }

        if ($action = $this->request->input('action')) {
            $this->defendDemo();
            if ($action == 'default') {
                $this->model('admin')->setDefaultDesign($this->request->input('id'));
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('admin/design'),
                    'message'=> l('set-default-success')
                ));
            }
            if($action == 'delete') {
                $this->model('admin')->deleteDesign($this->request->input('id'));
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('admin/design'),
                    'message'=> l('design-delete-success')
                ));
            }
        }
        return $this->render($this->view('admin/design/index', array('design' => $design)), true);
    }

    public function update() {
        $currentVersion = str_replace('.', '', VERSION);
        if (file_exists(path("update/v$currentVersion.php"))) {
            include(path("update/v$currentVersion.php"));

            if (is_ajax()) {
                return json_encode(array(
                    'type' => 'function',
                    'message' => l('database-updated')
                ));
            } else {
                return $this->request->redirectBack();
            }
        } else {
            if (is_ajax()) {
                return json_encode(array(
                    'type' => 'function',
                    'message' => l('database-updated')
                ));
            } else {
                return $this->request->redirectBack();
            }
        }
    }

}