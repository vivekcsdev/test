<?php
class PostModel extends Model {
    public function find($postId) {
        $query = $this->db->query("SELECT * FROM posts WHERE id=?", $postId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function add($val, $account, $type) {
        $ext = array(
            'post_type' => 'media',
            'repeat_end' => '',
            'schedule' => '',
            'repeat_freq' => '',
            'post_time' => '',
        );
        /**
         * @var $post_type
         * @var $repeat_freq
         * @var $schedule
         * @var $repeat_end
         * @var $post_time
         */
        extract(array_merge($ext, $val));

        unset($val['accounts']);unset($val['types']);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $val['text'] = $emojione->toShort($val['text']);
        $data = perfectSerialize($val);

        if (is_array($account)) {
            $post_time = convertTimeByTimezone($post_time, false, $account[0]);
            $repeat_end = convertTimeByTimezone($repeat_end, false, $account[0]);
            $account  = implode(',', $account);
        } else {
            $post_time = convertTimeByTimezone($post_time, false, $account);
            $repeat_end = convertTimeByTimezone($repeat_end, false, $account);
        }


        $post_type = ($post_type == 'general') ? 'media' : $post_type;
        $status = 1;
        if ($schedule) {
            $status = 2;
        }
        $this->db->query("INSERT INTO posts (userid,account,social_type,type,type_data,is_scheduled,schedule_date,repeat_freq,repeat_end,created_date,status)VALUES(?,?,?,?,?,?,?,?,?,?,?)",
            model('user')->authOwnerId,$account,$type,$post_type,$data,$schedule,$post_time,$repeat_freq,$repeat_end, time(),$status);

        $postId = $this->db->lastInsertId();
        Hook::getInstance()->fire('post.added', null, array($postId,$val));
        return $postId;
    }

    public function savePost($val, $postId, $draft = false) {
        $ext = array(
            'post_type' => 'media',
            'repeat_end' => '',
            'schedule' => '',
            'repeat_freq' => '',
            'post_time' => '',
        );
        /**
         * @var $post_type
         * @var $repeat_freq
         * @var $schedule
         * @var $repeat_end
         * @var $post_time
         */
        extract(array_merge($ext, $val));

        // unset($val['accounts']);unset($val['types']);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $val['text'] = $emojione->toShort($val['text']);
        $data = perfectSerialize($val);

        $post = $this->find($postId);

        if ($draft) {
            $post_time = convertTimeByTimezone($post_time, false, $val['accounts'][0]);
            $repeat_end = convertTimeByTimezone($repeat_end, false, $val['accounts'][0]);
            $account = implode(',', $val['accounts']);
            $firstAccount = model('account')->find($val['accounts'][0]);
            $social_type = $firstAccount['social_type'];

        } else {
            $post_time = convertTimeByTimezone($post_time, false, $post['account']);
            $repeat_end = convertTimeByTimezone($repeat_end, false, $post['account']);
            $account = $post['account'];
            $social_type = $post['social_type'];
        }
        $post_type = ($post_type == 'general') ? 'media' : $post_type;
        $status = 1;
        if ($schedule) {
            $status = 2;
        }

        $this->db->query("UPDATE posts SET account=?,social_type=?,type=?,type_data=?, is_scheduled=?,schedule_date=?,repeat_freq=?,repeat_end=?,status=?,account=? WHERE id=?",
            $account,$social_type, $post_type, $data,$schedule,$post_time,$repeat_freq,$repeat_end,$status,$account, $postId);

        return $postId;

    }

    public function addToDraft($val, $postId) {
        if ($val['draft_title']) {
            $sharable = (isset($val['sharable'])) ? $val['sharable'] : 0;
            $this->db->query("INSERT INTO draft_collections (title,userid,ownerid,sharable)VALUES(?,?,?,?)", $val['draft_title'], model('user')->authId, model('user')->authOwnerId,$sharable);
            $draftId = $this->db->lastInsertId();
        } else {
            $draftId = $val['draft_collection'];
        }

        if ($postId and !$this->alreadyInDraftCollection($postId, $draftId)) {
            $this->db->query("INSERT INTO draft_posts (post_id,collection_id,owner_id,last_save) VALUES(?,?,?,?)", $postId,$draftId, model('user')->authOwnerId,time());
        } else {
            $this->db->query("UPDATE draft_posts SET last_save=? WHERE post_id=? AND collection_id=?", time(), $postId, $draftId);
        }
        return true;
    }

    public function saveDraft($val) {
        $ext = array(
            'id' => '',
            'draft_title' => '',
            'sharable' => 0
        );
        /**
         * @var $id
         * @var $draft_title
         * @var $sharable
         */
        extract(array_merge($ext, $val));

        return $this->db->query("UPDATE draft_collections SET title=?,sharable=? WHERE id=? AND userid=? AND ownerid=?", $draft_title,$sharable, $id, model('user')->authId,model('user')->authOwnerId);
    }

    public function deleteDraftCollection($id) {
        return $this->db->query("DELETE FROM draft_collections WHERE id=? AND userid=? AND ownerid=?", $id, model('user')->authId,model('user')->authOwnerId);
    }

    public function countDraftPosts($id) {
        $query = $this->db->query("SELECT * FROM draft_posts WHERE collection_id=?", $id);
        return $query->rowCount();
    }
    public function alreadyInDraftCollection($postId, $draftId) {
        $query = $this->db->query("SELECT * FROM draft_posts WHERE post_id=? AND collection_id=?", $postId, $draftId);
        return $query->rowCount();
    }

    public function getMyDrafts() {
        $query = $this->db->query("SELECT * FROM draft_collections WHERE userid=? AND ownerid=? ORDER BY id DESC", model('user')->authId, model('user')->authOwnerId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDraftPosts($draftId, $offset = 0) {
        $query = $this->db->query("SELECT * FROM draft_posts WHERE collection_id=? ORDER BY id DESC LIMIT 10 OFFSET $offset", $draftId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteDraftPost($id) {
        $post = $this->findDraftPost($id);
        $this->deletePost($post['post_id']);
        return $this->db->query("DELETE FROM draft_posts WHERE id=?", $id);
    }

    public function findDraftPost($id) {
        $query = $this->db->query("SELECT * FROM draft_posts WHERE id=? ", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function findDraft($id) {
        $ownersId = $this->getSharedOwnersId();
        $ids = implode(',', $ownersId);

        $query = $this->db->query("SELECT * FROM draft_collections WHERE id=? AND ((userid=? AND ownerid=?) or userid IN ($ids))", $id, model('user')->authId,model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function copyFromOldPost($post, $account) {
        $query = $this->db->query("INSERT INTO posts (userid,type,type_data,is_scheduled,schedule_date,repeat_freq,repeat_end,status,result,created_date)
        SELECT userid,type,type_data,is_scheduled,schedule_date,repeat_freq,repeat_end,status,result,created_date FROM posts WHERE id=?", $post['id']);
        $newPostId = $this->db->lastInsertId();
        $account = model('account')->find($account);
        $this->db->query("UPDATE posts SET account=?,social_type=?,status=? WHERE id=?",$account['id'], $account['social_type'], ($post['is_scheduled']) ? 2 : 1, $newPostId);
        return $this->find($newPostId);
    }

    public function alreadyInvited($userid) {
        $query = $this->db->query("SELECT * FROM draft_access WHERE userid=? AND ownerid=?", $userid, model('user')->authId);
        return $query->rowCount();
    }
    public function inviteForCollection($val) {
        /**
         * @var $email
         */
        extract($val);
        $user = model('user')->findUserByEmail($email);
        $owner = model('user')->authUser;
        if (!$user) return false;
        if ($this->alreadyInvited($user['id']))  {
            exit(json_encode(array(
                'type' => 'error',
                'message' => l('user-already-invited')
            )));
        }
        $code = mEncrypt(''.time().'');
        $link = url('collection/access/accept/'.$code);

        $this->db->query("INSERT INTO draft_access (userid,ownerid,code)VALUES(?,?,?)", $user['id'], model('user')->authId, $code);

        //send email
        $full_name = $user['full_name'];
        Email::getInstance()->setAddress($email, $full_name)
            ->setSubject($owner['full_name'].' '.l('invite-collection-subject'))
            ->setMessage(l('invite-collection-content', array('link' => $link)))
            ->send();
        return true;
    }

    public function revokeAccess($id) {
        return $this->db->query("DELETE FROM draft_access WHERE userid=? AND ownerid=?", $id, model('user')->authId);
    }

    public function acceptCollection($code) {
        $this->db->query("UPDATE draft_access SET status=? WHERE code=?", 1, $code);
    }

    public function getSharedOwnersId() {
        $result = array(0);
        $query = $this->db->query("SELECT * FROM draft_access WHERE userid=?", model('user')->authId);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $fetch['ownerid'];
        }
        return $result;
    }

    public function getSharedCollections() {
        $ownersId = $this->getSharedOwnersId();
        $ownersId = implode(',', $ownersId);

        $query = $this->db->query("SELECT * FROM draft_collections WHERE userid IN ($ownersId) AND sharable=? ORDER BY id DESC", 1);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInvitedUsers() {
        $query = $this->db->query("SELECT * FROM draft_access WHERE ownerid=?", model('user')->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function setResult($message, $postId) {
        $this->db->query("UPDATE posts SET result=? WHERE id=?", $message, $postId);
    }

    public function setUnpublished($postId) {
        $this->db->query("UPDATE posts SET status=? WHERE id=?", 3, $postId);
    }

    public function setPublished($postId) {
        $this->db->query("UPDATE posts SET status=? WHERE id=?", 1, $postId);
    }

    public function getSchedules($type, $socialType = 'all') {
        $status = 1;
        switch ($type) {
            case 'published':
                $status = 1;
                break;
            case 'queue':
                $status = 2;
                break;
            case 'unpublished':
                $status = 3;
                break;
        }
        $userTimezone = model('user')->userData('timezone');
        $sql = "SELECT DATE(CONVERT_TZ(schedule_date,'".$this->timezoneConvert(config('timezone'))."','".$this->timezoneConvert($userTimezone)."')) as schedule_date, COUNT(schedule_date) as total,social_type,id FROM posts";
        $param = array();
        $sql.= " WHERE userid=? AND status=? AND social_type=?";
        $param[] = model('user')->authOwnerId;
        $param[] = $status;
        $param[] = $socialType;

        $sql .= " GROUP BY DATE(CONVERT_TZ(schedule_date,'".$this->timezoneConvert(config('timezone'))."','".$this->timezoneConvert($userTimezone)."')) ";
        $sql .= " ORDER BY total DESC ";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSchedulePosts($type, $socialType, $date, $offset = 0) {
        $sql = "SELECT posts.*,accounts.username FROM posts LEFT JOIN accounts ON posts.account = accounts.id WHERE posts.userid=?";
        $param = array(model('user')->authOwnerId);
        $startDate = $date." 00:00";
        $endDate = $date." 23:59";

        $status = 1;
        switch ($type) {
            case 'published':
                $status = 1;
                break;
            case 'queue':
                $status = 2;
                break;
            case 'unpublished':
                $status = 3;
                break;
        }

        $sql .= " AND posts.status=? AND posts.schedule_date >= '$startDate' AND posts.schedule_date <= '$endDate' AND posts.social_type=?";
        $param[] = $status;
        $param[] = $socialType;


        $sql .= " LIMIT 10 OFFSET $offset ";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCronPosts() {
        $now = time() + (60*60*10);
        $now = date("Y-m-d H:i", $now);
        $sql = "SELECT * FROM posts WHERE status=? AND schedule_date < '$now' ORDER BY schedule_date ASC, rand() LIMIT 5  ";
        $query = $this->db->query($sql, 2);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deletePostsByDate($type, $socialType, $date) {
        $startDate = convertTimeByTimezone($date." 00:00:00");
        $endDate = convertTimeByTimezone($date." 23:59:59");
        $status = 1;

        switch ($type) {
            case 'published':
                $status = 1;
                break;
            case 'queue':
                $status = 2;
                break;
            case 'unpublished':
                $status = 3;
                break;
        }
        return $this->db->query("DELETE FROM posts WHERE userid=? AND schedule_date>=? AND schedule_date<=? AND social_type=? AND status=?",
            model('user')->authOwnerId, $startDate,$endDate,$socialType,$status);
    }

    public function deletePost($id) {
        return $this->db->query("DELETE FROM posts WHERE id=? AND userid=?", $id, model('user')->authOwnerId);
    }

    public function timezoneConvert($timezone) {
        date_default_timezone_set($timezone);
        $zones_array = array();
        $timestamp = time();
        foreach(timezone_identifiers_list() as $key => $zone) {
            if($zone == $timezone){
                return date('P', $timestamp);
            }
        }

        return false;
    }

    public function countTotalPosts($type, $status = 'all') {
        $sql = "SELECT id FROM posts WHERE userid=? ";
        $param = array(model('user')->authOwnerId);
        if ($type != 'all') {
            $sql .= " AND social_type=? ";
            $param[] = $type;
        }
        if ($status != 'all') {
            $sql .= " AND status=? ";
            $param[] = $status;
        }

        $query = $this->db->query($sql, $param);
        return $query->rowCount();
    }

    public function countTotalTypes($type, $mediaType = 'media') {
        $sql = "SELECT id FROM posts WHERE userid=? AND social_type=?";
        $param = array(model('user')->authOwnerId, $type);


        if ($mediaType == 'media') {
            $sql .= ' AND (type=? OR type=? OR type=?) ';
            $param[] = 'media';
            $param[] = 'photo';
            $param[] = 'video';
        } else {
            $sql .= " AND type=? ";
            $param[] = $mediaType;
        }

        $query = $this->db->query($sql, $param);
        return $query->rowCount();
    }

    public function countTotalSuccessMonthly($type = '') {
        $result = array();
        $months = getMonths();
        $year = date('Y');
        foreach($months  as $month) {
            $startDate = strtotime("first day of $month ".date('Y'));
            $endDate = strtotime("last day of $month $year 12pm");
            if ($type) {
                $query = $this->db->query("SELECT id FROM posts WHERE created_date >= '$startDate' AND created_date <= $endDate AND status=? AND userid=? AND social_type=?",
                    1, model('user')->authOwnerId, $type);
            } else {
                $query = $this->db->query("SELECT id FROM posts WHERE created_date >= '$startDate' AND created_date <= $endDate AND status=? AND userid=? ",
                    1, model('user')->authOwnerId);
            }
            $result[] = $query->rowCount();
        }
        return $result;
    }
}