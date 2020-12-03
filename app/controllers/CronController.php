<?php
class CronController extends Controller {
    public function run() {
        $this->db = Database::getInstance();

        Hook::getInstance()->fire('cronjob.start');


        $posts = $this->model('post')->getCronPosts();


        foreach($posts as $post) {


            $user = $this->model('user')->getUser($post['userid']);
            $this->model('user')->loginWithObject($user);

            $accountDetail = $this->model('account')->find($post['account']);

            if (!$accountDetail) {
                $this->model('user')->logoutUser();
                $this->model('post')->setUnpublished($post['id']);
            } else {
                $now = date("Y-m-d H:i:s");

                date_default_timezone_set($user['timezone']);
                if ($post['schedule_date'] < date("Y-m-d H:i")) {

                    $socialType = $accountDetail['social_type'];
                    try  {
                        $this->model('post')->setPublished($post['id']);

                        $this->api($socialType)->post($post, $post['account']);

                        if( $post['repeat_freq'] != '' and strtotime($now) < strtotime($post['repeat_end'])) {
                            $nextTime = ($post['repeat_freq'] == 'once') ? $post['repeat_end'] : '';
                            $newFreq = ($post['repeat_freq'] == 'once') ? '' : $post['repeat_freq'];
                            if (!$nextTime) {
                                $freq = (($post['repeat_freq'] == 'everyday')) ? 1 : $post['repeat_freq'] ;
                                $freqSecs = $freq * 86400;
                                $nextTime = time() + $freqSecs;
                                $nextTime = date("Y-m-d H:i:s", $nextTime);
                            }
                            $this->db->query("INSERT INTO posts (userid,account,social_type,type,type_data,is_scheduled,schedule_date,repeat_freq,repeat_end,status,created_date)VALUES(?,?,?,?,?,?,?,?,?,?,?)",
                                $post['userid'],$post['account'],$post['social_type'],$post['type'],$post['type_data'],1,$nextTime,$newFreq,$post['repeat_end'], 2, time());
                        }

                    } catch (Exception $e) {

                        $this->model('post')->setUnpublished($post['id']);
                    }
                }
                $this->model('user')->logoutUser();
            }
        }

        $this->model('analytics')->runCron();


        $this->model('rss')->runCronJob();

        Hook::getInstance()->fire('cronjob.finished');
        exit('JOB DONE!!!');
    }
}