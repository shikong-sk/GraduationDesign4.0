<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

class MajorClass
{

    var $db;

    var $studentTable;

    var $teacherTable;
    var $departmentTable;

    var $classTable;
    var $gradeTable;
    var $majorTable;


    var $model = Array(
        'departmentId' => null,
        'departmentName' => null,
        'majorId' => null,
        'majorName' => null,
        'active' => null,
    );

    var $filter = Array(
        'page' => null,
        'num' => null,
    );

    public function __construct()
    {
        $this->db = new SqlHelper();

        $this->studentTable = $this->db->db_table_prefix . "_" . SqlHelper::STUDENT;

        $this->teacherTable = $this->db->db_table_prefix . "_" . SqlHelper::TEACHER;
        $this->departmentTable = $this->db->db_table_prefix . "_" . SqlHelper::DEPARTMENT;

        $this->classTable = $this->db->db_table_prefix . "_" . SqlHelper::CLASSES;
        $this->majorTable = $this->db->db_table_prefix . "_" . SqlHelper::MAJOR;
        $this->gradeTable = $this->db->db_table_prefix . "_" . SqlHelper::GRADE;
    }

    public function getMajorList($data, $filter)
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
                    return json_encode(Array("error" => "JSON filter 解析失败"), JSON_UNESCAPED_UNICODE);
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

        $query = $this->db->selectQuery('*', $this->majorTable);

        if (isset($data['departmentName'])) {
            $departmentName_Like = $data['departmentName'];
            unset($data['departmentName']);
            $query->andLikeQuery('departmentName', "%{$departmentName_Like}%");
        }
        if (isset($data['majorName'])) {
            $majorName_Like = $data['majorName'];
            unset($data['majorName']);
            $query->andLikeQuery('majorName', "%{$majorName_Like}%");
        }

        $query->andQueryList($data);


        return $query->orderBy('departmentId,majorId', 1)->selectLimit($page, $num)->getFetchAssocNumJson();

    }

    public function addMajor($data)
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            return json_encode(Array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE);
        } else if ($_SESSION['ms_identity'] != 'Teacher') {
            return json_encode(Array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('permission', $this->teacherTable)->andQueryList(Array('teacherId' => $_SESSION['ms_id'], 'permission' => '0'))->getSelectNum() == 0) {
            return json_encode(Array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        }

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

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }

        if(isset($data['departmentName']))
        {
            unset($this->model['departmentName']);
        }


        foreach ($this->model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(Array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            } else {
                switch ($k) {
                    case 'active':
                        if ($data[$k] != '0' && $data[$k] != '1') {
                            return json_encode(Array('error' => 'active 参数错误'));
                        }
                        break;
                    case 'departmentId':
                        if (strlen($data[$k]) != 2) {
                            return json_encode(Array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'));
                        } else if ($this->db->selectQuery('*', $this->departmentTable)->andQuery('departmentId', $data[$k])->getSelectNum() == 0) {
                            return json_encode(Array('error' => '此院系不存在'));
                        }
                        else{
                            $data['departmentName'] = $this->db->selectQuery('departmentName',$this->departmentTable)->andQuery('departmentId',$data['departmentId'])->getFetchAssoc()[0]['departmentName'];
                        }
                        break;
                    case 'majorId':
                        if (strlen($data[$k]) != 2) {
                            return json_encode(Array('error' => 'majorId 参数错误,majorId 参数需要2个字符 例：01'));
                        } else if ($this->db->selectQuery('*', $this->majorTable)->andQuery('majorId', $data[$k])->getSelectNum() != 0) {
                            return json_encode(Array('error' => '此编号已被使用'));
                        }
                        break;
                    case 'majorName':
                        if (strlen($data[$k]) == 0) {
                            return json_encode(Array('error' => '专业名称不能为空'));
                        }
                        break;
                }
            }
        }


        if ($this->db->insertQuery($this->majorTable, $data)->insertExecute()->getAffectedRows() == 1) {
            return json_encode(Array('success' => '专业添加成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => '院系添加失败,请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function updateDepartment($data)
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            return json_encode(Array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE);
        } else if ($_SESSION['ms_identity'] != 'Teacher') {
            return json_encode(Array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('permission', $this->teacherTable)->andQueryList(Array('teacherId' => $_SESSION['ms_id'], 'permission' => '0'))->getSelectNum() == 0) {
            return json_encode(Array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        }

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

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }

        if(!isset($data['departmentId']))
        {
            return json_encode(Array('error'=>'departmentId 为必填参数'),JSON_UNESCAPED_UNICODE);
        }

        foreach ($this->model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(Array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            } else {
                switch ($k) {
                    case 'active':
                        if ($data[$k] != '0' && $data[$k] != '1') {
                            return json_encode(Array('error' => 'active 参数错误'));
                        }
                        break;
                    case 'departmentId':
                        if (strlen($data[$k]) != 2) {
                            return json_encode(Array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'));
                        } else if ($this->db->selectQuery('*', $this->departmentTable)->andQuery('departmentId', $data[$k])->getSelectNum() == 0) {
                            return json_encode(Array('error' => '该院系不存在'));
                        }
                        break;
                    case 'departmentName':
                        if (strlen($data[$k]) == 0) {
                            return json_encode(Array('error' => '院系名称不能为空'));
                        }
                        break;
                }
            }
        }

        $departmentId = $data['departmentId'];
        unset($data["departmentId"]);

        if($this->db->updateQuery($this->departmentTable,$data)->andQuery('departmentId',$departmentId)->updateLimit(1)->updateExecute()->getAffectedRows() == 1)
        {
            $data = $this->db->selectQuery('departmentId,departmentName',$this->departmentTable)->andQuery('departmentId',$departmentId)->getFetchAssoc()[0];

            $this->db->updateQuery($this->gradeTable,$data)->andQuery('departmentId',$departmentId)->updateExecute();
            $this->db->updateQuery($this->majorTable,$data)->andQuery('departmentId',$departmentId)->updateExecute();
            $this->db->updateQuery($this->classTable,$data)->andQuery('departmentId',$departmentId)->updateExecute();
            $this->db->updateQuery($this->studentTable,$data)->andQuery('departmentId',$departmentId)->updateExecute();
            $this->db->updateQuery($this->teacherTable,$data)->andQuery('departmentId',$departmentId)->updateExecute();

            return json_encode(Array('success'=>'院系信息更新成功'),JSON_UNESCAPED_UNICODE);
        }
        else{
            return json_encode(Array('info'=>'院系信息未更改'),JSON_UNESCAPED_UNICODE);
        }

    }

    public function deleteDepartment($data)
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            return json_encode(Array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE);
        } else if ($_SESSION['ms_identity'] != 'Teacher') {
            return json_encode(Array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('permission', $this->teacherTable)->andQueryList(Array('teacherId' => $_SESSION['ms_id'], 'permission' => '0'))->getSelectNum() == 0) {
            return json_encode(Array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        }

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

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }

        if(!isset($data['departmentId']))
        {
            return json_encode(Array('error'=>'departmentId 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else{
            if($this->db->selectQuery('*',$this->studentTable)->andQuery('departmentId',$data['departmentId'])->getSelectNum() != 0)
            {
                return json_encode(Array('error'=>'该院系已有学生，请先修改/删除其相应的学生信息后再进行此操作'),JSON_UNESCAPED_UNICODE);
            }
            else if($this->db->selectQuery('*',$this->teacherTable)->andQuery('departmentId',$data['departmentId'])->getSelectNum() != 0)
            {
                return json_encode(Array('error'=>'该院系已有教工，请先修改/删除其相应的教工信息后再进行此操作'),JSON_UNESCAPED_UNICODE);
            }
            else if($this->db->selectQuery('*',$this->classTable)->andQuery('departmentId',$data['departmentId'])->getSelectNum() != 0)
            {
                return json_encode(Array('error'=>'该院系已有班级，请先删除其对应的信息后再进行此操作'),JSON_UNESCAPED_UNICODE);
            }
            else if($this->db->selectQuery('*',$this->gradeTable)->andQuery('departmentId',$data['departmentId'])->getSelectNum() != 0)
            {
                return json_encode(Array('error'=>'该院系已有年级，请先删除其对应的信息后再进行此操作'),JSON_UNESCAPED_UNICODE);
            }
            else if($this->db->selectQuery('*',$this->majorTable)->andQuery('departmentId',$data['departmentId'])->getSelectNum() != 0)
            {
                return json_encode(Array('error'=>'该院系已有专业，请先删除其对应的信息后再进行此操作'),JSON_UNESCAPED_UNICODE);
            }
            else{
                if($this->db->deleteQuery($this->departmentTable)->andQuery('departmentId',$data['departmentId'])->deleteExecute()->getAffectedRows() == 1)
                {
                    return json_encode(Array('success'=>'院系删除成功'),JSON_UNESCAPED_UNICODE);
                }
                else{
                    return json_encode(Array('error'=>'院系删除失败'),JSON_UNESCAPED_UNICODE);
                }
            }
        }
    }
}