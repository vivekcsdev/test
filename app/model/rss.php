<?php
class RssModel extends Model {
    public function add($result, $url) {
        if ($rss = $this->find($url)) {
            return false;
        } else {
            $this->db->query("INSERT INTO rss (userid,name,url) VALUES(?,?,?)", model('user')->authOwnerId, $result->title, $url);
            return $this->db->lastInsertId();
        }
    }

    public function saveSettings($val, $id) {
        $this->db->query("UPDATE rss SET per_hour=?,post_per_hour=?,enabled=?,publish_description=?,publish_url=?,includes=?,excludes=?,referral_code=?,autopost=? WHERE id=?",
            $val['per_hour'],$val['per_hour_post'],$val['enabled'],$val['description'],$val['url'],$val['includes'], $val['excludes'], $val['referral'], $val['autopost'], $id);
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM rss WHERE userid=? AND (url=? OR id=?)", model('user')->authOwnerId, $id, $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($delete) {
        return $this->db->query("DELETE FROM rss WHERE userid=? AND id=?", model('user')->authOwnerId, $delete);
    }

    public function lists() {
        $query = $this->db->query("SELECT * FROM rss WHERE userid=?", model('user')->authOwnerId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPosts($id) {
        $query = $this->db->query("SELECT id FROM rss_posts WHERE rss_id=?", $id);
        return $query->rowCount();
    }

    public function addPost($item, $rss) {

        if (!$this->postsExists($rss, $item->link)) {

            $image = $this->tryGetImageFromPost($item->link);
            $content = $item->{'content:encoded'};
            $title = $item->title;
            $rss = $this->find($rss);
            $canAdd = true;
            if ($rss['excludes']) {
                $excludes = explode(',', $rss['excludes']);
                foreach($excludes as $exclude) {
                    if ($canAdd) {
                        if (preg_match("#$exclude#", $title) or preg_match("#$exclude#", $content)) $canAdd = false;
                    }
                }
            }

            if ($rss['includes']) {
                $canAdd  = false;
                $includes = explode(',', $rss['includes']);
                foreach($includes as $include) {
                    if (!$canAdd) {
                        if (preg_match("#$include#", $title) or preg_match("#$include#", $content)) $canAdd = true;
                    }
                }
            }
            if ($canAdd) {
                $this->db->query("INSERT INTO rss_posts (rss_id,title,content,url,img)VALUES(?,?,?,?,?)", $rss['id'], $title,$content, $item->link, $image);
                $postId = $this->db->lastInsertId();
                $this->sharePost($rss['id'], $postId);
            }
        }
        return false;
    }

    public function sharePost($rss, $postId) {
        foreach($this->getAccounts($rss) as $account) {
            $this->db->query("INSERT INTO rss_history (rss_id,rss_post_id,account,status,post_time)VALUES(?,?,?,?,?)", $rss,$postId,$account['account_id'], 0, time() + 600);
        }
    }

    public function getAccounts($rss) {
        $query = $this->db->query("SELECT * FROM rss_accounts WHERE rss_id=?", $rss);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function postsExists($rss, $url) {
        $query = $this->db->query("SELECT id FROM rss_posts WHERE rss_id=? AND url=?", $rss, $url);
        return $query->rowCount();
    }


    public function findPost($id) {
        $query = $this->db->query("SELECT * FROM rss_posts WHERE id=?",  $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getLastPosts($id) {
        $query = $this->db->query("SELECT * FROM rss_posts WHERE rss_id=? ORDER BY id DESC LIMIT 20", $id);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPosts($id) {
        $query = $this->db->query("SELECT * FROM rss_posts WHERE rss_id=? ORDER BY id ASC", $id);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getAccoounts($rss) {
        $query  = $this->db->query("SELECT * FROM rss_accounts WHERE rss_id=?", $rss);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAccounts($id, $accounts) {
        $oldAccounts = $this->getAccounts($id);
        $schedule = false;
        if (empty($oldAccounts)) $schedule = true;
        $this->db->query("DELETE FROM rss_accounts WHERE rss_id=?", $id);
        foreach($accounts as $account) {
            $this->db->query("INSERT INTO rss_accounts (rss_id,account_id)VALUES(?,?)", $id, $account);
        }
        if ($schedule) {
            $posts = $this->getPosts($id);
            foreach($posts as $post) {
                $this->sharePost($id, $post['id']);
            }
        }
        return true;
    }

    private function tryGetImageFromPost($url) {
        if ( preg_match('/amazon./i', $url) ) {

            $url = explode('ref=', $url);
            $url = $url [0];

        }

        if ( preg_match('/news.google/i', $url) ) {

            $url = explode('&url=', $url);
            $url = (@$url[1]) ? $url[1] : $url[0];

        }
        if (!config('facebook-app-id'))return false;
        $params = array(
            'client_id' => config('facebook-app-id'),
            'client_secret' => config('facebook-app-secret'),
            'grant_type' => 'client_credentials'
        );

        // Get app's token
        $get_token = json_decode(
            curl_get_content(
                'https://graph.facebook.com/oauth/access_token?' . urldecode(http_build_query($params))
            ),
            true
        );

        if (isset($get_token['access_token'])) {

            // Get content
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://graph.facebook.com/');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'id=' . urlencode($url) . '&scrape=true&access_token=' . $get_token['access_token']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            $get_content = json_decode(curl_exec($curl), true);
            curl_close($curl);

            // Verify if image exists
            if (isset($get_content['image'][0]['url'])) {
                $image = $get_content['image'][0]['url'];
                return $image;
            }


        }
        return false;
    }

    public function getRssRandom() {
        $time = time();
        $query = $this->db->query("SELECT * FROM rss WHERE  enabled=? AND (next_post_time < $time OR next_post_time IS NULL ) ORDER BY rand() LIMIT 1", 1);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function runCronJob() {
        $rss = $this->getRssRandom();

        if ($rss) {

            $user = model('user')->getUser($rss['userid']);
            model('user')->loginWithObject($user);
            $this->autoPostRss($rss);
            autoLoadVendor();
            $rssUrl = $rss['url'];
            try {
                $result = Feed::loadRss($rssUrl);

                $limit = 0;
                foreach($result->item as $item) {
                    if (!$this->postsExists($rss['id'], $item->link)) {
                        if ($limit >= $rss['post_per_hour']) break;

                        $this->addPost($item, $rss['id']);
                        $limit++;
                    }

                }

            } catch (Exception $e) {
                //print_r($e->getMessage());
            }

            $time = time() + (3600 * $rss['per_hour']);
            $this->db->query("UPDATE rss SET next_post_time=? WHERE id=?", $time, $rss['id']);

            model('user')->logoutUser();
        }
    }

    public function autoPostRss($rss) {
        if (!$rss['autopost']) return true;
        $time = time();

        $query = $this->db->query("SELECT * FROM rss_history WHERE rss_id=? AND status=? AND  post_time < $time LIMIT 2", $rss['id'], 0);
        $posted = 0;
        $error = 0;
        while($history = $query->fetch(PDO::FETCH_ASSOC)) {

            $post = $this->findPost($history['rss_post_id']);

            $accountDetail = model('account')->find($history['account']);
            if ($accountDetail) {

                $link = $post['url'];
                if ($rss['referral_code']) {
                    $link .= $rss['referral_code'];
                }
                $val  = array(
                    'title' => $post['title'],
                    'post_type' => 'link',
                    'link' => $link,
                    'text' => $post['content'],
                );
                if ($post['img']) {
                    $image = $this->getImage($post['img']);
                    $val['media'] = array($image);
                    $val['post_type'] = 'media';
                    $val['text'] = $post['content'].' '.$link;
                }

                $socialType = $accountDetail['social_type'];
                $postId = model('post')->add($val, $history['account'], $socialType);
                $post = model('post')->find($postId);

                if (getController()->api($socialType)->post($post, $history['account'])) {
                    $posted++;
                    $this->db->query("UPDATE rss_history SET status=? WHERE id=?", 1, $history['id']);
                } else {
                    $this->db->query("UPDATE rss_history SET status=? WHERE id=?", 2, $history['id']);
                    $error++;
                }

                if (isset($image)) delete_file(path($image));
            }

        }
    }


    public function getImage($avatar) {
        $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
        if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
        $file = $dir.md5($avatar).'.png';
        getFileViaCurl($avatar, $file);
        return $file;
    }

    public function countHistory($id, $type) {
        $query = $this->db->query("SELECT id FROM rss_history WHERE rss_id=? AND status=?", $id, $type);
        return $query->rowCount();
    }
}