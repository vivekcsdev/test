<?php
autoLoadVendor();
require_once path('app/vendor/Facebook/autoload.php');

class FacebookAPI extends API {
    private $appId;
    private $appSecret;
    private $accessToken;
    private $fb;
    private $accountId = null;
    private $permissions = array();
    public function __construct()
    {
        parent::__construct();

    }

    public function setPermissions($permissions) {
        $this->permissions = $permissions;
    }
    public function init($appId, $appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;

        try {
            $this->fb = new \Facebook\Facebook(array(
                'app_id' => $this->appId,
                'app_secret' => $this->appSecret,
                'default_graph_version' => 'v2.12',
            ));
        } catch (Exception $e){
            print_r($e);
            exit;
        }

        return $this;
    }

    public function loginUrl($url) {
        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = (!empty($this->permissions)) ? $this->permissions : ['pages_manage_posts,pages_read_engagement,publish_to_groups'];

        $permissions = Hook::getInstance()->fire('facebook.permissions', $permissions);
        $loginUrl = $helper->getLoginUrl($url, $permissions);

        return $loginUrl;
    }

    function getUserAccessToken($url){
        $helper = $this->fb->getRedirectLoginHelper();
        try {
            $accessToken = $helper->getAccessToken($url);
            return $accessToken->getValue();
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }
    }

    function getLoginUser($fields = 'name,id'){
        return $this->fetchGet('/me?fields='.$fields);
    }

    function setAccessToken($access_token){
        $this->fb->setDefaultAccessToken($access_token);
        $this->accessToken = $access_token;
    }

    function fetchAccessToken($pid){
        $response = $this->fetchGet('/'.$pid.'/?fields=access_token');
        if(is_object($response)){
            return $response->access_token;
        }else{
            return false;
        }
    }

    function getGroups($admin = false){
        $result = $this->fetchGet('/me/groups?fields=id,icon,name,description,email,privacy,cover'.($admin?"&admin_only=true":"").'&limit=10000');
        if(is_string($result)){
            $result = $this->fetchGet('/me/groups?fields=id,icon,name,description,email,privacy,cover'.($admin?"&admin_only=true":"").'&limit=3');
        }
        return $result;
    }

    public function getPages() {
        $result = $this->fetchGet('/me/accounts?fields=id,name,single_line_address,phone,emails,website,fan_count,link,is_verified,about,picture,category&limit=10000');
        if(is_string($result)){
            $result = $this->fetchGet('/me/accounts?fields=id,name,single_line_address,phone,emails,website,fan_count,link,is_verified,about,picture,category&limit=3');
        }
        return $result;
    }

    public function fetchGet($params, $app_version = null){
        try {
            $response = $this->fb->get($params, null, null, $app_version);
            return json_decode($response->getBody());
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            return 'Graph returned an error: ' . $e->getMessage();
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            return 'Facebook SDK returned an error: ' . $e->getMessage();
        }
    }

    public function doLogin($response){
        $response = $response->getResponse()->getBody();
        $response = json_decode($response);

        if(isset($response->error) && $this->accountId != 0 &&
            (
                $response->error->code == 190
                || $response->error->code == 368
                || $response->error->code == 10
            )
        ){
           if ($this->accountId) $this->db->query("UPDATE accounts SET status=? WHERE id=?", 0, $this->accountId);
        }
    }

    public function fetchPost($params, $data){
        try {
            $response = $this->fb->post($params, $data);
            return json_decode($response->getBody());
        } catch(Facebook\Exceptions\FacebookResponseException $e) {

            if ($e->getMessage() == "Missing or invalid image file") return true;
            $this->doLogin($e);
            return false;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {

            return false;
        }
    }

    public function getPageAvatar($page, $generated = false) {
        if (isset($page['picture'])) {
            $avatar =  $page['picture']['data']['url'];
            if (!$generated) return $avatar;
            return $this->generateAvatar($avatar);
        }
        return ($generated)  ? 'assets/images/page.png' : assetUrl('assets/images/page.png');
    }

    public function getGroupAvatar($page, $generated = false) {
        if (isset($page['cover'])) {
            $avatar =  $page['cover']['source'];
            if (!$generated) return $avatar;
            return $this->generateAvatar($avatar);
        }
        return ($generated ) ? 'assets/images/group.png' : assetUrl('assets/images/group.png');
    }

    public function generateAvatar($avatar) {
        $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
        if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
        $file = $dir.md5($avatar).'.jpg';
        getFileViaCurl($avatar, $file);
        return $file;
    }

    function getPageAccessToken($sid){
        $response = $this->fetchGet('/'.$sid.'/?fields=access_token');
        if(is_object($response)){
            return $response->access_token;
        }else{
            return false;
        }
    }

    public function preInit($account) {
        $this->init(config('facebook-app-id'), config('facebook-app-secret'));
        $this->setAccessToken($account['access_token']);
        $this->accountId = $account['id'];
        if ($account['account_type'] == 'page') {
            //set token for page
            $accessToken = $this->getPageAccessToken($account['sid']);
            if ($accessToken) {
                $this->setAccessToken($accessToken);
            }
        }
        return $this;
    }

    public function post($post, $account) {
        $spintax = new Spintax();
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $this->init(config('facebook-app-id'), config('facebook-app-secret'));
        $this->setAccessToken($account['access_token']);
        if ($account['account_type'] == 'page') {
            //set token for page
            $accessToken = $this->getPageAccessToken($account['sid']);
            if ($accessToken) {
                $this->setAccessToken($accessToken);
            }
        }
        $postData = perfectUnserialize($post['type_data']);
        $caption    = @$spintax->process($postData['text']);
        $link = ($postData['link']) ? @$spintax->process($postData['link']) : '';
        $title = ($postData['title']) ? @$spintax->process($postData['title']) : '';
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);

        switch($post['type']) {
            case 'media':
                if (count($postData['media'])  == 1) {
                    $media = $postData['media'][0];
                    if (isImage($media)) {
                        if (model('user')->hasPermission('watermark')) {
                            $file = getWatermarkTmpFile($media);
                            $media = doWaterMark($media, $file);
                        }

                        $param = array(
                            'url' => assetUrl($media),
                            'value' => 'EVERYONE',
                            'message' => $caption
                        );
                        $response = $this->fetchPost('/'.$account['sid'].'/photos', $param);
                        model('post')->setResult(json_encode($response), $post['id']);
                        return true;
                    } else {
                        $param = array(
                            'file_url' => assetUrl($media),
                            'value' => 'EVERYONE',
                            'message' => $caption
                        );
                        $response = $this->fetchPost('/'.$account['sid'].'/videos', $param);
                        model('post')->setResult(json_encode($response), $post['id']);
                        return true;
                    }
                } elseif(count($postData['media']) > 0) {
                    $medias = array();
                    $count = 0;
                    foreach($postData['media'] as $media) {
                        if (isImage($media)) {
                            if (model('user')->hasPermission('watermark')) {
                                $file = getWatermarkTmpFile($media);
                                $media  = doWaterMark($media, $file);

                            }
                            $param = array(
                                'url' => assetUrl($media),
                                'published' => false
                            );
                            $r = $this->fetchPost('/'.$account['sid'].'/photos', $param);
                            if (is_object($r)) {
                                $medias['attached_media['.$count.']'] = '{"media_fbid":"'.$r->id.'"}';
                                $count++;
                            }
                        } else {
                            $param = array('file_url' => assetUrl($media));
                            $r = $this->fetchPost('/'.$account['sid'].'/videos', $param);
                            if (is_object($r)) {
                                $medias['attached_media['.$count.']'] = '{"media_fbid":"'.$r->id.'"}';
                                $count++;
                            }
                        }
                    }

                    $param = array(
                        'message' => $caption
                    );
                    $param = array_merge($param, $medias);
                    $response = $this->fetchPost('/'.$account['sid'].'/feed', $param);
                    model('post')->setResult(json_encode($response), $post['id']);
                    return true;
                } else {
                    model('post')->setResult('No media files is found', $post['id']);
                    return false;
                }
                break;
            case 'link':
                $param = array('message' => $caption, 'link' => $link);
                $response = $this->fetchPost('/'.$account['sid'].'/feed', $param);
                model('post')->setResult(json_encode($response), $post['id']);
                return true;
                break;
            case 'text':
                $param = array('message' => $caption);
                $response = $this->fetchPost('/'.$account['sid'].'/feed', $param);
                model('post')->setResult(json_encode($response), $post['id']);
                return true;
                break;
            case 'livestream':
                $watermark_build = "";
                $add_text_build = "";
                switch ($postData['watermark']) {
                    case 1:

                        //ADD WATERMARK AND POSITION
                        $watermark_build = $this->getWatermarkBuild($postData);

                        break;

                    case 2:

                        //Add Text And Postion
                        $add_text = $postData['watermark_text'];
                        if($add_text != ""){


                            $add_text_build = $this->getWatermarkText($postData, $add_text);
                        }

                        break;
                }
                try {
                    $params = ['title' => $title];

                    if($caption != ""){
                        $params['description'] = $caption;
                    }

                    $media = (isset($postData['media'][0])) ? $postData['media'][0] : '';
                    $file = path($media);
                    $params['status'] = "LIVE_NOW";


                    //Create Live Video
                    $gid = $account['sid'];
                    $createLiveVideo = $this->fb->post('/'.$gid.'/live_videos', $params);

                    if(is_string($createLiveVideo)){
                        return $createLiveVideo;
                    }

                    //$videoId = $createLiveVideo->id;
                    $result = $createLiveVideo->getDecodedBody();
                    $stream_url = $result['stream_url'];
                    $file_stream = $file;

                    //-protocol_whitelist "file,http,https,tcp,tls"
                    if($params['status'] == "LIVE_NOW"){
                        $ffmpeg = config('ffmeg-path');
                        $livestream_code = sprintf(
                            $ffmpeg.' -re -i "%s" %s %s -b:v 0 -flags +global_header -acodec libmp3lame -ar 44100 -b:a 128k -pix_fmt yuv420p -profile:v baseline -bufsize 6000k -vb 400k -maxrate 1500k -deinterlace -vcodec libx264 -preset veryfast -g 30 -r 30 -f flv "%s" > /dev/null',
                            $file_stream,
                            $add_text_build,
                            $watermark_build,
                            $stream_url
                        );

                        //Start Live Stream
                        @exec($livestream_code);
                    }

                    return $createLiveVideo;

                } catch (Exception $e) {
                    model('post')->setResult($e->getMessage(), $post['id']);
                    return false;
                }
                break;
        }
        return true;
    }

    public function getWatermarkText($postData, $add_text) {
        $add_text_padding = 15;
        $add_text_color = "white";
        $add_text_fontsize = 30;
        $add_text_position = $postData['watermark_position'];
        $add_text_build = '-vf drawtext="fontsize='.$add_text_fontsize.':fontcolor='.$add_text_color.':text=\''.$add_text.'\'';
        switch ($add_text_position) {
            case 'top_left':
                $add_text_position = ':x='.$add_text_padding.':y='.$add_text_padding.'"';
                break;

            case 'top_right':
                $add_text_position = ':x=(w-text_w)-'.$add_text_padding.':y='.$add_text_padding.'"';
                break;

            case 'bottom_left':
                $add_text_position = ':x='.$add_text_padding.':y=(h-text_h)-'.$add_text_padding.'"';
                break;

            case 'bottom_right':
                $add_text_position = ':x=(w-text_w)-'.$add_text_padding.':y=(h-text_h)-'.$add_text_padding.'"';
                break;

            case 'top_center':
                $add_text_position = ':x=(w-text_w)/2:y='.$add_text_padding.'"';
                break;

            case 'bottom_center':
                $add_text_position = ':x=(w-text_w)/2:y=(h-text_h)-'.$add_text_padding.'"';
                break;

            default:
                $add_text_position = ':x=(w-text_w)/2:y=(h-text_h)/2"';
                break;
        }
        return $add_text_build;
    }

    public function getWatermarkBuild($postData) {
        $watermark = model('user')->getSettings('watermark-image');
        $watermark_position = $postData['watermark_position'];
        if($watermark != ""){
            $watermark_padding = 15;
            $watermark_build = '-i '.$watermark.' -filter_complex "overlay=';
            switch ($watermark_position) {
                case 'top_left':
                    $watermark_position = ''.$watermark_padding.':'.$watermark_padding.'"';
                    break;

                case 'top_right':
                    $watermark_position = '(main_w-overlay_w)-'.$watermark_padding.':'.$watermark_padding.'"';
                    break;

                case 'bottom_left':
                    $watermark_position = ''.$watermark_padding.':(main_h-overlay_h)-'.$watermark_padding.'"';
                    break;

                case 'bottom_right':
                    $watermark_position = '(main_w-overlay_w)-'.$watermark_padding.':(main_h-overlay_h)-'.$watermark_padding.'"';
                    break;

                case 'top_center':
                    $watermark_position = '(main_w-overlay_w)/2:'.$watermark_padding.'"';
                    break;

                case 'bottom_center':
                    $watermark_position = '(main_w-overlay_w)/2:(main_h-overlay_h)-'.$watermark_padding.'"';
                    break;

                default:
                    $watermark_position = '(main_w-overlay_w)/2:(main_h-overlay_h)/2"';
                    break;
            }

            return $watermark_position;
        }
        return '';
    }
}