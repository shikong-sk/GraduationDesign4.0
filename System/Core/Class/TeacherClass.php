<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

//require_once(dirname(__FILE__) . '/FileClass.php');

class TeacherClass{

    var $db;
    var $teacherTable;

    var $model = Array(
        'teacherId' => null,
        'teacherName' => null,
        'gender' => null,
        'salt' => null,
        'password' => null,
        'contact' => null,
        'departmentId' => null,
        'departmentName' => null,
        'active' =>null,
        'idCard' => null,
        'address' => null,
        'teacherImg' => null,
    );

    var $filter = Array(
        'page' => null,
        'num' => null,
    );

    var $teacherId;
    var $teacherName;

    public function __construct()
    {
        $this->db = new SqlHelper();
        if(isset($_SESSION['ms_user']))
        {
            $this->teacherId = $_SESSION['ms_id'];
            $this->teacherName = $_SESSION['ms_user'];
        }
        $this->teacherTable = $this->db->db_table_prefix."_".SqlHelper::TEACHER;
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
            'teacherId' => null,
            'password' => null
        );

        foreach ($data as $k=>$v)
        {
            if(!array_key_exists($k,$model))
            {
                unset($data[$k]);
            }
        }

        if(isset($data['teacherId']) && isset($data['password']))
        {
            $salt = $this->db->selectQuery('salt',$this->teacherTable)->selectLimit(1,1)->getFetchAssoc()[0]['salt'];

            $data['password'] = sha1($data['password'].$salt);

            $query = $this->db->selectQuery('teacherId,teacherName',$this->teacherTable)->andQueryList($data)->selectLimit(1,1);
            if($query->getSelectNum() == 1)
            {
                $res = $query->getFetchAssoc()[0];
                $_SESSION['ms_id'] = $res['teacherId'];
                $_SESSION['ms_user'] = $res['teacherName'];
                $_SESSION['ms_identity'] = "Teacher";
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
            'teacherId' => null,
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

        if($data['teacherId'] != $_SESSION['ms_id'])
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
            unset($info['departmentId']);
            unset($info['departmentName']);
            unset($info['permission']);
            unset($info['active']);


            $query = $this->db->selectQuery('teacherImg',$this->teacherTable)->andQueryList($data)->selectLimit(1,1);

            if($query->getSelectNum() != 1)
            {
                return json_encode(Array('error'=>'此教工不存在'),JSON_UNESCAPED_UNICODE);
            }

            $oImg = "";
            $fileManger = new FileClass();

            if(isset($info['teacherImg'])){
                $oImg = $query->getFetchAssoc()[0]['teacherImg'];

                $info["teacherImg"] = $fileManger->uploadUserImage($info["teacherImg"]);
                if($info["teacherImg"] == null)
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

            $query = $this->db->updateQuery($this->teacherTable,$info)->andQueryList(Array("teacherId"=>$data["teacherId"]))->updateLimit(1)->updateExecute();

            if($query->getAffectedRows() == 1)
            {
                if(isset($info['teacherImg']))
                {
                    if (strlen($oImg) != 0) {
                        $res = $fileManger->deleteFile($oImg);
                        if(array_key_exists('error',json_decode($res,true)))
                        {
                            return $res;
                        }
//                        if(array_key_exists('warning',json_decode($res,true)))
//                        {
//                            return json_encode(Array('warning'=>'信息修改成功,但'.json_decode($res,true)['warning']),JSON_UNESCAPED_UNICODE);
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
        else if($_SESSION['ms_identity'] != 'Teacher'){
            return json_encode(Array('error'=>'接口调用错误，此用户不是教工账号'),JSON_UNESCAPED_UNICODE);
        }

        return $this->db->selectQuery('teacherId,teacherName,gender,`both`,contact,departmentId,
        departmentName,idCard,email,permission,address,teacherImg',$this->teacherTable)->andQueryList(Array('teacherId'=>$_SESSION['ms_id']))->selectLimit(1,1)->getFetchAssocNumJson();
    }
}
