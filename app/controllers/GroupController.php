<?php
class GroupController extends Controller {

    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveMenu('accounts');
        $this->appSideLayout = '';
        $this->setTitle(l('groups-manager'));
        $this->setActiveIconMenu('groups');
    }

    public function index() {

        if($action = $this->request->input('action')) {
            $group = (is_numeric($action)) ? $this->model('group')->find($action) : array();
            return $this->view('groups/group', array('group' => $group, 'action' => $action));
        }

        if ($val = $this->request->input('val')) {
            if (isset($val['action'])) {
                $groupId = (is_numeric($val['action'])) ? $val['action'] : '';
                $accounts = (isset($val['accounts'])) ? $val['accounts'] : array();
                if (count($accounts) < 1) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('please-select-an-account')
                    ));
                }
                $this->model('group')->save($val, $groupId);

                return json_encode(array(
                    'type' => 'modal-url',
                    'message' => l('group-save-success'),
                    'content' => '',
                    'value' => url('groups')
                ));
            }
        }

        if ($load = $this->request->input('load')) {
            $group = $this->model('group')->find($load);
            $selectAccoounts = perfectUnserialize($group['accounts']);
            $accounts = array();
            foreach($selectAccoounts as $account) {
                $account = $this->model('account')->find($account);
                if ($account) $accounts[] = $account;
            }
            return $this->view('groups/list', array('accounts' => $accounts));
        }

        if (isset($_GET['load'])) {
            $accounts = $this->model('account')->listAccounts('all');
            return $this->view('accounts/lists', array('accounts' => $accounts));
        }

        if ($delete = $this->request->input('delete')) {
            $this->model('group')->delete($delete);
            return json_encode(array(
                'type' => 'url',
                'message'=> l('group-delete-success'),
                'value' => url('groups')
            ));
        }
        return $this->render($this->view('groups/index'), true);
    }
}