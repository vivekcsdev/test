<?php
class AccountModel extends Model {
    public function hasAccount($type) {
        $query = $this->db->query("SELECT * FROM accounts WHERE social_type=? AND userid=?", $type, model('user')->authOwnerId);
        return $query->rowCount();
    }

    public function getAccounts($type, $term = null) {
        $sql = "SELECT * FROM accounts WHERE social_type=? AND userid=? ";
        $param = array($type, model('user')->authOwnerId);

        if ($term) {
            $term = '%'.$term.'%';
            $sql .= " AND username LIKE ? ";
            $param[] = $term;
        }
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAccounts($type = '') {
        $sql = "SELECT * FROM accounts WHERE userid=? ";
        $param = array(model('user')->authOwnerId);

        if ($type and $type != 'all') {
            $sql .= " AND social_type=? ";
            $param[] = $type;
        } else {
            $socials = model('user')->getAvailableSocials();
            $values = "";
            foreach($socials as $social) {
                $rss = Request::instance()->input('rss');
                if ($rss) {
                    if (!in_array($social, array('instagram','youtube','vimeo','dailymotion', 'pinterest'))) $values .= ($values) ? ",'$social'" : "'$social'";
                } else {
                    $values .= ($values) ? ",'$social'" : "'$social'";
                }

            }

            $sql .= " AND social_type IN ($values) ";

        }

        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAccountByUsername($username, $type) {
        $query = $this->db->query("SELECT * FROM accounts WHERE username=? AND social_type=? AND userid=?",
            $username, $type, model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function findAccountByToken($username, $type) {
        $query = $this->db->query("SELECT * FROM accounts WHERE access_token=? AND social_type=? AND userid=?",
            $username, $type, model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function findByRandom($type) {
        $query = $this->db->query("SELECT * FROM accounts WHERE social_type=? AND userid=? ORDER BY rand()",
            $type, model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function find($id) {
        $query = $this->db->query("SELECT * FROM accounts WHERE id=? AND userid=?",
            $id, model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function findAccountBySID($sid, $type, $accountType = '') {
        $sql = "SELECT * FROM accounts WHERE sid=? AND social_type=? AND userid=? ";
        $param = array($sid, $type, model('user')->authOwnerId);
        if ($accountType) {
            $sql .= " AND account_type=? ";
            $param[] = $accountType;
        }
        $query = $this->db->query($sql,
            $param);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function add($val) {
        $ext = array(
            'social_type' => '',
            'account_type' => '',
            'url' => '',
            'sid' => '',
            'avatar' => '',
            'username' => '',
            'password' => '',
            'access_token' => '',
            'is_official' => 1,
            'proxy' => '',
            'default_proxy' => '',
            'status' => 1,
        );
        /**
         * @var $social_type
         * @var $account_type
         * @var $url
         * @var $avatar
         * @var $username
         * @var $password
         * @var $access_token
         * @var $is_official
         * @var $proxy
         * @var $default_proxy
         * @var $status
         * @var $sid
         */
        extract(array_merge($ext, $val));

        $permissions = model('user')->getPermissions();
        if (!empty($permissions)) {
            $limit = model('user')->hasPermission('number');
            if ($limit and $limit > 0) {
                $totalAdded = $this->countAdded($social_type);
                if ($totalAdded >= $limit) {
                    return false;
                }
            }
        }

        $this->db->query("INSERT INTO accounts (sid,userid,social_type,account_type,url,avatar,username,password,access_token,is_official,proxy,default_proxy,status,changed,created)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            $sid,model('user')->authOwnerId, $social_type,$account_type,$url,$avatar,$username,$password,$access_token,$is_official,$proxy,$default_proxy,$status,time(), time());

        Hook::getInstance()->fire('account.added', null, array($this->db->lastInsertId(), $val));
        return true;
    }

    public function countAdded($type) {
        $query = $this->db->query("SELECT id FROM accounts WHERE userid=? AND social_type=?", model('user')->authOwnerId, $type);
        return $query->rowCount();
    }

    public function update($val, $id) {
        $ext = array(

            'avatar' => '',
            'username' => '',
            'password' => '',
            'access_token' => '',
            'proxy' => '',
            'default_proxy' => '',
            'sid' => ''
        );
        /**
         * @var $avatar
         * @var $username
         * @var $password
         * @var $access_token
         * @var $proxy
         * @var $default_proxy
         * @var $sid
         */
        extract(array_merge($ext, $val));

        return $this->db->query("UPDATE accounts SET username=?,avatar=?,password=?,access_token=?,proxy=?,default_proxy=?,sid=?,status=? WHERE id=?",
            $username,$avatar,$password,$access_token,$proxy,$default_proxy,$sid,1,$id);
    }

    public function countAll() {
        $query = $this->db->query("SELECT id FROM accounts WHERE userid=? ", model('user')->authOwnerId);
        return $query->rowCount();
    }


    public function delete($id) {
        $account = $this->find($id);
        if ($account['social_type'] == 'instagram') {
            $this->db->query("DELETE FROM instagram_sessions WHERE username=?", $account['username']);
        }
        if (moduleExists('automation')) {
            $this->db->query("DELETE FROM automations WHERE account=?", $id);
        }
        return $this->db->query("DELETE FROM accounts WHERE id=? AND userid=?", $id, model('user')->authOwnerId);
    }
}