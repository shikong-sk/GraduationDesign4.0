<?php

/*
 * 数据库操作类
 */

//require_once($_SERVER['DOCUMENT_ROOT'] .'/System/Core/Core.php');
//require_once(realpath('./'). '/../../Core.php');


require_once(dirname(__FILE__).'/../Core.php');

require_once(dirname(__FILE__) .'/../../Config/db.config.php');

require_once(dirname(__FILE__) . '/../Class/FileClass.php');

class SqlHelper
{

    /*
     * 数据库参数
     */
    private $db_ip; // 数据库 IP
    private $db_port; // 数据库端口
    private $db_user; // 数据库用户名
    private $db_password; // 数据库密码
    private $db_name; // 数据库名
    public $db_table_prefix; // 数据库表前缀

    /*
     * 数据库表定义
     */

    const STUDENT = "student"; // 学生信息表
    const TEACHER = "teacher"; //教工信息表
    const DEPARTMENT = "department"; // 院系信息表
    const MAJOR = "major"; // 专业信息表
    const GRADE = "grade"; // 年级信息表
    const CLASSES = "class"; // 班级信息表
    const COURSE = "course"; // 课程信息表
    const SCORE = "score"; // 分数登记表

    var $database;

    var $query;

    public function __construct()
    {

        global $db_ip;
        global $db_port;
        global $db_user;
        global $db_password;
        global $db_name;
        global $db_table_prefix;

        $this->db_ip = $db_ip;
        $this->db_port = $db_port;
        $this->db_user = $db_user;
        $this->db_password = $db_password;
        $this->db_name = $db_name;
        $this->db_table_prefix = $db_table_prefix;

        $this->database = new mysqli();

        $this->database->connect($this->db_ip . ':' . $this->db_port, $this->db_user, $this->db_password);

        if ($this->database->connect_error) {

            die(json_encode(Array('error' => '数据库连接失败,请检查数据库配置文件 ./Core/System/Config/db.config.php 配置是否有误'), JSON_UNESCAPED_UNICODE));
        }

        $this->database->query('set names utf8');
        $this->database->query('use ' . $this->db_name);
    }

    public function __destruct()
    {
        $this->database->close();
    }

    public function getQuery(){
        return $this->query;
    }

    public function selectQuery($field,$table){
        $this->query = "SELECT {$field} FROM {$table} WHERE 1=1";
        return $this;
    }

    public function selectDistinctQuery($field,$table){
        $this->query = "SELECT DISTINCT {$field} FROM {$table} WHERE 1=1";
        return $this;
    }

    public function andQuery($field,$data){
        $this->query .= " AND `{$field}` = '" . $this->database->real_escape_string($data) ."'";
        return $this;
    }

    public function andQueryList(Array $data){
        foreach ($data as $k =>$v)
        {
            $this->andQuery($k,$v);
        }
        return $this;
    }

    public function andLikeQuery($field,$data){
        $this->query .= " AND `{$field}` LIKE '" . $this->database->real_escape_string($data) ."'";
        return $this;
    }

    public function andLeftTripQuery_And(){
        $this->query .= " AND ( 1=1";
        return $this;
    }

    public function andLeftTripQuery_Or(){
        $this->query .= " AND ( 1=0";
        return $this;
    }

    public function andRightTripQuery_And(){
        return $this->rightTripQuery();
    }

    public function andRightTripQuery_Or(){
        return $this->rightTripQuery();
    }

    public function andBetweenQuery($field,Array $data){
        $this->query .= " AND `{$field}` BETWEEN '" . $this->database->real_escape_string($data[0]) ."' AND '" . $this->database->real_escape_string($data[1]) ."'";
        return $this;
    }

    public function orQuery($field,$data){
        $this->query .= " OR `{$field}` = '" . $this->database->real_escape_string($data) ."'";
        return $this;
    }

    public function orQueryList(Array $data){
        foreach ($data as $k =>$v)
        {
            $this->orQuery($k,$v);
        }
        return $this;
    }

    public function orLikeQuery($field,$data){
        $this->query .= " OR `{$field}` LIKE '" . $this->database->real_escape_string($data) ."'";
        return $this;
    }

    public function orBetweenQuery($field,Array $data){
        $this->query .= " OR `{$field}` BETWEEN '" . $this->database->real_escape_string($data[0]) ."' AND '" . $this->database->real_escape_string($data[1]) ."'";
        return $this;
    }

    public function orLeftTripQuery_And(){
        $this->query .= " OR ( 1=1";
        return $this;
    }

    public function orLeftTripQuery_Or(){
        $this->query .= " OR ( 1=0";
        return $this;
    }

    public function orRightTripQuery_And(){
        return $this->rightTripQuery();
    }

    public function orRightTripQuery_Or(){
        return $this->rightTripQuery();
    }

    private  function rightTripQuery(){
        $this->query .= " )";
        return $this;
    }

    public function selectLimit($page,$num){
        $page = intval($page);
        $num = intval($num);
        $page = ($page - 1) * $num;
        $this->query .= " LIMIT $page,$num";
        return $this;
    }

    public function updateLimit($num){
        return $this->limit($num);
    }

    public function deleteLimit($num){
        return $this->limit($num);
    }

    private function limit($num){
        $num = intval($num);
        $this->query .= " LIMIT $num";
        return $this;
    }

    public function insertQuery($table,Array $data){
        $this->query = "INSERT INTO $table (";
        foreach ($data as $k=>$v)
        {
            $this->query .= "`$k`,";
        }

        $this->query = rtrim($this->query,',');
        $this->query .= ") VALUES (";

        foreach ($data as $k=>$v)
        {
            $this->query .= "'". $this->database->real_escape_string($v)."',";
        }

        $this->query = rtrim($this->query,',');
        $this->query .= ")";

        return $this;
    }

    public function updateQuery($table,Array $data){
        $this->query = "UPDATE $table SET";
        foreach ($data as $k=>$v)
        {
            $this->query .= " `$k` = '".$this->database->real_escape_string($v)."',";
        }

        $this->query = rtrim($this->query,',');

        $this->query .= " WHERE 1=1";

        return $this;
    }

    public function deleteQuery($table){
        $this->query = "DELETE FROM $table WHERE 1=1";

        return $this;
    }

    public function getSelectNum(){
        return intval($this->database->query(preg_replace('/SELECT (.*) FROM/','SELECT count(*) num FROM',$this->query,1))->fetch_assoc()['num']);
    }

    public function getFetchAssoc(){
        $res = $this->database->query($this->query);
        $resNum = 0;
        $json = Array();
        while ($res->data_seek($resNum)) {
            $data = $res->fetch_assoc();
            array_push($json, $data);
            $resNum++;
        }
        return $json;
    }

    public function getFetchAssocNumJson(){
        $num = $this->getSelectNum();
        $json = $this->getFetchAssoc();
        array_unshift($json,$num);
        return json_encode($json,JSON_UNESCAPED_UNICODE);
    }

    public function insertExecute(){
        return $this->execute();
    }

    public function deleteExecute(){
        return $this->execute();
    }

    public function updateExecute(){
        return $this->execute();
    }

    private function execute(){
        $this->database->query($this->query);
        return $this;
    }

    public function getAffectedRows(){
        return $this->database->affected_rows;
    }

    public function getError(){
        return $this->database->error;
    }

    public function getErrorNo(){
        return $this->database->errno;
    }

    public function getErrorList(){
        return $this->database->error_list;
    }

    public function getErrorMessage(){
        $err_data = $this->database->error_list;
        if(count($err_data) == 0)
        {
            return null;
        }
        $err_data = $err_data[0];
        $message = "{$err_data['errno']} - ";
        switch ($err_data['errno']){
            case 1062 : $message .= "该数据与已有数据重复";break;
            default : $message .= $err_data['error'];
        }
        return $message;
    }

    public function getErrorMessageJson(){
        if($this->getErrorMessage() == null)
        {
            return null;
        }
        return json_encode(Array('error'=>$this->getErrorMessage()),JSON_UNESCAPED_UNICODE);
    }
}