<?php
class AnalyticsModel extends Model {
    public function find($accountId) {
        $query = $this->db->query("SELECT * FROM instagram_analytics WHERE account_id=? AND userid=?", $accountId, model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function save($id, $result) {
        $nextDay = time() + 86400;

        $statsResult = array(
            "media_count" => $result['userinfo']->media_count,
            "follower_count" => $result['userinfo']->follower_count,
            "following_count" => $result['userinfo']->following_count,
            "engagement" => $result['engagement']
        );
        $this->db->query("INSERT INTO instagram_analytics_stats (userid, account_id,result,time) VALUES(?,?,?,?) ",
            model('user')->authOwnerId,$id, perfectSerialize($statsResult),  date('Y-m-d 00:00:00', time()));
        if (!$this->find($id)) {
            return $this->db->query("INSERT INTO instagram_analytics (userid,account_id,result,fetch_time)VALUES(?,?,?,?)",
                model('user')->authOwnerId, $id, perfectSerialize($result), $nextDay);
        } else {
            return $this->db->query("UPDATE instagram_analytics SET result=?,fetch_time=? WHERE account_id=? AND userid=?",
                perfectSerialize($result), $nextDay, $id, model('user')->authOwnerId);
        }
    }

    public function getStats($accountId) {
        $analytics = model('analytics')->find($accountId);

        $query =  $this->db->query("SELECT * FROM instagram_analytics_stats WHERE account_id=? AND userid=? ORDER BY id LIMIT 15", $accountId, model('user')->authOwnerId);
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        $result_tmp = array_reverse($result);
        $list = array();
        $followers_tmp = -1;
        $following_tmp = -1;
        $posts_tmp = -1;

        $followers_value_string = "";
        $following_value_string = "";
        $engagement_value_string = "";
        $date_string = "";
        $count_date = 0;

        if(!empty($result_tmp)){
            foreach ($result_tmp as $row) {
                //List summary
                $data = perfectUnserialize($row['result']);

                $follower_count = $data['follower_count'];
                $following_count = $data['following_count'];
                $engagement = $data['engagement'];
                $media_count = $data['media_count'];

                $list[$row['time']] = (object)array(
                    "followers" => $follower_count,
                    "following" => $following_count,
                    "posts" => $media_count,
                    "followers_summary" => ($followers_tmp == -1)?"":($follower_count-$followers_tmp),
                    "following_summary" => ($following_tmp == -1)?"":($following_count-$following_tmp),
                    "posts_summary" => ($posts_tmp == -1)?"":($media_count-$posts_tmp),
                    "date" => $row['time']
                );

                $followers_tmp = $follower_count;
                $following_tmp = $following_count;
                $posts_tmp = $media_count;

                //Followers chart
                $followers_value_string .= "{$follower_count},";

                //Followers chart
                $following_value_string .= "{$following_count},";

                //Followers chart
                $engagement_value_string .= "{$engagement},";

                //Date chart
                $date_string .= "'{$row['time']}',";
            }

            //Cound Date
            $start_date = strtotime($result_tmp[0]['time']);
            $end_date = strtotime($result_tmp[count($result_tmp) - 1]['time']);
            $datediff = $end_date - $start_date;
            $count_date = round($datediff / (60 * 60 * 24));

            $followers_value_string = "[".substr($followers_value_string, 0, -1)."]";
            $following_value_string = "[".substr($following_value_string, 0, -1)."]";
            $engagement_value_string = "[".substr($engagement_value_string, 0, -1)."]";
            $date_string  = "[".substr($date_string, 0, -1)."]";

            //Following chart



        }
        $result = array(
            "list_summary" => $list,
            "followers_chart" => $followers_value_string,
            "following_chart" => $following_value_string,
            "engagement_chart" => $engagement_value_string,
            "date_chart" => $date_string,
            "total_days" => $count_date
        );
        $result = array_merge(perfectUnserialize($analytics['result']),$result);

        return $result;
    }

    public function runCron() {
        $time = time();
        $query = $this->db->query("SELECT * FROM instagram_analytics WHERE fetch_time<$time LIMIT 2");
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $user = model('user')->getUser($fetch['userid']);
            model('user')->loginWithObject($user);
            $accountId = $fetch['account_id'];
            $account = model('account')->find($accountId);
            if(!$account) {
                model('user')->logoutUser();
            } else {
                $analytics = model('analytics')->find($accountId);
                $proxy = model('proxy')->findOneProxy(null, $account);

                $instagramApi = getController()->api('instagram')->init($account['username'], mDcrypt($account['password']), $proxy['proxy']);

                $analyticsDetails = $instagramApi->analytics();
                $this->save($accountId, $analyticsDetails);
                model('user')->logoutUser();
            }
        }
    }
}