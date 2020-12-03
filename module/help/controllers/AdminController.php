<?php
class AdminController extends Controller {
    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->setSideLayout('admin/layout/menu');
        $this->setActiveSubMenu('tutorials');
        $this->subMenuIcon = 'la la-cog';
    }


    public function index() {
        $this->setTitle(l('tutorials'));
        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'name' => 'required',
                    'content' => 'required',
                ));

                if ($validator->passes()) {

                    if($image = $this->request->inputFile('image')) {
                        $uploader = new Uploader($image, 'image');
                        $uploader->setPath('blogs/images/');
                        if ($uploader->passed()) {
                            $val['image'] = $uploader->resize()->result();
                        } else {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => $uploader->getError()
                            ));
                        }
                    }

                    if ($val['video']) {
                        $link = $val['video'];
                        include_once path('app/vendor/autoload.php');
                        try {
                            $linkPreview = new \LinkPreview\LinkPreview($link);
                            $parsed = $linkPreview->getParsed();
                            foreach ($parsed as $parserName => $link) {
                                $val['image'] = $link->getImage();
                                $val['video'] = $link->getVideoId();
                            }
                        } catch (Exception $e){}
                    }
                    if (!$val['video'] and !isset($val['image'])) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('please-provide-media-file-tutorial')
                        ));
                    }
                    $this->model('help::help')->add($val);

                    return json_encode(array(
                        'message' => l('tutorial-added-successful'),
                        'type' => 'url',
                        'value' => url('admin/tutorials')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }



            if (isset($val['bulk_action'])) {
                $this->defendDemo();
                $ids = $val['id'];
                foreach($ids as $id) {
                    $this->model('help::help')->delete($id);
                }
                return json_encode(array(
                    'message' => l('bulk-action-successful'),
                    'type' => 'url',
                    'value' => url('admin/tutorials')
                ));
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            $this->defendDemo();
            switch($action) {
                case 'delete':
                    $this->model('help::help')->delete($id);
                    break;
            }

            return json_encode(array(
                'message' => l('tutorial-action-successful'),
                'type' => 'url',
                'value' => url('admin/tutorials')
            ));
        }


        $tutorials = $this->model('help::help')->lists($this->request->input('term'));
        if (isset($_GET['term']) and is_ajax() and !isFullSearch()) {
            return json_encode(array(
                'content' => $this->view('help::admin/display', array('tutorials' => $tutorials)),
                'title' => $this->getTitle(),
            ));
        }

        return $this->render($this->view('help::admin/index', array('tutorials' => $tutorials)), true);
    }

    public function helpEdit() {
        $this->setTitle(l('tutorials'));

        $id = $this->request->segment(3);
        $tutorial = $this->model('help::help')->find($id);

        if (!$tutorial) return $this->request->redirect(url('admin/tutorials'));

        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            $validator = Validator::getInstance()->scan($val, array(
                'name' => 'required',
                'content' => 'required'
            ));

            if ($validator->passes()) {
                if($image = $this->request->inputFile('image')) {
                    $uploader = new Uploader($image, 'image');
                    $uploader->setPath('blogs/images/');
                    if ($uploader->passed()) {
                        $val['image'] = $uploader->resize()->result();
                    } else {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => $uploader->getError()
                        ));
                    }
                } else {
                    $val['image'] = $tutorial['image'];
                }

                if ($val['video']) {
                    $link = $val['video'];
                    include_once path('app/vendor/autoload.php');
                    try {
                        $linkPreview = new \LinkPreview\LinkPreview($link);
                        $parsed = $linkPreview->getParsed();
                        foreach ($parsed as $parserName => $link) {
                            $val['image'] = $link->getImage();
                            $val['video'] = $link->getVideoId();
                        }
                    } catch (Exception $e){}
                } else {
                    $val['video'] = $tutorial['video'];
                }
                if (!$val['video'] and !isset($val['image'])) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('please-provide-media-file-tutorial')
                    ));
                }
                $this->model('help::help')->save($val, $id);

                return json_encode(array(
                    'message' => l('tutorial-save-successful'),
                    'type' => 'url',
                    'value' => url('admin/tutorials')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
        return $this->render($this->view('help::admin/edit', array('tutorial' => $tutorial)),true);
    }
}