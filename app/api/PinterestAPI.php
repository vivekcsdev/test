<?php
autoLoadVendor();
require_once path('app/vendor/pinterestapi/autoload.php');
class PinterestAPI extends API {
    private $pinterest;
    private $pinterestBot;
    public function __construct(){

        $this->pinterest = new \DirkGroenen\Pinterest\Pinterest(config('pinterest-app-id'), config('pinterest-app-secret'));
        $this->pinterestBot = \seregazhuk\PinterestBot\Factories\PinterestBot::create();
    }

    public function login($username, $password) {
        //$this->pinterestBot->getHttpClient()->useProxy('173.234.154.244', '12345', 'socialrrific:ScHeDuLeR');
        $result = $this->pinterestBot->auth->login($username, $password);
        if (!$this->pinterestBot->auth->isLoggedIn()) {
            return false;
        }
        return true;
    }

    public function setProxy($proxy) {
        list($auth, $second) = explode('@', $proxy);
        $auth = str_replace(array('https://','http://'), '', $auth);
        list($ip, $port) = explode(':', $second);
        $this->pinterestBot->getHttpClient()->useProxy($ip, $port, $auth);
    }
    public function loginUrl(){
        return $this->pinterest->auth->getLoginUrl(url("accounts/pinterest"), array('read_public,write_public,read_relationships,write_relationships'));
    }

    public function getToken(){
        try {
            if($code = Request::instance()->input("code")){
                $token = $this->pinterest->auth->getOAuthToken($code);
                $this->pinterest->auth->setOAuthToken($token->access_token);
                return $token->access_token;
            }else{
                Request::instance()->redirect(url("accounts/pinterest", array('auth' => true)));
            }

        } catch (Exception $e) {
            Request::instance()->redirect(url("accounts/pinterest", array('auth' => true)));
        }
    }

    public function setToken($token = ""){
        $this->pinterest->auth->setOAuthToken($token);
    }

    public function getState(){
        try {
            return $this->pinterest->auth->getState();
        } catch (Exception $e) {
            return false;
        }
    }

    public function getCurrentUser($access_token){
        try {
            $this->pinterest->auth->setOAuthToken($access_token);
            return $this->pinterest->users->me(array('access_token' => $access_token, "fields" => 'id, first_name, last_name, username, bio, image, account_type, url, counts, created_at'));
        } catch (Exception $e) {
            return false;
        }
    }

    public function getCurrentUserApi() {
        $profile = $this->pinterestBot->user->profile();
        if(!empty($profile)){
            return (object)$profile;
        }
        return false;

    }

    public function getApiBoards($username) {
        $boards = $this->pinterestBot->boards->forMe();

        if(!empty($boards)){
            $data = array();
            foreach ($boards as $board) {
                $data[] = (object)array(
                    "id" => $board['id'],
                    "name" => $board['name'],
                    "url" => "https://www.pinterest.com".$board['url']
                );
            }

            return $data;
        }

        return array();

    }

    public function getBoards(){
        try {
            return $this->pinterest->users->getMeBoards();
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
            return false;
        }
    }

    public function getAvatar($user, $api = false) {
        $image = '';
        if ($api) {
            $image = $user->profile_image_url;
        } else {
            if($user and !empty($user->image)){

                foreach ($user->image as $row) {
                    $row   = (object)$row;
                    $image = $row->url;
                }
            }
        }


        if (!$image) return 'assets/images/pinterest.png';
        $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
        if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
        $file = $dir.md5($image).'.jpg';
        getFileViaCurl($image, $file);
        return $file;
    }

    public function post($post, $account) {
        $spintax = new Spintax();
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        if ($account['password']) {
            if ($account['proxy']) $this->setProxy($account['proxy']);
            $this->login($account['access_token'], mDcrypt($account['password']));
        } else {
            $this->setToken($account['access_token']);
        }
        $postData = perfectUnserialize($post['type_data']);
        $caption    = $postData['text'];
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);
        $url = $postData['url'];
        $board = $account['sid'];
        $title = ($postData['title']) ? @$spintax->process($postData['title']) : '';
        try {
            $media = $postData['media'][0];
            if (model('user')->hasPermission('watermark')) {
                $file = getWatermarkTmpFile($media);
                $media = doWaterMark($media, $file);

            }
            if($account['password']) {
                $response = $this->pinterestBot->pins->create(
                    path($media),
                    $board,
                    $caption,
                    $url,
                    $title
                );
            } else {
                $response = $this->pinterest->pins->create(array(
                    "image_url"  => assetUrl($media),
                    "note"       => $caption,
                    "board"      => $board,
                    "link"       => $url,
                    'title'      => $title
                ));
            }

            model('post')->setResult('Posted successfully', $post['id']);
            return true;
        } catch (Exception $e) {    
            model('post')->setResult($e->getMessage(), $post['id']);
            return false;
        }
    }

    public function getBordFromUrl($url){
        $board = str_replace("https://www.pinterest.com/", "", $url );
        $board = explode("/", $board);
        array_pop($board);
        $board = implode('/', $board);
        return $board;
    }

    public function searchTags($term) {
        try {
            return $this->pinterestBot->suggestions->tagsFor($term);
        } catch (Exception $e) {
            return array();
        }
    }

    public function searchUsernames($term) {
        try {
            return $this->pinterestBot->pinners->search($term);
        } catch (Exception $e) {
            return array();
        }
    }

    public function getUserInfo($username) {
        try {
            return $this->pinterestBot->pinners->info($username);
        } catch (Exception $e) {
            return false;
        }
    }

    public function follow() {

    }

    public function unfollowUser() {

    }

    public function sendMessage() {

    }

    public function repost() {

    }

    public function comment() {

    }

    function processActivity($account, $automation, $fetch) {

    }
}