<?php
class HashtagController extends Controller {

    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveIconMenu('hashtag');
    }


    public function index() {
        $this->setTitle(l('hashtag-manager'));
        if ($val = $this->request->input('val')) {
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'title' => 'required',
                    'content' => 'required'
                ));

                autoLoadVendor();
                $emojione = new \Emojione\Client(new \Emojione\Ruleset());
                $val['content'] = $emojione->toShort($val['content']);

                if ($validator->passes()) {

                    $this->model('hashtag::hashtag')->add($val);

                    return json_encode(array(
                        'message' => l('hashtag-created-successful'),
                        'type' => 'url',
                        'value' => url('hashtags')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }

            if (isset($val['edit']) and $id = $val['edit']) {
                autoLoadVendor();

                $this->model('hashtag::hashtag')->save($val, $id);
                return json_encode(array(
                    'message' => l('hashtag-save-successful'),
                    'type' => 'modal-url',
                    'content' => '#hashtagEditModal'.$id,
                    'value' => url('hashtags')
                ));
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            switch($action) {
                case 'delete':
                    $this->model('hashtag::hashtag')->delete($id);
                    break;
            }

            return json_encode(array(
                'message' => l('hashtag-action-successful'),
                'type' => 'url',
                'value' => url('hashtags')
            ));
        }


        $hashtags = $this->model('hashtag::hashtag')->getHashtags($this->request->input('term'));

        if (isset($_GET['term']) and is_ajax() and !isFullSearch()) {
            return json_encode(array(
                'content' => $this->view('hashtag::display', array('hashtags' => $hashtags)),
                'title' => $this->getTitle(),
            ));
        }

        if ($load = $this->request->input('load')) {
            $hashtags = $this->model('hashtag::hashtag')->getAllHashtags();
            return $this->view('hashtag::load', array('hashtags' => $hashtags));
        }
        return $this->render($this->view('hashtag::index', array('hashtags' => $hashtags)), true);
    }
}