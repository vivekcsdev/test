<?php
autoLoadVendor();
require_once path('app/vendor/Google/vendor/autoload.php');
class GoogleAPI extends API {
    public $client;
    private $business;
    private $redirectURI = 'accounts/google';

    public function __construct(){

        $this->client = new Google_Client();
        $this->client->setAccessType("offline");
        $this->client->setApprovalPrompt("force");
        $this->client->setApplicationName('YouTube Tools');
        $this->client->setRedirectUri(url($this->redirectURI));
        $this->client->setClientId(config('gb-client-id'));
        $this->client->setClientSecret(config('gb-client-secret'));
        $this->client->setDeveloperKey(config('google-b-api-key'));
        $this->client->setScopes(array('https://www.googleapis.com/auth/business.manage', 'https://www.googleapis.com/auth/userinfo.email'));

        $this->business = new Google_Service_MyBusiness($this->client);
    }

    public function setClientSecret($clientId, $secret) {
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($secret);
        return $this;
    }

    public function setRedirectURI($url) {
        $this->redirectURI = $url;
        $this->client->setRedirectUri(url($this->redirectURI));
    }

    function loginUrl(){
        return $this->client->createAuthUrl();
    }

    function getToken(){
        try {
            if($code = Request::instance()->input('code')){
                $this->client->authenticate($code);
                $oauth2 = new Google_Service_Oauth2($this->client);
                $token = $this->client->getAccessToken();
                $this->client->setAccessToken($token);

                return $token;
            }else{
                Request::instance()->redirect(url($this->redirectURI, array('auth' => true)));
            }

        } catch (Exception $e) {
            Request::instance()->redirect(url($this->redirectURI, array('auth' => true)));
        }
    }

    function setToken($token){
        $this->client->setAccessToken($token);
    }

    function getCurrentUser($token = null){
        try {
            $oauth = new Google_Service_Oauth2($this->client);
            $userinfo = $oauth->userinfo->get();
            return $userinfo;
        } catch (Exception $e) {
            return false;
        }
    }

    public function listAccounts() {
        return $this->business->accounts->listAccounts()->getAccounts();
    }

    public function getLocations($accountName) {
        return $this->business->accounts_locations->listAccountsLocations($accountName);
    }

    public function getAvatar() {
        return 'assets/images/google-business.png';
    }

    public function post($post, $account) {
        $spintax = new Spintax();
        $account = model('account')->find($account);
        $this->accountId = $account['id'];


        $this->setToken(json_encode(perfectUnserialize($account['access_token'])));
        $postData = perfectUnserialize($post['type_data']);
        $caption    = @$spintax->process($postData['text']);
        $link = ($postData['call_link']) ? @$spintax->process($postData['call_link']) : '';
        $active = $postData['action'];
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);

        try {

            $media = $postData['media'][0];
            $callToAction = new Google_Service_MyBusiness_CallToAction();
            $callToAction->setActionType($callToAction);
            $callToAction->setUrl($link);
            $mediaItem = new Google_Service_MyBusiness_MediaItem();
            $mediaItem->setMediaFormat('PHOTO');
            $mediaItem->setSourceUrl(assetUrl($media));
            $posts = $this->business->accounts_locations_localPosts;
            $localPost = new Google_Service_MyBusiness_LocalPost();
            $localPost->setLanguageCode('en-US');
            $localPost->setName($caption);
            $localPost->setSummary($caption);
            $localPost->setCallToAction($callToAction);
            $localPost->setMedia($mediaItem);
            try {

                //Add Media

                //Call Action
                /**if($url != ""){
                    $CallToAction = new \Google_Service_MyBusiness_CallToAction();
                    $CallToAction->setActionType($cta_action); //BOOK,ORDER,SHOP,LEARN_MORE,SIGN_UP,CALL
                    $CallToAction->setUrl($url);
                }**/

                //Add Offer
                /*$Offer = new \Google_Service_MyBusiness_LocalPostOffer();
                $Offer->setCouponCode("BOGO-JET-CODE");
                $Offer->setRedeemOnlineUrl("https://stackposts.com/");
                $Offer->setTermsConditions("Offer only valid if you can prove you are a time traveler");*/

                //Add Product
                /*$LowerPrice = new \Google_Service_MyBusiness_Money();
                $LowerPrice->setCurrencyCode("USD");
                $LowerPrice->setUnits(5);
                $LowerPrice->setNanos(990000000);

                $UpperPrice = new \Google_Service_MyBusiness_Money();
                $UpperPrice->setCurrencyCode("USD");
                $UpperPrice->setUnits(7);
                $UpperPrice->setNanos(990000000);

                $Product = new \Google_Service_MyBusiness_LocalPostProduct();
                $Product->setLowerPrice($LowerPrice);
                $Product->setProductName("Product Name");
                $Product->setUpperPrice($UpperPrice);*/

                //Create the post
                $posts =  $this->business->accounts_locations_localPosts;


                /**if($url != ""){
                    $post->setCallToAction($CallToAction);
                }**/

                //$post->setOffer($Offer);
                //$post->setProduct($Product);
                $response = $posts->create($account['sid'],$localPost);
                print_r($response);
                exit;

                return $response->getSearchUrl();

            } catch (Exception $e) {
               print_r($e->getMessage());

            }

            exit;
            try {

                $location = $account['sid'];
                $api_end_point_url = 'https://mybusiness.googleapis.com/v4/'.$location.'/localPosts';
                $postfields = array(
                    'topicType' => "STANDARD",
                    'languageCode' => "en_US",
                    'summary' => 'test post 123',
                );
                $data_string = json_encode($postfields);
                $token = perfectUnserialize($account['access_token']);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_end_point_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $token['access_token'],'Content-Type: application/json'));
                $data1 = json_decode(curl_exec($ch), true);
                $http_code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                print_r($data1);
                exit;

                //$response = @$posts->create($account['sid'], $localPost);
            } catch (Google_Exception $e){}
            return true;
        } catch (Exception $e) {
            print_r($e);
        }
        return true;
    }
}

