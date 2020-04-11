<?php

/*
 * 权限控制类
 */

require_once(dirname(__FILE__) . '/Interface/SqlMethod.php');

require_once(dirname(__FILE__) . '/SqlHelper.php');

require_once(dirname(__FILE__) . '/Abstract/UserClass.php');

class roleClass implements SqlMethod {

    var $db;
    var $id;
    var $roleTable;

    public function __construct($id)
    {
        if(!$id || strlen($id) == 0){
            die(json_encode(Array('error'=>'禁止访问'), JSON_UNESCAPED_UNICODE));
        }
        $this->db = new SqlHelper();
        $this->id = $id;
        $this->roleTable = $this->db->db_table_prefix."_".SqlHelper::Role;
    }

    public function insert()
    {
        // TODO: Implement insert() method.
    }

    public function update()
    {
        // TODO: Implement update() method.
    }

    public function delete()
    {
        // TODO: Implement delete() method.
    }

    public function select()
    {
        return $this->db->database->query("SELECT `id`,`areaCode`,`areaName`,`dareaCode`,`dareaName`,`addPersonnel`,`delPersonnel`,`updatePersonnel`,`selectPersonnel`,`addCar`,`delCar`,`updateCar`,`selectCar`,`addEquipment`,`delEquipment`,`updateEquipment`,`selectEquipment` FROM $this->roleTable WHERE id = '".$this->id."'");
    }

    public function selectRole($area,$darea)
    {
        return $this->db->database->query("SELECT `id`,`areaCode`,`dareaCode`,`addPersonnel`,`delPersonnel`,`updatePersonnel`,`selectPersonnel`,`addCar`,`delCar`,`updateCar`,`selectCar`,`addEquipment`,`delEquipment`,`updateEquipment`,`selectEquipment` FROM $this->roleTable WHERE id = '".$this->id."' AND `areaCode` = '$area' AND `dareaCode` = '$darea'");
    }

    /*
     * 用户操作权限
     */
    public function checkAddUser(){
        $res = new User();
        $res->getPermission()->fetch_assoc();
        return $res['permission'] == '8' ? false : true;
    }

    public function checkDeleteUser(){
        $res = new User();
        $res->getPermission()->fetch_assoc();
        return $res['permission'] == '8' ? false : true;
    }

    public function checkUpdateUser(){
        $res = new User();
        $res->getPermission()->fetch_assoc();
        return $res['permission'] == '8' ? false : true;
    }

    public function checkSelectUser(){
        $res = new User();
        $res->getPermission()->fetch_assoc();
        return $res['permission'] == '8' ? false : true;
    }

    /*
     * 设备操作权限
     */
    public function checkAddDevice($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['addDevice'] === '0' ? false : true;
    }

    public function checkDeleteDevice($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['deleteDevice'] === '0' ? false : true;
    }

    public function checkUpdateDevice($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['updateDevice'] === '0' ? false : true;
    }

    public function checkSelectDevice($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['selectDevice'] === '0' ? false : true;
    }
    
    /*
     *  人员信息操作权限
     */
    public function checkAddPersonnel($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['addPersonnel'] === '0' ? false : true;
    }

    public function checkDeletePersonnel($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['deletePersonnel'] === '0' ? false : true;
    }

    public function checkUpdatePersonnel($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['updatePersonnel'] === '0' ? false : true;
    }

    public function checkSelectPersonnel($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['selectPersonnel'] === '0' ? false : true;
    }

    /*
     *  车辆信息操作权限
     */
    public function checkAddCar($area,$darea){
        $res = $this->selectRole($area,$darea);;
        return $res['addCar'] === '0' ? false : true;
    }

    public function checkDeleteCar($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['deleteCar'] === '0' ? false : true;
    }

    public function checkUpdateCar($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['updateCar'] === '0' ? false : true;
    }

    public function checkSelectCar($area,$darea){
        $res = $this->selectRole($area,$darea);
        return $res['selectCar'] === '0' ? false : true;
    }

    /*
     * 管理员操作权限
     */
    public function checkEditAdmin(){
        $res = new User();
        $res->getPermission()->fetch_assoc();
        return $res['permission'] == '8' ? false : true;
    }

    /*
     * 区域、场所编辑权限
     */
    public function checkEditPlan(){
        $res = new User();
        $res->getPermission()->fetch_assoc();
        return $res['permission'] == '8' ? false : true;
    }

    /*
     * 角色管理权限
     */
    public function checkEditRole(){
        $res = new User();
        $res->getPermission()->fetch_assoc();
        return $res['permission'] == '8' ? false : true;
    }

    /*
     * 获取权限列表
     */
    public function getRoleList(){
        $res = $this->select();
        $resNum = 0;
        $json = Array();
        while ($res->data_seek($resNum)) {
            $data = $res->fetch_assoc();
            array_push($json, $data);
            $resNum++;
        }
        array_splice($json, 0, 0, count($json));
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        return $json;
    }
}