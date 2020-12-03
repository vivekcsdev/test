<?php
autoLoadVendor();
class DailymotionAPI extends API {
    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        parent::__construct();
        $this->clientId = config('dailymotion-client-id');
        $this->clientSecret = config('dailymotion-client-secret');
    }
    public function loginUrl() {
        $url = url('accounts/dailymotion');
        $authUrl = 'https://www.dailymotion.com/oauth/authorize?response_type=code&client_id=' . $this->clientId . '&scope=manage_videos&redirect_uri=' . $url;
        return $authUrl;
    }

    public function getToken($code) {
        $curl = curl_init("https://api.dailymotion.com/oauth/token");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt(
            $curl, CURLOPT_POSTFIELDS, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => url('accounts/dailymotion'),
                'grant_type' => 'authorization_code'
            ]
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        return json_decode($data);
    }

    public function post($post, $account) {
        $spintax = new Spintax();
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $postData = perfectUnserialize($post['type_data']);
        $caption    = @$spintax->process($postData['text']);
        $title = ($postData['title']) ? @$spintax->process($postData['title']) : '';
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);
        $videoFile = assetUrl($postData['media'][0]);

        $token = json_decode($account['access_token'], true);
        $curl = curl_init('https://api.dailymotion.com/oauth/token');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt(
            $curl, CURLOPT_POSTFIELDS, array(
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $token['refresh_token'],
                'grant_type' => 'refresh_token'
            )
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $data = curl_exec($curl);

        curl_close($curl);


        $data = json_decode($data);

        $token = @$data->access_token;

        $curl = curl_init('https://api.dailymotion.com/videos');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt(
            $curl, CURLOPT_POSTFIELDS, [
                'url' => $videoFile,
                'title' => $title,
                'description' => $caption,
                'published' => true,
                'access_token' => $token
            ]
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($data);

        if ($data and !isset($data->error)) {
            model('post')->setResult('posted successfully', $post['id']);
            return true;
        } else {
            model('post')->setResult($data->error->message, $post['id']);
        }
        return false;

    }
}