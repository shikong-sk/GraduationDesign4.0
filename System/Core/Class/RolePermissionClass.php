<?php

/*
 * 权限控制类
 */

require_once(dirname(__FILE__) . '/Interface/SqlMethod.php');

require_once(dirname(__FILE__) . '/SqlHelper.php');

class rolePermissionClass implements SqlMethod {

    var $db;
    var $role;
    var $table;
    var $roleTable;

    public function __construct($role)
    {
        if(!$role || strlen($role) == 0){
            die(json_encode(Array('error'=>'禁止访问'), JSON_UNESCAPED_UNICODE));
        }
        $this->db = new SqlHelper();
        $this->role = $role;
        $this->table = $this->db->db_table_prefix."_".SqlHelper::RolePermission;
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

    public function search()
    {
        return $this->db->database->query("SELECT * FROM $this->table WHERE role = '".$this->role."'")->fetch_assoc();
    }

    /*
     * 登录权限
     */
    public function checkLogin(){
        $res = $this->search();
        return $res['login'] === '0' ? false : true;
    }

    /*
     * 用户操作权限
     */
    public function checkAddUser(){
        $res = $this->search();
        return $res['addUser'] === '0' ? false : true;
    }

    public function checkDeleteUser(){
        $res = $this->search();
        return $res['deleteUser'] === '0' ? false : true;
    }

    public function checkUpdateUser(){
        $res = $this->search();
        return $res['updateUser'] === '0' ? false : true;
    }

    public function checkSearchUser(){
        $res = $this->search();
        return $res['searchUser'] === '0' ? false : true;
    }

    /*
     * 设备操作权限
     */
    public function checkAddDevice(){
        $res = $this->search();
        return $res['addDevice'] === '0' ? false : true;
    }

    public function checkDeleteDevice(){
        $res = $this->search();
        return $res['deleteDevice'] === '0' ? false : true;
    }

    public function checkUpdateDevice(){
        $res = $this->search();
        return $res['updateDevice'] === '0' ? false : true;
    }

    public function checkSearchDevice(){
        $res = $this->search();
        return $res['searchDevice'] === '0' ? false : true;
    }
    
    /*
     *  人员信息操作权限
     */
    public function checkAddPersonnel(){
        $res = $this->search();
        return $res['addPersonnel'] === '0' ? false : true;
    }

    public function checkDeletePersonnel(){
        $res = $this->search();
        return $res['deletePersonnel'] === '0' ? false : true;
    }

    public function checkUpdatePersonnel(){
        $res = $this->search();
        return $res['updatePersonnel'] === '0' ? false : true;
    }

    public function checkSearchPersonnel(){
        $res = $this->search();
        return $res['searchPersonnel'] === '0' ? false : true;
    }

    /*
     *  车辆信息操作权限
     */
    public function checkAddCar(){
        $res = $this->search();
        return $res['addCar'] === '0' ? false : true;
    }

    public function checkDeleteCar(){
        $res = $this->search();
        return $res['deleteCar'] === '0' ? false : true;
    }

    public function checkUpdateCar(){
        $res = $this->search();
        return $res['updateCar'] === '0' ? false : true;
    }

    public function checkSearchCar(){
        $res = $this->search();
        return $res['searchCar'] === '0' ? false : true;
    }
    
    /*
     * 管理员操作权限
     */
    public function checkAddAdmin(){
        $res = $this->search();
        return $res['addAdmin'] === '0' ? false : true;
    }

    public function checkDeleteAdmin(){
        $res = $this->search();
        return $res['deleteAdmin'] === '0' ? false : true;
    }

    /*
     * 区域、场所编辑权限
     */
    public function checkEditArea(){
        $res = $this->search();
        return $res['editArea'] === '0' ? false : true;
    }

    public function checkEditPlace(){
        $res = $this->search();
        return $res['editPlace'] === '0' ? false : true;
    }

    /*
     * 角色管理权限
     */
    public function checkEditRole(){
        $res = $this->search();
        return $res['editRole'] === '0' ? false : true;
    }

    public function checkEditRolePermission(){
        $res = $this->search();
        return $res['editRolePermission'] === '0' ? false : true;
    }

    /*
     * 获取角色权限列表
     */
    public function getRolePermissionList(){
        if($this->checkEditRole()){
            $res = $this->db->database->query("SELECT r.name,rp.* FROM $this->table rp,$this->roleTable r WHERE rp.role = r.role ORDER BY rp.role ASC");
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
        else
        {
            return json_encode(Array('error'=>'您所在用户组没有该权限'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function getRoleList(){
        if($this->checkLogin()){
            $res = $this->db->database->query("SELECT r.role,r.name FROM $this->roleTable r ORDER BY r.role ASC");
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
        else
        {
            return json_encode(Array('error'=>'您所在用户组没有该权限'), JSON_UNESCAPED_UNICODE);
        }
    }
}