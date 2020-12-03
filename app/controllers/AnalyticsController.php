<?php
class AnalyticsController extends Controller {

    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveIconMenu('reports');
        $this->setActiveMenu('accounts');
    }

    public function index() {
        $accountId = $this->request->segment(1);
        $account = $this->model('account')->find($accountId);
        if ($account['social_type'] != 'instagram') return $this->request->redirect(url('accounts/instagram'));

        if (is_ajax() and $load = $this->request->input('load')) {
            $analytics = $this->model('analytics')->find($accountId);
            if (!$analytics) {
                $proxy = $this->model('proxy')->findOneProxy(null, $account);

                $instagramApi = $this->api('instagram')->init($account['username'], mDcrypt($account['password']), $proxy['proxy']);

                $analyticsDetails = $instagramApi->analytics();
                $this->model('analytics')->save($accountId, $analyticsDetails);
            }

            $analyticsDetails = $this->model('analytics')->getStats($accountId);
            return $this->view('analytics/details', array('details' => $analyticsDetails));
        }
        return $this->render($this->view('analytics/index'), true);
    }
}