<?php
class ProxyModel extends Model {
    public function getProxies($term = '') {
        $sql = "SELECT * FROM proxies WHERE id!=? ";
        $param = array('');

        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (address LIKE ?) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 10);
    }

    public function addProxy($val) {
        $ext = array(
            'location' => '',
            'address' => '',
            'limit' => ''
        );

        /**
         * @var $location
         * @var $address
         * @var $limit
         */
        extract(array_merge($ext, $val));

        $this->db->query("INSERT INTO proxies (address,location,usage_limit,active,status,changed,created)VALUES(?,?,?,?,?,?,?)",
            $address, $location, $limit, 1, 1, time(), time());

        $proxyId = $this->db->lastInsertId();
        Hook::getInstance()->fire('proxy.added', null, array($proxyId, $val));
        return true;
    }

    public function adminEditProxy($val, $proxyId) {
        $ext = array(
            'location' => '',
            'address' => '',
            'limit' => '',

        );

        /**
         * @var $location
         * @var $address
         * @var $limit
         */
        extract(array_merge($ext, $val));

        $this->db->query('UPDATE proxies SET address=?,location=?,usage_limit=?,changed=? WHERE id=?', $address,$location,$limit, time(), $proxyId);

        Hook::getInstance()->fire('proxy.edited', null, array($proxyId, $val));
        return true;
    }

    public function disableProxy($id) {
        return $this->db->query("UPDATE proxies SET status=? WHERE id=?", 0, $id);
    }

    public function enableProxy($id) {
        return $this->db->query("UPDATE proxies SET status=? WHERE id=?", 1, $id);
    }

    public function deleteProxy($id) {
        return $this->db->query("DELETE FROM proxies WHERE id=?", $id);
    }

    public function findOneProxy($proxy, $account) {
        $defaultProxy = $this->findLeastUsedProxy();

        if($defaultProxy) {
            $systemProxy = (empty($account)) ? $defaultProxy['id'] : $account['default_proxy'];
        } else {
            $systemProxy = $defaultProxy['id'];
        }

        if (!empty($account) and !empty($account['proxy'])) {
            return array(
                'proxy' => $proxy,
                'default' => $account['default_proxy']
            );
        }

        if (config('users-can-add-own-proxies', true) and $proxy) {
            return array(
                'proxy' => $proxy,
                'default' => $systemProxy
            );
        }

        if (config('enable-system-proxies', true) and $defaultProxy) {
            return array(
                'proxy' => $defaultProxy['address'],
                'default' => $defaultProxy['id']
            );
        }
        return array(
            'proxy' => "",
            'default' => ""
        );
    }

    public function countProxyUsed($proxyId) {
        $query = $this->db->query("SELECT * FROM accounts WHERE social_type=? AND default_proxy=?", 'instagram', $proxyId);
        return $query->rowCount();
    }

    public function findLeastUsedProxy() {
        $sql = "SELECT proxies.*,COUNT(accounts.default_proxy) as total FROM proxies LEFT JOIN accounts ON proxies.id=accounts.default_proxy GROUP BY proxies.id ORDER BY total ASC";
        $query = $this->db->query($sql);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $owner = model('user')->getOwner();
        $newResult = array();
        foreach($results as $result) {
            $canBe = true;
            if ($result['total'] >= $result['usage_limit']) {
                $canBe = false;
            }


            if (!moduleExists('saas')) {
                $packages = ($result['package']) ? perfectUnserialize($result['package']) : array();
                $packages[] = 1;
                if ($owner['package'] and $packages and !in_array($owner['package'], $packages)) {

                    foreach($packages as $package) {
                        if ($package == $owner['package']) {
                            $canBe = true;
                            break;
                        }
                    }
                }

            }

            if ($canBe) $newResult[] = $result;
        }


        if (count($newResult) > 0 ) {
            return $newResult[rand(0,count($newResult) - 1)];
        }
    }
}