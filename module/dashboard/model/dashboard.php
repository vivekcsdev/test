<?php
class DashboardModel extends Model {
    public function count($type, $admin = false) {
        switch($type) {
            case 'total-posts':
                $query = $this->db->query("SELECT * FROM posts WHERE userid=?", model('user')->authOwnerId);
                return $query->rowCount();
                break;
            case  'all-posts' :
                $query = $this->db->query("SELECT * FROM posts");
                return $query->rowCount();
                break;
            case 'scheduled':
                if ($admin) {
                    $query = $this->db->query("SELECT * FROM posts WHERE is_scheduled=? AND status=?", 1, 2);
                } else {
                    $query = $this->db->query("SELECT * FROM posts WHERE userid=? AND is_scheduled=? and status=?", model('user')->authOwnerId, 1, 2);
                }
                return $query->rowCount();
                break;
            case 'completed':
                if ($admin) {
                    $query = $this->db->query("SELECT * FROM posts WHERE  (status=? OR status=?)",  1, 3);
                } else {
                    $query = $this->db->query("SELECT * FROM posts WHERE userid=? AND (status=? OR status=?)", model('user')->authOwnerId, 1, 3);
                }
                return $query->rowCount();
                break;
            case 'success':
                if ($admin) {
                    $query = $this->db->query("SELECT * FROM posts WHERE  status=?", 1);
                } else {
                    $query = $this->db->query("SELECT * FROM posts WHERE userid=? AND status=?", model('user')->authOwnerId, 1);
                }
                return $query->rowCount();
                break;
            case 'failed':
                if ($admin) {
                    $query = $this->db->query("SELECT * FROM posts WHERE  status=?",  3);
                } else {
                    $query = $this->db->query("SELECT * FROM posts WHERE userid=? AND status=?", model('user')->authOwnerId, 3);
                }
                return $query->rowCount();
                break;
            case 'accounts':
                $query = $this->db->query("SELECT * FROM accounts WHERE userid=?", model('user')->authOwnerId);
                return $query->rowCount();
                break;
            case 'files':
                $query = $this->db->query("SELECT * FROM files WHERE userid=?", model('user')->authOwnerId);
                return $query->rowCount();
                break;
            case 'captions':
                $query = $this->db->query("SELECT * FROM captions WHERE userid=?", model('user')->authOwnerId);
                return $query->rowCount();
                break;
            case 'all-parties':
                $query = $this->db->query("SELECT * FROM parties WHERE userid=?", model('user')->authOwnerId);
                return $query->rowCount();
                break;
            case 'scheduled-parties':
                $query = $this->db->query("SELECT * FROM parties_scheduled WHERE userid=?", model('user')->authOwnerId);
                return $query->rowCount();
                break;
        }

        return 0;
    }
}