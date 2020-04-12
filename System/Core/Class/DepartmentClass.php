<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

class DepartmentClass
{

    var $db;

    var $departmentTable;

    var $model = Array(
        'departmentId' => null,
        'departmentName' => null,
        'active' => null,
    );

    var $filter = Array(
        'page' => null,
        'num' => null,
    );

    public function __construct()
    {
        $this->db = new SqlHelper();
        $this->departmentTable = $this->db->db_table_prefix . "_" . SqlHelper::DEPARTMENT;
    }

    public function getDepartmentList($data, $filter)
    {
        if (!is_array($data)) {
            if (!is_string($data)) {
                die(json_encode(Array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            if (strlen($data) == 0) {
                $data = Array();
            }

            $data = json_decode($data, true);
            if (!$data) {
                return json_encode(Array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        if (!is_array($filter)) {
            if (!is_string($filter)) {
                return json_encode(Array("error" => "JSON filter 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($filter) != 0) {
                $filter = json_decode($filter, true);
                if (!$filter) {
                    die(json_encode(Array("error" => "JSON filter 解析失败"), JSON_UNESCAPED_UNICODE));
                }
            } else {
                return json_encode(Array('error' => '请传入查询参数'), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }

        foreach ($filter as $k => $v) {
            if (!array_key_exists($k, $this->filter)) {
                unset($filter[$k]);
            }
        }

        $page = 1;
        $num = 1;

        if (isset($filter['page'])) {
            $page = intval($filter['page']);

            if (isset($filter['num'])) {
                $num = intval($filter['num']);
            } else {
                $num = 10;
            }
        }

        if ($page == null || $num == null) {
            return json_encode(Array('error' => 'filter page 或 num 参数不合法'), JSON_UNESCAPED_UNICODE);
        }

        $query = $this->db->selectQuery('*', $this->departmentTable);

        if (isset($data['departmentName'])) {
            $departmentName_Like = $data['departmentName'];
            unset($data['departmentName']);
            $query->andLikeQuery('departmentName',"%{$departmentName_Like}%");
        }

        $query->andQueryList($data);


        return $query->orderBy('departmentId', 1)->selectLimit($page, $num)->getFetchAssocNumJson();

    }
}