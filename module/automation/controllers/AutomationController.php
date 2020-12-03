<?php
class AutomationController extends Controller {
    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveIconMenu('automation');
    }


    public function index() {
        $this->setTitle(l('automations'));

        if (!model('user')->hasPermission('automations', true)) return $this->request->redirect(url('post'));

        $accountId = $this->request->segment(1);

        if ($accountId) {
            $this->subMenuIcon = 'las la-robot';
            $account = $this->model('account')->find($accountId);
            if ($account['social_type'] != 'instagram') return $this->request->redirect(url('automations'));
            $this->model('automation::automation')->ensureRow($accountId);

            if ($val = $this->request->input('val')) {

                $this->defendDemo();
                $this->model('automation::automation')->saveBot($val, $accountId);
                return json_encode(array(
                    'type' => 'function',
                    'message' => 'Automation bot updated'
                ));
            }

            if ($action = $this->request->input('action')) {
                $this->defendDemo();
                if ($action == 'start') {
                    $automation = $this->model('automation::automation')->findByAccount($accountId);
                    $features = ($automation['features']) ? perfectUnserialize($automation['features']) : array();
                    $settings = ($automation['settings']) ? perfectUnserialize($automation['settings']) : array();
                    $actionAvailable = false;
                    foreach($features as $feature => $value) {
                        if ($value) $actionAvailable = true;
                    }
                    if (!$actionAvailable) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('you-need-activate-automation-action')
                        ));
                    }
                    if(empty($settings['tags']) and empty($settings['keywords']) and empty($settings['users'])) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('please-bot-target')
                        ));
                    }

                    if ($features['comments'] and empty($settings['comments'])) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('please-add-bot-comments')
                        ));
                    }

                    if ($features['messages'] and empty($settings['messages'])) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('please-add-bot-messages')
                        ));
                    }

                    //then start automation
                    $this->model('automation::automation')->start($automation);
                    return json_encode(array(
                        'type' => 'url',
                        'value' => getFullUrl(),
                        'message' => l('automation-bot-started')
                    ));
                } elseif($action == 'stop') {
                    $automation = $this->model('automation::automation')->findByAccount($accountId);
                    $this->model('automation::automation')->stop($automation);
                    return json_encode(array(
                        'type' => 'url',
                        'value' => getFullUrl(),
                        'message' => l('automation-bot-stopped')
                    ));
                }
            }
        }
        $accounts = model('automation::automation')->getAvailableAccounts();

        return $this->render($this->view('automation::index', array('accountId' => $accountId, 'accounts' => $accounts)), true);
    }

    public function fetch() {
        $type = $this->request->segment(1);
        $id = $this->request->segment(3);
        $account = $this->model('account')->find($id);
        if ($account['social_type'] == 'instagram') {
            $proxy = model('proxy')->findOneProxy($account['proxy'], $account);
            $instagramApi = $this->api('instagram')->init($account['username'], mDcrypt($account['password']), $proxy);

            if ($type == 'username') {
                $usernames = $instagramApi->searchForUsernames($this->request->input('key'));
                $result = array();
                foreach($usernames as $username) {
                    $result[] = array('value' => $username->username, 'text' => $username->username);
                }
                return json_encode($result);
            } elseif($type == 'tag') {
                $tags = $instagramApi->searchForTags($this->request->input('key'));
                $result = array();
                foreach($tags as $tag) {
                    $result[] = array('value' => $tag->name, 'text' => $tag->name);
                }
                return json_encode($result);
            } else {
                $locations = $instagramApi->searchForLocation($this->request->input('key'));
                $result = array();
                foreach($locations as $location) {
                    $result[] = array('value' => $location->title, 'text' => $location->title);
                }
                return json_encode($result);
            }
        } else {
            $pinterest = $this->api('pinterest');
            if ($pinterest->login($account['access_token'], mDcrypt($account['password']))) {
                if ($type == 'username') {
                    $usernames = $pinterest->searchUsernames($this->request->input('key'));
                    $result = array();
                    foreach($usernames as $pinner) {
                        $result[] = array('value' => $pinner['username'], 'text' => $pinner['username']);
                    }
                    return json_encode($result);
                } else {
                    $tags = $pinterest->searchTags($this->request->input('key'));
                    $result = array();
                    foreach($tags as $tag) {
                        if(isset($tag['query'])) {
                            $tag = str_replace('#','', $tag['query']);
                            $result[] = array('value' => $tag, 'text' => $tag);
                        }
                    }
                    return json_encode($result);
                }
            }
        }
    }

    public function getCount() {
        $account = $this->model('account')->find($this->request->input('id'));
        if ($account['social_type'] == 'instagram') {
            $proxy = model('proxy')->findOneProxy($account['proxy'], $account);
            $instagramApi = $this->api('instagram')->init($account['username'], mDcrypt($account['password']), $proxy);
            $userInfo = $instagramApi->getUserInfo();
            return json_encode(array(
                'posts' => $userInfo->media_count,
                'followers' => $userInfo->follower_count,
                'following' => $userInfo->following_count
            ));
        } else {
            $pinterest = $this->api('pinterest');
            if ($pinterest->login($account['access_token'], mDcrypt($account['password']))) {
                $userInfo = $pinterest->getUserInfo($account['access_token']);
                if ($userInfo) {
                    return json_encode(array(
                        'posts' => $userInfo['pin_count'],
                        'followers' => $userInfo['follower_count'],
                        'following' => $userInfo['following_count']
                    ));
                }
            }
        }
    }

    public function runCron() {

    }
}