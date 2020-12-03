<?php
class ProfileController extends Controller {
    public function __construct($request)
    {
        parent::__construct($request);
    }

    public function index() {
        $this->setTitle(l('my-profile'))->setActiveIconMenu('profile');

        $user = $this->model('user')->getUser($this->model('user')->authId);

        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if ($val['action'] == 'profile') {
                $validator = Validator::getInstance()->scan($val, array(
                    'full_name' => 'required',
                    'email' => 'required',
                    'timezone' => 'required',
                ));
                if ($validator->passes()) {


                    if ($val['email'] != model('user')->userData('email')) {
                        if (model('user')->emailExists($val['email'])) {
                            return json_encode(array(
                                'message' => l('new-email-already-exits'),
                                'type' => 'error'
                            ));
                        }
                    }

                    $this->model('user')->saveProfile($val);
                    return json_encode(array(
                        'message' => l('profile-save-successful'),
                        'type' => 'url',
                        'value' => url('profile')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }

            }

            if ($val['action'] == 'password') {

                if (md5($val['currentpassword']) !== $user['password']) {
                    return json_encode(array(
                        'message' => l('password-does-not-found'),
                        'type' => 'error'
                    ));
                }
                if ($val['password'] != $val['confirm']) {
                    return json_encode(array(
                        'message' => l('password-does-not-match'),
                        'type' => 'error'
                    ));
                }
                $this->model('user')->savePassword($val);
                return json_encode(array(
                    'message' => l('password-changed-success'),
                    'type' => 'url',
                    'value' => url('profile')
                ));
            }

            if ($val['action'] == 'watermark') {
                $dImage = model('user')->getSettings('watermark-image');
                if ($image = $this->request->inputFile('image')) {
                    $uploader = new Uploader($image, 'image');
                    $uploader->setPath('watermark/'.model('user')->authOwnerId.'/');

                    if ($uploader->passed()) {
                        $dImage = $uploader->uploadFile()->result();
                    } else {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => $uploader->getError()
                        ));
                    }

                }
                $values = array(
                    'watermark-image' => $dImage,
                    'watermark-size' => $val['size'],
                    'watermark-position' => $val['position'],
                    'watermark-opacity' => $val['opacity']
                );
                model('user')->saveSettings($values);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('profile'),
                    'message' => l('watermark-settings-save')
                ));
            }


        }

        if ($action = $this->request->input('action')) {
            if ($action == 'set-color') {
                Database::getInstance()->query("UPDATE users SET default_color=? WHERE id=?", $this->request->input('id'), $this->model('user')->authUser['id']);
                return json_encode(array(
                    'type' => 'normal-url',
                    'value' => url('profile'),
                    'message' => l('color-scheme-selected')
                ));
            }
        }

        return $this->render($this->view('profile/index', array('user' => $user)), true);
    }


    public function deleteAccount() {

        if(moduleExists('saas')) {
            $subscriptions = $this->model('saas::saas')->getSubscription();

            if (config('demo', false) and $this->model('user')->authId == 66) return $this->request->redirect(url('profile'));
            if ($subscriptions['method'] == 'stripe') {
                try {
                    require_once path('module/saas/stripe/init.php');
                    \Stripe\Stripe::setApiKey(config("stripe-secret-key", ""));
                    $subscription = \Stripe\Subscription::retrieve(
                        $subscriptions['subscription_id']
                    );
                    $subscription->delete();
                    Database::getInstance()->query("DELETE FROM subscriptions WHERE id=?", $subscriptions['subscription_id']);
                } catch (Exception $e){}
            } elseif($subscriptions['method'] == 'paypal') {
                require_once path('module/saas/paypal/autoload.php');
                $paypal = new \PayPal\Rest\ApiContext(
                    new \PayPal\Auth\OAuthTokenCredential(
                        config('paypal-client-id'),
                        config('paypal-client-secret')
                    )
                );
                if(!config('paypal-sandbox', false)) $paypal->setConfig(array('mode' => 'live'));
                $agreement = new \PayPal\Api\Agreement();

                $agreement->setId($subscriptions['subscription_id']);
                $agreementStateDescriptor = new \PayPal\Api\AgreementStateDescriptor();
                $agreementStateDescriptor->setNote("Cancel the agreement");

                try {
                    $agreement->cancel($agreementStateDescriptor, $paypal);
                } catch (Exception $ex) {
                    print_r($ex);
                    exit;
                }
            }
        }
        $this->model('user')->deleteUser($this->model('user')->authId);

        $this->model('user')->logoutUser();
        return $this->request->redirect(url(''));
    }


    public function removeWatermark() {
        $values = array(
            'watermark-image' => '',
        );
        model('user')->saveSettings($values);
        return json_encode(array(
            'type' => 'url',
            'value' => url('profile'),
            'message' => l('watermark-remove-success')
        ));
    }

    public function team() {
        $this->setTitle(l('manage-team'));
        $this->setActiveIconMenu('manage-team');

        if ($val = $this->request->input('val')) {
            if ($val['action'] == 'add') {
                $allowUsers = model('user')->permission('manage-team-user', '-1');
                if ($allowUsers != '-1' and model('user')->countTeamMember() >= $allowUsers) {
                    return json_encode(
                        array(
                            'type' => 'error',
                            'message' => l('team-member-limit-reach')
                        )
                    );
                }
                if ($this->model('user')->saveTeam($val)) {
                    return json_encode(array(
                        'type' => 'modal-url',
                        'content' => '#newMemberModal',
                        'value' => url('manage/team'),
                        'message' => l('team-invited-success')
                    ));
                } else  {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('team-invite-failed')
                    ));
                }
            }elseif($val['action'] == 'edit') {
                $this->model('user')->saveTeam($val, $val['id']);
                return json_encode(array(
                    'type' => 'modal-url',
                    'content' => '#editMemberModal'.$val['id'],
                    'value' => url('manage/team'),
                    'message' => l('team-saved-success')
                ));
            }

        }

        if ($action = $this->request->input('action')) {
            $id = $this->request->input('id');
            if ($action == 'resend') {
                model('user')->sendInviteCode($id);
                return json_encode(array(
                    'type' => 'function',
                    'message' => l('team-invite-send-again')
                ));
            } elseif($action == 'delete') {
                model('user')->deleteTeam($id);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('manage/team'),
                    'message' => l('team-member-deleted')
                ));
            }
        }
        return $this->render($this->view('team/index', array('users' => model('user')->getTeamMembers())), true);
    }

    public function activateInvite() {
        $this->setfrontend();
        $this->setTitle(l('accept-invitation'));

        $code = $this->request->segment(2);
        $team = model('user')->findTeamByCode($code);
        if (!$team) return $this->request->redirect(url());
        $user = model('user')->findUserByEmail($team['email']);
        if ($user) {
            //we activate the invite and auto select this owner and take to post page
            $this->model('user')->activateTeamMember($team, $user);
            $this->model('user')->loginWithObject($user);
            return $this->request->redirect(url('post'));
        }
        //show the form for user to create password e.t.c
        if ($val = $this->request->input('val')) {
            if ($val['password'] != $val['cpassword']) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('password-not-match')
                ));
            }

            $userid = $this->model('user')->addUser(array(
                'password' => $val['password'],
                'full_name' => $team['name'],
                'email' => $team['email'],
                'timezone' => $val['timezone']
            ), false, true);
            $user = $this->model('user')->getUser($userid);

            $this->model('user')->activateTeamMember($team, $user);
            $this->model('user')->loginWithObject($user);
            return json_encode(array(
                'type' => 'normal-url',
                'value' => url('post'),
            ));
        }
        return $this->render(view('team/activate'));
    }

    public function switchAccount() {
        $this->setfrontend();
        $this->setTitle(l('switch-account'));
        if (!model('user')->isTeamMember()) return $this->request->redirect(url('post'));
        if ($id = $this->request->input('id')) {
            $this->model('user')->setOwnerId($id, $this->model('user')->authId);
            return $this->request->redirect(url('post'));
        }
        return $this->render($this->view('team/switch'));
    }
}