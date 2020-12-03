<?php
autoLoadVendor();
require_once path('app/vendor/Google/vendor/autoload.php');
class YoutubeAPI extends API {
    private $client;
    private $youtube;
    protected $googleLiveBroadcastSnippet;
    protected $googleLiveBroadcastStatus;
    protected $googleYoutubeLiveBroadcast;
    protected $googleYoutubeLiveStreamSnippet;
    protected $googleYoutubeCdnSettings;
    protected $googleYoutubeLiveStream;
    protected $googleYoutubeVideoRecordingDetails;
    public function __construct(){

        $this->client = new Google_Client();
        $this->client->setAccessType("offline");
        $this->client->setApprovalPrompt("force");
        $this->client->setApplicationName('YouTube Tools');
        $this->client->setRedirectUri(url("accounts/youtube"));
        $this->client->setClientId(config('youtube-client-id'));
        $this->client->setClientSecret(config('youtube-client-secret'));
        $this->client->setDeveloperKey(config('google-youtube-api-key'));
        $this->client->setScopes(array('https://www.googleapis.com/auth/youtube.readonly', 'https://www.googleapis.com/auth/youtube', 'https://www.googleapis.com/auth/youtube', 'https://www.googleapis.com/auth/youtube.force-ssl', 'https://www.googleapis.com/auth/userinfo.email'));

        $this->youtube = new Google_Service_YouTube($this->client);
        $this->googleLiveBroadcastSnippet = new Google_Service_YouTube_LiveBroadcastSnippet;
        $this->googleLiveBroadcastStatus = new Google_Service_YouTube_LiveBroadcastStatus;
        $this->googleYoutubeLiveBroadcast = new Google_Service_YouTube_LiveBroadcast;
        $this->googleYoutubeLiveStreamSnippet = new Google_Service_YouTube_LiveStreamSnippet;
        $this->googleYoutubeCdnSettings = new Google_Service_YouTube_CdnSettings;
        $this->googleYoutubeLiveStream = new Google_Service_YouTube_LiveStream;
        $this->googleYoutubeVideoRecordingDetails = new Google_Service_YouTube_VideoRecordingDetails;
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
                Request::instance()->redirect(url("accounts/youtube", array('auth' => true)));
            }

        } catch (Exception $e) {
            Request::instance()->redirect(url("accounts/youtube", array('auth' => true)));
        }
    }

    function setToken($token){
        $this->client->setAccessToken(perfectUnserialize($token));
    }

    function getCurrentUser($token){
        try {
            $oauth = new Google_Service_Oauth2($this->client);
            $userinfo = $oauth->userinfo->get();
            return $userinfo;
        } catch (Exception $e) {
            return false;
        }
    }

    function getChannel(){
        try {
            $part = 'brandingSettings,status,id,snippet,contentDetails,contentOwnerDetails,statistics';
            $params = array(
                'mine' => true
            );
            return $this->youtube->channels->listChannels($part, $params);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAvatar($avatar) {
        $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
        if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
        $file = $dir.md5($avatar).'.jpg';
        getFileViaCurl($avatar, $file);
        return $file;
    }

    public function post($post, $account) {
        $spintax = new Spintax();
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $this->setToken($account['access_token']);
        $postData = perfectUnserialize($post['type_data']);
        $caption    = @$spintax->process($postData['text']);
        $title = ($postData['title']) ? @$spintax->process($postData['title']) : '';
        $tags = @$spintax->process($postData['youtube_tags']);
        $category = (int)@$spintax->process($postData['category']);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);
        try {
            $videoFile = path($postData['media'][0]);
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($caption);
            if($tags != ""){
                $tags = explode(",", $tags);
                $snippet->setTags($tags);
            }

            if($category != 0){
                $snippet->setCategoryId($category);
            }

            $status = new Google_Service_YouTube_VideoStatus();
            $status->privacyStatus = "public";

            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            // Specify the size of each chunk of data, in bytes. Set a higher value for
            // reliable connection as fewer chunks lead to faster uploads. Set a lower
            // value for better recovery on less reliable connections.
            $chunkSizeBytes = 1 * 1024 * 1024;

            // Setting the defer flag to true tells the client to return a request which can be called
            // with ->execute(); instead of making the API call immediately.
            $this->client->setDefer(true);

            // Create a request for the API's videos.insert method to create and upload the video.
            $insertRequest = $this->youtube->videos->insert("status,snippet", $video);

            // Create a MediaFileUpload object for resumable uploads.
            $media = new Google_Http_MediaFileUpload(
                $this->client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($videoFile));


            // Read the media file and upload it chunk by chunk.
            $status = false;
            $handle = fopen($videoFile, "rb");
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }

            fclose($handle);

            // If you want to make other calls after the file upload, set setDefer back to false
            $this->client->setDefer(false);

            model('post')->setResult('Posted successfully', $post['id']);
            return true;

        } catch (Google_Service_Exception $e) {
            model('post')->setResult($e->getMessage(), $post['id']);
            return false;
        } catch (Google_Exception $e) {
            model('post')->setResult($e->getMessage(), $post['id']);
            return false;
        }
    }
}