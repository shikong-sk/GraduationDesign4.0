<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

//require_once(dirname(__FILE__) . '/FileClass.php');

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

    public function register($data){
        if(isset($_SESSION['ms_id']) || isset($_SESSION['ms_user']))
        {
            return json_encode(Array('error'=>'您已登录，无需进行此操作'),JSON_UNESCAPED_UNICODE);
        }

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
                return json_encode(Array('error'=>'班级 与相关信息不匹配'),JSON_UNESCAPED_UNICODE);
            }

            if($data['studentId'] != $data['classId'].$data['seat'])
            {
                return json_encode(Array('error'=>'学号 与相关信息不匹配'),JSON_UNESCAPED_UNICODE);
            }

            $password = $data['password'];
            unset($data['password']);

            $query = $this->db->selectQuery('*',$this->studentTable);

            $query->andQueryList($data);

            if($query->selectLimit(1,1)->getSelectNum() != 1)
            {
                return json_encode(Array('error'=>'此学生信息不存在 或 学生信息错误'),JSON_UNESCAPED_UNICODE);
            }

            $query = $this->db->selectQuery('*',$this->studentTable)->andQueryList($data)->andQuery('active','0');

            if($query->selectLimit(1,1)->getSelectNum() != 1)
            {
                return json_encode(Array('error'=>'此学生账号已激活'),JSON_UNESCAPED_UNICODE);
            }

            $salt = ''; // 随机加密密钥
            while (strlen($salt) < 6) {
                $x = mt_rand(0, 9);
                $salt .= $x;
            }
            $updateData['salt'] = $salt;
            $updateData['password'] = sha1($password . $salt); // sha1哈希加密
            $updateData['active'] = '1';

            if($this->db->updateQuery($this->studentTable,$updateData)->andQueryList($data)->updateExecute()->getAffectedRows() == 1)
            {
                return json_encode(Array('success'=>"{$data['studentId']} - {$data['studentName']} 账号激活成功"),JSON_UNESCAPED_UNICODE);
            }
            else{
                return json_encode(Array('success'=>"{$data['studentId']} - {$data['studentName']} 账号激活失败"),JSON_UNESCAPED_UNICODE);
            }
        }
        else{
            return json_encode(Array('error'=>'学生信息填写不完整'),JSON_UNESCAPED_UNICODE);
        }

    }

    public function login($data){
        if(isset($_SESSION['ms_id']) || isset($_SESSION['ms_user']))
        {
            return json_encode(Array('error'=>'您已登录，无需进行此操作'),JSON_UNESCAPED_UNICODE);
        }

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

        $model = Array(
            'studentId' => null,
            'password' => null
        );

        foreach ($data as $k=>$v)
        {
            if(!array_key_exists($k,$model))
            {
                unset($data[$k]);
            }
        }

        if(isset($data['studentId']) && isset($data['password']))
        {
            $salt = $this->db->selectQuery('salt',$this->studentTable)->selectLimit(1,1)->getFetchAssoc()[0]['salt'];

            $data['password'] = sha1($data['password'].$salt);

            $query = $this->db->selectQuery('studentId,studentName',$this->studentTable)->andQueryList($data)->selectLimit(1,1);
            if($query->getSelectNum() == 1)
            {
                $res = $query->getFetchAssoc()[0];
                $_SESSION['ms_id'] = $res['studentId'];
                $_SESSION['ms_user'] = $res['studentName'];
                $_SESSION['ms_identity'] = "Student";
                return json_encode(Array('success'=>'登录成功'),JSON_UNESCAPED_UNICODE);
            }
            else{
                return json_encode(Array('error'=>'账号不存在/未激活 或 密码 错误'),JSON_UNESCAPED_UNICODE);
            }
        }
        else{
            return json_encode(Array('error'=>'参数错误'),JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateInfo($data,$info){
        if(!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user']))
        {
            return json_encode(Array('error'=>'请登录后再进行此操作'),JSON_UNESCAPED_UNICODE);
        }

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

        if (!is_array($info)) {
            if(!is_string($info)){
                die(json_encode(Array("error"=>"JSON info 解析失败"),JSON_UNESCAPED_UNICODE));
            }

            if(strlen($info) == 0)
            {
                die(json_encode(Array("error"=>"JSON info 解析失败"),JSON_UNESCAPED_UNICODE));
            }

            $info = json_decode($info, true);
            if(!$info){
                die(json_encode(Array("error"=>"JSON info 解析失败"),JSON_UNESCAPED_UNICODE));
            }
        }

        $dataModel = Array(
            'studentId' => null,
        );
        $infoModel = $this->model;

        foreach ($data as $k=>$v)
        {
            if(!array_key_exists($k,$dataModel))
            {
                unset($data[$k]);
            }
            else
            {
                unset($info[$k]);
            }
        }

        if($data['studentId'] != $_SESSION['ms_id'])
        {
            return json_encode(Array('error'=>'您无权修改他人的信息'),JSON_UNESCAPED_UNICODE);
        }
        else{
            foreach ($info as $k=>$v)
            {
                if(!array_key_exists($k,$infoModel))
                {
                    unset($info[$k]);
                }
            }

            unset($info['salt']);
            unset($info['grade']);
            unset($info['years']);
            unset($info['departmentId']);
            unset($info['departmentName']);
            unset($info['majorId']);
            unset($info['majorName']);
            unset($info['classId']);
            unset($info['class']);
            unset($info['seat']);
            unset($info['active']);


            $query = $this->db->selectQuery('studentImg',$this->studentTable)->andQueryList($data)->andQuery('active','1')->selectLimit(1,1);

            if($query->getSelectNum() != 1)
            {
                return json_encode(Array('error'=>'此学生不存在 或 账号未激活'),JSON_UNESCAPED_UNICODE);
            }

            $oImg = "";
            $fileManger = new FileClass();

            if(isset($info['studentImg'])){
                $oImg = $query->getFetchAssoc()[0]['studentImg'];

                $info["studentImg"] = $fileManger->uploadUserImage($info["studentImg"]);
                if($info["studentImg"] == null)
                {
                    return json_encode(Array('error'=>'图片上传失败，请稍后再试'),JSON_UNESCAPED_UNICODE);
                }
            }

            if(isset($info['password']))
            {
                $salt = ''; // 随机加密密钥
                while (strlen($salt) < 6) {
                    $x = mt_rand(0, 9);
                    $salt .= $x;
                }
                $info['salt'] = $salt;
                $info['password'] = sha1($info['password']. $salt); // sha1哈希加密
            }

            $query = $this->db->updateQuery($this->studentTable,$info)->updateLimit(1)->updateExecute();

            if($query->getAffectedRows() == 1)
            {
                if(isset($info['studentImg']))
                {
                    if (strlen($oImg) != 0) {
                        $res = $fileManger->deleteFile($oImg);
                        if(array_key_exists('error',json_decode($res,true)))
                        {
                            return $res;
                        }
//                        if(array_key_exists('warning',json_decode($res,true)))
//                        {
////                            return json_encode(Array('warning'=>'信息修改成功,但'.json_decode($res,true)['warning']),JSON_UNESCAPED_UNICODE);
//                        }
                    }
                }
                return json_encode(Array('success'=>'信息修改成功'),JSON_UNESCAPED_UNICODE);
            }
            else{
                return json_encode(Array('info'=>'信息未变更'),JSON_UNESCAPED_UNICODE);
            }

        }
    }

    public function logout(){
        if(isset($_SESSION['ms_user']) || isset($_SESSION['ms_id']))
        {
            session_unset();
            session_destroy();
            return json_encode(Array('success'=>'已退出登录'), JSON_UNESCAPED_UNICODE);
        }
        else{
            return json_encode(Array('error'=>'您尚未登录，无需进行此操作'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function getUserInfo(){
        if(!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user']))
        {
            return json_encode(Array('error'=>'请登录后再进行此操作'),JSON_UNESCAPED_UNICODE);
        }
        else if($_SESSION['ms_identity'] != 'Student'){
            return json_encode(Array('error'=>'接口调用错误，此用户不是学生账号'),JSON_UNESCAPED_UNICODE);
        }

        return $this->db->selectQuery('studentId,studentName,gender,`both`,contact,grade,years,departmentId,
        departmentName,majorId,majorName,class,classId,seat,idCard,address,studentImg',$this->studentTable)->andQueryList(Array('studentId'=>$_SESSION['ms_id']))->selectLimit(1,1)->getFetchAssocNumJson();
    }
}
