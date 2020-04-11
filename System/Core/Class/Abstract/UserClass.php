<?php

/*
 * 用户抽象类
 */

require_once(dirname(__FILE__) .'/../Interface/SqlMethod.php');

require_once(dirname(__FILE__) .'/../SqlHelper.php');

require_once(dirname(__FILE__) .'/FileClass.php');

abstract class UserClass implements SqlMethod,UserMethod {

    var $db;

    var $dataTable;

    var $id;
    var $password;

    var $salt;
    var $contact;
    var $email;
    var $area;
    var $place;
    var $gender;
    var $name;
    var $idCard;
    var $Img;
    var $permission = '3';

    public function __construct($dataTable)
    {
        $this->db = new SqlHelper();
        if(isset($_SESSION['user']))
        {
            $this->userName = $_SESSION['ms_user'];
            $this->id = $_SESSION['ms_id'];
        }
        $this->dataTable = $this->db->db_table_prefix."_".$dataTable;
    }

    public function register($id,$password,$contact,$gender,$name,$email,$idCard,$userImg='')
    {
        if(isset($_SESSION['user'])){
            return json_encode(Array('error'=>'您已登录，无需进行此操作'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen($id) == 0 || strlen($password) == 0) {
            return json_encode(Array('success' => '用户注册失败，请检查信息是否完整'), JSON_UNESCAPED_UNICODE);
        }

        $this->id = $id;
        $this->password = $password;
        $this->contact = $contact;
        $this->gender = $gender;
        $this->name = $name;
        $this->email = $email;
        $this->idCard = $idCard;
        $this->userImg = $userImg;

        $this->id = trim(guid(), '{}');

        $salt = ''; // 随机加密密钥
        while (strlen($salt) < 6) {
            $x = mt_rand(0, 9);
            $salt .= $x;
        }
        $this->salt = $salt;
        $this->password = sha1($password . $salt); // sha1哈希加密

        $this->insert();

        if ($this->db->database->affected_rows == 1) {
            return json_encode(Array('success' => '用户注册成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => '用户注册失败,该用户已存在'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function updateUser($password,$contact,$email,$gender,$name,$idCard,$cardType,$userImg){
        if(!isset($_SESSION['user'])){
            return json_encode(Array('error'=>'请先登录再进行此操作'), JSON_UNESCAPED_UNICODE);
        }
        if(strlen($password.$contact.$email.$gender.$name.$idCard.$cardType.$userImg) == 0)
        {
            return json_encode(Array('info'=>'信息未更改'), JSON_UNESCAPED_UNICODE);
        }

        $this->userName = $_SESSION['user'];
        $this->id = $_SESSION['uid'];

        $old = $this->select()->fetch_assoc();

        if(sha1($password.$old['salt']).$contact.$email.$gender.$name.$idCard.$cardType.$userImg === $old['password'].$old['contact'].$old['email'].$old['gender'].$old['name'].$old['idCard'].$old['cardType'].$old['userImg'])
        {
            return json_encode(Array('info'=>'信息未更改'), JSON_UNESCAPED_UNICODE);
        }

        if(strlen($password) == 0){
            $this->password = $old["password"];
            $this->salt = $old["salt"];
        }
        else{
            $salt = ''; // 随机加密密钥
            while (strlen($salt) < 6) {
                $x = mt_rand(0, 9);
                $salt .= $x;
            }
            $this->salt = $salt;
            $this->password = sha1($password . $salt); // sha1哈希加密
        }

        $this->contact = strlen($contact) == 0 ? $old["contact"] : $contact;
        $this->gender = strlen($gender) == 0 ? $old["gender"] : $gender;
        $this->name = strlen($name) == 0 ? $old["name"] : $name;
        $this->idCard = strlen($idCard) == 0 ? $old["idCard"] : $idCard;
        $this->cardType = strlen($cardType) == 0 ? $old["cardType"] : $cardType;
        $this->userImg = strlen($userImg) == 0 ? $old["userImg"] : $userImg;
        $this->email = strlen($email) == 0 ? $old["email"] : $email;

        $f = new FileManager();
        if(strlen($userImg)!=0)
        {
            $userImg = json_decode($f->uploadUserImage($userImg),true)[0];
        }
        else{
            $userImg = $old["userImg"];
        }

        $this->userImg = $userImg;

        $res = $this->update();

        if($res){
            if($this->db->database->affected_rows == 1 ){
                if($userImg != $old['userImg'])
                {
                    $f->deleteImage($old['userImg'],'user');
                }
                return json_encode(Array('success' => '用户信息更新成功'), JSON_UNESCAPED_UNICODE);
            }
            else if($this->db->database->affected_rows == 0){
                return json_encode(Array('info'=>'信息未更改'), JSON_UNESCAPED_UNICODE);
            }
        }
        else{
            return json_encode(Array('error' => '用户信息更新出错'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function insert()
    {
        $query = "INSERT INTO ".$this->userTable ."(`id`, `userName`, `salt`, `password`, `name`, `gender`, `contact`, `idCard`,`cardType`,`userImg`,`email`,`permission`) 
        VALUES ('$this->id', '$this->userName', '$this->salt', '$this->password', '$this->name', '$this->gender', '$this->contact', '$this->idCard', '$this->cardType','$this->userImg', '$this->email','$this->permission')";

        return $this->db->database->query($query);
    }

    public function update()
    {
        return $this->db->database->query("UPDATE ".$this->userTable." SET `salt` = '$this->salt', `password` = '$this->password', `name` = '$this->name', `gender` = '$this->gender', `contact` = '$this->contact', `idCard` = '$this->idCard' , `cardType` = '$this->cardType' , `userImg` = '$this->userImg' , `email` = '$this->email' WHERE `id` = '$this->id' AND `userName` = '$this->userName'");
    }

    public function delete()
    {
        // TODO: Implement delete() method.
    }

    public function select()
    {
        return $this->db->database->query("SELECT * FROM ".$this->userTable." WHERE userName = '$this->userName'");
    }

    public function login($userName,$password)
    {

        if(isset($_SESSION['user'])){
            return json_encode(Array('error'=>'您已登录，无需进行此操作'), JSON_UNESCAPED_UNICODE);
        }

        $this->userName = $userName;
        $this->password = $password;

        $res = $this->select()->fetch_assoc();

        if(!$res)
        {
            return json_encode(Array('error'=>'登录失败，账号或密码错误'), JSON_UNESCAPED_UNICODE);
        }
        else
        {
            if(sha1($this->password.$res['salt']) === $res['password']){
                $this->id = $res["id"];

                $_SESSION['user'] = $this->userName;
                $_SESSION['uid'] = $this->id;

                return json_encode(Array('success'=>'登录成功'), JSON_UNESCAPED_UNICODE);
            }
            else{
                return json_encode(Array('error'=>'登录失败，账号或密码错误'), JSON_UNESCAPED_UNICODE);
            }
        }

    }

    public function logout()
    {
        if(isset($_SESSION['user'])){
            session_unset();
            session_destroy();
            return json_encode(Array('success'=>'已退出登录'), JSON_UNESCAPED_UNICODE);
        }
        else{
            return json_encode(Array('error'=>'您尚未登录，无需进行此操作'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function getUserInfo(){
        if(!isset($_SESSION['user'])){
            return json_encode(Array('error'=>'请先登录再进行此操作'), JSON_UNESCAPED_UNICODE);
        }

        $res = $this->db->database->query("SELECT userName,name,gender,contact,idCard,cardType,userImg,permission FROM ".$this->userTable." WHERE userName = '$this->userName' AND id = '$this->id'");
        $resNum = 0;

        $json = Array();
        while ($res->data_seek($resNum)) {
            $data = $res->fetch_assoc();
            array_push($json, $data);
            $resNum++;
        }
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        return $json;

    }

    public function getPermission(){
        if(!isset($_SESSION['user'])){
            die(json_encode(Array('error'=>'请先登录再进行此操作'), JSON_UNESCAPED_UNICODE));
        }

        $res = $this->db->database->query("SELECT permission FROM ".$this->userTable." WHERE userName = '$this->userName' AND id = '$this->id'");

        return $res;

    }
}

class User extends UserClass{
}