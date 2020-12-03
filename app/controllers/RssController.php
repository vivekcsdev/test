<?php
class RssController extends Controller {
    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveIconMenu('rss');
    }

    public function index() {
        if(!config('enable-rss', true) or !model('user')->hasPermission('rss')) return $this->request->redirect(url('post'));
        if ($val = $this->request->input('val')) {
            if (isset($val['create'])) {
                autoLoadVendor();
                $rssUrl = $val['url'];
                try {
                    $result = Feed::loadRss($rssUrl);

                    $rss = $this->model('rss')->add($result, $rssUrl);

                    if ($rss) {
                        $i = 0;
                        foreach($result->item as $item) {
                            $this->model('rss')->addPost($item, $rss);
                            if ($i >=5) break;
                            $i++;
                        }
                        return json_encode(array(
                            'type' => 'url',
                            'value' => url('rss/'.$rss),
                            'message' => l('rss-added-success')
                        ));
                    } else {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('rss-feed-already-exit')
                        ));
                    }
                } catch (Exception $e) {

                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('error-process-rss-url')
                    ));
                }
            }
        }

        if ($delete = $this->request->input('delete')) {
            $this->model('rss')->delete($delete);
            return json_encode(array(
                'type' => 'url',
                'value' => url('rss'),
                'message' => l('rss-delete-success')
            ));
        }

        $rss = $this->model('rss')->lists();
        return $this->render($this->view('rss/index', array('lists' => $rss)), true);
    }

    public function page() {
        if(!config('enable-rss', true) or !model('user')->hasPermission('rss')) return $this->request->redirect(url('post'));

        $id = $this->request->segment(1);
        $rss = $this->model('rss')->find($id);

        if ($account = $this->request->input('account')) {
            $accounts = $this->request->input('accounts');
            $this->model('rss')->updateAccounts($id, $accounts);
            return json_encode(array(
                'type' => 'function',
                'message' => l('rss-accounts-updated')
            ));
        }

        if ($val = $this->request->input('val')) {
            if (isset($val['settings'])) {
                $this->model('rss')->saveSettings($val, $id);
                return json_encode(array(
                    'type' => 'function',
                    'message' => l('rss-settings-saved')
                ));
            }

            if (isset($val['bulk_action'])) {
                $ids = $val['id'];
                foreach($ids as $postId) {
                    $this->model('rss')->sharePost($id, $postId);
                }
                return json_encode(array(
                    'message' => l('bulk-action-successful'),
                    'type' => 'function',
                ));
            }
        }

        if ($respost = $this->request->input('repost')) {
            $this->model('rss')->sharePost($id, $respost);
            return json_encode(array(
                'type' => 'function',
                'message' => l('rss-post-shared')
            ));
        }
        return $this->render($this->view('rss/page', array('rss' => $rss)), true);
    }
}