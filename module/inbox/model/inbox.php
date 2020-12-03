<?php
class InboxModel  extends Model {
    private $availableSocial = array();

    public function __construct($controller)
    {
        parent::__construct($controller);

    }

    public function addSocial($social, $info) {
        $this->availableSocial[$social] = $info;
    }

    public function getSocials() {
        return $this->availableSocial;
    }

    public function ensureAccount($account) {
        if (!$this->accountExists($account)) {
            $this->db->query("INSERT INTO inboxes (account)VALUES(?)", $account);
        } else {
            $this->db->query("UPDATE inboxes SET userid=?,unread=? WHERE account=?", model('user')->authOwnerId, 0, $account);
        }
        return true;
    }

    public function countUnread() {
        $query = $this->db->query("SELECT SUM(unread ) as total FROM inboxes WHERE userid=?", model('user')->authOwnerId);
        $fetch = $query->fetch(PDO::FETCH_ASSOC);
        return $fetch['total'];
    }

    public function accountExists($id) {
        $query = $this->db->query("SELECT * FROM inboxes WHERE account=?", $id);
        return $query->rowCount();
    }

    public function getAccounts($social) {
        $sql = "SELECT * FROM accounts WHERE social_type=? AND userid=?";
        $param = array($social, model('user')->authOwnerId);
        if ($social == 'facebook') {
            $sql .= " AND account_type=? ";
            $param[] = 'page';
        }
        $query = $this->db->query($sql, $param);
        $result = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'id' => $fetch['id'],
                'title' => str_limit($fetch['username'], 30),
                'url' => assetUrl($fetch['avatar'])
            );
        }
        return $result;
    }

    public function getLetters($name) {
        $names = explode(' ', $name);
        if (count($names) == 1) {
            return strtoupper(mb_substr($name, 0, 2));
        } else {
            return strtoupper(mb_substr($names[0], 0,1).mb_substr($names[1], 0, 1));
        }
    }
}