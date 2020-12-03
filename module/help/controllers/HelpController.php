<?php
class HelpController extends Controller {
    public function index() {

        $offset = $this->request->input('offset', 0);
        $tutorials = model('help::help')->getTutorials($this->request->input('term'), $offset);
        if ($paginate = $this->request->input('paginate')) {
            $content = '';
            foreach($tutorials as $tutorial) {
                $content .= view('help::display', array('tutorial' => $tutorial));
            }
            return json_encode(array(
                'offset' => $offset + 10,
                'content' => $content
            ));
        }
        return $this->view('help::load', array('tutorials' => $tutorials));
    }

    public function content() {
        $id = $this->request->input('id');
        $url = $this->request->input('url');
        $tutorial = $this->model('help::help')->find($id);

        return $this->view('help::content', array('tutorial' => $tutorial, 'url' => $url));
    }
}