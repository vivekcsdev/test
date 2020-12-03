<?php
require_once path('app/vendor/autoload.php');
require_once path('app/vendor/instagram-php/autoload.php');

class InstagramAPI extends API {

    public $username;
    public $password;
    public $proxy;
    public $instagramObj;
    public $sCode;
    public $vCode;
    public $twoFactorIdentifier = null;
    public $choice;

    public function init($username = null, $password = null, $proxy = null, $isLogin = false, $sCode = null, $vCode = null)
    {
        parent::__construct();

        $this->choice = config('instagram-challenge-type', '0');
        if ($username) {
            $this->username = $username;
            $this->password  = $password;
            $this->proxy = $proxy;
            $this->sCode = $sCode;
            $this->vCode = $vCode;

            $this->instagramObj = new \InstagramAPI\Instagram(false,false, array(
                'storage' => 'mysql',
                'dbhost' => config('db_host'),
                'dbname' => config('db_name'),
                'dbusername' => config('db_username'),
                'dbpassword' => config('db_password'),
                'dbtablename' => "instagram_sessions",
            ));

            $this->instagramObj->setVerifySSL(false);

            if ($proxy) $this->instagramObj->setProxy($proxy);

            if (!$isLogin) {
                try {
                    $this->instagramObj->login($this->username, $this->password);
                } catch (\Exception $e) {
                    $this->checkPoint($e);
                    throw new InvalidArgumentException($this->getMessage($e->getMessage()));
                }
            }
        }

        return $this;
    }

    public function getMessage($message) {
        try {
            $message = explode(": ", $message);
            if(count($message) == 2){
                return $message[1];
            }else if(count($message) > 2){
                return $message[2];
            }else{
                return $message[0];
            }
        } catch (Exception $e) {
            print_r($e);
            exit;
        }
    }

    public function loginUser() {
        if(session_get('twofactor_'.$this->username)) {
            return $this->validateTwoFactorLogin();
        }

        try {
            $response = $this->instagramObj->login($this->username, $this->password);
            return $this->checkTwoFactorLogin($response);
        } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {

            $response = $e->getResponse()->getChallenge();
            if (is_array($response)) {
                $apiPath = $response['api_path'];
            } else {
                $apiPath = $e->getResponse()->getChallenge()->getApiPath();
            }
            return $this->validateSecurityCode($apiPath);

        } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                'message' => l('login-on-instagram-pass-checkpoint')
            );

        } catch (\InstagramAPI\Exception\AccountDisabledException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-disabled-voilate")
            );

        } catch (\InstagramAPI\Exception\SentryBlockException $e) {
            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-banned-for-spamming")
            );

        } catch (\InstagramAPI\Exception\IncorrectPasswordException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-password-invalid")
            );

        } catch (\InstagramAPI\Exception\InstagramException $e) {
            if ($e->hasResponse()) {

                if($e->getResponse()->getMessage() == "consent_required"){
                    return array(
                        "status" => "error",
                        "error_type" => 'general',
                        "message" => l("go-to-login-on-instagram-try-again")
                    );
                }

                return array(
                    "status" => "error",
                    "error_type" => 'general',
                    "message" => $e->getResponse()->getMessage()
                );

            } else {

                $message = explode(":", $e->getMessage(), 2);
                return array(
                    "status" => "error",
                    "error_type" => 'general',
                    "message" => end($message)
                );
            }

        } catch (\Exception $e) {
            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("oops-something-went-wrong")
            );

        }
    }

    public function validateTwoFactorLogin() {
        $twoFactorIdentifier = session_get("twofactor_".$this->username);
        session_forget("twofactor_".$this->username);
        try {

            $this->instagramObj->finishTwoFactorLogin($this->username, $this->password,  $twoFactorIdentifier, $this->vCode);

            return array(
                "status" => "success",
                "message" => l("login-successful")
            );

        } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                'message' => l('login-on-instagram-pass-checkpoint')
            );

        } catch (\InstagramAPI\Exception\AccountDisabledException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-disabled-voilate")
            );

        } catch (\InstagramAPI\Exception\SentryBlockException $e) {
            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-banned-for-spamming")
            );

        } catch (InstagramAPI\Exception\IncorrectPasswordException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-password-invalid")
            );

        } catch (\InstagramAPI\Exception\InstagramException $e) {

            if ($e->hasResponse()) {

                if($e->getResponse()->getMessage() == "consent_required"){
                    return array(
                        "status" => "error",
                        "error_type" => 'general',
                        "message" => l("go-to-login-on-instagram-try-again")
                    );
                }

                if ($e->getResponse()->getMessage() == 'challenge_required') {
                    if (session_get('challenge_hidden_code')) {
                        $apiPath = $e->getResponse()->getChallenge()->getApiPath();
                        $this->sCode = session_get('challenge_hidden_code');
                        return $this->validateSecurityCode($apiPath);
                    }
                    $apiPath = $e->getResponse()->getChallenge()->getApiPath();
                    return $this->sendSecurityCode($apiPath);
                }
                return array(
                    "status" => "error",
                    "error_type" => 'general',
                    "message" => $e->getResponse()->getMessage()
                );

            } else {

                $message = explode(":", $e->getMessage(), 2);
                return array(
                    "status" => "error",
                    "error_type" => 'general',
                    "message" => end($message).'its here 2'
                );
            }

        } catch (\Exception $e) {
            $this->db->query("DELETE FROM instagram_sessions WHERE username=?", $this->username);
            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("oops-something-went-wrong")
            );

        }
    }

    public function checkTwoFactorLogin($response) {
        try {
            if (!is_null($response) && $response->isTwoFactorRequired()) {

                $phone_number = $response->getTwoFactorInfo()->getObfuscatedPhoneNumber();
                $twoFactorIdentifier = $response->getTwoFactorInfo()->getTwoFactorIdentifier();
                session_put('twofactor_'.$this->username, $twoFactorIdentifier);
                if ($this->sCode) session_put('challenge_hidden_code', $this->sCode);

                return array(
                    "status"   => "error",
                    'error_type' => 'enter-digit-two-factor',
                    "message"  => l("enter-number-sent-to-phone", array('phone' => $phone_number))
                );

            }

        } catch (Exception $e) {
            print_r($e);
            exit;
        }


        return array(
            "status" => "success",
            "message" => l("login-successful")
        );
    }
    public function checkPoint($e) {
        $challenge_type = $this->getMessage($e->getMessage());

        if($challenge_type == "challenge_required"
            || $challenge_type == "login_required"
            || $challenge_type == "checkpoint_required"
            || strpos($challenge_type, "The password you entered is incorrect") !== false
            || strpos($challenge_type, "Challenge required") !== false){
            $this->db->query("UPDATE accounts SET status=? WHERE social_type=? AND username=?", 0, 'instagram', $this->username);
            $this->db->query("DELETE FROM instagram_sessions WHERE username=?", $this->username);
            if (moduleExists('automation')) {
                $account = model('account')->findAccountByUsername($this->username, 'instagram');
                $this->db->query("UPDATE automations SET status=? WHERE account=?", 0, $account['id']);
                $automation = model('automation::automation')->findByAccount($account['id']);
                $this->db->query("DELETE FROM automations_actions WHERE automation_id=?", $automation['id']);
            }

        }
    }

    public function  validateSecurityCode($apiPath) {
        try {

            $confirmSecurityCode = $this->instagramObj->checkpoint->confirmSecurityCode($this->username, $this->password, $apiPath, $this->sCode);
            return $this->checkTwoFactorLogin($confirmSecurityCode);

        } catch (InvalidArgumentException $e) {
            return array(
                "status" => "error",
                'error_type' => 'general',
                "message" => $e->getMessage()
            );

        } catch (Exception $e) {

            if(empty($e)){
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            $response = $e->getResponse();

            if($response and $response->getStatus() != "ok"){
                try {
                    if($response->getMessage() == "This field is required."){
                        return $this->sendSecurityCode($apiPath);
                    }
                    return $this->resendSecurityCode($apiPath);
                } catch (Exception $e) {

                    return array(
                        "status" => "error",
                        'error_type' => 'general',
                        "message" => $e->getMessage()
                    );

                }
            } else {
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }
        }
    }

    public function sendSecurityCode($apiPath) {
        try {
            $sendSecurityCode = $this->instagramObj->checkpoint->sendSecurityCode($apiPath, $this->choice);

            if(empty($sendSecurityCode) || is_null($sendSecurityCode)){
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            if(isset($sendSecurityCode->message) && strpos($sendSecurityCode->message, "is not one of the available choices") !== false){
                $new_choice = $this->choice==1?0:1;
                $sendSecurityCode = $this->instagramObj->checkpoint->sendSecurityCode($apiPath, $new_choice);
            }

            if($sendSecurityCode->status != "ok"){
                if($sendSecurityCode->message == "This field is required."){
                    return $this->resendSecurityCode($apiPath);
                }

                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            if($sendSecurityCode->step_name == "verify_email"){
                return array(
                    "status" => "error",
                    'error_type' => 'enter-digit',
                    "message"  => l("enter-number-sent-to-email", array('email' => $sendSecurityCode->step_data->contact_point))
                );
            }else{
                return array(
                    "status" => "error",
                    'error_type' => 'enter-digit',
                    "message"  => l("enter-number-sent-to-phone", array('phone' => $sendSecurityCode->step_data->contact_point))
                );
            }

        } catch (InvalidArgumentException $e) {

            return array(
                "status" => "error",
                'error_type' => 'general',
                "message" => $e->getMessage()
            );

        }
    }

    public function resendSecurityCode($apiPath) {
        try {
            $resendSecurityCode = $this->instagramObj->checkpoint->resendSecurityCode($this->username, $apiPath, $this->choice);

            if(empty($resendSecurityCode) || is_null($resendSecurityCode)){
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            if(isset($resendSecurityCode->message) && strpos($resendSecurityCode->message, "is not one of the available choices") !== false){
                $new_choice = $this->choice==1?0:1;
                $resendSecurityCode = $this->instagramObj->checkpoint->resendSecurityCode($this->username, $apiPath, $new_choice);
            }

            if($resendSecurityCode->status != "ok"){
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            if($resendSecurityCode->step_name == "verify_email"){
                return array(
                    "status" => "error",
                    'error_type' => 'enter-digit',
                    "message"  => l("enter-number-sent-to-email", array('email' => $resendSecurityCode->step_data->contact_point))
                );
            }else{
                return array(
                    "status" => "error",
                    'error_type' => 'enter-digit',
                    "message"  => l("enter-number-sent-to-phone", array('phone' => $resendSecurityCode->step_data->contact_point))
                );
            }
        } catch (Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    function getCurrentUser(){
        try {
            $user = $this->instagramObj->account->getCurrentUser();
            return json_decode($user);
        } catch (\Exception $e) {
            $this->checkpoint($e);
            $this->db->query("DELETE FROM instagram_sessions WHERE username=?", $this->username);
            exit(json_encode(array(
                "type"  => "error",
                "message" => l('oops-something-went-wrong')
            )));
        }
    }

    function getAvatar($user) {
        if (isset($user->user->profile_pic_url)) {
            $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
            if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
            $file = $dir.md5($user->user->username).'.jpg';
            getFileViaCurl($user->user->profile_pic_url, $file);
            return $file;
        }
        return null;
    }

    function searchLocations($term){
        try {
            $response = $this->instagramObj->location->search("37.2759932","-104.6515434", $term);
            if ($response->isOk()) {
                return $response->getVenues();
            }
            return false;
        } catch (Exception $e) {
            $this->checkpoint($e);
            return false;
        }
    }


    public function post($post, $account) {
        $refineImages = array();
        try {
            $postData = perfectUnserialize($post['type_data']);
            $postLocation = '';
            if (isset($postData['selected_location']) and $postData['selected_location']) {
                $postLocation = @unserialize($postData['selected_location']);
                if (!$postLocation || !($postLocation instanceof \InstagramAPI\Response\Model\Location)) {
                    $postLocation = '';
                }
            }
            $spintax = new Spintax();
            $caption    = @$spintax->process($this->formatText($postData['text']));
            $comment    = @$spintax->process($this->formatText($postData['first_comment']));
            autoLoadVendor();
            $emojione = new \Emojione\Client(new \Emojione\Ruleset());
            $caption = $emojione->shortnameToUnicode($caption);
            $data = array('caption' => $caption);
            $account = model('account')->find($account);
            $proxy = model('proxy')->findOneProxy($account['proxy'], $account);
            $this->init($account['username'], mDcrypt($account['password']), $proxy);



            switch ($post['type']) {
                case 'media':
                    if($postLocation) $data['location'] = $postLocation;
                    $media = (isset($postData['media'][0])) ? $postData['media'][0] : '';
                    if ($media) {
                        if (isImage($media)) {
                            $media = $this->imageHandler($media, 'photo');
                            $refineImages[] = $media;
                            $response = $this->instagramObj->timeline->uploadPhoto(path($media), $data);
                            model('post')->setResult($response, $post['id']);
                            $response = json_decode($response);
                            Hook::getInstance()->fire('instagram.response', null, array($this,$post,$postData, $response));
                        } else {
                            if (!$this->videoPostSupported()) {
                                model('post')->setResult('Instagram video not supported', $post['id']);
                                return false;
                            }
                            $response = $this->instagramObj->timeline->uploadVideo(path($media), $data);
                            model('post')->setResult($response, $post['id']);
                            $response = json_decode($response);
                        }

                        if ($data['caption'] && empty($response->media->caption)) {
                            sleep(5);//sleep for 5 seconds to try add
                            try {
                                $this->instagramObj->media->edit($response->media->id, $data['caption']);
                            } catch (Exception $e){}
                        }
                        if ($postData['first_comment']) {
                            $this->instagramObj->media->comment($response->media->pk, $comment);
                        }
                    } else {
                        model('post')->setResult('No media files is found', $post['id']);
                        return false;
                    }
                    break;
                case 'igtv':
                    $media = (isset($postData['media'][0])) ? $postData['media'][0] : '';
                    if (!$this->videoPostSupported()) {
                        model('post')->setResult('Instagram video not supported', $post['id']);
                        return false;
                    }
                    $data['title'] = 'This is good app ';
                    $response = $this->instagramObj->tv->uploadVideo(path($media), $data);
                    model('post')->setResult($response, $post['id']);
                    $response = json_decode($response);
                    break;
                case 'story':
                    if ($postData['story_link']) {
                        $data['link'] = $postData['story_link'];
                    }
                    $media = (isset($postData['media'][0])) ? $postData['media'][0] : '';
                    if ($media) {
                        if (isImage($media)) {
                            $media = $this->imageHandler($media, 'story');
                            $refineImages[] = $media;
                            $data = Hook::getInstance()->fire('instagram.story.data', $data, array($this,$post,$postData));
                            if ($postData['friend_story']) {
                                $response = $this->instagramObj->story->uploadCloseFriendsPhoto(path($media), $data);
                            } else {
                                $response = $this->instagramObj->story->uploadPhoto(path($media), $data);
                            }
                            model('post')->setResult($response, $post['id']);
                            $response = json_decode($response);
                            Hook::getInstance()->fire('instagram.response', null, array($this,$post,$postData, $response));
                        } else {

                            if (!$this->videoPostSupported()) {
                                model('post')->setResult('Instagram video not supported', $post['id']);
                                return false;
                            }

                            if ($postData['friend_story']) {
                                $response = $this->instagramObj->story->uploadCloseFriendsVideo(path($media), $data);
                            } else {
                                $response = $this->instagramObj->story->uploadVideo(path($media), $data);
                            }
                        }

                        if ($data['caption'] && empty($response->media->caption)) {
                            sleep(5);//sleep for 5 seconds to try add
                            try {
                                $this->instagramObj->media->edit($response->media->id, $data['caption']);
                            } catch (Exception $e){}
                        }

                        if ($postData['first_comment']) {
                            $this->instagramObj->media->comment($response->media->pk, $comment);
                        }
                    } else {
                        model('post')->setResult('No media files is found', $post['id']);
                        return false;
                    }
                    break;
                case 'livestream':
                    try {

                        $createLive = $this->instagramObj->live->create();
                        $createLive = json_decode( $createLive );
                        $broadcastId = $createLive->broadcast_id;

                        //Start Live
                        $startLive = $this->instagramObj->live->start($broadcastId);
                        $startLive = json_decode( $startLive );

                        //Watermark
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

                        $media = (isset($postData['media'][0])) ? $postData['media'][0] : '';
                        $file = path($media);
                        $videoId = $broadcastId;

                        $stream_url = $createLive->upload_url;
                        $file_stream = $file;
                        $ffmpeg = config('ffmeg-path');
                        $livestream_code = sprintf(
                            $ffmpeg.' -re -i "%s" %s %s -b:v 0 -flags +global_header -acodec libmp3lame -ar 44100 -b:a 128k -pix_fmt yuv420p -profile:v baseline -bufsize 6000k -vb 400k -maxrate 1500k -deinterlace -vcodec libx264 -preset veryfast -g 30 -r 30 -f flv "%s" > /dev/null',
                            $file_stream,
                            $add_text_build,
                            $watermark_build,
                            $stream_url
                        );


                        @exec($livestream_code);

                        return true;

                    } catch (Exception $e) {
                        $this->checkPoint($e);
                        model('post')->setResult($this->getMessage($e->getMessage()), $post['id']);
                        return false;
                    }
                    break;
                case 'album':

                    if($postLocation) $data['location'] = $postLocation;
                    $mediaItems = array();
                    foreach($postData['media'] as $media) {
                        if (isImage($media)) {
                            $media = $this->imageHandler($media, 'carousel');
                            $refineImages[] = $media;
                            $mediaItems[] = array(
                                'type' => 'photo',
                                'file' => path($media)
                            );
                        } else {
                            if ($this->videoPostSupported()) {
                                $mediaItems[] = array(
                                    'type' => 'video',
                                    'file' => path($media)
                                );
                            }
                        }
                    }

                    $response = $this->instagramObj->timeline->uploadAlbum($mediaItems, $data);
                    model('post')->setResult($response, $post['id']);
                    $response = json_decode($response);
                    Hook::getInstance()->fire('instagram.response', null, array($this,$post,$postData, $response));

                    if ($data['caption'] && empty($response->media->caption)) {
                        sleep(5);//sleep for 5 seconds to try add
                        try {
                            $this->instagramObj->media->edit($response->media->id, $data['caption']);
                        } catch (Exception $e){

                        }
                    }
                    if ($postData['first_comment']) {
                        $this->instagramObj->media->comment($response->media->pk, $comment);
                    }
                    break;
            }
        } catch (Exception $e) {
            
            $this->checkPoint($e);
            foreach($refineImages as $image) {
                delete_file(path($image));
            }
            model('post')->setResult($this->getMessage($e->getMessage()), $post['id']);
            return false;
        }

        foreach($refineImages as $image) {
            delete_file(path($image));
        }
        return true;
    }

    public function imageHandler($media, $type) {
        $tmpFile = '';

        switch ($type) {
            case 'direct':
                $image =   new \InstagramAPI\Media\Photo\InstagramPhoto(path($media), [
                    "targetFeed" => \InstagramAPI\Constants::FEED_DIRECT,
                    "operation" => \InstagramAPI\Media\InstagramMedia::CROP
                ]);
                break;
            case 'photo':
                $image =   new \InstagramAPI\Media\Photo\InstagramPhoto(path($media), [
                    "targetFeed" => \InstagramAPI\Constants::FEED_TIMELINE,
                    "operation" => \InstagramAPI\Media\InstagramMedia::CROP
                ]);
                break;

            case 'story':
                $image =   new \InstagramAPI\Media\Photo\InstagramPhoto(path($media), [
                    "targetFeed" => \InstagramAPI\Constants::FEED_STORY,
                    "operation" => \InstagramAPI\Media\InstagramMedia::CROP,
                    "minAspectRatio" => \InstagramAPI\MediaAutoResizer::BEST_MIN_STORY_RATIO,
                    "maxAspectRatio" => \InstagramAPI\MediaAutoResizer::BEST_MAX_STORY_RATIO
                ]);
                break;

            case 'carousel':
                $image =  new \InstagramAPI\Media\Photo\InstagramPhoto(path($media), [
                    "targetFeed" => \InstagramAPI\Constants::FEED_TIMELINE_ALBUM,
                    "operation" => \InstagramAPI\Media\InstagramMedia::CROP,
                ]);
                break;
        }

        $fileContent = file_get_contents($image->getFile());

        $file = getWatermarkTmpFile($image->getFile());
        file_put_contents(path($file), $fileContent);
        if ($tmpFile) {
            @delete_file(path($tmpFile));
            @delete_file(path(str_replace('920', '200', $tmpFile)));
        }
        return doWaterMark($file, $file);
    }

    public function videoPostSupported() {
        $ffmpeg = config('ffmeg-path', "");
        $ffprobe = config('ffprobe-path', "");
        \InstagramAPI\Media\Video\FFmpeg::$defaultBinary = ($ffmpeg == "") ? NULL : $ffmpeg;
        \InstagramAPI\Utils::$ffprobeBin = ($ffprobe == '') ? NULL : $ffprobe;

        if (\InstagramAPI\Utils::checkFFPROBE()) {
            try {
                InstagramAPI\Media\Video\FFmpeg::factory($ffmpeg);
                return true;
            } catch (\Exception $e) {
                print_r($e->getMessage());
                exit;
                return false;
            }
        }
        return false;
    }

    public function formatText($text) {
        $text = preg_replace("/\r\n\r\n/", "?.??.?", $text);
        $text = preg_replace("/\r\n/", "?.?", $text);
        $text = str_replace("?.? ?.?", "?.?", $text);
        $text = str_replace(" ?.?", "?.?", $text);
        $text = str_replace("?.? ", "?.?", $text);
        $text = str_replace("?.??.?", "\n\n", $text);
        $text = str_replace("?.?", "\n", $text);
        return $text;
    }

    public function analytics() {
        try {
            $userDetails = $this->instagramObj->people->getSelfInfo();
            $userDetails = json_decode($userDetails);
            $userDetails = $userDetails->user;
            $result = array(
                "posts" => array(),
                "userinfo" =>$userDetails,
                "engagement" => 0,
                "average_likes" => 0,
                "average_comments" => 0,
                "hashtags" => array(),
                "mentions" => array(),
            );

            $medias = $this->instagramObj->timeline->getSelfUserFeed();
            $medias = $medias->getItems();
            $followerCount  = (int)$userDetails->follower_count;
            $totalLikes = 0;
            $totalComments = 0;
            $postCount = 0;
            $posts = array();
            if (!empty($medias)) {
                foreach($medias as $key => $row) {
                    $totalLikes += (int) $row->getLikeCount();
                    $totalComments += (int) $row->getCommentCount();
                    $engagement = (int) $row->getLikeCount() + (int)$row->getViewCount()+(int)$row->getCommentCount();

                    $rate = 0;
                    if($engagement != 0 && $followerCount != 0){
                        $rate = $engagement/$followerCount*100;
                    }
                    $posts[] = array(
                        'media' => $row->getCode(),
                        'engagement' => $rate
                    );

                    if($row->getCaption() != ""){
                        $hashtags = $this->getHashtags($row->getCaption()->getText());
                        foreach ($hashtags as $hashtag) {
                            if (!isset($result['hashtags'][$hashtag])) {
                                $result['hashtags'][$hashtag] = 1;
                            } else {
                                $result['hashtags'][$hashtag]++;
                            }
                        }

                        $mentions = $this->getMentions($row->getCaption()->getText());
                        foreach ($mentions as $mention) {
                            if (!isset($result['mentions'][$mention])) {
                                $result['mentions'][$mention] = 1;
                            } else {
                                $result['mentions'][$mention]++;
                            }
                        }
                    }

                    $postCount++;

                    if ($key >= 10) break;

                }
                usort($posts, function($a, $b) {
                    return $b['engagement'] - $a['engagement'];
                });
            }

            $engagement = array_sum(array_column($posts, 'engagement'));

            if ($engagement != 0 && !empty($posts)) {
                $engagement = number_format($engagement / sizeof($posts), 2);
            }
            $result['engagement'] = $engagement;

            if ($totalComments!= 0 && $postCount != 0) {
                $result['average_comments'] = number_format($totalComments / $postCount, 2);
            }

            if ($totalLikes != 0 && $postCount != 0) {
                $result['average_likes'] = number_format($totalLikes / $postCount, 2);
            }

            arsort($result['hashtags']);
            arsort($result['mentions']);
            $posts = array_slice($posts, 0, 3);
            $result['hashtags'] = array_slice($result['hashtags'], 0, 15);
            $result['mentions'] = array_slice($result['mentions'], 0, 15);
            $result['posts'] = $posts;

            return $result;
        } catch (Exception $e) {
            return false;
        }
    }


    public static function getHashtags($text) {
        preg_match_all('/#([^\s#]+)/', $text, $result);
        return $result[1];

    }

    public  function getMentions($text) {
        preg_match_all('/@([^\s@]+)/', $text, $result);
        return $result[1];
    }


    public  function getHtml($shortcode) {
        $url = 'https://api.instagram.com/oembed/?url=http://instagr.am/p/' . $shortcode . '/&hidecaption=true&maxwidth=450';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($data);
        return $response ? $response->html : false;
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

    function genateUID(){
        return \InstagramAPI\Signatures::generateUUID();
    }

    function getUserInfo($id = null){
        if($id == "") $id = $this->instagramObj->account_id;
        try {
            $response = $this->instagramObj->people->getInfoById($id);
            $response = json_decode($response);
            if(isset($response->user) && !empty($response->user)){
                return $response->user;
            }
            return false;
        } catch (Exception $e) {
            $this->checkpoint($e);
            return false;
        }
    }

    public function getUserInfoByName($id = null){
        if($id == "") $id = $this->instagramObj->account_id;
        try {
            $response = $this->instagramObj->people->getInfoByName($id);
            $response = json_decode($response);
            if(isset($response->user) && !empty($response->user)){
                return $response->user;
            }
            return false;
        } catch (Exception $e) {
            $this->checkpoint($e);
            return false;
        }
    }

    function searchForUsernames($term){
        try {
            $response = $this->instagramObj->people->search($term, array(), $this->genateUID());
            $response = json_decode($response);
            if(isset($response->users) && !empty($response->users)){
                return $response->users;
            }
            return false;
        } catch (Exception $e) {
            $this->checkpoint($e);
            return false;
        }
    }


    function searchForTags($term){
        try {
            $response = $this->instagramObj->hashtag->search($term, array(), $this->genateUID());
            $response = json_decode($response);
            if(isset($response->results) && !empty($response->results)){
                return $response->results;
            }
            return false;
        } catch (Exception $e) {
            $this->checkpoint($e);
            return false;
        }
    }

    function searchForlocation($term){
        try {
            $response = $this->instagramObj->location->findPlaces($term, array(), $this->genateUID());
            $response = json_decode($response);
            if(isset($response->items) && !empty($response->items)){
                return $response->items;
            }
            return false;
        } catch (Exception $e) {
            $this->checkpoint($e);
            return false;
        }
    }

    public function getFeeds($target = null, $userid = null, $automation, $maxId = null, $limit = 80) {
        if(!$userid) $userid = $this->instagramObj->account_id;

        try {
            if ($target) {
                switch($target['type']) {
                    case 'tags':
                        $items = array();
                        do {
                            $result = $this->instagramObj->hashtag->getFeed($target['value'], $this->genateUID(), $maxId);
                            $result = json_decode($result);

                            if(isset($result->ranked_items)){
                                $items = array_merge($items, $result->ranked_items);
                            }else if(isset($result->items)){
                                $items = array_merge($items, $result->items);
                            }

                        } while (isset($result->next_max_id) && !is_null($maxId = $result->next_max_id) && $limit > count($items));

                        return $items;
                        break;
                    case 'keywords':
                        $items = array();
                        do {
                            $result = $this->instagramObj->hashtag->getFeed($target['value'], $this->genateUID(), $maxId);
                            $result = json_decode($result);

                            if(isset($result->ranked_items)){
                                $items = array_merge($items, $result->ranked_items);
                            }else if(isset($result->items)){
                                $items = array_merge($items, $result->items);
                            }

                        } while (isset($result->next_max_id) && !is_null($maxId = $result->next_max_id) && $limit > count($items));

                        return $items;
                        break;
                    case 'users':
                        $theUserId = $this->instagramObj->people->getUserIdForName($target['value']);
                        $result = $this->instagramObj->timeline->getUserFeed($theUserId, $this->genateUID());
                        $result = json_decode($result);
                        if(isset($result->items)) return $result->items;
                        return array();
                        break;
                    case 'target-follower':
                        $value = $target['value'];
                        $usernames = explode(',', model('automation::automation')->getSettingValue('users', $automation));
                        $users = array();
                        if ($value == 1 or $value == 3) $users[] = $this->instagramObj->account_id;
                        if ($value == 2 or $value == 1) {
                            foreach($usernames as $username){
                                $users[] = $this->instagramObj->people->getUserIdForName($username);
                            }
                        }

                        $userid = $users[rand(0, count($users)-1)];
                        $followers = $this->getFollowers($userid);
                        $toUseFollowers = array();
                        for($i = 1;$i<=5;$i++) {
                            $index = rand(0, 5);
                            if (isset($followers[$index]))$toUseFollowers[] = $followers[$index];
                        }
                        $results = array();
                        foreach($toUseFollowers as $follower) {
                            $result = $this->instagramObj->timeline->getUserFeed($follower->pk, $this->genateUID());
                            $result = json_decode($result);
                            if(isset($result->items)) {
                                $result =  $result->items;
                                $results = array_merge($results, $result);
                            }

                        }
                        return $results;
                        break;
                    case 'target-following':
                        $value = $target['value'];
                        $usernames = explode(',', model('automation::automation')->getSettingValue('users', $automation));
                        $users = array();
                        if ($value == 1 or $value == 3) $users[] = $this->instagramObj->account_id;
                        if ($value == 2 or $value == 1) {
                            foreach($usernames as $username){
                                $users[] = $this->instagramObj->people->getUserIdForName($username);
                            }
                        }

                        $userid = $users[rand(0, count($users)-1)];
                        $followers = $this->getFollowing($userid);
                        $toUseFollowers = array();
                        for($i = 1;$i<=5;$i++) {
                            $index = rand(0, 5);
                            if (isset($followers[$index]))$toUseFollowers[] = $followers[$index];
                        }
                        $results = array();
                        foreach($toUseFollowers as $follower) {
                            $result = $this->instagramObj->timeline->getUserFeed($follower->pk, $this->genateUID());
                            $result = json_decode($result);
                            if(isset($result->items)) {
                                $result =  $result->items;
                                $results = array_merge($results, $result);
                            }
                        }
                        return $results;
                        break;
                    case 'locations':
                        $items = array();
                        $pk = '';
                        foreach($this->searchForlocation($target['value']) as $location) {
                            if ($location->title == $target['value']) $pk = $location->location->pk;
                        }


                        if ($pk) {
                            do {
                                $response = $this->instagramObj->location->getFeed($pk, $this->genateUID(), "ranked", null, null, $maxId);
                                $response = json_decode($response);
                                foreach ($response->sections as $row) {
                                    $medias = $row->layout_content->medias;
                                    foreach ($medias as $media) {
                                        $items[] = $media->media;
                                    }
                                }

                            } while (isset($response->next_max_id) && !is_null($maxId = $response->next_max_id) && $limit > count($items));
                            return $items;
                        }
                        break;
                }
            } else {
                if (!$limit) {
                    $result = array();
                    do {
                        $response = $this->instagramObj->timeline->getUserFeed($userid, $maxId);
                        $response = json_decode($response);
                        $result = array_merge($result, $response->items);
                    } while (isset($response->next_max_id) && !is_null($maxId = $response->next_max_id) && $limit > count($result));
                    return $result;
                } else {
                    $response = $this->instagramObj->timeline->getUserFeed($userid, $maxId);
                    $response = json_decode($response);
                    return $response;
                }
            }
        } catch (Exception $e){
            //print_r($e->getMessage());

            $this->checkPoint($e);
            return array();
        }
    }

    public function isBlacklisted($row, $type = 'feed', $automation) {
        $text = '';
        if ($type == 'feed' and isset($row->caption->text)) {
            $text = $row->caption->text;
        }

        if (!$text) return false;
        $blackListTags = explode(',', model('automation::automation')->getSettingValue('blacklist-tags',$automation));
        $keywords = explode(',', model('automation::automation')->getSettingValue('blacklist-keywords',$automation));
        $usernames = explode(',', model('automation::automation')->getSettingValue('blacklist-usernames',$automation));
        foreach($blackListTags as $tag) {
            if (mb_strpos($text, '#'.$tag) !== false) return true;
        }

        foreach($keywords as $tag) {
            if (mb_strpos($text, $tag) !== false) return true;
        }

        if (isset($row->user)) {
            $username = $row->user->username;
            foreach($usernames as $name) {
                if ($username == $name) return true;
            }
        }
        return false;

    }

    public function mediaFilter($row,$automation) {
        $mediaType = model('automation::automation')->getSettingValue('media-type',$automation);
        $mediaAge = model('automation::automation')->getSettingValue('media-age',$automation);

        if ($mediaType and $mediaType != 1 and isset($row->media_type)) {
            switch($mediaType) {
                case '2':
                    $type = 1;
                case '3':
                    $type = 2;
                    break;
            }
            if ($row->media_type != $type) return false;
        }

        //lets do media age
        $times = array("2" => 1800, "3" => 86400, "4" => 604800, "5" => 2419200);
        if($mediaAge != 1 and isset($row->taken_at) and strtotime(time()) - $row->taken_at > $times[$mediaAge]) return false;
        return true;
    }

    public function minLikeCommentFilter($row,$automation) {

        $minLikes = model('automation::automation')->getSettingValue('min-likes',$automation);
        $maxLikes = model('automation::automation')->getSettingValue('max-likes',$automation);

        $minComments = model('automation::automation')->getSettingValue('min-comments',$automation);
        $maxComments = model('automation::automation')->getSettingValue('max-comments',$automation);


        if (isset($row->like_count) and $minLikes != 0 and $minLikes > $row->like_count) return false;
        if (isset($row->like_count) and $maxLikes != 0 and $maxLikes < $row->like_count) return false;

        if (isset($row->comment_count) and $minComments != 0 and $minComments > $row->comment_count) return false;
        if (isset($row->comment_count) and $maxComments != 0 and $maxComments < $row->comment_count) return false;
        return true;
    }

    public function minFollowFilter($row, $automation) {
        $minFollower = model('automation::automation')->getSettingValue('min-follower',$automation);
        $maxFollower = model('automation::automation')->getSettingValue('max-follower',$automation);

        $minFollowing = model('automation::automation')->getSettingValue('min-following',$automation);
        $maxFollowing = model('automation::automation')->getSettingValue('max-following',$automation);

        if (isset($row->user)) {
            $userInfo = $this->getUserInfo($row->user->pk);
            if ($minFollower != 0 and $userInfo->follower_count < $minFollower) return false;
            if ($maxFollower != 0 and $userInfo->follower_count > $maxFollower) return false;

            if ($minFollowing != 0 and $userInfo->following_count < $minFollowing) return false;
            if ($maxFollowing != 0 and $userInfo->following_count > $maxFollowing) return false;
        }

        return true;
    }

    public function filterFeeds($feeds , $type, $automation) {
        $result = array();

        try {
            if ($feeds) {
                foreach ($feeds as $key => $row) {
                    $row = (object) $row;
                    if ($this->isBlacklisted($row, 'feed',$automation)) continue;

                    if (!$this->mediaFilter($row,$automation)) continue;
                    if (!$this->minLikeCommentFilter($row,$automation)) continue;
                    if (!$this->minFollowFilter($row, $automation)) continue;

                    $result[$key] = $row;
                }
            }
        } catch (Exception $e){}
        return $result;
    }

    public function filterComment($feeds, $actionRow) {
        $result = array();
        foreach($feeds as $key => $value) {
            if (isset($value->comment_disabled) and $value->comment_disabled == 1) continue;
            //don't comment same user

            if (isset($value->user)) {
                if(model('automation::automation')->logExists($actionRow['id'], $value->user->pk, 'comment')) continue;
            }
            $result[$key] = $value;
        }
        return $result;
    }

    public function limitFeedDo($feeds, $automation) {
        $account = model('account')->find($automation['account']);
        return array_slice($feeds, 0, config($account['social_type'].'-action-per-run', 2));
    }

    public function filterPrivateUsers($users, $automation) {

        $value = model('automation::automation')->getSettingValue('follow-private-user',$automation);
       if (!$value) return $users;
        $result = array();
        foreach($users as $user) {
            if (isset($user->is_private) and $user->is_private != 1) {
                $result[] = $user;
            }
        }
        return $result;
    }

    public function comment($id, $comment) {
        try {
            $spintax  = new Spintax();
            $comment = @$spintax->process($comment);
            $response = $this->instagramObj->media->comment($id, $comment);
            $response = json_decode($response);
            return $response;
        } catch (Exception $e) {
            $response = json_decode($e->getResponse());
            $this->checkpoint($e);
            return $response;
        }
    }

    public function like($id){
        try {
            $response = $this->instagramObj->media->like($id, 0);
            $response = json_decode($response);
            return $response;
        } catch (Exception $e) {
            $response = json_decode($e->getResponse());
            $this->checkpoint($e);
            return $response;
        }
    }

    public function repost($row, $automation) {
        $spintax  = new Spintax();

        // Download the media
        $photos = array();
        $videos = array();
        if ($row->media_type == 1) {
            $photos[] = $row->image_versions2->candidates[0]->url;
        } elseif($row->media_type == 2) {
            $videos[] = $row->video_versions[0]->url;
        } elseif($row->media_type == 8) {
            foreach($row->carousel_media as $m) {
                if ($m->media_type == 1) {
                    $photos[] = $m->image_versions2->candidates[0]->url;
                } else {
                    $videos[] = $m->video_versions[0]->url;
                }
            }
        }
        
        $downloadPhotos = array();
        $downloadVideos = array();

        foreach($photos as $photo) {
            $parts = parse_url($photo);
            if (empty($parts['path'])) {
                continue;
            }

            $ext = strtolower(pathinfo($parts['path'], PATHINFO_EXTENSION));
            $downloadPhotos[] = downloadUrlContent($photo, $ext);
        }

        foreach($videos as $video) {
            $parts = parse_url($video);
            if (empty($parts['path'])) {
                continue;
            }

            $ext = strtolower(pathinfo($parts['path'], PATHINFO_EXTENSION));
            $downloadVideos[] = downloadUrlContent($video, $ext);
        }

        $mergeValues = array_merge($downloadPhotos, $downloadVideos);

        if (empty($mergeValues)) {
            return false;
        }

        $caption = (isset($row->caption)) ? $row->caption->text : '';

        $predefinedCaption = model('automation::automation')->getSettingValue('reposts', $automation);
        $username = (isset($row->user)) ? $row->user->username : '';
        $fullname = (isset($row->user)) ? $row->user->full_name : $username;

        $caption = str_replace(array('{originalcaption}','{username}','{fullname}'), array($caption, $username, $fullname), $predefinedCaption);

        $caption = @$spintax->process($caption);
        $caption = mb_substr($caption, 0, 2200);
        $caption = preg_replace("/\r\n\r\n/", "?.??.?", $caption);
        $caption = preg_replace("/\r\n/", "?.?", $caption);
        $caption = str_replace("?.? ?.?", "?.?", $caption);
        $caption = str_replace(" ?.?", "?.?", $caption);
        $caption = str_replace("?.? ", "?.?", $caption);
        $caption = str_replace("?.??.?", "\n\n", $caption);
        $caption = str_replace("?.?", "\n", $caption);

        // Try to repost
        try {
            $response = array();
            if (count(array_merge($downloadPhotos, $downloadVideos)) > 1) {
                $medias = [];

                foreach($downloadPhotos as $photo) {
                    $media[] = array('type' => 'photo', 'file' => $photo);
                }

                foreach($downloadVideos as $video) {
                    $media[] = array('type' => 'video', 'file' => $video);
                }


                $response = $this->instagramObj->timeline->uploadAlbum($medias, ['caption' => $caption]);
            } else {
                if (empty($downloadPhotos)) {
                    $response = $this->instagramObj->timeline->uploadVideo($downloadVideos[0], ["caption" => $caption]);
                } else {
                    $response = $this->instagramObj->timeline->uploadPhoto($downloadPhotos[0], ["caption" => $caption]);
                }
            }

            //delete the download files
            foreach(array_merge($downloadVideos,$downloadPhotos) as $file) {
                delete_file(path($file));
            }
            return true;
        } catch (\Exception $e) {
            //print_r($e);
            return false;
        }
    }

    public function getFollowers($userid = null, $maxId = null) {
        if (!$userid) $userid = $this->instagramObj->account_id;

        $response = $this->instagramObj->people->getFollowers($userid, $this->genateUID(), array(), $maxId);
        $response = json_decode($response);
        if(isset($response->users) && !empty($response->users)){
            return $response->users;
        }
        return array();
    }

    public function getFollowing($userid = null, $maxId = null) {
        if (!$userid) $userid = $this->instagramObj->account_id;

        $response = $this->instagramObj->people->getFollowing($userid, $this->genateUID(), array(), $maxId);
        $response = json_decode($response);
        if(isset($response->users) && !empty($response->users)){
            return $response->users;
        }
        return array();
    }

    public function sendMessage($id, $message) {
        try {
            $userid = array('users' => array($id));
            $message = (array)$message;
            $spintax  = new Spintax();


            $message = @$spintax->process($message);
            $response = $this->instagramObj->direct->sendText($userid, $message);
            $response = json_decode($response);
            return $response;
        } catch (Exception $e) {
            $response = $e->getMessage();
            $this->checkpoint($e);
            return $response;
        }
    }

    function processActivity($account, $automation, $actionRow) {
        $proxy = model('proxy')->findOneProxy($account['proxy'], $account);
        try {
            $this->init($account['username'], mDcrypt($account['password']), $proxy);
            $target = model('automation::automation')->getTarget($automation);


            $finilize = false;
            switch($actionRow['action']) {
                case 'comments':

                    $feeds = $this->getFeeds($target, null, $automation);

                    $feeds = $this->filterFeeds($feeds, 'comments',$automation);
                    $feeds = $this->filterComment($feeds, $actionRow);
                    $feeds = $this->limitFeedDo($feeds, $automation);


                    foreach($feeds as $key => $row) {
                        $finilize = true;
                        $comment = model('automation::automation')->getComment($automation);
                        if(isset($row->id)) {
                            $this->comment($row->id, $comment);
                            model('automation::automation')->addLog($automation['id'],$actionRow['id'], $row->user->pk, 'comments', perfectSerialize($row));
                        }
                    }

                    break;
                case 'likes':
                    $feeds = $this->getFeeds($target, null, $automation);

                    $feeds = $this->filterFeeds($feeds, 'likes',$automation);
                    $feeds = $this->limitFeedDo($feeds, $automation);


                    foreach($feeds as $key => $row) {
                        if(isset($row->id)) {
                            $this->like($row->id);
                            $finilize = true;
                            model('automation::automation')->addLog($automation['id'],$actionRow['id'], $row->user->pk, 'likes', perfectSerialize($row));
                        }
                    }
                    break;
                case 'repost':
                    $feeds = $this->getFeeds($target, null, $automation);

                    $feeds = $this->filterFeeds($feeds, 'repost',$automation);
                    $feeds = $this->limitFeedDo($feeds, $automation);


                    foreach($feeds as $key => $row) {
                        if(isset($row->id)) {
                            $this->repost($row, $automation);
                            $finilize = true;
                            model('automation::automation')->addLog($automation['id'],$actionRow['id'], $row->user->pk, 'repost', perfectSerialize($row));
                        }
                    }
                    break;
                case 'messages':
                    $target = model('automation::automation')->getSettingValue('messages-target', $automation);
                    if ($target == 1) {
                        $followers = $this->getFollowers();

                        $storedFollowers = ($actionRow['data']) ? perfectUnserialize($actionRow['data']) : array();
                        $newToStore = array();

                        foreach($followers as $follower) {

                            $newToStore[] = $follower->pk;
                            if(!empty($storedFollowers) and !in_array($follower->pk, $storedFollowers)) {
                                if (!model('automation::automation')->logExists($actionRow['id'], $follower->pk,'message')) {
                                    $message = model('automation::automation')->getMessage($automation);
                                    $this->sendMessage($follower->pk, $message);
                                    $finilize = true;
                                    model('automation::automation')->addLog($automation['id'],$actionRow['id'], $follower->pk, 'messages', perfectSerialize($follower));

                                }
                            }
                        }


                        Database::getInstance()->query("UPDATE automations_actions SET data=? WHERE id=?", perfectSerialize($newToStore), $actionRow['id']);

                    } else {
                        $feeds = $this->getFeeds($target, null, $automation);

                        $feeds = $this->filterFeeds($feeds, 'repost',$automation);
                        $feeds = $this->limitFeedDo($feeds, $automation);


                        foreach($feeds as $key => $row) {
                            if (!model('automation::automation')->logExists($actionRow['id'], $row->user->pk,'message')) {
                                $message = model('automation::automation')->getMessage($automation);
                                $this->sendMessage($row->user->pk, $message);
                                $finilize = true;
                                model('automation::automation')->addLog($automation['id'],$actionRow['id'], $row->user->pk, 'message', perfectSerialize($row));

                            }
                        }
                    }
                    break;
                case 'follow':
                    $feeds = $this->getFeeds($target, null, $automation);

                    $feeds = $this->filterFeeds($feeds, 'repost',$automation);
                    $feeds = $this->limitFeedDo($feeds, $automation);
                    $users = array();

                    foreach($feeds as $key => $row) {
                        if (isset($row->user)) {
                            $users = array_merge($this->getFollowers($row->user->pk));
                           $users = array_merge($this->getFollowing($row->user->pk));
                        }

                    }


                    $users = $this->filterPrivateUsers($users, $automation);
                    $users = $this->limitFeedDo($users, $automation);



                    foreach($users as $user) {
                        if (!model('automation::automation')->logExists($actionRow['id'], $user->pk,'follow')) {
                            $rs = $this->followUser($user->pk);

                            if ($rs) {
                                $finilize = true;
                                model('automation::automation')->addLog($automation['id'],$actionRow['id'], $user->pk, 'follow', perfectSerialize($user));

                            }
                        }
                    }


                    break;
                case 'unfollow':
                    $target = model('automation::automation')->getSettingValue('unfollow-source', $automation);
                    if ($target) {
                        $followers = $this->getFollowing();
                        foreach($followers as $follower) {
                            $rs = $this->unfollowUser($follower->pk);
                            if ($rs) {
                                $finilize = true;
                                model('automation::automation')->addLog($automation['id'],$actionRow['id'], $follower->pk, 'unfollow', null);

                            }

                        }
                    } else {
                        //only the unfollow the followed users by this system
                        $after = model('automation::automation')->getSettingValue('unfollow-user-after', $automation);
                        $time = time()  - 60*60*24*$after;
                        $logs = model('automation::automation')->getLogs($automation['id'], 'follow');
                        foreach($logs as $log ) {
                            if ($time > $log['created']) {
                                $rs = $this->unfollowUser($log['userid']);
                                if ($rs) {
                                    model('automation::automation')->addLog($automation['id'],$actionRow['id'], $log['userid'], 'unfollow', null);
                                    $finilize = true;
                                }
                            }
                        }
                    }
                    break;
                case 'stories':

                    break;
            }
            if ( $finilize)model('automation::automation')->finilize($actionRow, $automation);
        } catch (Exception $e) {
            $this->checkPoint($e);
            return false;
        }
    }



    public function followUser($id){
        try {

            $response = $this->instagramObj->people->follow($id);
            $response = json_decode($response);
            return true;
        } catch (Exception $e) {
            $response = json_decode($e->getResponse());
            $this->checkpoint($e);
            return false;
        }
    }

    public function unfollowUser($id){
        try {
            $response = $this->instagramObj->people->unfollow($id);
            $response = json_decode($response);
            return true;
        } catch (Exception $e) {
            $response = json_decode($e->getResponse());
            $this->checkpoint($e);
            return false;
        }
    }

    function search_locations($keyword){
        try {
            $response = $this->ig->location->search("37.2759932","-104.6515434", $keyword);
            if ($response->isOk()) {
                return $response->getVenues();
            }
            return false;
        } catch (Exception $e) {
            $this->checkpoint($e);
            return false;
        }
    }

    function search_location($keyword){
        try {
            $response = $this->ig->location->findPlaces($keyword, array(), $this->rankToken());
            $response = json_decode($response);
            if(isset($response->items) && !empty($response->items)){
                return $response->items;
            }
            return false;
        } catch (Exception $e) {
            $this->checkpoint($e);
            return false;
        }
    }




}