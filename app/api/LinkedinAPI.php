<?php
autoLoadVendor();
require_once path('app/vendor/linkedin/autoload.php');
class LinkedinAPI extends API {

    private $linkedin;
    private $accountId = null;

    public function __construct(){
        $this->linkedin = new \Phillipsdata\LinkedIn\LinkedIn(config('linkedin-client-id'), config('linkedin-client-secret'), url('accounts/linkedin'));
    }

    function loginUrl(){
        $scope = "r_emailaddress r_liteprofile w_member_social";
        if (config('enable-linkedin-page')) $scope = 'r_emailaddress r_liteprofile w_member_social w_organization_social r_organization_social rw_organization_admin';
        return $this->linkedin->getPermissionUrl( $scope );
    }

    function getToken(){
        try {
            if($code = Request::instance()->input("code")){
                $tokenResponse = $this->linkedin->getAccessToken($code);

                if($tokenResponse->status() == 200){
                    $tokenResponse = $tokenResponse->response();
                    return $tokenResponse->access_token;
                }else{
                    Request::instance()->redirect(url("accounts/linkedin", array('auth' => true)));
                }

            }else{
                Request::instance()->redirect(url("accounts/linkedin", array('auth' => true)));

            }

        } catch (Exception $e) {
            Request::instance()->redirect(url("accounts/linkedin", array('auth' => true)));

        }
    }

    function setToken($token){
        $token = (object)array(
            "access_token" => $token
        );

        $this->linkedin->setAccessToken($token);
    }

    function getCurrentUser(){
        try {
            $profile = $this->linkedin->getUser();
            if($profile->status() == 200){
                return $profile->response();
            }else{
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    function getCompanies(){
        try {
            $companies = $this->linkedin->getCompanies();
            if($companies->status() == 200){
                return $companies->response();
            }else{
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAvatar($user) {
        if (isset($user->profilePicture)) {
            $picture = json_decode(json_encode($user->profilePicture), true);
            if (isset($picture['displayImage~']['elements'][0]['identifiers']['0']['identifier'])) {
              $avatar = $picture['displayImage~']['elements'][0]['identifiers']['0']['identifier'];
                $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
                if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
                $file = $dir.md5($avatar).'.jpg';
                getFileViaCurl($avatar, $file);
                return $file;
            }
        }
        return 'assets/images/linkedin.png';
    }

    public function dologin($response) {
        if(isset($response->status) && $response->status == 401){
            if ($this->accountId) $this->db->query("UPDATE accounts SET status=? WHERE id=?", 0, $this->accountId);
        }
    }

    public function post($post, $account) {
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $this->setToken($account['access_token']);

        $author = ($account['account_type'] == 'profile') ? "urn:li:person:".$account['sid'] : "urn:li:organization:".$account['sid'];


        try {
            $postData = perfectUnserialize($post['type_data']);
            $caption = $postData['text'];
            autoLoadVendor();
            $emojione = new \Emojione\Client(new \Emojione\Ruleset());
            $caption = $emojione->shortnameToUnicode($caption);
            $link = $postData['link'];
            switch($post['type']) {
                case 'link':
                    $content = [
                        'lifecycleState' => 'PUBLISHED',
                        'specificContent' => [
                            'com.linkedin.ugc.ShareContent' => [
                                'shareCommentary' => [
                                    'text' => $caption
                                ],
                                "shareMediaCategory" => "ARTICLE",
                                "media" => [
                                    [
                                        "status" => "READY",
                                        "description"=> [
                                            "text" => $caption
                                        ],
                                        "originalUrl" => $link,
                                        "title" => [
                                            "text" => ''
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC']
                    ];
                    break;
                case 'media':
                    $media = $postData['media'][0];
                    if (model('user')->hasPermission('watermark')) {
                        $file = getWatermarkTmpFile($media);
                        $media = doWaterMark($media, $file);
                    }
                    $media = $this->linkedin->upload(path($media), $author);
                    $content = [
                        'lifecycleState' => 'PUBLISHED',
                        'specificContent' => [
                            'com.linkedin.ugc.ShareContent' => [
                                'shareCommentary' => [
                                    'text' => $caption
                                ],
                                "shareMediaCategory" => "IMAGE",
                                "media" => [
                                    [
                                        "status" => "READY",
                                        "description"=> [
                                            "text" => $caption
                                        ],
                                        "media" => $media,
                                        "title" => [
                                            "text" => $caption
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC']
                    ];
                    break;
                case 'text':
                    $content = [
                        'lifecycleState' => 'PUBLISHED',
                        'specificContent' => [
                            'com.linkedin.ugc.ShareContent' => [
                                'shareCommentary' => [
                                    'text' => $caption
                                ],
                                "shareMediaCategory" => "NONE"
                            ]
                        ],
                        'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC']
                    ];
                    break;
            }
            $response = (object)$this->linkedin->share($content, $author);
            $response = $response->response();

            if(isset($response->id)){
                model('post')->setResult('posted successfully', $post['id']);
                return $response->id;

            }else if(isset($response->message)){
                $this->dologin($response);
                $message = explode(": ", $response->message);
                model('post')->setResult($message, $post['id']);

                return false;
            }
        } catch (Exception $e) {
            model('post')->setResult($e->getMessage(), $post['id']);
            return false;
        }
        return false;
    }


}