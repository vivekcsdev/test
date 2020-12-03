<?php
class ReferralModel extends Model {

    public function ensureAffiliate() {
        $query = $this->db->query("SELECT * FROM referrals WHERE userid=?", model('user')->authId);
        if ($query->rowCount() < 1) {
            $code = $this->generateCode();
            $this->db->query("INSERT INTO referrals (userid,code)VALUES(?,?)", model('user')->authId, $code);
        }
    }

    public function generateCode() {
        $key = uniqueKey(10, 10);
        $query = $this->db->query("SELECT * FROM referrals WHERE code=?", $key);
        if ($query->rowCount() > 1) {
            return $this->generateCode();
        } else {
            return $key;
        }
    }

    public function getReferralCode() {
        $query = $this->db->query("SELECT * FROM referrals WHERE userid=?", model('user')->authId);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['code'];
    }

    public function getDetails($userid = null) {
        $userid = ($userid) ? $userid : model('user')->authId;
        $query = $this->db->query("SELECT * FROM referrals WHERE userid=?", $userid);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getDetailsByRef($ref) {
        $query = $this->db->query("SELECT * FROM referrals WHERE code=?", $ref);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function getDetailsById($ref) {
        $query = $this->db->query("SELECT * FROM referrals WHERE id=?", $ref);
        return $query->fetch(PDO::FETCH_ASSOC);
    }


    public function savePayout($val) {
        return $this->db->query("UPDATE referrals SET paypal_email=?,payout_type=? WHERE userid=?", $val['email'], $val['type'], model('user')->authId);
    }

    public function processRef($c, $ref) {
        $details = $this->getDetailsByRef($ref);
        if ($details) {
            if (!$c->model('user')->isLoggedIn() or $details['userid'] != $c->model('user')->authId) {
                $clicks = $details['clicks']+1;
                $this->db->query("UPDATE referrals SET clicks=? WHERE code=?", $clicks, $ref);
                setcookie("referral_ref", $ref, time() + 30 * 24 * 60 * 60*60, config('cookie_path'));
            }
        }
    }

    public function addUser($userid, $ref) {
        $details = $this->getDetailsByRef($ref);
        return $this->db->query("INSERT INTO referral_tracking (userid,referral_id,signup_date)VALUES(?,?,?)", $userid, $details['id'], time());
    }

    public function countTrialMembers() {
        $details = $this->getDetails();
        $query = $this->db->query("SELECT * FROM referral_tracking WHERE referral_id=? AND status=?", $details['id'], 0);
        return $query->rowCount();
    }

    public function countPaidMembers() {
        $details = $this->getDetails();
        $query = $this->db->query("SELECT * FROM referral_tracking WHERE referral_id=? AND status=?", $details['id'], 1);
        return $query->rowCount();
    }

    public function getTotalEarned() {
        $details = $this->getDetails();
        $query = $this->db->query("SELECT SUM(commission) as total FROM referral_tracking WHERE referral_id=? AND status=?", $details['id'], 1);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return ($result['total']) ? $result['total'] : 0;
    }

    public function getUsers() {
        $details = $this->getDetails();
        $sql = "SELECT * FROM referral_tracking WHERE referral_id=? ";
        $param = array($details['id']);

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 200);
    }

    public function findUnProcessed($userid) {
        $query = $this->db->query("SELECT * FROM referral_tracking WHERE userid=? AND status=?", $userid, 1);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function processPayout($user, $amount) {
        $user = $this->getDetailsById($user['referral_id']);
        $balance = $user['balance'] + $amount;
        $this->db->query("UPDATE referrals SET balance=? WHERE id=?", $balance, $user['id']);
        $this->addPayout($user['id']);

        return true;
    }

    public function addPayout($id) {
        $referral = $this->getDetailsById($id);

        if ($referral['balance'] >= $referral['minimum_payout']) {
            $this->db->query("INSERT INTO referral_payouts (userid,amount,type,created)VALUES(?,?,?,?)", $referral['userid'],$referral['balance'],1, time());
            $this->db->query("UPDATE referrals SET balance=? WHERE id=?", 0, $referral['id']);
        }
    }

    public function getPendingPayouts() {
        $query = $this->db->query("SELECT * FROM referral_payouts WHERE status=? AND type=?", 0, 1);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMyPayouts() {
        $query = $this->db->query("SELECT * FROM referral_payouts WHERE userid=?", model('user')->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function markPaid($id) {
        return $this->db->query("UPDATE referral_payouts SET status=? WHERE id=?", 1, $id);
    }
}