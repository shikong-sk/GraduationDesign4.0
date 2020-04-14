<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

class GradeClass
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
        'grade'=>null,
        'classNum' => null,
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

    public function getGradeList($data, $filter)
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

        $query = $this->db->selectQuery('*', $this->gradeTable);

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


        return $query->orderBy('grade,departmentId,majorId', 1)->selectLimit($page, $num)->getFetchAssocNumJson();

    }

    public function addGrade($data)
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


        unset($this->model['departmentName']);
        unset($this->model['majorName']);


        foreach ($this->model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(Array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            } else {
                switch ($k) {
                    case 'classNum':
                        if (strlen($data[$k]) != 1 || intval($data[$k]) > 9 ) {
                            return json_encode(Array('error' => 'classNum 参数错误'),JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case 'departmentId':
                        if (strlen($data[$k]) != 2) {
                            return json_encode(Array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'),JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery('*', $this->departmentTable)->andQuery('departmentId', $data[$k])->getSelectNum() == 0) {
                            return json_encode(Array('error' => '此院系不存在'),JSON_UNESCAPED_UNICODE);
                        }
                        else{
                            $data['departmentName'] = $this->db->selectQuery('departmentName',$this->departmentTable)->andQuery('departmentId',$data['departmentId'])->getFetchAssoc()[0]['departmentName'];
                        }
                        break;
                    case 'majorId':
                        if (strlen($data[$k]) != 2) {
                            return json_encode(Array('error' => 'majorId 参数错误,majorId 参数需要2个字符 例：01'),JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery('*', $this->majorTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data[$k]))->getSelectNum() == 0) {
                            return json_encode(Array('error' => '此专业不存在'),JSON_UNESCAPED_UNICODE);
                        }
                        else{
                            $data['majorName'] = $this->db->selectQuery('majorName',$this->majorTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data['majorId']))->getFetchAssoc()[0]['majorName'];
                        }
                        break;
                    case 'grade':
                        if(strlen($data[$k]) != 2)
                        {
                            return json_encode(Array('error' => 'grade 参数错误,grade 参数需要2个字符 例：01'),JSON_UNESCAPED_UNICODE);
                        }
                        else if($this->db->selectQuery('*',$this->gradeTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data['majorId'],'grade'=>$data[$k]))->getSelectNum() != 0){
                            return json_encode(Array('error'=>"{$data['grade']} 级 {$data['departmentName']}-{$data['majorName']}已经开设"),JSON_UNESCAPED_UNICODE);
                        }
                        break;
                }
            }
        }


        if ($this->db->insertQuery($this->gradeTable, $data)->insertExecute()->getAffectedRows() == 1) {
            return json_encode(Array('success' => "{$data['grade']} 级 {$data['departmentName']}-{$data['majorName']} 开设成功"), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => "{$data['grade']} 级 {$data['departmentName']}-{$data['majorName']} 开设失败,请检查参数是否正确"), JSON_UNESCAPED_UNICODE);
        }

    }

    public function updateGrade($data)
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
        else if(!isset($data['majorId']))
        {
            return json_encode(Array('error'=>'majorId 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else if(!isset($data['grade']))
        {
            return json_encode(Array('error'=>'grade 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else if(!isset($data['classNum']))
        {
            return json_encode(Array('error'=>'classNum 为必填参数'),JSON_UNESCAPED_UNICODE);
        }

        $model = $this->model;

        unset($model['departmentName']);
        unset($model['majorName']);

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(Array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            } else {
                switch ($k) {
                    case 'classNum':
                        if (strlen($data[$k]) != 1 || intval($data[$k]) > 9 ) {
                            return json_encode(Array('error' => 'classNum 参数错误'),JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case 'departmentId':
                        if (strlen($data[$k]) != 2) {
                            return json_encode(Array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'));
                        } else if ($this->db->selectQuery('*', $this->departmentTable)->andQuery('departmentId', $data[$k])->getSelectNum() == 0) {
                            return json_encode(Array('error' => '该院系不存在'));
                        }
                        break;
                    case 'majorId':
                        if (strlen($data[$k]) != 2) {
                            return json_encode(Array('error' => 'majorId 参数错误,majorId 参数需要2个字符 例：01'));
                        } else if ($this->db->selectQuery('*', $this->majorTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data[$k]))->getSelectNum() == 0) {
                            return json_encode(Array('error' => '该专业不存在'));
                        }
                        break;
                    case 'grade':
                        if(strlen($data[$k]) != 2)
                        {
                            return json_encode(Array('error' => 'grade 参数错误,grade 参数需要2个字符 例：01'));
                        }
                        else if ($this->db->selectQuery('*', $this->gradeTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data['majorId'],'grade'=>$data[$k]))->getSelectNum() == 0) {
                            return json_encode(Array('error' => '该年级未开设此专业'));
                        }
                        break;
                }
            }
        }

        $departmentId = $data['departmentId'];
        unset($data["departmentId"]);
        $majorId = $data['majorId'];
        unset($data["majorId"]);
        $grade = $data["grade"];
        unset($data["grade"]);

        if($this->db->selectQuery('*',$this->classTable)->andQueryList(Array('departmentId'=>$departmentId,'majorId'=>$majorId,'grade'=>$grade))->getSelectNum() > intval($data["classNum"]))
        {
            return json_encode(Array('error'=>'原班级数量大于所设定的班级数量'),JSON_UNESCAPED_UNICODE);
        }
        else if($this->db->updateQuery($this->gradeTable,$data)->andQueryList(Array('departmentId'=>$departmentId,'majorId'=>$majorId,'grade'=>$grade))->updateLimit(1)->updateExecute()->getAffectedRows() == 1)
        {
            return json_encode(Array('success'=>'年级信息更新成功'),JSON_UNESCAPED_UNICODE);
        }
        else{
            return json_encode(Array('info'=>'年级信息未变更'),JSON_UNESCAPED_UNICODE);
        }

    }

    public function deleteGrade($data)
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
        else if(strlen($data['departmentId']) != 2){
            return json_encode(Array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'));
        }
        else if(!isset($data['majorId']))
        {
            return json_encode(Array('error'=>'majorId 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data['majorId']) != 2){
            return json_encode(Array('error' => 'majorId 参数错误,majorId 参数需要2个字符 例：01'));
        }
        else if(!isset($data['grade']))
        {
            return json_encode(Array('error'=>'grade 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data['grade']) != 2){
            return json_encode(Array('error' => 'grade 参数错误,grade 参数需要2个字符 例：01'));
        }
        else{
            if($this->db->selectQuery('*',$this->studentTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data["majorId"],"grade"=>$data["grade"]))->getSelectNum() != 0)
            {
                return json_encode(Array('error'=>'该专业已有学生，请先修改/删除其相应的学生信息后再进行此操作'),JSON_UNESCAPED_UNICODE);
            }
            else if($this->db->selectQuery('*',$this->classTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data["majorId"],"grade"=>$data["grade"]))->getSelectNum() != 0)
            {
                return json_encode(Array('error'=>'该专业已有班级，请先删除其对应的信息后再进行此操作'),JSON_UNESCAPED_UNICODE);
            }
            else{
                if($this->db->deleteQuery($this->gradeTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data["majorId"],"grade"=>$data["grade"]))->deleteExecute()->getAffectedRows() == 1)
                {
                    return json_encode(Array('success'=>'专业撤销成功'),JSON_UNESCAPED_UNICODE);
                }
                else{
                    return json_encode(Array('error'=>'专业撤销失败'),JSON_UNESCAPED_UNICODE);
                }
            }
        }
    }
}