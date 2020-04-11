<?php

require_once(dirname(__FILE__) .'/../Interface/SqlMethod.php');
require_once(dirname(__FILE__) .'/../SqlHelper.php');

session_start();
abstract class KeepAliveClass implements KeepAliveMethod,SqlMethod
{
    var $db;
    var $equipmentTable;
    
    var $try = 3; // 默认重试3-1次
    var $checkTime = 30; // 默认 30 秒检测一次
    var $live; // 存活状态 (0：离线 1：在线 6:重连 8：异常)
    var $lastTime; // 上一次存活时间

    var $equipmentId; // 设备ID

    public function setTry(int $try): void
    {
        $this->try = $try;
    }

    public function setCheckTime(int $checkTime): void
    {
        $this->checkTime = $checkTime;
    }

    public function __construct($equipmentId)
    {
        error_reporting(2);

        $this->db = new SqlHelper();

        $this->equipmentTable = $this->db->db_table_prefix . "_" . SqlHelper::Equipment;

        $this->equipmentId = $equipmentId;

    }

    public function checkAlive()
    {

        ignore_user_abort();//关闭浏览器后，继续执行php代码
        set_time_limit($this->checkTime * ($this->try));//程序执行时间无限制


        $equipmentId = $this->db->database->query("SELECT equipmentId FROM $this->equipmentTable WHERE equipmentId = '$this->equipmentId'")->fetch_assoc()['equipmentId'];


        if(!$equipmentId)
        {
            die(json_encode(Array('error' => '此设备不存在或尚未登记'), JSON_UNESCAPED_UNICODE));
        }

        $this->select();


        if(strtotime($this->lastTime)<0)
        {
            $this->lastTime = 0;
        }
        else
        {
            $this->lastTime = strtotime($this->lastTime);
        }


        if($this->lastTime < (time() - ($this->checkTime * $this->try)))
        {
            $query = "UPDATE $this->equipmentTable SET `equipmentStatus` = '1',`checkTime` = from_unixtime(" . time() . ") WHERE `equipmentId` = '$equipmentId'";
            $this->db->database->query($query);
            echo json_encode(Array('info' => '设备在线'), JSON_UNESCAPED_UNICODE);
//            die($query);
        }
        else{
            $query = "UPDATE $this->equipmentTable SET `equipmentStatus` = '1',`checkTime` = from_unixtime(" . time() . ") WHERE `equipmentId` = '$equipmentId'";
            $this->db->database->query($query);
            echo json_encode(Array('info' => '设备在线'), JSON_UNESCAPED_UNICODE);
        }

        sleep($this->checkTime * ($this->try-1));
//
        $this->select();

        $this->lastTime = strtotime($this->lastTime);
        if($this->lastTime <= (time() - ($this->checkTime * ($this->try - 2))))
        {
            $query = "UPDATE $this->equipmentTable SET `equipmentStatus` = '0',`checkTime` = from_unixtime(" . time() . ") WHERE `equipmentId` = '$equipmentId'";
            $this->db->database->query($query);
            die(json_encode(Array('info' => '设备已离线'), JSON_UNESCAPED_UNICODE));
        }
//        
    }

    public function update()
    {
        // TODO: Implement update() method.
    }

    public function insert()
    {
        // TODO: Implement insert() method.
    }

    public function delete()
    {
        // TODO: Implement delete() method.
    }

    public function select()
    {
//        $res = $this->db->database->query("SELECT status,UNIX_TIMESTAMP(checkTime) checkTime,ip FROM ".$this->db->db_table_prefix."_".SqlHelper::equipmentId." WHERE equipmentId = '$this->equipmentId'")->fetch_assoc();
        $res = $this->getEquipmentList($this->equipmentId);
        $this->live = $res["status"];
        $this->lastTime = $res["checkTime"];
    }

    public function getEquipmentList($equipmentId)
    {
        
        $selectFilter = '';
        

        $selectFilter .= "AND equipmentId = '" . $equipmentId . "' ";

        $query = "SELECT * FROM $this->equipmentTable WHERE 1=1 " . $selectFilter;
        
        return $this->db->database->query($query)->fetch_assoc();

    }
    
}