<?php
class InboxController extends Controller {

    public function index() {

        /**$account = $this->model('account')->find(57);
        $proxy = model('proxy')->findOneProxy($account['proxy'], $account);
        $instagramObj = $this->api('instagram')->init($account['username'], mDcrypt($account['password']), $proxy);

        foreach($instagramObj->instagramObj->direct->getInbox(null, 10)->getInbox()->getThreads() as $thread){
            print_r($thread);
        }
        exit;**/
        $this->setTitle(l('inbox'));
        $this->setActiveIconMenu('inbox');
        $this->subMenuIcon = 'las la-envelope';
        return $this->render($this->view('inbox::index'), true);
    }

    public function loadThreads() {
        $social = $this->request->input('social');
        $id = $this->request->input('id');
        $this->model('inbox::inbox')->ensureAccount($id);

        $offset = $this->request->input('offset', 10);
        $result = array(
            'threads' => array()
        );
        $result = Hook::getInstance()->fire('inbox.threads', $result, array($social, $id, $offset, $this->request->input('paginate', false)));

        if ($paginate = $this->request->input('paginate')) {
            $content = '';
            foreach($result['threads'] as $thread) {
                $content .= view('inbox::display/thread', array('thread' => $thread,'social' => $social, 'id' => $id));
            }
            return json_encode(array(
                'offset' => $offset + 10,
                'content' => $content
            ));
        }

        return $this->view('inbox::threads', array('threads' => $result['threads'], 'social' => $social, 'id' => $id));
    }

    public function loadChat() {
        $social = $this->request->input('social');
        $id = $this->request->input('id');
        $thread = $this->request->input('thread');

        $result = array('content' => '');
        $result = Hook::getInstance()->fire('inbox.chat', $result, array($thread, $social, $id, $this));
        return $result['content'];
    }

    public function sendChat()
    {
        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if ($file = $this->request->inputFile('file')) {

                if (!$this->model('user')->canUpload()) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('file-upload-usage-limit')
                    ));
                }
                if (isImage($file)) {
                    if (!$this->model('user')->hasPermission('photo')) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('you-are-not-allow-photo')
                        ));
                    }
                } else {
                    if (!$this->model('user')->hasPermission('video')) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('you-are-not-allow-video')
                        ));
                    }
                }
                $upload = new Uploader($file, (isImage($file)) ? 'image' : 'video');
                (isImage($file)) ? $upload->setPath("files/images/" . model('user')->authOwnerId . '/' . time() . '/') : $upload->setPath('files/videos/' . model('user')->authOwnerId . '/');
                if ($upload->passed()) {
                    if (isImage($file)) {
                        $result = $upload->resize()->result();
                        $val['file_name'] = str_replace('%w', 920, $result);
                        $val['file_type'] = 'image';

                    } else {
                        $val['file_type'] = 'video';
                        $val['file_name'] = $upload->uploadFile()->result();

                    }

                } else {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => $upload->getError()
                    ));
                }

            }

            $result = array('content' => '');
            $result = Hook::getInstance()->fire('send.chat', $result, array($val));
            return json_encode(array(
                'type' => 'function',
                'value' => 'render_send_chat',
                'content' => $result['content'],
            ));
        }
    }

    public function cron() {
        $query = $this->db->query("SELECT * FROM inboxes ORDER BY rand() LIMIT 5");
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            Hook::getInstance()->fire('inbox.cron', null, array($fetch));
        }
    }
}
