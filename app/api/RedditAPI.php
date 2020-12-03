<?php
autoLoadVendor();
class RedditAPI extends API {
    private $clientId;
    private $clientScret;

    public function __construct()
    {
        parent::__construct();
        $this->clientId = config('reddit-client-id');
        $this->clientScret = config('reddit-client-secret');
    }

    public function loginUrl() {
        $permission = 'save,modposts,identity,edit,read,report,submit';

        // Set url
        $url = 'https://www.reddit.com/api/v1/authorize';

        $code = rand();
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => url('accounts/reddit'),
            'scope' => $permission,
            'state' => $code,
            'duration' => 'permanent',
        );

        // Get redirect url
        $url = $url . '?' . urldecode(http_build_query($params));
        return $url;
    }

    public function getToken($code) {
        $curl = curl_init('https://www.reddit.com/api/v1/access_token');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientScret);
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => url('accounts/reddit'),
            )
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // Decode Response
        $data = json_decode(curl_exec($curl), true);
        return $data;
    }

    public function post($post, $account) {
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $postData = perfectUnserialize($post['type_data']);
        $caption = $postData['text'];
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);
        $link = $postData['link'];
        $spintax = new Spintax();
        $title = ($postData['title']) ? @$spintax->process($postData['title']) : '';
        try {
            switch ($post['type']){
                case 'text':
                    $data = array(
                        'url' => '',
                        'title' => $title,
                        'sr' => 'worldnews',
                        'text' => $caption,
                        'kind' => 'self',
                    );
                    break;
                case 'photo':
                    $media = $postData['media'][0];
                    if (isImage($postData['media'][0])) {
                        if (model('user')->hasPermission('watermark')) {
                            $file = getWatermarkTmpFile($media);
                            $media = doWaterMark($media, $file);
                        }
                        $data = array(
                            'url' => assetUrl($media),
                            'title' => $title,
                            'sr' => 'pics',
                            'text' => $caption,
                            'kind' => 'image',
                        );
                    } else {
                        $data = array(
                            'url' => assetUrl($media),
                            'title' => $title,
                            'sr' => 'videos',
                            'text' => $caption,
                            'kind' => 'video',
                        );
                    }
                    break;
                case 'link':
                    $data = array(
                        'url' => $link,
                        'title' => $title,
                        'sr' => 'worldnews',
                        'text' => $caption,
                        'kind' => 'link',
                    );

                    break;
            }

            $token = json_decode($account['access_token'], true);


            $curl = curl_init('https://www.reddit.com/api/v1/access_token');
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientScret);
            curl_setopt(
                $curl, CURLOPT_POSTFIELDS, [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $token['refresh'],
                ]
            );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $result = json_decode(curl_exec($curl), true);
            curl_close($curl);

            // then we check if the token was refreshed
            if ( isset($result['access_token']) ) {


                // curl settings and call to post to the subreddit
                $curl = curl_init('https://oauth.reddit.com/api/submit');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_USERAGENT, $account['username'] . ' by /u/' . $account['username'] . ' (Phapper 1.0)');
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $result['access_token']));
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                $response = curl_exec($curl);
                $response = json_decode($response, true);
                curl_close($curl);

                // Verify id response is successfully

                if ( isset($response['success']) and $response['success'] ) {
                    model('post')->setResult('posted successfully', $post['id']);
                    // The post was published
                    return true;

                } else {
                    model('post')->setResult('Unknown error', $post['id']);
                    return false;

                }

            }
        } catch (Exception $e) {
            model('post')->setResult($e->getMessage(), $post['id']);
            return false;
        }
    }
}