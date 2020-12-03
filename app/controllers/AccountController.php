<?php
class AccountController extends Controller {
    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveMenu('accounts');
        $this->appSideLayout = '';
        $this->setTitle(l('accounts'));
        $this->setActiveIconMenu('accounts');
    }

    public function index() {

        return $this->render($this->view('accounts/index'), true);
    }

    public function accounts() {
        $type = $this->request->segment(1);
        if (!model('user')->canUseSocial($type)) return $this->request->redirect(url('accounts'));
        switch ($type) {
            case 'instagram':
                return $this->instagram();
                break;
            case 'facebook':
                return $this->facebook();
                break;
            case 'twitter':
                return $this->twitter();
                break;
            case 'vk':
                return $this->vk();
                break;
            case 'tumblr':
                return $this->tumblr();
                break;
            case 'google':
                return $this->google();
                break;
            case 'youtube':
                return $this->youtube();
                break;
            case 'linkedin':
                return $this->linkedin();
                break;
            case 'pinterest':
                return $this->pinterest();
                break;
            case 'vimeo':
                return $this->vimeo();
                break;
            case 'dailymotion':
                return $this->dailymotion();
                break;
            case 'reddit':
                return $this->reddit();
                break;
            case 'telegram':
                return $this->telegram();
                break;
        }
    }

    public function vimeo() {
        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('vimeo')->loginUrl()
            ));
        }

        if ($code = $this->request->input('code')) {
            $lib = $this->api('vimeo')->getObject();

            // Get access token
            $data = $lib->accessToken($code, url('accounts/vimeo'));

            // Verify if access token exists
            if ( @$data['body']['access_token'] ) {

                // Get access token
                $token = $data['body']['access_token'];

                // Get user name
                $name = $data['body']['user']['name'];

                // Get user ID
                $userId = str_replace('/users/', '', $data['body']['user']['uri']);

                $account = model('account')->findAccountBySID($userId, 'vimeo');
                if ($account) {
                    $this->model('account')->update(array(
                        'avatar' => 'assets/images/vimeo.png',
                        'username' => $name,
                        'access_token' => $token,
                        'sid' => $userId
                    ), $account['id']);
                } else {
                    $this->model('account')->add(array(
                        'avatar' => 'assets/images/vimeo.png',
                        'username' => $name,
                        'access_token' => $token,
                        'sid' => $userId,
                        'social_type' => 'vimeo',
                    ));
                }
            }
            return $this->request->redirect(url('accounts/vimeo'));
        }

        $accounts = $this->model('account')->getAccounts('vimeo', $this->request->input('term'));

        return $this->render($this->view('accounts/vimeo/index', array('accounts' => $accounts)), true);
    }

    public function dailymotion(){
        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('dailymotion')->loginUrl()
            ));
        }

        if ($code = $this->request->input('code')) {
            $token = $this->api('dailymotion')->getToken($code);

            if(@$token->access_token) {
                $refresh = $token->refresh_token;

                // Get access token
                $accessToken = $token->access_token;

                // we will use the token to get user data
                $curl = curl_init();

                // Set some options - we are passing in a useragent too here
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://api.dailymotion.com/me?access_token=' . $accessToken,
                    CURLOPT_HEADER => false
                ));

                // Send the request & save response to $resp
                $udata = curl_exec($curl);

                // Close request to clear up some resources
                curl_close($curl);

                // Verify if the request was done successfully
                if ( $udata ) {

                    $udecode = json_decode($udata, true);

                    if ( isset($udecode['id']) ) {
                        $name = $udecode['screenname'];
                        $userId = $udecode['id'];
                        $account = model('account')->findAccountBySID($userId, 'dailymotion');
                        if ($account) {
                            $this->model('account')->update(array(
                                'avatar' => 'assets/images/dailymotion.png',
                                'username' => $name,
                                'access_token' => json_encode($token),
                                'sid' => $userId
                            ), $account['id']);
                        } else {
                            $this->model('account')->add(array(
                                'avatar' => 'assets/images/dailymotion.png',
                                'username' => $name,
                                'access_token' => json_encode($token),
                                'sid' => $userId,
                                'social_type' => 'dailymotion',
                            ));
                        }

                    }

                }
            }
        }
        $accounts = $this->model('account')->getAccounts('dailymotion', $this->request->input('term'));

        return $this->render($this->view('accounts/dailymotion/index', array('accounts' => $accounts)), true);
    }

    public function reddit() {
        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('reddit')->loginUrl()
            ));
        }

        if ($code = $this->request->input('code')) {
            $data = $this->api('reddit')->getToken($code);
            // Verify if response is valid
            if ( isset($data['access_token']) ) {

                // Get access token
                $token = $data['access_token'];

                // Get refresh token
                $refresh_token = $data['refresh_token'];

                // Get user data
                $curl = curl_init('https://oauth.reddit.com/api/v1/me');
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $token, 'User-Agent: flairbot/1.0 by '));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                $data = json_decode(curl_exec($curl), true);
                curl_close($curl);


                // Verify if response is valid
                if ( isset($data['name']) ) {

                    // Get user name
                    $name = $data['name'];

                    $account = model('account')->findAccountBySID($name, 'reddit');
                    if ($account) {
                        $this->model('account')->update(array(
                            'avatar' => 'assets/images/reddit.png',
                            'username' => $name,
                            'access_token' => json_encode(array('token' => $token, 'refresh' => $refresh_token)),
                            'sid' => $name
                        ), $account['id']);
                    } else {
                        $this->model('account')->add(array(
                            'avatar' => 'assets/images/reddit.png',
                            'username' => $name,
                            'access_token' => json_encode(array('token' => $token, 'refresh' => $refresh_token)),
                            'sid' => $name,
                            'social_type' => 'reddit',
                        ));
                    }

                }

                return $this->request->redirect(url('accounts/reddit'));

            }
        }

        $accounts = $this->model('account')->getAccounts('reddit', $this->request->input('term'));

        return $this->render($this->view('accounts/reddit/index', array('accounts' => $accounts)), true);
    }

    public function telegram() {

        if ($val = $this->request->input('val')) {
            $data = curl_get_content('https://api.telegram.org/bot' . $val['key'] . '/getUpdates');

            $data = json_decode($data);
            if ( $data ) {


                if (isset($data->result)) {
                    $added = array();
                    foreach ( $data->result as $result ) {
                        $chat_id = null;
                        if (isset($result->channel_post)) {
                            $chat_id = @$result->channel_post->chat->id;
                            $type = 'channel';
                            $title = @$result->channel_post->chat->title;
                        }elseif(isset($result->message)) {
                            $chat_id = @$result->message->chat->id;
                            $type = 'group';
                            $title = @$result->message->chat->title;
                        }

                        if ( $chat_id) {
                            $added[] = $chat_id;
                            $account = model('account')->findAccountBySID($chat_id, 'telegram');
                            if ($account) {
                                $this->model('account')->update(array(
                                    'avatar' => 'assets/images/telegram.png',
                                    'username' => $title,
                                    'access_token' => $val['key'],
                                    'sid' => $chat_id
                                ), $account['id']);
                            } else {
                                $this->model('account')->add(array(
                                    'avatar' => 'assets/images/telegram.png',
                                    'username' => $title,
                                    'access_token' => $val['key'],
                                    'sid' => $chat_id,
                                    'account_type' => $type,
                                    'social_type' => 'telegram',
                                ));
                            }

                        }

                    }

                    if (count($added) > 0) {
                        return json_encode(array(
                            'type' => 'url',
                            'message' => l('accounts-added-success'),
                            'value' => url('accounts/telegram')
                        ));
                    } else {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('no-telegram-group-found'),
                        ));
                    }
                } else {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('invalid-telegram-api-key'),
                    ));
                }
            }
        }
        $accounts = $this->model('account')->getAccounts('telegram', $this->request->input('term'));

        return $this->render($this->view('accounts/telegram/index', array('accounts' => $accounts)), true);
    }

    public function vk() {
        if ($auth = $this->request->input('auth')) {
            return $this->request->redirect($this->api('vk')->init()->loginUrl());
        }
        if ($codeSubmit = $this->request->input('code_submit')) {
            $code = $this->request->input('code');
            if (!$code) {
                return json_encode(array(
                   'type' => 'error',
                   'message' => l('please-enter-a-code')
                ));
            }
            $vkAPI = $this->api('vk');
            $token = $vkAPI->init()->getToken();

            $user     = $vkAPI->getCurrentUser()[0];
            $groups       = $vkAPI->getGroups();

            return json_encode(array(
                'type' => 'function',
                'message' => l('vk-code-success'),
                'value' => 'vkCodeSuccess',
                'content' => $this->view('accounts/vk/lists', array('user' => $user, 'groups' => $groups, 'token' => $token))
            ));
        }

        if($val = $this->request->input('val')) {
            if (isset($val['token'])) {
                $token = $val['token'];
                $vkAPI = $this->api('vk');
                $vkAPI->setToken($token);

                $user     = $vkAPI->getCurrentUser()[0];
                $groups       = $vkAPI->getGroups();
                if (isset($val['user'])) {
                    $account = model('account')->findAccountBySID($user->id, 'vk', 'profile');
                    if ($account) {
                        $this->model('account')->update(array(
                            'avatar' => $this->api('vk')->getAvatar($user->photo_big),
                            'username' => $user->first_name.' '.$user->last_name,
                            'access_token' => $token,
                            'sid' => $user->id
                        ), $account['id']);
                    } else {
                        $this->model('account')->add(array(
                            'avatar' => $this->api('vk')->getAvatar($user->photo_big),
                            'username' => $user->first_name.' '.$user->last_name,
                            'access_token' => $token,
                            'sid' => $user->id,
                            'social_type' => 'vk',
                            'account_type' => 'profile'
                        ));
                    }
                }

                $selectedGroups = (isset($val['groups'])) ? $val['groups'] : array();
                foreach ($groups->items as $group) {
                    if (in_array('group-'.$group->id, $selectedGroups)) {
                        $account = model('account')->findAccountBySID($group->id, 'vk', $group->type);
                        if ($account) {
                            $this->model('account')->update(array(
                                'avatar' => $this->api('vk')->getAvatar($group->photo_100),
                                'username' => $group->name,
                                'access_token' => $token,
                                'sid' => $group->id
                            ), $account['id']);
                        } else {
                            $this->model('account')->add(array(
                                'avatar' => $this->api('vk')->getAvatar($group->photo_100),
                                'username' => $group->name,
                                'access_token' => $token,
                                'sid' => $group->id,
                                'social_type' => 'vk',
                                'account_type' => 'group'
                            ));
                        }
                    }
                }

                return json_encode(array(
                    'type' => 'modal-url',
                    'message' => l('accounts-added-success'),
                    'value' => url('accounts/vk'),
                    'content' => '#vkCodeModal'
                ));
            }
        }
        $accounts = $this->model('account')->getAccounts('vk', $this->request->input('term'));

        return $this->render($this->view('accounts/vk/index', array('accounts' => $accounts)), true);
    }

    public function pinterest() {
        if ($auth = $this->request->input('auth')) {
            if (!is_ajax()) return $this->request->redirect($this->api('pinterest')->loginUrl());
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('pinterest')->loginUrl()
            ));
        }

        $details = array();

        if ($code = $this->request->input('code')) {
            $pinterest = $this->api('pinterest');
            $token = $pinterest->getToken();
            $pinterest->setToken($token);
            $user = (object)$pinterest->getCurrentUser($token);
            $boards = $pinterest->getBoards();

            $details = array(
                'user' => $user,
                'boards' => $boards,
                'token' => $token
            );
        }

        if ($val = $this->request->input('val')) {
            if (isset($val['add'])) {
                $selectedBoards = $val['boards'];
                $pinterest = $this->api('pinterest');
                if (isset($val['username'])) {
                    $proxy = (isset($val['proxy'])) ? $val['proxy'] : null;

                    $account = $this->model('account')->findAccountByToken($val['username'], 'pinterest');

                    $proxy = $this->model('proxy')->findOneProxy($proxy, $account);
                    if ($proxy['proxy']) $pinterest->setProxy($proxy['proxy']);
                    if ($pinterest->login($val['username'], $val['password'])) {
                        $boards = $pinterest->getApiBoards($val['username']);
                        $user = $pinterest->getCurrentUserApi();
                        foreach ($boards as $board) {
                            if (in_array($board->id, $selectedBoards)) {
                                $account = model('account')->findAccountBySID($board->id, 'pinterest', 'board');
                                if ($account) {
                                    $this->model('account')->update(array(
                                        'avatar' => $this->api('pinterest')->getAvatar($user, true),
                                        'username' => $board->name,
                                        'access_token' => $val['username'],
                                        'password' => mEncrypt($val['password']),
                                        'sid' => $board->id,
                                        'proxy' => $proxy['proxy'],
                                        'default_proxy' => $proxy['default'],
                                    ), $account['id']);
                                } else {
                                    $this->model('account')->add(array(
                                        'avatar' => $this->api('pinterest')->getAvatar($user, true),
                                        'username' => $board->name,
                                        'access_token' => $val['username'],
                                        'password' => mEncrypt($val['password']),
                                        'sid' => $board->id,
                                        'social_type' => 'pinterest',
                                        'proxy' => $proxy['proxy'],
                                        'default_proxy' => $proxy['default'],

                                    ));
                                }
                            }
                        }

                    } else {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('invalid-pinterest-login')
                        ));
                    }
                } else {
                    $token = $val['token'];
                    $pinterest->setToken($token);
                    $user = (object)$pinterest->getCurrentUser($token);
                    $boards = $pinterest->getBoards();


                    foreach ($boards as $key => $board) {
                        if (in_array($board->url, $selectedBoards)) {
                            $account = model('account')->findAccountBySID($board->url, 'pinterest', 'board');
                            if ($account) {
                                $this->model('account')->update(array(
                                    'avatar' => $this->api('pinterest')->getAvatar($user),
                                    'username' => $board->name,
                                    'access_token' => $token,
                                    'sid' => $board->url
                                ), $account['id']);
                            } else {
                                $this->model('account')->add(array(
                                    'avatar' => $this->api('pinterest')->getAvatar($user),
                                    'username' => $board->name,
                                    'access_token' => $token,
                                    'sid' => $board->url,
                                    'social_type' => 'pinterest',

                                ));
                            }
                        }
                    }
                }
                return json_encode(array(
                    'type' => 'modal-url',
                    'message' => l('accounts-added-success'),
                    'value' => url('accounts/pinterest'),
                    'content' => '#pinterestSelectModal'
                ));
            } elseif(isset($val['action']) and $val['action'] == 'login') {
                $pinterest = $this->api('pinterest');
                if ($pinterest->login($val['username'], $val['password'])) {

                    $boards = $pinterest->getApiBoards($val['username']);
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'finishPinterestApiLogin',
                        'content' => $this->view('accounts/pinterest/boards', array('boards' => $boards, 'val' => $val))
                    ));
                } else {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('invalid-pinterest-login')
                    ));
                }
            }
        }
        $accounts = $this->model('account')->getAccounts('pinterest', $this->request->input('term'));

        return $this->render($this->view('accounts/pinterest/index', array('accounts' => $accounts, 'details' => $details)), true);
    }

    public function youtube() {

        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('youtube')->loginUrl()
            ));
        }

        if ($code = $this->request->input('code')) {
            $youtubeAPI = $this->api('youtube');
            $token = $youtubeAPI->getToken();

            $user = $youtubeAPI->getCurrentUser($token);
            $channel = $youtubeAPI->getChannel();


            if($channel and $channel->getItems()){

                $channel = $channel->getItems()[0];

                $id = $channel->getId();
                $title = $channel->getSnippet()->getTitle();
                $thumbnail = $channel->getSnippet()->getThumbnails()->getDefault()->getUrl();

                $account = model('account')->findAccountBySID($id, 'youtube');
                if ($account) {
                    $this->model('account')->update(array(
                        'avatar' => $this->api('youtube')->getAvatar($thumbnail),
                        'username' => $title,
                        'access_token' => perfectSerialize($token),
                        'sid' => $id
                    ), $account['id']);
                } else {
                    $this->model('account')->add(array(
                        'avatar' => $this->api('youtube')->getAvatar($thumbnail),
                        'username' => $title,
                        'access_token' => perfectSerialize($token),
                        'sid' => $id,
                        'social_type' => 'youtube',

                    ));
                }

                return $this->request->redirect(url('accounts/youtube'));
            }
        }
        $accounts = $this->model('account')->getAccounts('youtube', $this->request->input('term'));
        return $this->render($this->view('accounts/youtube/index', array('accounts' => $accounts)), true);
    }

    public function linkedin() {

        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('linkedin')->loginUrl()
            ));
        }

        $details = array();

        if ($code = $this->request->input('code')) {
            $linkedin = $this->api('linkedin');
            $token = $linkedin->getToken();
            $linkedin->setToken($token);
            $user = (object)$linkedin->getCurrentUser($token);
            //$companies = $linkedin->getCompanies();
            $firstName_param = (array)$user->firstName->localized;
            $lastName_param = (array)$user->lastName->localized;

            $firstName = reset($firstName_param);
            $lastName = reset($lastName_param);
            $fullname = $firstName." ".$lastName;
            $account = model('account')->findAccountBySID($user->id, 'linkedin', 'profile');
            if ($account) {
                $this->model('account')->update(array(
                    'avatar' => $this->api('linkedin')->getAvatar($user),
                    'username' => $fullname,
                    'access_token' => $token,
                    'sid' => $user->id,
                    'account_type'=> 'profile',
                ), $account['id']);
            } else {
                $this->model('account')->add(array(
                    'avatar' => $this->api('linkedin')->getAvatar($user),
                    'username' => $fullname,
                    'access_token' => $token,
                    'sid' => $user->id,
                    'social_type' => 'linkedin',
                    'account_type'=> 'profile',

                ));
            }

            if (config('enable-linkedin-page')) {
                $companies = $linkedin->getCompanies();
                if(!empty($companies)){
                    foreach ($companies->elements as $company) {
                        $company = (array)$company;
                        $company = $company['organizationalTarget~'];
                        $account = model('account')->findAccountBySID($company->id, 'linkedin', 'page');
                        $logo = (array)$company->logoV2;
                        $logo = $logo['original~'];
                        $logo = $logo->elements[0]->identifiers[0]->identifier;
                        if ($account) {
                            $this->model('account')->update(array(
                                'avatar' => $logo,
                                'username' => $company->localizedName,
                                'access_token' => $token,
                                'sid' => $company->id,
                                'account_type'=> 'page',
                                'status' => 1,
                            ), $account['id']);
                        } else {
                            $this->model('account')->add(array(
                                'avatar' => $logo,
                                'username' => $company->localizedName,
                                'access_token' => $token,
                                'sid' => $company->id,
                                'account_type'=> 'page',
                                'social_type' => 'linkedin',
                            ));
                        }
                    }

                }
            }

           /** $companies = $linkedin->getCompanies();
            print_r($companies);
            exit;**/

            return $this->request->redirect(url('accounts/linkedin'));

        }


        $accounts = $this->model('account')->getAccounts('linkedin', $this->request->input('term'));

        return $this->render($this->view('accounts/linkedin/index', array('accounts' => $accounts)), true);
    }

    public function google() {

        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('google')->loginUrl()
            ));
        }

        $details = null;

        if ($code = $this->request->input('code')) {
            $googleAPI = $this->api('google');
            $token = $googleAPI->getToken();
            $accounts = $googleAPI->listAccounts();
            session_put('gb_token', $token);
            $details = array('accounts' => $accounts);
        }

        if($fetch = $this->request->input('fetch')) {
            $token = session_get('gb_token');
            $googleAPI = $this->api('google');
            $googleAPI->setToken($token);
            $locations = $googleAPI->getLocations($fetch);

            return $this->view('accounts/google/locations', array('locations' => $locations));
        }

        if ($val = $this->request->input('val')) {
            if (isset($val['add'])) {
                $selectedLocations = $this->request->input('val.locations');
                $fetch = $val['name'];
                $token = session_get('gb_token');
                $googleAPI = $this->api('google');
                $googleAPI->setToken($token);
                $locations = $googleAPI->getLocations($fetch);

                if (empty($selectedLocations)) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('please-select-one-business-location')
                    ));
                }

                foreach($locations->getLocations() as $location) {
                    if (in_array($location->name, $selectedLocations)) {
                        $account = model('account')->findAccountBySID($location->name, 'google', 'location');
                        if ($account) {
                            $this->model('account')->update(array(
                                'avatar' => $this->api('google')->getAvatar(),
                                'username' => $location->locationName,
                                'access_token' => perfectSerialize($token),
                                'sid' => $location->name
                            ), $account['id']);
                        } else {
                            $this->model('account')->add(array(
                                'avatar' => $this->api('google')->getAvatar(),
                                'username' => $location->locationName,
                                'access_token' => perfectSerialize($token),
                                'sid' => $location->name,
                                'social_type' => 'google',

                            ));
                        }
                    }

                }

                return json_encode(array(
                    'type' => 'modal-url',
                    'message' => l('accounts-added-success'),
                    'value' => url('accounts/google'),
                    'content' => '#googleSelectModal'
                ));
            }
        }

        $accounts = $this->model('account')->getAccounts('google', $this->request->input('term'));

        return $this->render($this->view('accounts/google/index', array('accounts' => $accounts, 'details' => $details)), true);
    }

    public function tumblr() {
        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('tumblr')->init(null,null, true)->loginUrl()
            ));
        }

        if ($oauthToken = $this->request->input('oauth_token')) {
            $verifier = $this->request->input('oauth_verifier');
            $oldOauthToken = session_get('tumblr_oauth_token');
            $oldOauthTokenSecret = session_get('tumblr_oauth_token_secret');
            $tumblr = $this->api('tumblr')->init($oldOauthToken, $oldOauthTokenSecret, true);
            $accessToken = $tumblr->getAccessToken($verifier);

            $tumblr = $tumblr->init($accessToken['oauth_token'], $accessToken['oauth_token_secret']);

            $user = $tumblr->getCurrentUser();

            $blogs = $user->blogs;

            foreach($blogs as $blog) {
                $account = $this->model('account')->findAccountBySID($blog->name, 'tumblr');
                if ($account) {
                    $this->model('account')->update(array(
                        'avatar' => $blog->avatar[0]->url,
                        'username' => $blog->name,
                        'access_token' => json_encode($accessToken),
                        'sid' => $blog->name
                    ), $account['id']);
                } else {
                    $this->model('account')->add(array(
                        'avatar' => $blog->avatar[0]->url,
                        'username' => $blog->name,
                        'access_token' => json_encode($accessToken),
                        'sid' => $blog->name,
                        'social_type' => 'tumblr',

                    ));
                }
            }


            return $this->request->redirect(url('accounts/tumblr'));

        }
        $accounts = $this->model('account')->getAccounts('tumblr', $this->request->input('term'));

        return $this->render($this->view('accounts/tumblr/index', array('accounts' => $accounts)), true);
    }


    
    public function twitter() {
        $accounts = $this->model('account')->getAccounts('twitter', $this->request->input('term'));

        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('twitter')->loginUrl()
            ));

        }

        if ($verifyIdentifier = $this->request->input('oauth_verifier')) {
            $twitter = $this->api('twitter')->init();
            $accessToken = (object)$twitter->getToken();
            $account = $this->model('account')->findAccountBySID($accessToken->user_id, 'twitter');
            if ($account) {
                $this->model('account')->update(array(
                    'avatar' => $this->api('twitter')->getAvatar($accessToken->user_id,json_encode($accessToken)),
                    'username' => $accessToken->screen_name,
                    'access_token' => json_encode($accessToken),
                    'sid' => $accessToken->user_id
                ), $account['id']);
            } else {
                $this->model('account')->add(array(
                    'avatar' => $this->api('twitter')->getAvatar($accessToken->user_id,json_encode($accessToken)),
                    'username' => $accessToken->screen_name,
                    'access_token' => json_encode($accessToken),
                    'sid' => $accessToken->user_id,
                    'social_type' => 'twitter',

                ));
            }
            return $this->request->redirect(url('accounts/twitter'));
        }
        return $this->render($this->view('accounts/twitter/index', array('accounts' => $accounts)), true);

    }

    public function facebook() {
        $accounts = $this->model('account')->getAccounts('facebook', $this->request->input('term'));
        $details = array();

        if ($auth = $this->request->input('auth')) {
            $fbApi = $this->api('facebook')->init(config('facebook-app-id'), config('facebook-app-secret'));
            $this->defendDemo();
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $fbApi->loginUrl(url('accounts/facebook'))
            ));

        }

        if ($code = $this->request->input('code')) {
            $fbApi = $this->api('facebook')->init(config('facebook-app-id'), config('facebook-app-secret'));
            $accessToken = $fbApi->getUserAccessToken(url('accounts/facebook'));
            if (!$accessToken) return $this->request->redirect($fbApi->loginUrl(url('accounts/facebook')));
            $fbApi->setAccessToken($accessToken);

            $user = $fbApi->getLoginUser();
            $groups = json_encode($fbApi->getGroups(true));
            $pages = json_encode($fbApi->getPages());

            $details = array(
                'user' => $user,
                'groups' => json_decode($groups, true),
                'pages' => json_decode($pages, true),
                'access_token' => $accessToken
            );
        }

        if ($val = $this->request->input('val')) {
            if(isset($val['add'])) {
                $fbApi = $this->api('facebook')->init(config('facebook-app-id'), config('facebook-app-secret'));
                $fbApi->setAccessToken($val['token']);

                $selectedGroups = (isset($val['groups'])) ? $val['groups'] : array();
                $selectedPages = (isset($val['pages'])) ? $val['pages'] : array();
                if (empty($selectedPages) and empty($selectedGroups)) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('selected-atleast-one-account')
                    ));
                }
                $accessToken = $val['token'];
                $groups = json_decode(json_encode($fbApi->getGroups(true)), true);
                $pages = json_decode(json_encode($fbApi->getPages()), true);


                foreach($groups['data'] as $group) {
                    if (in_array('groups-'.$group['id'], $selectedGroups)) {
                        $groupId = $group['id'];
                        $account = $this->model('account')->findAccountBySID($groupId, 'facebook', 'group');

                        if ($account) {
                            $this->model('account')->update(array(
                                'avatar' => $this->api('facebook')->getGroupAvatar($group, true),
                                'username' => $group['name'],
                                'access_token' => $accessToken,
                                'sid' => $groupId
                            ), $account['id']);
                        } else {
                            $this->model('account')->add(array(
                                'avatar' => $this->api('facebook')->getGroupAvatar($group, true),
                                'username' => $group['name'],
                                'access_token' => $accessToken,
                                'sid' => $groupId,
                                'social_type' => 'facebook',
                                'account_type' => 'group',
                            ));
                        }
                    }
                }


                foreach($pages['data'] as $page) {
                    if (in_array('pages-'.$page['id'], $selectedPages)) {
                        $pageId = $page['id'];
                        $account = $this->model('account')->findAccountBySID($pageId, 'facebook', 'page');
                        if ($account) {
                            $this->model('account')->update(array(
                                'avatar' => $this->api('facebook')->getPageAvatar($page, true),
                                'username' => $page['name'],
                                'access_token' => $accessToken,
                                'sid' => $pageId
                            ), $account['id']);
                        } else {
                            $this->model('account')->add(array(
                                'avatar' => $this->api('facebook')->getPageAvatar($page, true),
                                'username' => $page['name'],
                                'access_token' => $accessToken,
                                'sid' => $pageId,
                                'social_type' => 'facebook',
                                'account_type' => 'page',
                            ));
                        }
                    }
                }

                return json_encode(array(
                    'type' => 'modal-url',
                    'message' => l('accounts-added-success'),
                    'value' => url('accounts/facebook'),
                    'content' => '#facebookSelectModal'
                ));
                
            }
        }

        return $this->render($this->view('accounts/facebook/index', array('accounts' => $accounts, 'details' => $details)), true);
    }

    public function instagram() {
        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'username' => 'required',
                    'password' => 'required',
                ));
                if($validator->passes()) {
                    $username = $val['username'];
                    $password = $val['password'];
                    $sCode = (isset($val['scode'])) ? $val['scode'] : null;
                    $vCode = (isset($val['vcode'])) ? $val['vcode'] : null;
                    $proxy = (isset($val['proxy'])) ? $val['proxy'] : null;

                    $account = $this->model('account')->findAccountByUsername($username, 'instagram');

                    $proxy = $this->model('proxy')->findOneProxy($proxy, $account);

                    $instagramApi = $this->api('instagram')->init($username, $password, $proxy['proxy'], true, $sCode, $vCode);

                    try {
                        $response = $instagramApi->loginUser();
                        if ($response['status'] == 'success') {
                            $user = $instagramApi->getCurrentUser();
                            if (!empty($user)) {
                                if ($account) {
                                    $this->model('account')->update(array(
                                        'social_type' => 'instagram',
                                        'account_type' => '',
                                        'url' => '',
                                        'avatar' => $instagramApi->getAvatar($user),
                                        'username' => $username,
                                        'password' => mEncrypt($password),
                                        'access_token' => $user->user->pk,
                                        'is_official' => 1,
                                        'proxy' => $proxy['proxy'],
                                        'default_proxy' => $proxy['default'],
                                    ), $account['id']);
                                } else {
                                    $this->model('account')->add(array(
                                        'social_type' => 'instagram',
                                        'account_type' => '',
                                        'url' => '',
                                        'avatar' => $instagramApi->getAvatar($user),
                                        'username' => $username,
                                        'password' => mEncrypt($password),
                                        'access_token' => $user->user->pk,
                                        'is_official' => 0,
                                        'proxy' => $proxy['proxy'],
                                        'default_proxy' => $proxy['default'],
                                    ));
                                }
                                return json_encode(array(
                                    'type' => 'url',
                                    'message' => l('accounts-added-success'),
                                    'value' => url('accounts/instagram')
                                ));
                            } else {
                                return json_encode(array(
                                    "type"  => "error",
                                    "message" => l('oops-something-went-wrong')
                                ));
                            }

                        } else {
                            return json_encode(array(
                                'type' => 'function',
                                'value' => 'processInstagramLogin',
                                'content' => $response
                            ));
                        }
                    } catch (Exception $e) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => $e->getMessage()
                        ));
                    }
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error',
                    ));
                }

            }
        }
        $accounts = $this->model('account')->getAccounts('instagram', $this->request->input('term'));

        return $this->render($this->view('accounts/instagram/index', array('accounts' => $accounts)), true);
    }

    public function action() {
        $action = $this->request->input('action');
        $id = $this->request->input('id');
        $account = $this->model('account')->find($id);
        switch($action) {
            case 'delete':
                $this->defendDemo();
                $this->model('account')->delete($id);
                return json_encode(array(
                    'type' => 'normal-url',
                    'value' => url('accounts/'.$account['social_type']),
                    'message' => l('account-delete-success'),
                ));
                break;
        }
    }

}