<?php
class ScheduleController extends Controller {

    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveMenu("schedules")->setActiveIconMenu('schedules');
        $this->subMenuIcon = 'la la-calendar';
    }

    public function index() {
        $this->setTitle(l('schedules'));

        if ($now = $this->request->input('start')) {
            //header("Content-type: text/xml");



            $type = $this->request->segment(2, 'all');
            $types = ($type == 'all') ? array('facebook','instagram','twitter','youtube','vk','linkedin','google','tumblr','pinterest','vimeo','dailymotion','telegram') : array($type);
            $which = $this->request->segment(1);
            $result = array();
            foreach($types as $type) {
                $posts = $this->model('post')->getSchedules($which, $type);

                foreach($posts as $post){
                    $url = url('schedule/posts/'.$which.'/'.$post['social_type'], array('date' => $post['schedule_date']));

                    $user = $this->model('user')->authUser;

                    date_default_timezone_set($user['timezone']);

                    $datetime = new DateTime($post['schedule_date']);
                    $start = $datetime->format(DateTime::ATOM);
                    list($first,$second) = explode(':', $start);
                    $start = $first.':00:00';
                    $result[] = array(
                        'id' => $post['id'],
                        'title' => $post['total'].' '.ucwords($post['social_type']),
                        'start' => $start,
                        'url' => $url,
                        'className' => $post['social_type'].'-bg'
                    );
                }
            }

            $result = Hook::getInstance()->fire('schedule.result', $result, array($result));
            return json_encode($result);
        }
        return $this->render($this->view('schedule/index'), true);
    }

    public function posts() {
        $this->setTitle(l('posts'));

        if ($action = $this->request->input('action')) {
            $this->model('post')->deletePostsByDate($this->request->segment(2), $this->request->segment(3), $this->request->input('date'));
            return json_encode(array(
                'type' => 'url',
                'value' => url('schedules/'.$this->request->segment(2)),
                'message' => l('posts-deleted-success')
            ));
        }

        if ($val = $this->request->input('val')) {
            if (isset($val['bulk_action'])) {
                $ids = $val['id'];
                foreach($ids as $id) {
                    $this->model('post')->deletePosts($id);
                }
                return json_encode(array(
                    'message' => l('bulk-action-successful'),
                    'type' => 'reload',
                ));
            }
        }

        $offset = $this->request->input('offset', 0);



        $posts = $this->model('post')->getSchedulePosts($this->request->segment(2), $this->request->segment(3), $this->request->input('date'), $offset);
        if ($paginate = $this->request->input('paginate')) {
            $content = '';
            foreach($posts as $post) {
                $content .= view('schedule/display-post', array('post' => $post));
            }
            return json_encode(array(
                'offset' => $offset + 10,
                'content' => $content
            ));
        }
        return view('schedule/posts', array('posts' => $posts));
    }
}