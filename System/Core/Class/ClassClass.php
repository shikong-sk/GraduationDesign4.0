<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

class ClassClass
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
        'years'=>null,
        "class"=>null,
        "classId"=>null,
        'studentNum' => null,
    );

    var $filter = Array(
        'page' => null,
        'num' => null,
    );

    public function __construct()
    {
        $this->db = new SqlHelper();

        $this->teacherTable = $this->db->db_table_prefix."_".SqlHelper::TEACHER;

        $this->classTable = $this->db->db_table_prefix . "_" . SqlHelper::CLASSES;

        $this->departmentTable = $this->db->db_table_prefix . "_" . SqlHelper::DEPARTMENT;
        $this->majorTable = $this->db->db_table_prefix . "_" . SqlHelper::MAJOR;
        $this->gradeTable = $this->db->db_table_prefix . "_" . SqlHelper::GRADE;

        $this->studentTable = $this->db->db_table_prefix . "_" . SqlHelper::STUDENT;
    }

    public function getClassList($data, $filter)
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

        $query = $this->db->selectQuery('*', $this->classTable);

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
        if (isset($data['classId'])) {
            $majorName_Like = $data['classId'];
            unset($data['classId']);
            $query->andLikeQuery('classId', "%{$majorName_Like}%");
        }

        $query->andQueryList($data);


        return $query->orderBy('grade,departmentId,majorId,years', 1)->selectLimit($page, $num)->getFetchAssocNumJson();

    }

    public function addClass($data)
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

        $model = $this->model;
        unset($model['departmentName']);
        unset($model["majorName"]);
        unset($model["studentNum"]);


        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(Array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            } else {
                switch ($k) {
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
                        else if($this->db->selectQuery('*',$this->gradeTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data['majorId'],'grade'=>$data[$k]))->getSelectNum() == 0){
                            return json_encode(Array('error'=>"{$data['grade']} 级 未开设 {$data['departmentName']}-{$data['majorName']}"),JSON_UNESCAPED_UNICODE);
                        }
                        else if(
                            intval($this->db->selectQuery('classNum',$this->gradeTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data['majorId'],'grade'=>$data[$k]))->getFetchAssoc()[0]['classNum'])
                            <
                            intval($this->db->selectQuery('*',$this->classTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data['majorId'],'grade'=>$data[$k]))->getSelectNum()+1)
                        ){
                            return json_encode(Array('error'=>"{$data['grade']} 级 {$data['departmentName']}-{$data['majorName']} 所开设班级数量已达到所设置上限"),JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case "years":
                        if(strlen($data[$k]) != 1 || intval($data[$k]) > 9)
                        {
                            return json_encode(Array('error' => 'years 参数错误,years 参数需要1个字符 例：3'),JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case "class":
                        if(strlen($data[$k]) != 1 || intval($data[$k]) > 9)
                        {
                            return json_encode(Array('error' => 'class 参数错误,class 参数需要1个字符 例：1'),JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case "classId":
                        if(strlen($data[$k]) != 8)
                        {
                            return json_encode(Array('error' => 'classId 参数错误,classId 参数需要8个字符 例：17305021'),JSON_UNESCAPED_UNICODE);
                        }
                        else if($data[$k] != $data["grade"].$data["years"].$data["departmentId"].$data["majorId"].$data["class"]){
                            return json_encode(Array('error' => 'classId 编码格式错误, 例：[年级编号][学制][院系编号][专业编号][班级序号]'),JSON_UNESCAPED_UNICODE);
                        }
                        else if($this->db->selectQuery("*",$this->classTable)->andQueryList(Array("classId"=>$data[$k],"departmentId"=>$data["departmentId"],"majorId"=>$data["majorId"],"grade"=>$data["grade"]))->getSelectNum() != 0){
                            return json_encode(Array('error' => "{$data['grade']} 级 {$data['departmentName']}-{$data['majorName']} {$data["class"]}班 已存在"),JSON_UNESCAPED_UNICODE);
                        }
                }
            }
        }

        $data["studentNum"] = 0;


        if ($this->db->insertQuery($this->classTable, $data)->insertExecute()->getAffectedRows() == 1) {
            return json_encode(Array('success' => "{$data['grade']} 级 {$data['departmentName']}-{$data['majorName']} {$data["class"]}班 添加成功"), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => '班级添加失败,请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function deleteClass($data)
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
            return json_encode(Array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'),JSON_UNESCAPED_UNICODE);
        }
        else if(!isset($data['majorId']))
        {
            return json_encode(Array('error'=>'majorId 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data['majorId']) != 2){
            return json_encode(Array('error' => 'majorId 参数错误,majorId 参数需要2个字符 例：01'));
        }
        else if(!isset($data['class']))
        {
            return json_encode(Array('error'=>'class 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data['class']) != 1){
            return json_encode(Array('error' => 'class 参数错误,class 参数需要1个字符 例：1'));
        }
        else if(!isset($data['years']))
        {
            return json_encode(Array('error'=>'years 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data['years']) != 1){
            return json_encode(Array('error' => 'years 参数错误,years 参数需要1个字符 例：3'),JSON_UNESCAPED_UNICODE);
        }
        else if(!isset($data['grade']))
        {
            return json_encode(Array('error'=>'grade 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data['grade']) != 2){
            return json_encode(Array('error' => 'grade 参数错误,grade 参数需要2个字符 例：01'),JSON_UNESCAPED_UNICODE);
        }
        else if(!isset($data['classId']))
        {
            return json_encode(Array('error'=>'classId 为必填参数'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data['classId']) != 8){
            return json_encode(Array('error' => 'classId 参数错误,classId 参数需要8个字符 例：17305021'),JSON_UNESCAPED_UNICODE);
        }
        else if($data["classId"] != $data["grade"].$data["years"].$data["departmentId"].$data["majorId"].$data["class"]){
            return json_encode(Array('error' => 'classId 编码格式错误, 例：[年级编号][学制][院系编号][专业编号][班级序号]'),JSON_UNESCAPED_UNICODE);
        }
        else{
            if($this->db->selectQuery('*',$this->studentTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data["majorId"],'grade'=>$data["grade"],"years"=>$data["years"],"class"=>$data['class'],"classId"=>$data['classId']))->getSelectNum() != 0)
            {
                return json_encode(Array('error'=>'该班级已有学生，请先修改/删除其相应的学生信息后再进行此操作'),JSON_UNESCAPED_UNICODE);
            }
            else{
                if($this->db->deleteQuery($this->classTable)->andQueryList(Array('departmentId'=>$data['departmentId'],'majorId'=>$data["majorId"],'grade'=>$data["grade"],"years"=>$data["years"],"class"=>$data['class'],"classId"=>$data['classId']))->deleteExecute()->getAffectedRows() == 1)
                {
                    return json_encode(Array('success'=>'班级删除成功'),JSON_UNESCAPED_UNICODE);
                }
                else{
                    return json_encode(Array('error'=>'班级删除失败'),JSON_UNESCAPED_UNICODE);
                }
            }
        }
    }
}