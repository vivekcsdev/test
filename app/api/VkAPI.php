<?php
class VkAPI extends API {

    private $requestParam;
    public  $accessToken;
    private $version = "5.73";

    public function init() {
        $clientId = config('vk-application-id');
        $secret = config('vk-secure-key');
        $uri = "http://oauth.vk.com/authorize?client_id=".$clientId. "&scope=wall,photos,video,friends,audio,docs,groups,users,email,pages,offline&redirect_uri=http://oauth.vk.com/blank.html&display=page&v=5.73&response_type=token";
        $this->requestParam = array(
            'client_id' => $clientId,
            'client_secret' => $secret,
            'redirect_uri' => $uri,
            'response_type' => 'code'
        );
        return $this;
    }

    public function loginUrl() {
        return 'http://oauth.vk.com/authorize?' . urldecode(http_build_query($this->requestParam));;
    }

    function getToken(){
        try {
            if($code = Request::instance()->input("code")){
                $params = $this->requestParam;
                $params['code'] = $code;
                $curl = curl_init();
                curl_setopt_array(
                    $curl,
                    array(
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_URL => 'https://oauth.vk.com/access_token' . '?' . urldecode(http_build_query($params)),
                        CURLOPT_HEADER => false
                    )
                );
                $resp = curl_exec($curl);
                curl_close($curl);

                $result = (object)json_decode($resp);

                if(isset($result->access_token)){
                    $this->accessToken = $result->access_token;
                    return $result->access_token;
                }else{
                    exit(json_encode(array(
                        "type"  => "error",
                        "message" => $result->error_description
                    )));
                }
            }

        } catch (Exception $e) {
        }
    }

    public function setToken($token) {
        $this->accessToken = $token;
    }

    function getCurrentUser(){
        $params = array(
            'fields' => 'uid,screen_name,photo_big,wall,offline'
        );

        $result = $this->get("users.get", $params);
        return $result;
    }

    function getGroups(){
        $result = $this->get('groups.get', array('access_token' => $this->accessToken, 'extended' => 1, 'fields' => 'last_name,first_name,screen_name,wall_comments,can_post,can_write_private_message,contacts', 'filter' => 'admin,editor'));
        return $result;
    }

    function get($method, $params){
        if($this->accessToken != ""){
            $params['access_token'] = $this->accessToken;
        }

        $params['v'] = $this->version;

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_URL => 'https://api.vk.com/method/'. $method . '?' . urldecode(http_build_query($params)),
                CURLOPT_HEADER => false
            )
        );
        $resp = curl_exec($curl);
        curl_close($curl);
        $result = (object)json_decode($resp);

        if(isset($result->response)){
            return $result->response;
        }

        return $result;
    }

    function CurlPost($method, $params){

        if($this->accessToken != ""){
            $params['access_token'] = $this->accessToken;
        }

        $params['v'] = $this->version;

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_URL => 'https://api.vk.com/method/'. $method . '?' . urldecode(http_build_query($params)),
                CURLOPT_HEADER => false
            )
        );
        $resp = curl_exec($curl);
        curl_close($curl);
        $result = (object)json_decode($resp);

        if(isset($result->response)){
            return $result->response;
        }

        return $result;
    }

    public function getAvatar($avatar) {
        $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
        if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
        $file = $dir.md5($avatar).'.png';
        getFileViaCurl($avatar, $file);
        return $file;
    }

    public function preInit($account) {
        $this->setToken($account['access_token']);
        return $this;
    }

    public function post($post, $account) {
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $this->setToken($account['access_token']);
        $postData = perfectUnserialize($post['type_data']);
        $caption = $postData['text'];
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);
        $link = $postData['link'];
        $accountType = ($account['account_type'] == 'group') ? '-'.$account['sid'] : $account['sid'];
        try {
            switch ($post['type']){
                case 'text':
                    $data = array(
                        'owner_id' => $accountType,
                        'message' => urlencode($caption)
                    );
                    break;
                case 'media':
                    if (isImage($postData['media'][0])) {
                        $attachments = $this->uploadPhoto($postData['media']);
                        $data = array(
                            'owner_id' => $accountType,
                            'message' => urlencode($caption),
                            'attachments' => $attachments
                        );
                    } else {
                        $attachments = $this->upload_video(array(
                            'description' => urlencode($caption),
                            'wallpost' => 1,
                            'name' => ''
                        ), $postData['media'][0]);
                        $data = array(
                            'owner_id' => $accountType,
                            'message' => urlencode($caption),
                            'attachments' => $attachments
                        );
                    }
                    break;
                case 'link':
                    $data = array(
                        'owner_id' => $accountType,
                        'message' => urlencode($caption),
                        'attachments' => urlencode($link)
                    );

                    break;
            }
            $response = $this->CurlPost('wall.post', $data);


            if(!isset($response->error)){
                model('post')->setResult('posted successfully', $post['id']);
                return true;
            }else{
                model('post')->setResult('Unknown error: '.$response->error->error_msg, $post['id']);
                return false;
            }
        } catch (Exception $e) {
            model('post')->setResult($e->getMessage(), $post['id']);
            return false;
        }
    }


    public function uploadPhoto($files = array(), $return_ids = false, $additional_data = array(), $usleep = 0){

        if(count($files) == 0) return false;
        if(!function_exists('curl_init')) return false;
        $data_json = $this->CurlPost('photos.getWallUploadServer', array( 'group_id'=> intval(0) ));
        if(!isset($data_json->upload_url)) return false;
        $temp = array_chunk($files, 4);
        $attachments = array();

        foreach($temp as $chunk_index => $temp_chunk){

            if($chunk_index) usleep($usleep);

            $files = [];

            foreach ($temp_chunk as $key => $data) {
                $data = path($data);
                $path = realpath($data);

                $info = @getimagesize($path);
                if ($info === false) {
                    throw new \RuntimeException(sprintf('File "%s" is not an image.', $data));
                }

                if($path){
                    $files['file' . ($key+1)] = (class_exists('CURLFile', false)) ? new CURLFile(realpath($data)) : '@' . realpath($data);
                }
            }

            $upload_url = $data_json->upload_url;
            $ch = curl_init($upload_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $files);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $upload_data = json_decode(curl_exec($ch), true);
            $upload_data['group_id'] = intval(0);
            $upload_data += $additional_data;

            usleep($usleep);

            $response = $this->CurlPost('photos.saveWallPhoto', $upload_data);
            if(!isset($response->error)){
                if(isset($response) && count($response) > 0){
                    foreach($response as $key => $photo){
                        if($return_ids)
                            $attachments[] = $photo->id;
                        else
                            $attachments[] = 'photo'.$photo->owner_id.'_'.$photo->id;
                    }
                }
            }else{
                throw new Exception($response->error->error_msg);
            }
        }

        return $attachments;
    }

    public function upload_video($options = [], $file = false){
        if(!is_array($options)) return false;
        if(!function_exists('curl_init')) return false;
        $data_json = $this->CurlPost('video.save', $options);

        if(!isset($data_json->upload_url)) return false;
        $attachment = 'video'.$data_json->owner_id.'_'.$data_json->video_id;
        $upload_url = $data_json->upload_url;
        $ch = curl_init($upload_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // если указан файл то заливаем его отправкой POST переменной video_file
        $file = path($file);
        if($file && file_exists($file)){
            //@todo надо протестировать заливку
            $path = realpath($file);
            if(!$path) return false;
            $files['video_file'] = (class_exists('CURLFile', false)) ? new CURLFile($file) : '@' . $file;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $files);
            curl_exec($ch);
            // иначе просто обращаемся по адресу (ну надо так!)
        } else {
            curl_exec($ch);
        }
        return $attachment;
    }
}