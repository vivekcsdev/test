<?php
class GroupModel extends Model {
    public function lists() {
        $query = $this->db->query("SELECT * FROM groups WHERE userid=? ORDER BY id DESC", model('user')->authOwnerId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save($val, $groupId = null) {
        $ext = array(
            'title' => '',
            'accounts' => array()
        );
        /**
         * @var $title
         * @var $accounts
         */
        extract(array_merge($ext, $val));

        $accounts = perfectSerialize($accounts);
        if ($groupId) {
            return $this->db->query("UPDATE groups SET title=?,accounts=? WHERE id=?", $title, $accounts, $groupId);
        } else {
            return $this->db->query("INSERT INTO groups (title,userid,accounts,date_created)VALUES(?,?,?,?)",
                $title,model('user')->authOwnerId, $accounts, time());
        }
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM groups WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($groupId){
        return $this->db->query("DELETE FROM groups WHERE id=? AND userid=?", $groupId, model('user')->authOwnerId);
    }
}