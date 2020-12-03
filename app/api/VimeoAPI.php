<?php
autoLoadVendor();
class VimeoAPI extends API {

    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        parent::__construct();
        $this->clientId = config('vimeo-client-id');
        $this->clientScret = config('vimeo-client-secret');
    }

    public function loginUrl() {
        // Get redirect
        $url = url('accounts/vimeo');
        return 'https://api.vimeo.com/oauth/authorize?response_type=code&client_id='.$this->clientId.'&redirect_uri='.$url.'&scope=public+private+upload+edit&state=12345';
    }

    public function getObject($token = null) {
        require_once path('app/vendor/vm/autoload.php');
        $vimeo = new \Vimeo\Vimeo($this->clientId, $this->clientScret, $token);
        return $vimeo;
    }

    public function post($post, $account) {
        $spintax = new Spintax();
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $postData = perfectUnserialize($post['type_data']);
        $caption    = @$spintax->process($postData['text']);
        $title = ($postData['title']) ? @$spintax->process($postData['title']) : '';
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);
        $videoFile = path($postData['media'][0]);

        $vimeo = $this->getObject($account['access_token']);

        try {
            // Upload the video
            $uri = $vimeo->upload($videoFile);
        } catch ( Exception $e){}

        // Get video url
        $up = $vimeo->request($uri);


        // Verify if url exists

        // Verify if title is not empty
        if ( $title) {

            $vidu = array(
                'name' => $title,
                'description' => $caption
            );

        } else {

            $vidu = array('name' => $caption);

        }

        // Publish the video
        if ($up) {

            $response = $vimeo->request($up['body']['uri'], $vidu, 'PATCH');

            if ( $response ) {
                model('post')->setResult('posted successfully', $post['id']);
                return true;
            } else {
                model('post')->setResult('Unknown error', $post['id']);
            }
        }
        return false;
    }
}