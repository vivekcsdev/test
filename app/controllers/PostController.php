<?php
class PostController extends Controller {
    public function accounts_index(){
        $this->setTitle(l('dashboard'));
        if($val = $this->request->input('val'))
            return $this->request->redirect(url('post/details'));
        else
            return $this->render($this->view("post/index"),true);
    }
    public function index() {
        $this->setTitle(l('dashboard'));
        $this->setActiveMenu('post')->setActiveIconMenu('post');

        Hook::getInstance()->fire('post.page');

        if ($load = $this->request->input('load')) {
            $accounts = $this->model('account')->listAccounts($load);
            return $this->view('accounts/lists', array('accounts' => $accounts));
        }

        if ($fetchLocation = $this->request->input('fetchlocation')) {
            $account = $this->model('account')->findByRandom( 'instagram');

            $result = array();
            if($account) {
                $proxy = $this->model('proxy')->findOneProxy($account['proxy'], $account);
                $instagramApi = $this->api('instagram')->init($account['username'], mDcrypt($account['password']), $proxy['proxy'], false, null, null);
                $data = $instagramApi->searchLocations($fetchLocation);
                if($data) $result = $data;
            }
            return $this->view('post/location', array('results' => $result));
        }

        if ($val = $this->request->input('val')) {
            if (empty($val['accounts'])) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('select_an_account_to_continue')
                ));
            }

            if ($this->model('user')->hasPermission('drafts')) {
                if ($val['is_draft'] or isset($val['draft_id'])) {

                    foreach($val['accounts'] as $account) {
                        $accountDetail = $this->model('account')->find($account);
                        $socialType = $accountDetail['social_type'];

                    }
                    if (isset($val['edit_post'])) {
                        $postId = $val['edit_post'];

                        $this->model('post')->savePost($val, $postId,true);
                    } else {
                        $postId = $this->model('post')->add($val, $val['accounts'], $socialType);
                    }

                    Database::getInstance()->query("UPDATE posts SET status=? WHERE id=?", 4, $postId);

                    $post = $this->model('post')->find($postId);
                    $this->model('post')->addToDraft($val, $postId);
                    return json_encode(array(
                        'type' => 'url',
                        'value' => url('drafts'),
                        'message' => l('draft-saved-success')
                    ));
                }
            }

            if ($val['post_type'] == 'media' or $val['post_type'] == 'photo'
                or $val['post_type'] == 'video' or
                $val['post_type'] == 'story' or
                $val['post_type'] == 'livestream' or $val['post_type'] == 'album' or $val['post_type']== 'igtv') {
                if (empty($val['media'])) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('please_select_a_media_file')
                    ));
                }
            }

            if ($val['post_type'] == 'igtv' and empty($val['title'])) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('title-field-required')
                ));
            }
            if ($val['post_type'] == 'link' and empty($val['link'])) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('please_enter_link')
                ));
            }
            foreach($val['types'] as $socialType) {
                if (!$this->model('user')->hasPermission($socialType.'-post')) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('you-dont-have-permission-to-postto', array('social' => ucwords($socialType)))
                    ));
                }
                if ($socialType == 'instagram' and ($val['post_type'] == 'text' or $val['post_type'] == 'link')) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('please-remove-instagram-accounts-cant-post-link-or-text')
                    ));
                }
            }

            if (isset($val['edit_post'])) {
                $postId = $val['edit_post'];
                $this->model('post')->savePost($val, $postId);
                $post = $this->model('post')->find($postId);
                if (!$val['schedule']) {
                    if ($this->api($post['social_type'])->post($post, $post['account'])) {
                        $success[] = $postId;
                        return json_encode(array(
                            'type' => 'function',
                            'message' => l('posted-successful'),
                            'value' => 'resetPosting'
                        ));
                    } else {
                        $this->model('post')->setUnpublished($postId);
                        $error[] = $postId;
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('post-failed-try-again'),
                        ));
                    }
                }
                return json_encode(array(
                    'type' => 'function',
                    'message' => l('post-saved-success'),
                    'value' => 'resetPosting'
                ));
            }


            if (!$val['schedule']) {
                $success = array();
                $error = array();

                $this->defendDemo();
                foreach($val['accounts'] as $account) {
                    $accountDetail = $this->model('account')->find($account);
                    $socialType = $accountDetail['social_type'];
                    $postId = $this->model('post')->add($val, $account, $socialType);
                    $post = $this->model('post')->find($postId);
                    if ($this->api($socialType)->post($post, $account)) {
                        $success[] = $postId;
                    } else {
                        $this->model('post')->setUnpublished($postId);
                        $error[] = $postId;
                    }
                }
                if (empty($error)) {
                    return json_encode(array(
                        'type' => 'function',
                        'message' => l('posted-on-all-accounts'),
                        'value' => 'resetPosting'
                    ));
                } else {
                    if(!empty($success)) {
                        return json_encode(array(
                            'type' => 'function',
                            'message' => l('posted-on-some-accounts', array('success' => count($success), 'error' => count($error))),
                            'value' => 'resetPosting'
                        ));
                    } else {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('post-failed-on-all-accounts'),
                        ));
                    }
                }
            } else {
                foreach($val['accounts'] as $account) {
                    $accountDetail = $this->model('account')->find($account);
                    $socialType = $accountDetail['social_type'];
                    $this->model('post')->add($val, $account, $socialType);
                }

                return json_encode(array(
                    'type' => 'function',
                    'message' => l('posted-scheduled-success'),
                    'value' => 'resetPosting'
                ));
            }

            return $this->request->redirect(url('post/engagement'));

        }
        return $this->render($this->view('post/compose'), true);
    }

    public function action() {
        $id = $this->request->input('id');
        $this->model('post')->deletePost($id);
        return json_encode(array(
            'type' => 'reload',
            'message' => l('posts-deleted-success')
        ));
    }

    public function drafts() {
        $this->setTitle(l('drafts'));
        $this->setActiveMenu('post')->setActiveIconMenu('drafts');
        if(!$this->model('user')->hasPermission('drafts')) return $this->request->redirect(url('post'));

        if ($val = $this->request->input('val')) {
            if ($val['action'] == 'add') {
                $this->model('post')->addToDraft($val, null);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('drafts'),
                    'message' => l('draft-created-success')
                ));
            } elseif ($val['action'] == 'edit') {
                $this->model('post')->saveDraft($val);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('drafts'),
                    'message' => l('draft-saved-success')
                ));
            } elseif ($val['action'] == 'invite') {
                $result = $this->model('post')->inviteForCollection($val);
                if ($result) {
                    return json_encode(array(
                        'type' => 'url',
                        'value' => url('drafts'),
                        'message' => l('user-invite-success')
                    ));
                } else {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('user-not-found-email')
                    ));
                }
            }
        }

        if ($action = $this->request->input('action')) {
            if ($action == 'delete') {
                $this->model('post')->deleteDraftCollection($this->request->input('id'));
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('drafts'),
                    'message' => l('draft-deleted-success')
                ));
            } elseif($action == 'delete-post') {
                $this->model('post')->deleteDraftPost($this->request->input('id'));
                return json_encode(array(
                    'type' => 'reload',
                    'message' => l('draft-post-deleted-success')
                ));
            } elseif($action == 'revoke') {
                $this->model('post')->revokeAccess($this->request->input('id'));

                return json_encode(array(
                    'type' => 'url',
                    'value' => url('drafts'),
                    'message' => l('user-revoke-success')
                ));
            }elseif($action == 'post') {
                $draftPost = $this->model('post')->findDraftPost($this->request->input('id'));
                $post = $this->model('post')->find($draftPost['post_id']);
                $accounts = explode(',', $post['account']);
                $success = array();
                $error =  array();
                $this->defendDemo();
                foreach($accounts as $account) {
                   $newPost = $this->model('post')->copyFromOldPost($post, $account);
                    if (!$post['is_scheduled']) {
                        if ($this->api($newPost['social_type'])->post($newPost, $account)) {
                            $success[] = $newPost['id'];
                            return json_encode(array(
                                'type' => 'function',
                                'message' => l('posted-successful'),
                                'value' => 'resetPosting'
                            ));
                        } else {
                            $this->model('post')->setUnpublished($newPost['id']);
                            $error[] = $newPost['id'];
                            return json_encode(array(
                                'type' => 'error',
                                'message' => l('post-failed-try-again'),
                            ));
                        }
                    }
                }

                //here we should delete the draft post now

                if ($post['is_scheduled']) {
                    $this->model('post')->deleteDraftPost($this->request->input('id')); //hahaha
                    return json_encode(array(
                        'type' => 'reload',
                        'message' => l('posted-scheduled-success'),
                        'value' => 'resetPosting'
                    ));
                } else {
                    if (empty($error)) {
                        $this->model('post')->deleteDraftPost($this->request->input('id')); //hahaha
                        return json_encode(array(
                            'type' => 'reload',
                            'message' => l('posted-on-all-accounts'),
                            'value' => 'resetPosting'
                        ));
                    } else {
                        if(!empty($success)) {
                            $this->model('post')->deleteDraftPost($this->request->input('id')); //hahaha
                            return json_encode(array(
                                'type' => 'reload',
                                'message' => l('posted-on-some-accounts', array('success' => count($success), 'error' => count($error))),
                                'value' => 'resetPosting'
                            ));
                        } else {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => l('post-failed-on-all-accounts'),
                            ));
                        }
                    }
                }
            }
        }

        $draftPosts = null;
        $draft = null;
        if ($draftId = $this->request->segment(1)) {

            $draft = $this->model('post')->findDraft($draftId);
            if (!$draft) return $this->request->redirect(url('drafts'));

            $offset = $this->request->input('offset', 0);
            $draftPosts = $this->model('post')->getDraftPosts($draftId, $offset);

            if ($paginate = $this->request->input('paginate')) {
                $content = '';
                foreach($draftPosts as $post) {
                    $content .= view('drafts/display', array('draftPost' => $post));
                }
                return json_encode(array(
                    'offset' => $offset + 10,
                    'content' => $content
                ));
            }
        }
        return $this->render(view('drafts/index', array('draftPosts' => $draftPosts,'draft' => $draft)), true);
    }

    public function fetchLink() {
        $link = $this->request->input('link');
        include_once path('app/vendor/autoload.php');
        try {
            $linkPreview = new \LinkPreview\LinkPreview($link);
            $parsed = $linkPreview->getParsed();
            foreach ($parsed as $parserName => $link) {
                if ($link->getImage() == '') {
                    $pictures = $link->getPictures();
                    foreach($pictures as $picture) {
                        if ($picture) {
                            list($width, $height) = getimagesize($picture);
                            if($width > 80) {
                                $link->setImage($picture);
                                break;
                            }
                        }

                    }
                }

                  return $this->view('post/link', array('link' => $link));
            }
        } catch (Exception $e){}
    }

    public function acceptCollection() {
        $code = $this->request->segment(3);
        $this->model('post')->acceptCollection($code);
        return $this->request->redirect(url('drafts'));
    }
    public function engagement(){
        if($val = $this->request->input('val'))
            return $this->request->redirect(url('post'));
        else
            return $this->render($this->view("post/engagement"),true);
    }
    public function details(){
        if($val = $this->request->input('val'))
            return $this->request->redirect(url('post/ad_format'));
        else
            return $this->render($this->view("post/details"),true);
    }
    public function confirm(){
        return $this->render($this->view("post/confirm"),true);
    }
}