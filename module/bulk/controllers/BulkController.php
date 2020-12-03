<?php
class BulkController extends Controller {

    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveIconMenu('bulk');
    }

    public function index() {
        $this->setTitle(l('bulk-schedule'));
        if (!model('user')->hasPermission('bulk-schedule', true)) return $this->request->redirect(url('post'));
        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            $bulkId = $this->model('bulk::bulk')->addBulk($val);
            return json_encode(array(
                'type' => 'modal-url',
                'content' => '#newBulkModal',
                'message' => l('bulk-created-success'),
                'value' => url('bulk/schedule/'.$bulkId)
            ));
        }

        if ($now = $this->request->input('start')) {
            //header("Content-type: text/xml");


            $rules = $this->model('bulk::bulk')->getSchedules();
            $colors = array('color1','color2', 'color3','color4','color5','color6','color7','color8','color9');
            $result = array();
            foreach($rules as $rule){
                $url = url('bulk/schedule/'.$rule['bulk_id']);

                $datetime = new DateTime($rule['from_date'],new DateTimeZone(config('timezone')));
                $start = $datetime->format(DateTime::ATOM);
                list($first,$second) = explode(':', $start);
                $start = $first.':00:00+01:00';
                $datetime = new DateTime($rule['to_date'],new DateTimeZone(config('timezone')));
                $end = $datetime->format(DateTime::ATOM);
                list($first,$second) = explode(':', $end);
                $end = $first.':00:00+01:00';
                $bulk = model('bulk::bulk')->find($rule['bulk_id']);
                $title = $bulk['title'];
                $color = $colors[rand(0, count($colors)-1)];
                $result[] = array(
                    'id' => $rule['id'],
                    'title' => $title,
                    'start' => $start,
                    'url' => $url,
                    'end' => $end,
                    'className' => $color.'-bg'
                );
            }

            return json_encode($result);
        }
        return $this->render($this->view('bulk::index'), true);
    }

    public function page() {
        $bulk  = $this->model('bulk::bulk')->find($this->request->segment(2));
        if (!$bulk) return $this->request->redirect(url('bulk/schedule'));
        $this->setTitle(l('bulk-schedule'));
        if ($account = $this->request->input('account')) {
            $accounts = $this->request->input('accounts');
            $this->model('bulk::bulk')->updateAccounts($bulk['id'], $accounts);
            return json_encode(array(
                'type' => 'function',
                'message' => l('post-accounts-updated')
            ));
        }
        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if ($val['action'] == 'rule') {
                if (!isset($val['rules'])) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('add-one-post-rule')
                    ));
                }
                Database::getInstance()->query("DELETE  FROM bulk_rules WHERE bulk_id=?", $bulk['id']);
                foreach($val['rules'][0] as $rule) {
                    $this->model('bulk::bulk')->addRule($rule, $bulk['id']);
                }
                return json_encode(array(
                    'type' => 'function',
                    'message' => l('post-rules-save')
                ));
            }


            if ($val['action'] == 'upload-csv') {
                if ($file = $this->request->inputFile('file')) {
                    $uploader = new Uploader($file, 'file',false,false,false,false, array('csv'));
                    if ($uploader->passed()) {
                        $uploader->setPath("tmp/csv/");
                        $file = $uploader->uploadFile()->result();
                        $h = fopen(path($file), "r");
                        while (($data = fgetcsv($h, 1000, ",")) !== FALSE)
                        {
                            // Read the data
                            $text = $data[0];
                            $image  = $data[1];

                            $dir = "uploads/bulk/schedule/".model('user')->authOwnerId.'/';
                            if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
                            $file = $dir.md5($image).'.'.get_file_extension($image);
                            getFileViaCurl($image, $file);
                            $fileSize = filesize(path($file));
                            $val = array();
                            if(isImage($file)) {
                                $upload = new Uploader(path($file), 'image', false, true);
                                $upload->setPath("files/images/".model('user')->authOwnerId.'/'.time().'/');
                                $result = $upload->resize()->result();
                                $val['file_name'] = str_replace('%w', 920, $result);
                                $val['resize_image'] = str_replace('%w', 200, $result);
                                $val['file_type'] = 'image';
                            } else {
                                $val['file_type'] = 'video';
                                $val['resize_image'] = '';
                                $val['file_name'] = $file;
                            }
                            $val['file_size'] = $fileSize;
                            $id = $this->model('filemanager')->save($val);
                            $id = $this->model('bulk::bulk')->upload($id, $bulk['id']);
                            Database::getInstance()->query("UPDATE bulk_posts SET caption=? WHERE id=?", $text, $id);
                        }

                        return json_encode(array(
                            'type' => 'url',
                            'value' => url('bulk/schedule/'.$bulk['id']),
                            'message' => l('post-imported-success')
                        ));

                    } else {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => $uploader->getError()
                        ));
                    }
                }
            }

            if ($val['action'] == 'upload') {
                if ($files = $this->request->inputFile('file')) {

                    $uploadedFiles = array();
                    foreach($files as $file) {
                        if (!$this->model('user')->canUpload()) {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => l('file-upload-usage-limit')
                            ));
                        }
                        if (isImage($file)) {
                            if (!$this->model('user')->hasPermission('photo')){
                                return json_encode(array(
                                    'type' => 'error',
                                    'message' => l('you-are-not-allow-photo')
                                ));
                            }
                        } else {
                            if (!$this->model('user')->hasPermission('video')){
                                return json_encode(array(
                                    'type' => 'error',
                                    'message' => l('you-are-not-allow-video')
                                ));
                            }
                        }
                        $upload = new Uploader($file, (isImage($file)) ? 'image' : 'video');
                        (isImage($file)) ? $upload->setPath("files/images/".model('user')->authOwnerId.'/'.time().'/') : $upload->setPath('files/videos/'.model('user')->authOwnerId.'/');
                        if ($upload->passed()) {
                            if (isImage($file)) {
                                $result = $upload->resize()->result();
                                $val['file_name'] = str_replace('%w', 920, $result);
                                $val['resize_image'] = str_replace('%w', 200, $result);
                                $val['file_size'] = filesize(path($val['file_name']));
                                $val['file_type'] = 'image';

                            } else {
                                $val['file_type'] = 'video';
                                $val['file_name'] = $upload->uploadFile()->result();
                                $val['file_size'] = filesize(path($val['file_name']));
                                $val['resize_image'] = '';
                            }

                            $id = $this->model('filemanager')->save($val);
                            $id = $this->model('bulk::bulk')->upload($id, $val['id']);
                        } else {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => $upload->getError()
                            ));
                        }
                    }


                    return json_encode(array(
                        'type' => 'url',
                        'message'=> l('upload-successful'),
                        'value' => url('bulk/schedule/'.$val['id']),
                    ));

                }

            }

            if ($val['action'] == 'edit-post') {
                $this->model('bulk::bulk')->savePost($val);
                return json_encode(array(
                    'type' => 'function',
                    'message'=> l('bulk-post-updated'),
                ));

            }
        }

        if ($action = $this->request->input('action')) {
            $this->defendDemo();
            if ($action == 'delete-post') {
                $this->model('bulk::bulk')->deletePost($this->request->input('id'));
                return json_encode(array(
                    'type' => 'url',
                    'message'=> l('post-delete-successful'),
                    'value' => url('bulk/schedule/'.$bulk['id']),
                ));

            } elseif($action == 'delete-bulk') {
                $this->model('bulk::bulk')->delete($bulk['id']);
                return json_encode(array(
                    'type' => 'url',
                    'message'=> l('bulk-schedule-delated'),
                    'value' => url('bulk/schedule'),
                ));
            }
        }

        $offset = $this->request->input('offset', 0);
        $posts = $this->model('bulk::bulk')->getPosts($bulk['id'], $offset);

        if ($paginate = $this->request->input('paginate')) {
            $content = '';
            foreach($posts as $post) {
                $content .= view('bulk::page/display', array('post' => $post, 'bulk' => $bulk));
            }
            return json_encode(array(
                'offset' => $offset + 8,
                'content' => $content
            ));
        }
        return $this->render($this->view('bulk::page', array('bulk' => $bulk, 'posts' => $posts)), true);
    }


    public function create() {
        $id = $this->request->input('id');
        $folder = $this->model('filemanager')->find($id);
        $bulkId = $this->model('bulk::bulk')->addBulk(array(
            'title' => $folder['file_name'],
        ));
        $files = $this->model('filemanager')->loadFiles(0,$id, 500);
        foreach($files as $file) {
            if($file['file_type'] != 'folder') $this->model('bulk::bulk')->upload($file['id'], $bulkId);
        }
        return json_encode(array(
            'type' => 'url',
            'message' => l('bulk-created-success'),
            'value' => url('bulk/schedule/'.$bulkId)
        ));
    }
}