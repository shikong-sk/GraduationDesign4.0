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
        $this->teacherTable = $this->db->db_table_prefix."_".SqlHelper::TEACHER;

        $this->classTable = $this->db->db_table_prefix . "_" . SqlHelper::CLASSES;

        $this->departmentTable = $this->db->db_table_prefix . "_" . SqlHelper::DEPARTMENT;
        $this->majorTable = $this->db->db_table_prefix . "_" . SqlHelper::MAJOR;
        $this->gradeTable = $this->db->db_table_prefix . "_" . SqlHelper::GRADE;

        $this->studentTable = $this->db->db_table_prefix . "_" . SqlHelper::STUDENT;
    }

    private function checkAccess(){
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
            ->andQueryList(array("teacherId"=>$_SESSION["ms_id"],"teacherName"=>$_SESSION["ms_user"]))->selectLimit(1,1)->getFetchAssoc()[0]["permission"];
    }

    public function addStudent($data)
    {

        $this->checkAccess();

        $permission = $this->getPermission();
        if($permission != '0' && $permission != '1')
        {
            return json_encode(Array("error"=>"您没有执行此操作的权限"),JSON_UNESCAPED_UNICODE);
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


        unset($model["contact"]);
        unset($model["active"]);
        unset($model["gender"]);
        unset($model["both"]);
        unset($model["salt"]);
        unset($model["password"]);
        unset($model["address"]);
        unset($model["studentImg"]);
        unset($model["active"]);

        foreach($model as $k =>$v )
        {
            if (!isset($data[$k])) {
                return json_encode(Array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            }
        }

        if($this->db->selectQuery('departmentId',$this->departmentTable)->andQueryList(Array("departmentId"=>$data["departmentId"]))->getSelectNum() == 0)
        {
            return json_encode(Array("error"=>"该院系不存在"),JSON_UNESCAPED_UNICODE);
        }
    }

}