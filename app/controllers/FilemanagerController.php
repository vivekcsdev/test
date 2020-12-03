<?php
class FilemanagerController extends Controller {
    public function __construct($request)
    {
        parent::__construct($request);
    }

    public function index(){
        $this->setTitle(l('file-manager'))->setActiveIconMenu('filemanager');
        $this->appSideLayout = '';

        if ($this->request->input('dragged') and $files = $this->request->inputFile('file')) {

            $val = array('folder_id' => $this->request->input('folder', 0));
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
                    $uploadedFiles[] = model('filemanager')->find($id);
                } else {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => $upload->getError()
                    ));
                }
            }

            $content = '';
            foreach($uploadedFiles as $file) {
                $content .= view('filemanager/display', array('file' => $file));
            }

            return json_encode(array(
                'type' => 'function',
                'message'=> l('upload-successful'),
                'value' => 'uploadFinished',
                'content' => $content
            ));

        }

        if ($val = $this->request->input('val')) {
            if (isset($val['upload'])) {
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
                            $uploadedFiles[] = model('filemanager')->find($id);
                        } else {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => $upload->getError()
                            ));
                        }
                    }

                    $content = '';
                    foreach($uploadedFiles as $file) {
                        $content .= view('filemanager/display', array('file' => $file));
                    }

                    return json_encode(array(
                        'type' => 'function',
                        'message'=> l('upload-successful'),
                        'value' => 'uploadFinished',
                        'content' => $content
                    ));

                }

            }
            if (isset($val['action'])) {
                if ($val['action'] == 'sort') {
                    $i = 0;
                    foreach($val['filesi'] as $file) {
                        Database::getInstance()->query("UPDATE files SET sort_number=? WHERE id=?", $i, $file);
                        $i++;
                    }
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'confirmFileSort',

                    ));
                } else {
                    if(isset($val['files']) and !empty($val['files'])) {
                        foreach($val['files'] as $id) {
                            $this->model('filemanager')->delete($id);
                        }
                        return json_encode(array(
                            'type' => 'function',
                            'value' => 'confirmFileDelete',
                            'message' => l('files-deleted-successfully'),
                            'content' => implode(',',$val['files'])
                        ));
                    }
                }

            }

            if (isset($val['folder'])) {
                $id = $this->model('filemanager')->addFolder($val);
                $folder = $this->model('filemanager')->find($id);
                return json_encode(array(
                    'type' => 'function',
                    'value' => 'confirmFolderCreate',
                    'message' => l('folder-created'),
                    'content' => $this->view('filemanager/display-folder', array('file' => $folder))
                ));
            }


        }

        if ($editFolder = $this->request->input('editfolder')) {
            $val = array(
                'name' => $this->request->input('name'),
                'folder_id' => $this->request->input('id')
            );
            $this->model('filemanager')->saveFolder($val);
            return json_encode(array(
                'type' => 'function',
                'value' => 'confirmFolderEdit',
                'message' => l('folder-edited'),
                'content' => json_encode(array('id' => $val['folder_id'], 'name' => $val['name']))
            ));
        }
        if ($action = $this->request->input('action')) {
            switch($action) {
                case 'delete':
                    $this->defendDemo();
                    $id = $this->request->input('id');
                    $this->model('filemanager')->delete($id);
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'confirmFileDelete',
                        'message' => l('files-deleted-successfully'),
                        'content' => implode(',',array($id))
                    ));
                    break;
            }
        }

        if ($google = $this->request->input('google')) {
            $fileId = $this->request->input('file_id');
            $fileName = $this->request->input('file_name');
            $fileSize = $this->request->input('file_size');
            $oAuthToken = $this->request->input('oauthToken');

            if (!$this->model('filemanager')->validSelectedFile($fileName)) {
                return json_encode(array('status' => '0', 'message' => l('selected-file-not-supported')));
            }

            $getUrl = 'https://www.googleapis.com/drive/v2/files/' . $fileId . '?alt=media';
            $authHeader = 'Authorization: Bearer ' . $oAuthToken;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $getUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($authHeader));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $data = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            $ext = get_file_extension($fileName);
            $fileName = md5($fileName.time()).'.'.$ext;
            $val = array();
            if (!$this->model('user')->canUpload()) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('file-upload-usage-limit')
                ));
            }

            if (isImage($fileName)) {
                if (!$this->model('user')->hasPermission('photo')){
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('you-are-not-allow-photo')
                    ));
                }
                $tempFileDir = 'uploads/files/images/'.model('user')->authOwnerId.'/';
                if (!is_dir(path($tempFileDir))) {
                    @mkdir(path($tempFileDir), 0777, true);
                }
                $tempFile = $tempFileDir.$fileName;
                file_put_contents(path($tempFile), $data);
                $upload = new Uploader(path($tempFile), 'image', false, true);
                $upload->setPath("files/images/".model('user')->authOwnerId.'/'.time().'/');
                $result = $upload->resize()->result();
                $val['file_name'] = str_replace('%w', 920, $result);
                $val['resize_image'] = str_replace('%w', 200, $result);
                $val['file_size'] = $fileSize;
                $val['file_type'] = 'image';
                $val['folder_id'] = $this->request->input('folder_id');


            } else {
                if (!$this->model('user')->hasPermission('video')){
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('you-are-not-allow-video')
                    ));
                }
                //for videos mp4
                $tempFileDir = 'uploads/files/videos/'.model('user')->authOwnerId.'/';
                if (!is_dir(path($tempFileDir))) {
                    @mkdir(path($tempFileDir), 0777, true);
                }
                $tempFile = $tempFileDir.$fileName;
                file_put_contents(path($tempFile), $data);

                $val['file_type'] = 'video';
                $val['file_name'] = $tempFile;
                $val['file_size'] = $fileSize;
                $val['resize_image'] = '';
                $val['folder_id'] = $this->request->input('folder_id');
            }

            $id = $this->model('filemanager')->save($val);
            return json_encode(array(
                'status' => 1,
                'message'=> l('upload-successful'),
                'content' => view('filemanager/display', array('file' => model('filemanager')->find($id)))
            ));
        }

        if ($dropbox = $this->request->input('dropbox') or $onedrive = $this->request->input('onedrive')) {
            $fileName = $this->request->input('file_name');
            $fileSize = $this->request->input('file_size');
            $fileLink = $this->request->input('file');
            if (!$this->model('filemanager')->validSelectedFile($fileName)) {
                return json_encode(array('status' => '0', 'message' => l('selected-file-not-supported')));
            }

            if (!$this->model('user')->canUpload()) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('file-upload-usage-limit')
                ));
            }

            $ext = get_file_extension($fileName);

            $dir = "uploads/files/file/".model('user')->authOwnerId.'/';
            if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
            $file = $dir.md5($fileName).'.'.$ext;
            getFileViaCurl($fileLink, $file);
            $val = array();
            if (isImage($fileName)) {
                if (!$this->model('user')->hasPermission('photo')){
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('you-are-not-allow-photo')
                    ));
                }
                $upload = new Uploader(path($file), 'image', false, true);
                $upload->setPath("files/images/".model('user')->authOwnerId.'/'.time().'/');
                $result = $upload->resize()->result();
                $val['file_name'] = str_replace('%w', 920, $result);
                $val['resize_image'] = str_replace('%w', 200, $result);
                $val['file_size'] = $fileSize;
                $val['file_type'] = 'image';
                $val['folder_id'] = $this->request->input('folder_id');

            } else {
                //for videos mp4
                if (!$this->model('user')->hasPermission('video')){
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('you-are-not-allow-video')
                    ));
                }
                $val['file_type'] = 'video';
                $val['file_name'] = $file;
                $val['file_size'] = $fileSize;
                $val['resize_image'] = '';
                $val['folder_id'] = $this->request->input('folder_id');
            }

            $id = $this->model('filemanager')->save($val);
            return json_encode(array(
                'status' => 1,
                'message'=> l('upload-successful'),
                'content' => view('filemanager/display', array('file' => model('filemanager')->find($id)))
            ));
        }

        if ($onedrive = $this->request->input('onedrive')) {

        }

        $offset = $this->request->input('offset', 0);

        $files = $this->model('filemanager')->getFiles($offset);
        if ($paginate = $this->request->input('paginate')) {
            $content = '';
            foreach($files as $file) {
                $content .= view('filemanager/display', array('file' => $file));
            }
            return json_encode(array(
                'offset' => $offset + 40,
                'content' => $content
            ));
        }
        return $this->render($this->view('filemanager/index', array('files' => $files)), true);

    }

    public function onedriveCallback() {
        return $this->render("");
    }

    public function imageEditor() {
        $id = $this->request->input('id');
        $file = $this->model('filemanager')->find($id);
        if ($image = $this->request->inputFile('picture')) {
            $image['name'] = 'editedimage.jpg';
            $upload = new Uploader($image, 'image');
            $upload->setPath("files/images/".model('user')->authOwnerId.'/'.time().'/');
            $val = array();
            if ($upload->passed()) {
                $result = $upload->resize()->result();
                $val['file_name'] = str_replace('%w', 920, $result);
                $val['resize_image'] = str_replace('%w', 200, $result);
                $val['file_size'] = filesize(path($val['file_name']));
                $val['file_type'] = 'image';

                $id = $this->model('filemanager')->save($val);
                return json_encode(array(
                    'type' => 'success',
                    'message'=> l('image-edit-successful'),
                ));
            } else {
                return json_encode(array(
                    'type' => 'error',
                    'message' => $upload->getError()
                ));
            }
        }
        return $this->view('filemanager/editor', array('file' => $file));
    }

    public function openFolder() {
        $id = $this->request->input('id');
        $from = $this->request->input('from');
        $offset = $this->request->input('offset', 0);

        if ($val = $this->request->input('val')) {
            if ($val['action'] == 'sort') {
                $i = 0;
                foreach($val['filesi'] as $file) {
                    Database::getInstance()->query("UPDATE files SET sort_number=? WHERE id=?", $i, $file);
                    $i++;
                }
                return json_encode(array(
                    'type' => 'function',
                    'value' => 'confirmFileSort',

                ));
            } else {
                if(isset($val['files']) and !empty($val['files'])) {
                    foreach($val['files'] as $id) {
                        $this->model('filemanager')->delete($id);
                    }
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'confirmFileDelete',
                        'message' => l('files-deleted-successfully'),
                        'content' => implode(',',$val['files'])
                    ));
                }
            }
        }

        $files = $this->model('filemanager')->getFiles($offset, $id);
        if ($paginate = $this->request->input('paginate')) {
            $content = '';
            foreach($files as $file) {
                $content .= view('filemanager/display', array('file' => $file));
            }
            return json_encode(array(
                'offset' => $offset + 40,
                'content' => $content
            ));
        }

        return $this->view('filemanager/open-folder', array('files' => $files, 'from' => $from, 'id' => $id));

    }

}