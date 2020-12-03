<?php
require_once  path('app/vendor/tumblr/vendor/autoload.php');
require_once  path('app/vendor/tumblr/tumblroauth.php');

class TumblrAPI extends API {

    private $tumblr;
    private $token;

    public function __construct()
    {

    }

    public function init($oauthToken = null, $oauthTokenSecret = null, $old  = false) {
        if (!$old) {
            $this->tumblr = new Tumblr\API\Client(config('tumblr-client-id'), config('tumblr-client-secret'));
            if ($oauthToken) {
                $this->tumblr->setToken($oauthToken, $oauthTokenSecret);
            }
        } else {
            $this->tumblr = new TumblrOAuth(config('tumblr-client-id'), config('tumblr-client-secret'), $oauthToken, $oauthTokenSecret);

        }

        return $this;
    }

    public function loginUrl() {
        $this->token = $this->tumblr->getRequestToken(url('accounts/tumblr'));
        session_put('tumblr_oauth_token', $this->token['oauth_token']);
        session_put('tumblr_oauth_token_secret', $this->token['oauth_token_secret']);
        return $this->tumblr->getAuthorizeURL($this->token, false);
    }

    public function getAccessToken($verifier) {
        return $this->token = $this->tumblr->getAccessToken($verifier);
    }

    public function setToken($token) {
        $this->tumblr->setToken($token['oauth_token'], $token['oauth_token_secret']);
        $this->token = $token;
    }
    public function getCurrentUser() {
        return $this->tumblr->getUserInfo()->user;
    }

    public function post($post, $account) {
        $this->init();
        $spintax = new Spintax();
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $this->setToken(json_decode($account['access_token'], true));
        $postData = perfectUnserialize($post['type_data']);
        $caption    = @$spintax->process($postData['text']);
        $link = ($postData['link']) ? @$spintax->process($postData['link']) : '';

        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);

        switch($post['type']) {
            case 'link':
                $thumbnail = '';
                include_once path('app/vendor/autoload.php');
                try {
                    $linkPreview = new \LinkPreview\LinkPreview($link);
                    $parsed = $linkPreview->getParsed();
                    foreach ($parsed as $parserName => $link2) {

                        if ($link2->getImage() == '') {

                            $pictures = $link2->getPictures();
                            foreach($pictures as $picture) {
                                if ($picture) {
                                    list($width, $height) = getimagesize($picture);
                                    if($width > 80) {
                                        $link2->setImage($picture);
                                        break;
                                    }
                                }

                            }
                        }
                        $thumbnail = $link2->getImage();
                    }
                } catch (Exception $e){

                }

                if ($thumbnail) {
                    $data = array(
                        'type' => 'link',
                        'url' => $link,
                        'thumbnail' => $thumbnail
                    );
                } else {
                    $data = array(
                        'type' => 'link',
                        'url' => $link,
                    );
                }
                break;
            case 'media':
                $media = $postData['media'][0];
                if (isImage($media)) {
                    $data = array(
                        'type' => 'photo',
                        'data' => path($media),
                        'caption' => $caption
                    );
                } else {
                    $data = array(
                        'type' => 'video',
                        'data' => path($media),
                        'caption' => $caption
                    );
                }
                break;
            case 'text':
                $data = array(
                    'type' => 'text',
                    'body' => $caption
                );
                break;
        }
        try {

            $createPost = $this->tumblr->createPost($account['username'],$data);
            model('post')->setResult('Posted successfully', $post['id']);
            return true;
        } catch (Exception $e) {
            print_r($e->getMessage());
            model('post')->setResult($e->getMessage(), $post['id']);
            return false;
        }
        return true;
    }
}