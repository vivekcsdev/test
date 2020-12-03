<?php
class HelpModel extends  Model {
    public function lists($term = '') {
        $sql = "SELECT * FROM tutorials WHERE id!=? ";
        $param = array('');

        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (name LIKE ? ) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 100);
    }

    public function getTutorials($term = '', $offset = 0) {
        $sql = "SELECT * FROM tutorials WHERE id!=? ";
        $param = array('');

        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (name LIKE ? ) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC LIMIT 10 OFFSET $offset";
        return $this->db->query($sql, $param)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($val) {
        $ext = array(
            'name' => '',
            'content' => '',
            'image' => '',
            'video' => '',
            'description' => ''
        );

        /**
         * @var $name
         * @var $video
         * @var $content
         * @var $image
         * @var $description
         */
        extract(array_merge($ext, $val));

        $slug = toAscii($name);
        return $this->db->query("INSERT INTO tutorials (name,slug,content,changed,created,image,video,description) VALUES(?,?,?,?,?,?,?,?)", $name, $slug, $content, time(), time(), $image,$video,$description);
    }

    public function save($val, $id) {
        $ext = array(
            'name' => '',
            'video' => '',
            'content' => '',
            'image' => '',
            'description' => ''
        );

        /**
         * @var $name
         * @var $video
         * @var $content
         * @var $image
         * @var $description
         */
        extract(array_merge($ext, $val));
        return $this->db->query("UPDATE tutorials SET name=?, content=?,changed=?,image=?,video=?,description=? WHERE id=?", $name,  $content, time(),$image,$video,$description, $id);
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM tutorials WHERE id=? OR slug=?", $id, $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        return $this->db->query("DELETE FROM tutorials WHERE id=?", $id);
    }


}