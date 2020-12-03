<?php
autoLoadVendor();
require_once path('app/vendor/twitteroauth/autoload.php');
class TwitterAPI extends API {
    private $consumerKey;
    private $consumerSecret;
    public $twitter;
    private $accountId = null;

    public function __construct()
    {
        parent::__construct();
        $this->consumerKey = config('twitter-consumer-key');
        $this->consumerSecret = config('twitter-consumer-secret');

    }

    public function init() {
        try {
            $oauthToken = session_get('twiter.oauth.token');
            $oauthTokenSecret = session_get('twiter.oauth.secret');
            $this->twitter = new \Abraham\TwitterOAuth\TwitterOAuth($this->consumerKey, $this->consumerSecret, $oauthToken, $oauthTokenSecret);
        } catch (Exception $e) {
            $this->twitter = new \Abraham\TwitterOAuth\TwitterOAuth($this->consumerKey, $this->consumerSecret);
        }
        return $this;
    }

    public function loginUrl($url = 'accounts/twitter') {
        try {
            $this->init();
            $outhToken = (object) $this->twitter->oauth('oauth/request_token', ['oauth_callback' => url($url)]);
            session_put('twiter.oauth.token', $outhToken->oauth_token);
            session_put('twiter.oauth.secret', $outhToken->oauth_token_secret);
            $url = $this->twitter->url("oauth/authorize", ["oauth_token" => $outhToken->oauth_token]);
            return $url;
        } catch (Exception $e) {

            print_r($e);
            session_forget('twiter.oauth.token');
            session_forget('twiter.oauth.secret');
        }
    }

    function getToken(){
        try {
            session_forget('twiter.oauth.token');
            session_forget('twiter.oauth.secret');
            $accessToken = $this->twitter->oauth("oauth/access_token", ["oauth_verifier" => Request::instance()->input("oauth_verifier")]);
            return $accessToken;
        } catch (Exception $e) {
            Request::instance()->redirect(url('accounts/twitter', array('auth' => true)));
        }
    }

    function setToken($token){
        $token = json_decode($token);
        $this->twitter->setOauthToken($token->oauth_token, $token->oauth_token_secret);
    }

    function getAvatar($name, $token) {

        try {
            $this->setToken($token);
            $user = $this->twitter->get('users/show', array('user_id' => $name));

            $avatar = $user->profile_image_url_https;

            $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
            if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
            $file = $dir.md5($avatar).'.jpg';
            getFileViaCurl($avatar, $file);
            return $file;
        } catch (Exception $e){}

        return 'assets/images/twitter.png';
    }

    function userInfo(){
        return $this->twitter->get("account/verify_credentials", ["include_email" => 'true']);
    }

    function doLogin($response){
        if(isset($response->errors) &&
            (
                $response->errors[0]->code == 89
                || $response->errors[0]->code == 135
                || $response->errors[0]->code == 64
                || $response->errors[0]->code == 63
                || $response->errors[0]->code == 50
                || $response->errors[0]->code == 32
            )
        ){
            if ($this->accountId) $this->db->query("UPDATE accounts SET status=? WHERE id=?", 0, $this->accountId);
        }
    }

    public function preInit($account) {
        $this->accountId = $account['id'];
        $this->init();
        $this->setToken($account['access_token']);
        return $this;
    }

    public function post($post, $account) {
        $spintax = new Spintax();
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $this->init();
        $this->setToken($account['access_token']);
        $postData = perfectUnserialize($post['type_data']);
        $caption    = @$spintax->process($postData['text']);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);
        $caption = str_limit($caption , 259);
        $link = ($postData['link']) ? @$spintax->process($postData['link']) : '';

        $param = array('status' => $caption);
        try {
            switch($post['type']) {
                case 'link':
                    $param['status'] = $link;
                    $response = $this->twitter->post('statuses/update', $param);
                    if (isset($response->id)) {
                        model('post')->setResult('posted successfully', $post['id']);
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'text':
                    $response = $this->twitter->post('statuses/update', $param);
                    if (isset($response->id)) {
                        model('post')->setResult('posted successfully', $post['id']);
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'photo':
                    $this->twitter->setTimeouts(120,60);
                    $medias = array();
                    $mediaFiles = array_chunk($postData['media'], 4);

                    foreach($mediaFiles[0] as $media) {

                        if (model('user')->hasPermission('watermark')) {
                            $file = getWatermarkTmpFile($media);
                            $media = doWaterMark($media, $file);
                        }

                        $upload = (object)$this->twitter->upload('media/upload', array('media' => path($media)));

                        if (!$upload) {
                            model('post')->setResult('No media files is found', $post['id']);
                            return false;
                        }
                        $medias[] = isset($upload->media_id_string) ? $upload->media_id_string: '';
                    }

                    $param['media_ids'] = implode(',', $medias);

                    $response = $this->twitter->post('statuses/update', $param);
                    $this->doLogin($response);
                    if (isset($response->id)) {
                        model('post')->setResult('posted successfully', $post['id']);
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'media':
                    $this->twitter->setTimeouts(120,60);
                    $medias = array();
                    $mediaFiles = array_chunk($postData['media'], 4);

                    foreach($mediaFiles[0] as $media) {

                        if (isImage($media)) {
                            if (model('user')->hasPermission('watermark')) {
                                $file = getWatermarkTmpFile($media);
                                $media = doWaterMark($media, $file);
                            }

                            $upload = (object)$this->twitter->upload('media/upload', array('media' => path($media)));

                        } else {
                            $upload = $this->twitter->upload('media/upload', array(
                                'media' => path($media),
                                'media_type' => 'video/mp4',
                                'media_category' => 'tweet_video'
                            ), true);
                            $count = 0;
                            do {
                                $response = $this->twitter->mediaStatus(
                                    $upload->media_id_string
                                );


                                if ($response->processing_info->state != 'succeeded'){
                                    sleep(5);
                                }
                                $count++;
                            } while($response->processing_info->state != 'succeeded' && $count < 5);
                        }
                        $medias[] = isset($upload->media_id_string) ? $upload->media_id_string: '';
                    }

                    $param['media_ids'] = implode(',', $medias);

                    $response = $this->twitter->post('statuses/update', $param);
                    $this->doLogin($response);
                    if (isset($response->id)) {
                        model('post')->setResult('posted successfully', $post['id']);
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'video':
                    $this->twitter->setTimeouts(120,60);
                    $upload = $this->twitter->upload('media/upload', array(
                        'media' => path($postData['media'][0]),
                        'media_type' => 'video/mp4',
                        'media_category' => 'tweet_video'
                    ), true);


                    if (!$upload) {
                        model('post')->setResult('No media files is found', $post['id']);
                        return false;
                    }
                    $count = 0;
                    do {
                        $response = $this->twitter->mediaStatus(
                            $upload->media_id_string
                        );


                        if ($response->processing_info->state != 'succeeded'){
                            sleep(5);
                        }
                        $count++;
                    } while($response->processing_info->state != 'succeeded' && $count < 5);
                    $param['media_ids'] = $upload->media_id;
                    $response = $this->twitter->post('statuses/update', $param);

                    $this->doLogin($response);
                    if (isset($response->id)) {
                        model('post')->setResult('posted successfully', $post['id']);
                        return true;
                    } else {
                        return false;
                    }
                    break;
            }

        } catch (Exception $e) {
            return false;
        }

    }
}