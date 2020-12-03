<?php
class ReportController extends Controller {

    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveMenu('reports');
        $this->setActiveIconMenu('reports');
        $this->subMenuIcon = 'la la-chart-bar';
    }

    public function index() {
        $type = $this->request->segment(1, 'general');


        switch ($type) {
            case 'general':
                $title = l('overall-report');
                $stats = array(
                    'total-accounts' => $this->model('account')->countAll(),
                    'total-posts' => $this->model('post')->countTotalPosts('all', 'all'),
                    'total-post-success' => $this->model('post')->countTotalPosts('all', '1'),
                    'total-post-failed' => $this->model('post')->countTotalPosts('all', '3'),
                    'completed-posts' => $this->model('post')->countTotalSuccessMonthly()
                );

                if ($this->model('user')->isAdmin()) {
                   $stats['total-users'] = $this->model('admin')->countStatistics('all');
                    $stats['total-users-today'] = $this->model('admin')->countByTime('today');
                    if (date('w', time()) == 1) {
                        $time = 'today';
                    } else {
                        $time = 'last monday';
                    }
                    $stats['total-users-this-week'] = $this->model('admin')->countByTime($time);
                    $stats['total-users-this-month'] = $this->model('admin')->countByTime('first day of '.date( 'F Y'));
                    $stats['total-users-this-year'] = $this->model('admin')->countByTime('first day of january '.date( 'Y'));
                    $stats['total-active-users'] = $this->model('admin')->countStatistics(1);
                    $stats['total-inactive-users'] = $this->model('admin')->countStatistics('0');
                    $stats['total-monthly-users'] = $this->model('admin')->monthlyStatistics();
                }
                break;
            default:
                $title = ucwords($type).' '.l('reports');
                $stats = array(
                    'post-success' => $this->model('post')->countTotalPosts($type, '1'),
                    'post-failed' => $this->model('post')->countTotalPosts($type, 3),
                    'post-queue' => $this->model('post')->countTotalPosts($type, 2),
                    'post-type-media' => $this->model('post')->countTotalTypes($type, 'media'),
                    'post-type-link' => $this->model('post')->countTotalTypes($type, 'link'),
                    'post-type-text' => $this->model('post')->countTotalTypes($type, 'text'),
                    'success-monthy-data' => $this->model('post')->countTotalSuccessMonthly($type)
                );
                break;
        }
        return $this->render($this->view('reports/index', array('stats' => $stats, 'title' => $title, 'type' => $type)), true);
    }
}