<?php

require_once(dirname(__FILE__) .'/../Interface/SqlMethod.php');

require_once(dirname(__FILE__) .'/../SqlHelper.php');

require_once(dirname(__FILE__) .'/FileClass.php');

class StudentClass{

    var $db;
    var $studentTable;

    var $model = Array(
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
        'active' =>null,
        'idCard' => null,
        'address' => null,
        'studentImg' => null,
    );

    var $filter = Array(
        'page' => null,
        'num' => null,
    );

    var $studentId;
    var $studentName;

    public function __construct()
    {
        $this->db = new SqlHelper();
        if(isset($_SESSION['ms_user']))
        {
            $this->studentId = $_SESSION['ms_id'];
            $this->studentName = $_SESSION['ms_user'];
        }
        $this->studentTable = $this->db->db_table_prefix."_".SqlHelper::STUDENT;
    }

    public function test($data,$filter=""){
        if (!is_array($data)) {
            if(!is_string($data)){
                die(json_encode(Array("error"=>"JSON data 解析失败"),JSON_UNESCAPED_UNICODE));
            }

            if(strlen($data) == 0)
            {
                die(json_encode(Array("error"=>"JSON data 解析失败"),JSON_UNESCAPED_UNICODE));
            }

            $data = json_decode($data, true);
            if(!$data){
                die(json_encode(Array("error"=>"JSON data 解析失败"),JSON_UNESCAPED_UNICODE));
            }
        }

        if (!is_array($filter)) {
            if(!is_string($filter)){
                die(json_encode(Array("error"=>"JSON filter 解析失败"),JSON_UNESCAPED_UNICODE));
            }

            if(strlen($filter) != 0)
            {
                $filter = json_decode($filter, true);
                if(!$filter){
                    die(json_encode(Array("error"=>"JSON filter 解析失败"),JSON_UNESCAPED_UNICODE));
                }
            }
        }

        foreach ($data as $k=>$v)
        {
            if(!array_key_exists($k,$this->model))
            {
                unset($data[$k]);
            }
        }

        if(is_array($filter))
        {
            foreach ($filter as $k=>$v)
            {
                if(!array_key_exists($k,$this->filter))
                {
                    unset($filter[$k]);
                }
            }
        }
    }

    public function register($data,$filter=""){
        if (!is_array($data)) {
            if(!is_string($data)){
                return json_encode(Array("error"=>"JSON data 解析失败"),JSON_UNESCAPED_UNICODE);
            }

            if(strlen($data) == 0)
            {
                return json_encode(Array("error"=>"JSON data 解析失败"),JSON_UNESCAPED_UNICODE);
            }

            $data = json_decode($data, true);
            if(!$data){
                return json_encode(Array("error"=>"JSON data 解析失败"),JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k=>$v)
        {
            if(!array_key_exists($k,$this->model))
            {
                unset($data[$k]);
            }
        }

        if( isset($data['studentId']) && isset($data['studentName']) && isset($data['gender']) && isset($data['both']) && isset($data['password']) &&
            isset($data['grade']) && isset($data['years']) && isset($data['departmentId']) && isset($data['majorId']) && isset($data['classId']) && isset($data['class'])
            && isset($data['seat']) && isset($data['idCard']) )
        {

            if($data['classId'] != $data['grade'].$data['years'].$data['departmentId'].$data['majorId'].$data['class'])
            {
                die(json_encode(Array('error'=>'班级 与相关信息不匹配')));
            }

            if($data['studentId'] != $data['classId'].$data['seat'])
            {
                die(json_encode(Array('error'=>'学号 与相关信息不匹配')));
            }

            $password = $data['password'];
            unset($data['password']);

            $query = $this->db->selectQuery('*',$this->studentTable);
//            foreach ($data as $k =>$v)
//            {
//                $query->andQuery($k,$v);
//            }

            $query->andQueryList($data);

            var_dump($query->selectLimit(1,1)->getSelectNum());

            $salt = ''; // 随机加密密钥
            while (strlen($salt) < 6) {
                $x = mt_rand(0, 9);
                $salt .= $x;
            }
            $data['salt'] = $salt;
            $data['password'] = sha1($password . $salt); // sha1哈希加密

            echo $this->db->insertQuery($this->studentTable,$data)->getQuery();
        }
        else{
            die(json_encode(Array('error'=>'学生信息填写不完整')));
        }

    }

}