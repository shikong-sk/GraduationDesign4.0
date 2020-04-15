<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

class ManagementClass
{
    var $db;

    var $studentTable;
    var $teacherTable;

    var $departmentTable;
    var $classTable;
    var $gradeTable;
    var $majorTable;

    var $studentModel = array(
        'studentId' => null,
        'studentName' => null,
        'gender' => null,
        'both' => null,
        'salt' => null,
        'password' => null,
        'contact' => null,
        'grade' => null,
        'years' => null,
        'departmentId' => null,
        'departmentName' => null,
        'majorId' => null,
        'majorName' => null,
        'classId' => null,
        'class' => null,
        'seat' => null,
        'active' => null,
        'idCard' => null,
        'address' => null,
        'studentImg' => null,
    );

    var $teacherModel = array(
        'teacherId' => null,
        'teacherName' => null,
        'gender' => null,
        'salt' => null,
        'password' => null,
        'contact' => null,
        'departmentId' => null,
        'departmentName' => null,
        'active' => null,
        'idCard' => null,
        'address' => null,
        'teacherImg' => null,
    );

    var $filter = array(
        'page' => null,
        'num' => null,
    );


    public function __construct()
    {
        $this->db = new SqlHelper();
        $this->teacherTable = $this->db->db_table_prefix . "_" . SqlHelper::TEACHER;

        $this->classTable = $this->db->db_table_prefix . "_" . SqlHelper::CLASSES;

        $this->departmentTable = $this->db->db_table_prefix . "_" . SqlHelper::DEPARTMENT;
        $this->majorTable = $this->db->db_table_prefix . "_" . SqlHelper::MAJOR;
        $this->gradeTable = $this->db->db_table_prefix . "_" . SqlHelper::GRADE;

        $this->studentTable = $this->db->db_table_prefix . "_" . SqlHelper::STUDENT;
    }

    private function checkAccess()
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            die(json_encode(array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE));
        } else if ($_SESSION["ms_identity"] != "Teacher") {
            die(json_encode(array('error' => "您无权访问此接口"), JSON_UNESCAPED_UNICODE));
        }
    }

    public function getPermission()
    {
        $this->checkAccess();
        return $this->db->selectQuery('permission', $this->teacherTable)
            ->andQueryList(array("teacherId" => $_SESSION["ms_id"], "teacherName" => $_SESSION["ms_user"]))->selectLimit(1, 1)->getFetchAssoc()[0]["permission"];
    }

    public function addStudent($data)
    {

        $this->checkAccess();

        $permission = $this->getPermission();
        if ($permission != '0' && $permission != '1') {
            return json_encode(array("error" => "您没有执行此操作的权限"), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($data) == 0) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            $data = json_decode($data, true);
            if (!$data) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->studentModel)) {
                unset($data[$k]);
            }
        }

        $model = $this->studentModel;


        unset($model["departmentName"]);
        unset($model["majorName"]);

        unset($model["contact"]);
        unset($model["active"]);
        unset($model["gender"]);
        unset($model["both"]);
        unset($model["salt"]);
        unset($model["password"]);
        unset($model["address"]);
        unset($model["studentImg"]);
        unset($model["active"]);

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            }
        }

        if (strlen($data['departmentId']) != 2) {
            return json_encode(array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'),JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('departmentId', $this->departmentTable)->andQueryList(array("departmentId" => $data["departmentId"]))->getSelectNum() == 0) {
            return json_encode(array("error" => "该院系不存在"), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data['majorId']) != 2) {
            return json_encode(array('error' => 'majorId 参数错误,majorId 参数需要2个字符 例：01'),JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('*', $this->majorTable)->andQueryList(array("departmentId" => $data["departmentId"], "majorId" => $data["majorId"]))->getSelectNum() == 0) {
            return json_encode(array("error" => "该专业不存在"), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data["grade"]) != 2) {
            return json_encode(array('error' => 'grade 参数错误,grade 参数需要2个字符 例：01'),JSON_UNESCAPED_UNICODE);
        }
        else if($this->db->selectQuery('*',$this->gradeTable)->andQueryList(array("departmentId" => $data["departmentId"], "majorId" => $data["majorId"],"grade"=>$data['grade']))->getSelectNum() == 0)
        {
            return json_encode(array('error' => '该年级未开设此专业'));
        }
        else if(strlen($data["years"]) != 1 || intval($data["years"]) > 9)
        {
            return json_encode(array('error' => 'years 参数错误,years 参数需要1个字符 例：3'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data["class"]) != 1 || intval($data["class"]) > 9)
        {
            return json_encode(array('error' => 'class 参数错误,class 参数需要1个字符 例：1'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data["classId"]) != 8)
        {
            return json_encode(array('error' => 'classId 参数错误,classId 参数需要8个字符 例：17305021'),JSON_UNESCAPED_UNICODE);
        }
        else if($data["classId"] != $data["grade"].$data["years"].$data["departmentId"].$data["majorId"].$data["class"])
        {
            return json_encode(Array('error' => 'classId 编码格式错误, 例：[年级编号][学制][院系编号][专业编号][班级序号]'),JSON_UNESCAPED_UNICODE);
        }
        else if($this->db->selectQuery("*",$this->classTable)->andQueryList(Array("classId"=>$data["classId"],"departmentId"=>$data["departmentId"],"majorId"=>$data["majorId"],"grade"=>$data["grade"]))->getSelectNum() == 0){
            return json_encode(Array('error' => "该班级不存在"),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data["seat"]) != 2 || intval($data["seat"]) > 99){
            return json_encode(array('error' => 'seat 参数错误,seat 参数需要2个字符 例：01'),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data["studentId"]) != 10)
        {
            return json_encode(array('error' => 'studentId 参数错误,studentId 参数需要10个字符 例：1730502127'),JSON_UNESCAPED_UNICODE);
        }
        else if($data["studentId"] != $data["grade"].$data["years"].$data["departmentId"].$data["majorId"].$data["class"].$data["seat"])
        {
            return json_encode(Array('error' => 'studentId 编码格式错误, 例：[年级编号][学制][院系编号][专业编号][班级序号][座位号]'),JSON_UNESCAPED_UNICODE);
        }
        else if($this->db->selectQuery("*",$this->studentTable)->andQueryList(Array("studentId"=>$data["studentId"]))->getSelectNum() != 0){
            return json_encode(Array('error' => "此学号已被使用"),JSON_UNESCAPED_UNICODE);
        }
        else if(strlen($data["idCard"]) != 18 && strlen($data["idCard"]) != 15)
        {
            return json_encode(Array('error' => "身份证格式错误"),JSON_UNESCAPED_UNICODE);
        }
        else {
            $data["departmentName"] = $this->db->selectQuery('departmentName', $this->departmentTable)->andQueryList(array("departmentId" => $data["departmentId"]))->getFetchAssoc()[0]["departmentName"];
            $data["majorName"] = $this->db->selectQuery('majorName', $this->majorTable)->andQueryList(array("departmentId" => $data["departmentId"],"majorId"=>$data["majorId"]))->getFetchAssoc()[0]["majorName"];
        }

        $data["active"] = 0;

        if($this->db->insertQuery($this->studentTable,$data)->insertExecute()->getAffectedRows() == 1){

            $studentNum = $this->db->selectQuery('*',$this->studentTable)->andQueryList(Array("classId"=>$data["classId"]))->getSelectNum();

            $this->db->updateQuery($this->classTable,Array("studentNum"=>$studentNum))->andQueryList(Array("classId"=>$data["classId"]))->updateLimit(1)->updateExecute();

            return json_encode(Array('success' => '学生添加成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => '学生添加失败,请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function deleteStudent($data)
    {
        $this->checkAccess();
        $permission = $this->getPermission();
        if ($permission != '0' && $permission != '1') {
            return json_encode(array("error" => "您没有执行此操作的权限"), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($data) == 0) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            $data = json_decode($data, true);
            if (!$data) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->studentModel)) {
                unset($data[$k]);
            }
        }

        $model = Array(
            "studentId"=>null
        );

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            }
        }

        if(strlen($data["studentId"]) != 10)
        {
            return json_encode(array('error' => 'studentId 参数错误,studentId 参数需要10个字符 例：1730502127'),JSON_UNESCAPED_UNICODE);
        }
        else if($this->db->selectQuery("*",$this->studentTable)->andQueryList(Array("studentId"=>$data["studentId"]))->getSelectNum() == 0){
            return json_encode(Array('error' => "该学生不存在"),JSON_UNESCAPED_UNICODE);
        }

        $data["classId"] = substr($data["studentId"],0,8);

        if($this->db->deleteQuery($this->studentTable)->andQueryList(Array("studentId"=>$data["studentId"]))->deleteLimit(1)->deleteExecute()->getAffectedRows() == 1)
        {
            $studentNum = $this->db->selectQuery('*',$this->studentTable)->andQueryList(Array("classId"=>$data["classId"]))->getSelectNum();

            $this->db->updateQuery($this->classTable,Array("studentNum"=>$studentNum))->andQueryList(Array("classId"=>$data["classId"]))->updateLimit(1)->updateExecute();

            return json_encode(Array('success' => "学生信息删除成功"),JSON_UNESCAPED_UNICODE);
        }
        else{
            return json_encode(Array('error' => "学生信息删除失败"),JSON_UNESCAPED_UNICODE);
        }

    }

}