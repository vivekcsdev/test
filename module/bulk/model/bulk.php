<?php
class BulkModel extends Model {

    public function getBulks() {
        $query = $this->db->query("SELECT * FROM bulks WHERE userid=? ORDER BY id DESC", model('user')->authOwnerId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addBulk($val) {
        /**
         * @var $title
         */
        extract($val);

        $this->db->query("INSERT INTO bulks (userid,title,created)VALUES(?,?,?)", model('user')->authOwnerId, $title, time());
        return $this->db->lastInsertId();
    }

    public function countPosts($id) {
        $query = $this->db->query("SELECT * FROM bulk_posts WHERE bulk_id=?", $id);
        return $query->rowCount();
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM bulks WHERE id=? AND (userid=?)", $id, model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getAccounts($id) {
        $query = $this->db->query("SELECT * FROM bulk_account WHERE bulk_id=?", $id);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAccounts($id, $accounts) {
        $this->db->query("DELETE FROM bulk_account WHERE bulk_id=?", $id);
        foreach($accounts as $account) {
            $this->db->query("INSERT INTO bulk_account (bulk_id,account_id)VALUES(?,?)", $id, $account);
        }
        return true;
    }

    public function upload($id, $bulkId) {
        $file = model('filemanager')->find($id);
        $this->db->query("INSERT INTO bulk_posts (bulk_id,file) VALUES(?,?)", $bulkId, $id);
        return $this->db->lastInsertId();
    }


    public function getPosts($id, $offset) {
        $query = $this->db->query("SELECT * FROM bulk_posts WHERE bulk_id=? ORDER BY id DESC LIMIT 8 OFFSET $offset", $id);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function savePost($val) {
        /**
         * @var $caption
         * @var $id
         */
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $val['caption'] = $emojione->toShort($val['caption']);
        extract($val);
        $this->db->query("UPDATE bulk_posts SET caption=? WHERE id=?", $caption,  $id);
    }

    public function deletePost($id) {
        return $this->db->query("DELETE FROM bulk_posts WHERE id=?", $id);
    }

    public function delete($id) {
        $this->db->query("DELETE FROM bulk_account WHERE bulk_id=?", $id);
        $this->db->query("DELETE FROM bulk_posts WHERE bulk_id=?", $id);

        $this->db->query("DELETE FROM bulks WHERE id=?", $id);
    }

    public function getAccountsId($id) {
        $result = array();
        foreach($this->getAccounts($id) as $account) {
            $result[]  = $account['account_id'];
        }
        return $result;
    }

    public function getPostRules($id) {
        $query = $this->db->query("SELECT * FROM bulk_rules WHERE bulk_id=?", $id);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRule($val, $id) {
        /**
         * @var $days
         * @var $date_from
         * @var $date_to
         * @var $time_from
         * @var $time_to
         * @var $order
         * @var $number
         */
        extract($val);
        $date_from = date(getAdminDateFormat().' H:i', strtotime($date_from.' '.$time_from));
        $date_to = date(getAdminDateFormat().' H:i', strtotime($date_to.' '.$time_to));
        $this->db->query("INSERT INTO bulk_rules(bulk_id,mon,tues,wed,thur,fri,sat,sun,from_date,to_date,post_order,post_daily)VALUES(?,?,?,?,?,?,?,?,?,?,?,?)",
            $id, $days['mon'],$days['tues'],$days['wed'],$days['thur'],$days['fri'],$days['sat'],$days['sun'], $date_from,$date_to,$order,$number);

    }

    public function getSchedules() {
        $ids = array(0);
        foreach($this->getBulks() as $bulk) {
            $ids[] = $bulk['id'];
        }
        $ids = implode(',', $ids);
        $query = $this->db->query("SELECT * FROM bulk_rules WHERE bulk_id IN ($ids) ");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPostPostedOnAccount($id, $bulkId) {
        $startTime  = strtotime('today');
        $endTime = $startTime + (60*60*24);
        $query = $this->db->query("SELECT * FROM bulk_completed WHERE account_id=? AND bulk_id=? AND created BETWEEN $startTime AND $endTime ", $id, $bulkId);
        return $query->rowCount();
    }

    public function getAccountPostCompletedIds($id, $bulkId) {
        $result = array(0);
        $query = $this->db->query("SELECT * FROM bulk_completed WHERE account_id=? AND bulk_id=?",$id, $bulkId);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $fetch['post_id'];
        }
        return $result;
    }
    public function getAccountPost($id, $rule) {
        $completed = $this->getAccountPostCompletedIds($id, $rule['bulk_id']);
        $completed = implode(',', $completed);

        $sql = "SELECT * FROM bulk_posts WHERE bulk_id=? AND id NOT IN ($completed) ";

        if ($rule['post_order'] == 1) {
            $sql .= " ORDER BY id ASC ";
        } else {
            $sql .= "ORDER BY rand() ";
        }
        $sql .= " LIMIT 1";
        $query = $this->db->query($sql, $rule['bulk_id']);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function publish($post, $account) {
        $socialType = $account['social_type'];
        $file = model('filemanager')->find($post['file']);
        $val = array(
            'post_type' => 'media',
            'repeat_end' => '',
            'schedule' => 0,
            'repeat_freq' => '',
            'post_time' => '',
            'link' => '',
            'title' => '',
            'category' => 0,
            'text' => $post['caption'],
            'url' => '',
            'youtube_tag' => '',
            'media' => array($file['file_name'])
        );
        $postId = model('post')->add($val, $account['id'], $socialType);
        $post = model('post')->find($postId);
        getController()->api($post['social_type'])->post($post, $post['account']);
        return true;
    }
    public function runCron() {
        $currentDate = date(getAdminDateFormat().' H:i');
        $day = strtolower(date('D'));
        if ($day == 'thu') $day = 'thur';
        if ($day == 'tue') $day = 'tues';

        $query = $this->db->query("SELECT * FROM bulk_rules WHERE (from_date <= '$currentDate') AND (to_date >= '$currentDate') AND $day='1'  ");
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $bulkDetails = $this->find($fetch['bulk_id']);
            $user = $this->model('user')->getUser($bulkDetails['userid']);
            $this->model('user')->loginWithObject($user);
            $accounts = $this->getAccounts($fetch['bulk_id']);
            $posted = 0;
            foreach($accounts as $account) {
                $account = model('account')->find($account['account_id']);
                if ($account) {
                    if ($this->countPostPostedOnAccount($account['id'], $fetch['bulk_id']) < $fetch['post_daily']) {
                        $post = $this->getAccountPost($account['id'], $fetch);
                        if ($post) {
                            $posted++;
                            //we can post this one now
                            $this->publish($post, $account);
                            $this->db->query("INSERT INTO bulk_completed (bulk_id,account_id,post_id,created)VALUES(?,?,?,?)", $fetch['bulk_id'],$account['id'],$post['id'], time());
                        }
                    }
                }

            }
            model('user')->logoutUser();
        }
    }

}