<?php
class AutomationModel extends Model {
    public function ensureRow($id) {
        $row = $this->findByAccount($id);
        if (!$row) {
            $this->db->query("INSERT INTO automations (userid,account)VALUES(?,?)", model('user')->authId, $id);
        }
    }

    public function saveBot($val, $accountId) {
        $ext = array(
            'features' => array(),
            'settings' => array(),
        );
        /**
         * @var $features
         * @var $settings
         */
        extract(array_merge($ext, $val));

        $automation = $this->findByAccount($accountId);
        $beforeFeatures = ($automation['features']) ? perfectUnserialize($automation['features']) : array(
            'likes' => 0,
            'comments' => 0,
            'follow' => 0,
            'unfollow' => 0,
            'stories' => 0,
            'repost' => 0,
            'messages' => 0
        );
        $submittedFeatures = $features;
        $features = perfectSerialize($features);

        if ($automation['status'] == 1) {
            foreach($submittedFeatures as $feature => $value) {
                if ($value and !$beforeFeatures[$feature]) {
                    //we can activate this features as started as well
                    $hour = date('H');
                    $this->db->query("INSERT INTO automations_actions (automation_id,action,time,current_hour)VALUES(?,?,?,?)", $automation['id'], $feature, time(),$hour);

                }
            }
        }
        $settings = perfectSerialize($settings);
        return $this->db->query("UPDATE automations SET features=?,settings=? WHERE account=?", $features, $settings, $accountId);
    }

    public function getAutomations() {
        $query = $this->db->query("SELECT * FROM automations WHERE userid=?", model('user')->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByAccount($id) {
        $query = $this->db->query("SELECT * FROM automations WHERE account=? AND userid=?", $id,model('user')->authId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getAvailableAccounts() {
        $query = $this->db->query("SELECT * FROM accounts WHERE userid=? AND (social_type=? ) ORDER BY id DESC", model('user')->authOwnerId, 'instagram');
        $result =  $query->fetchAll(PDO::FETCH_ASSOC);
        $accounts = array();
        $added = array();
        foreach($result as $res) {
            if(config('enable-'.$res['social_type'].'-activity', true)) {
                if (!in_array($res['access_token'], $added)) {
                    if($res['social_type'] == 'pinterest')  {
                        $res['username']  = $res['access_token'];
                    }
                    $accounts[] = $res;
                    $added[] = $res['access_token'];
                }
            }

        }
        return $accounts;
    }

    public function countActionSinceLastStart($id, $type) {
        $query = $this->db->query("SELECT * FROM automations_log WHERE automation_id=? AND action=?", $id, $type);
        return $query->rowCount();
    }

    public function getBoards($account) {
        $query = $this->db->query("SELECT * FROM accounts WHERE userid=? AND (social_type=? AND password!='' AND access_token=?) ORDER BY id DESC", model('user')->authOwnerId,  'pinterest', $account['access_token']);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function start($automation) {
        $features = ($automation['features']) ? perfectUnserialize($automation['features']) : array();
        $settings = ($automation['settings']) ? perfectUnserialize($automation['settings']) : array();
        $this->db->query("DELETE FROM automations_actions WHERE automation_id=?", $automation['id']);
        $this->db->query("UPDATE automations SET status=?,time=? WHERE id=?", 1, time(), $automation['id']);
        foreach($features as $action => $value) {
            if ($value) {
                $hour = date('H');
                $this->db->query("INSERT INTO automations_actions (automation_id,action,time,current_hour)VALUES(?,?,?,?)", $automation['id'], $action, time(),$hour);
            }
        }
        return true;
    }
    public function stop($automation) {
        $this->db->query("DELETE FROM automations_actions WHERE automation_id=?", $automation['id']);
        $this->db->query("UPDATE automations SET status=?,time=?", 0, time());
        return true;
    }

    public function getSettings($automation) {
        return ($automation['settings']) ? perfectUnserialize($automation['settings']) : array();
    }

    public function getSettingValue($key, $automation) {
        $settings = $this->getSettings($automation);
        return (isset($settings[$key])) ? $settings[$key] : false;
    }
    public function  getTarget($automation) {
        $settings = $this->getSettings($automation);
        $targets = array('tags', 'keywords', 'users','follower','following');
        if (isset($settings['locations']) and $settings['locations']) $targets[] = 'locations';
        if (isset($settings['tags']) and $settings['tags']) $targets[] = 'tags';
        if (isset($settings['keywords']) and $settings['keywords']) $targets[] = 'keywords';
        if (isset($settings['users']) and $settings['users']) $targets[] = 'users';

        $index = $targets[rand(0, count($targets)-1)];

        if ($index == 'follower' or $index == 'following') $index = 'target-'.$index;

        $tags = explode(',', $settings[$index]);
        return array('type' => $index, 'value' => $tags[rand(0, count($tags) - 1)]);
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM automations WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function logExists($id, $userid, $action) {
        $query = $this->db->query("SELECT * FROM automations_log WHERE action_id=? AND userid=? AND action=?", $id, $userid, $action);
        return $query->rowCount();
    }

    public function addLog($autoId, $id, $userid, $action, $data = null) {
        return $this->db->query("INSERT INTO automations_log (automation_id,action_id,userid,action,created,data)VALUES(?,?,?,?,?,?)",$autoId, $id,$userid,$action, time(),$data);
    }

    public function getLogs($autoId, $action) {
        $query = $this->db->query("SELECT * FROM automations_log WHERE automation_id=? AND action=?", $autoId, $action);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllLogs($id) {
        $query = $this->db->query("SELECT * FROM automations_log WHERE automation_id=? ORDER BY id DESC LIMIT 50", $id);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countAllLogs($id) {
        $query = $this->db->query("SELECT * FROM automations_log WHERE automation_id=?", $id);
        return $query->rowCount();
    }

    public function getComment($automation) {
        $comments = $this->getSettingValue('comments', $automation);
        $comments = $comments['comments'];
        return $comments[rand(0, count($comments)-1)];
    }


    public function getMessage($automation) {
        $messages = $this->getSettingValue('messages', $automation);
        return $messages[rand(0, count($messages)-1)];
    }

    public function finilize($actionRow, $automation) {
        $counter = $this->getSettingValue($actionRow['action'].'-counter', $automation);
        if ($counter != 0) {
            $logs = $this->getLogs($automation['id'], $actionRow['action']);
            if (count($logs) >= $counter) $this->db->query("DELETE FROM automations_actions WHERE automation_id=? AND action=?", $automation['id'], $actionRow['action']);
        }

        //next time
        $speed = $this->getSettingValue('speed', $automation);
        $nextSettings = $this->getNextTimeSettings($actionRow['action']);
        $numbers = $nextSettings[$speed];
        $user = model('user')->getUser($automation['userid']);
        date_default_timezone_set($user['timezone']);
        $currentHour = date('H');
        if ($actionRow['current_hour'] != $currentHour) {
            $time = time() + (60*60/$numbers);
            $this->db->query("UPDATE automations_actions SET time=?,current_hour=?,hour_ran=?  WHERE id=?", $time, $currentHour, 0, $actionRow['id']);
        } else {
            if ($actionRow['hour_ran'] <= $numbers) {
                $time = time() + (60*60/$numbers);
                $this->db->query("UPDATE automations_actions SET time=?,current_hour=?,hour_ran=? WHERE id=?", $time, $currentHour, $actionRow['hour_ran']+1, $actionRow['id']);
            }
        }
    }

    public function getNextTimeSettings($action) {
        switch($action) {
            case 'comments':
                return array(
                    'slow' => config('instagram_slow_comment', 2),
                    'very-slow' => config('instagram_very_slow_comment', 1),
                    'normal' => config('instagram_medium_comment', 3),
                    'fast' => config('instagram_fast_comment', 4),
                    'very-fast' => config('instagram_slow_comment', 8)
                );
                break;
            case 'likes':
                return array(
                    'slow' => config('instagram_slow_like', 2),
                    'very-slow' => config('instagram_very_slow_like', 1),
                    'normal' => config('instagram_medium_like', 3),
                    'fast' => config('instagram_fast_like', 4),
                    'very-fast' => config('instagram_slow_like', 8)
                );
                break;
            case 'messages':
                return array(
                    'slow' => config('instagram_slow_message', 2),
                    'very-slow' => config('instagram_very_slow_message', 1),
                    'normal' => config('instagram_medium_message', 3),
                    'fast' => config('instagram_fast_message', 4),
                    'very-fast' => config('instagram_slow_message', 8)
                );
                break;
            case 'follow':
                return array(
                    'slow' => config('instagram_slow_follow', 2),
                    'very-slow' => config('instagram_very_slow_follow', 1),
                    'normal' => config('instagram_medium_follow', 3),
                    'fast' => config('instagram_fast_follow', 4),
                    'very-fast' => config('instagram_slow_follow', 8)
                );
                break;
            case 'unfollow':
                return array(
                    'slow' => config('instagram_slow_unfollow', 2),
                    'very-slow' => config('instagram_very_slow_unfollow', 1),
                    'normal' => config('instagram_medium_unfollow', 3),
                    'fast' => config('instagram_fast_unfollow', 4),
                    'very-fast' => config('instagram_slow_unfollow', 8)
                );
                break;
            case 'repost':
                return array(
                    'slow' => config('instagram_slow_repost', 2),
                    'very-slow' => config('instagram_very_slow_repost', 1),
                    'normal' => config('instagram_medium_repost', 3),
                    'fast' => config('instagram_fast_repost', 4),
                    'very-fast' => config('instagram_slow_repost', 8)
                );
                break;
            case 'stories':
                return array(
                    'slow' => config('instagram_slow_stories', 2),
                    'very-slow' => config('instagram_very_slow_stories', 1),
                    'normal' => config('instagram_medium_stories', 3),
                    'fast' => config('instagram_fast_stories', 4),
                    'very-fast' => config('instagram_slow_stories', 8)
                );
                break;
        }
    }

    public function delete($automation) {
        $this->stop($automation);
        $this->db->query("DELETE FROM automations WHERE id=?", $automation['id']);
        $this->db->query("DELETE FROM automations_actions WHERE automation_id=?", $automation['id']);
        $this->db->query("DELETE FROM automations_log WHERE automation_id=?", $automation['id']);
        return true;
    }

    public function runCron() {
        $hour = date('H');
        $time = time();
        $query = $this->db->query("SELECT * FROM automations_actions WHERE  (current_hour=? OR current_hour=? OR time < $time) AND time < $time ORDER BY rand() LIMIT 10", $hour, $hour);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $automation = $this->find($fetch['automation_id']);
            $user = model('user')->getUser($automation['userid']);
            model('user')->loginWithObject($user);
            $account = model('account')->find($automation['account']);
            if (!$account)  {
                $this->delete($automation);
                model('user')->logoutUser();

            } else {
                switch($account['social_type']) {
                    case 'instagram':
                        getController()->api('instagram')->processActivity($account, $automation, $fetch);
                        break;
                    case 'pinterest':
                        getController()->api('pinterest')->processActivity($account, $automation, $fetch);
                        break;
                }
                model('user')->logoutUser();
            }
        }

        //delete old logs
        $time = time() - (24 * 60 * 60 * config('instagram_log_limit', 7));
        $this->db->query("DELETE FROM automations_log WHERE created < $time ");
    }
}