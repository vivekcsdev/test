<?php
class AdminController extends Controller {
    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->setSideLayout('admin/layout/menu');
        $this->setActiveSubMenu('automation');
        $this->subMenuIcon = 'la la-cog';
    }

    public function index() {
        $this->setTitle(l('manage-automations'));
        if ($val = $this->request->input("val", null, false)) {
            $this->defendDemo();

            $this->model('admin')->saveSettings($val);
            return json_encode(array(
                'type' => 'success',
                'message' => l('settings-saved')
            ));
        }
        return $this->render($this->view('automation::admin/index'), true);
    }
}