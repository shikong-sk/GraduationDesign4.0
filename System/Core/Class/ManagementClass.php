<?php

/*
 * 管理类
 */

require_once(dirname(__FILE__) . '/Interface/SqlMethod.php');

require_once(dirname(__FILE__) . '/SqlHelper.php');

require_once(dirname(__FILE__) . '/RoleClass.php');

require_once(dirname(__FILE__) . '/Abstract/UserClass.php');

require_once(dirname(__FILE__) . '/Abstract/FileClass.php');

class  ManagementClass
{
    var $db;
    var $user;
    var $permission;

    var $rolePermission;

    var $userTable;
    var $roleTable;
    var $planTable;
    var $equipmentTable;
    var $carTable;
    var $personnelTable;


    var $file_maxSize;
    var $disk_maxSize;
    var $max_record;

    public function __construct()
    {
        $this->db = new SqlHelper();

        $this->user = new User();

        $this->rolePermission = new roleClass($_SESSION['uid']);

        $this->permission = $this->user->getPermission()->fetch_assoc()['permission'];

        $this->userTable = $this->db->db_table_prefix . "_" . SqlHelper::User;
        $this->roleTable = $this->db->db_table_prefix . "_" . SqlHelper::Role;
        $this->planTable = $this->db->db_table_prefix . "_" . SqlHelper::Plan;
        $this->equipmentTable = $this->db->db_table_prefix . "_" . SqlHelper::Equipment;
        $this->carTable = $this->db->db_table_prefix . "_" . SqlHelper::Car;
        $this->personnelTable = $this->db->db_table_prefix . "_" . SqlHelper::Personnel;

        $f = new FileManager();
        $this->file_maxSize = $f->MaxFileSize;
        $this->disk_maxSize = intval(disk_free_space('./')/1024) - 1048576;
        $this->max_record = intval($this->disk_maxSize / $this->file_maxSize / 2 / 2);
        if($this->max_record > 50000)
        {
            $this->max_record = 50000;
        }
        // var_dump($this->file_maxSize,$this->disk_maxSize,$this->max_record);
    }

    /*
     * 信息查询
     */

    public function getAdminList()
    {
        if ($this->permission != '8' && $this->permission != '1') {
            return json_encode(Array('error' => '查询失败，您无此权限'), JSON_UNESCAPED_UNICODE);
        }
        $query = "SELECT `id`,`userName`,`name`,`gender`,`contact`,`idCard`,`cardType`,`userImg`,`permission`,`email` FROM $this->userTable WHERE permission = '1' OR permission = '8'";
        $res = $this->db->database->query($query);
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

    public function getRoleList($data = '')
    {
        if (!is_array($data) && strlen($data) == 0) {
            return $this->rolePermission->getRoleList();
        } else {
            if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
                return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
            }

            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if(isset($data['filter']))
            {
                $filter = $data['filter'];
                if (!is_array($filter)) {
                    $filter = json_decode($filter, true);
                }
            }

            if (isset($data['area']) && strlen($data['area']) != 9) {
                return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['darea']) && strlen($data['darea']) != 19) {
                return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['area']) && isset($data['darea']) && $data['area'] !== substr($data['darea'], 0, 9)) {
                return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
            }

            if (isset($data['id']) && strlen($data['id']) != 36) {
                return json_encode(Array('error' => 'id 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if (isset($filter['id']) && strlen($filter['id']) != 36) {
                return json_encode(Array('error' => 'id 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            else if(isset($filter['id'])){
                $data['id'] = $filter['id'];
            }

            if (!isset($data['page'])) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['num'])) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if(isset($data['all']) && !isset($data['area']) && !isset($data['darea']) && !isset($data['id']))
            {
                return $this->getPlanList($data['page'],$data['num'],['areaName'=>$data['areaName'],'dareaName'=>$data['dareaName']]);
            }

            $filter = "";
            $query = "SELECT `id`,`areaCode`,`areaName`,`dareaCode`,`dareaName`,`addPersonnel`,`delPersonnel`,`updatePersonnel`,`selectPersonnel`,`addCar`,`delCar`,`updateCar`,`selectCar`,`addEquipment`,`delEquipment`,`updateEquipment`,`selectEquipment` FROM $this->roleTable WHERE 1=1 ";
            $count = "SELECT count(*) num FROM $this->roleTable WHERE 1=1 ";

            if (isset($data['id'])) {
                if (json_decode($this->getUserList(1, 1, ['id' => $data['id']]), true)[0] == 0) {
                    return json_encode(Array('error' => '此 id 不存在'), JSON_UNESCAPED_UNICODE);
                } else {
                    $filter .= "AND id = '{$data["id"]}' ";
                }
            }


            if (isset($data['area']) && json_decode($this->getAreaList(1, 1, ['area' => $data['area']]), true)[0] != 0) {
                $filter .= "AND areaCode = '{$data["area"]}' ";
            }
            if (isset($data['darea']) && json_decode($this->getPlanList(1, 1, ['darea' => $data['darea']]), true)[0] != 0) {
                $filter .= "AND dareaCode = '{$data["darea"]}' ";
            }
            if (isset($data['areaName'])) {
                $filter .= "AND areaName LIKE '%" . $data['areaName'] . "%' ";
            }
            if (isset($data['dareaName'])) {
                $filter .= "AND dareaName LIKE '%" . $data['dareaName'] . "%' ";
            }

            $query .= $filter;
            $count .= $filter;

            $page = intval($data['page']);
            $num = intval($data['num']);
            $page = ($page - 1) * $num;
            $query .= " LIMIT $page,$num";

            $count = $this->db->database->query($count)->fetch_assoc()['num'];
            $json = Array('0' => $count);

            $res = $this->db->database->query($query);
            $resNum = 0;

            while ($res->data_seek($resNum)) {
                $data = $res->fetch_assoc();
                array_push($json, $data);
                $resNum++;
            }
            $json = json_encode($json, JSON_UNESCAPED_UNICODE);
            return $json;
        }

    }

    public function getAreaName($area)
    {

        $query = "SELECT DISTINCT areaName FROM $this->planTable WHERE area = '$area' ";
        $res = $this->db->database->query($query);
        return $res->fetch_assoc()['areaName'];
    }

    public function getDareaName($darea)
    {
        $query = "SELECT DISTINCT dareaName FROM $this->planTable WHERE darea = '$darea' ";
        $res = $this->db->database->query($query)->fetch_assoc();
        return $this->getAreaName(substr($darea, 0, 9)) . ':' . $res['dareaName'];
    }

    public function getPersonnelList($area, $areaList, $darea, $dareaList, $page, $num, $filter)
    {

        if($this->user->getPermission()->fetch_assoc()['permission'] != '8')
        {
            if ((!$area || strlen($area) == 0) && (!$areaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (strlen($area) != 9 && (!$areaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if ((!$darea || strlen($darea) == 0) && (!$dareaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (strlen(strval($darea)) != 19 && (!$dareaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
        }

//      $areaList 格式 ["440511000","110000000"]

        $selectPlan = '';
        $selectFilter = '';

        $roleList = json_decode($this->getRoleList(), true);

        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            if (!$area || strlen($area) == 0) {
                if (!is_array($areaList)) {
                    $areaList = json_decode($areaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";

                foreach ($areaList as $a) {
                    $flag = false;
                    foreach ($roleList as $r) {
//                        if ($r['selectPersonnel'] == 0) {
//                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($a) . '：' . $a . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
//                        }

                        if (in_array($a, $r)) {
                            $flag = true;
                            $selectPlan .= "OR area = '$a' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($a) . '：' . $a . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                    }

                }
            } else {
                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($area, $r)) {
                        $flag = true;
//                        if ($r['selectPersonnel'] == 0) {
//                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($area) . '：' . $area . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
//                        }
                        $selectPlan .= "AND (area = '$area' ";
                        break;
                    }
                }
                if (!$flag) {
                    return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($area) . '：' . $area . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                }
            }
            $selectPlan .= ") ";

            if (!$darea || strlen($darea) == 0) {
                if (!is_array($dareaList)) {
                    $dareaList = json_decode($dareaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";
                foreach ($dareaList as $d) {
                    $flag = false;
                    foreach ($roleList as $r) {
//                        if ($r['selectPersonnel'] == 0) {
//                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($d) . '：' . $d . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
//                        }

                        if (in_array($d, $r)) {
                            $flag = true;
                            $selectPlan .= "OR darea = '$d' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($d) . '：' . $d . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                    }

                }
            } else {
                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($darea, $r)) {
                        $flag = true;
                        if ($r['selectPersonnel'] == 0) {
                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($darea) . '：' . $darea . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                        }
                        $selectPlan .= "AND (darea = '$darea' ";
                        break;
                    }
                }
                if (!$flag) {
                    return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($darea) . '：' . $darea . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                }
            }
            $selectPlan .= ") ";

        } else if ($this->user->getPermission()->fetch_assoc()['permission'] == '8' &&
            (strlen($area) != 0 || is_array($areaList) || strlen($areaList) !=0) &&
            (strlen($darea) != 0 || is_array($dareaList) || strlen($dareaList) !=0)
        ) {
            if (!$area || strlen($area) == 0) {
                if (!is_array($areaList)) {
                    $areaList = json_decode($areaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";

                foreach ($areaList as $a) {
                    $selectPlan .= "OR area = '$a' ";
                }
            } else {
                $selectPlan .= "AND (area = '$area' ";
            }
            $selectPlan .= ") ";

            if (!$darea || strlen($darea) == 0) {
                if (!is_array($dareaList)) {
                    $dareaList = json_decode($dareaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";
                foreach ($dareaList as $d) {
                    $selectPlan .= "OR darea = '$d' ";
                }
            } else {
                $selectPlan .= "AND (darea = '$darea' ";
            }
            $selectPlan .= ") ";

        }

        if ($filter) {
            if (!is_array($filter)) {
                $filter = json_decode($filter, true);
            }

            if (isset($filter['name'])) {
                $selectFilter .= "AND name LIKE '%" . $filter['name'] . "%' ";
            }
            if (isset($filter['gender'])) {
                $selectFilter .= "AND gender = '" . $filter['gender'] . "' ";
            }
            if (isset($filter['nation'])) {
                $selectFilter .= "AND nation LIKE '%" . $filter['nation'] . "%' ";
            }
            if (isset($filter['idCard'])) {
                $selectFilter .= "AND idCard LIKE '%" . $filter['idCard'] . "%' ";
            }
            if (isset($filter['cardType'])) {
                $selectFilter .= "AND cardType = '" . $filter['cardType'] . "' ";
            }
            if (isset($filter['countryOrAreaCode'])) {
                $selectFilter .= "AND countryOrAreaCode = '" . $filter['countryOrAreaCode'] . "' ";
            }
            if (isset($filter['countryOrAreaName'])) {
                $selectFilter .= "AND countryOrAreaName LIKE '%" . $filter['countryOrAreaName'] . "%' ";
            }
            if (isset($filter['cardVersion'])) {
                $selectFilter .= "AND cardVersion LIKE '%" . $filter['cardVersion'] . "%' ";
            }
            if (isset($filter['currentApplyOrgCode'])) {
                $selectFilter .= "AND currentApplyOrgCode LIKE '%" . $filter['currentApplyOrgCode'] . "%' ";
            }
            if (isset($filter['signNum'])) {
                $selectFilter .= "AND signNum = '" . $filter['signNum'] . "' ";
            }
            if (isset($filter['address'])) {
                $selectFilter .= "AND address LIKE '%" . $filter['address'] . "%' ";
            }
            if (isset($filter['higherTemp'])) {
                $selectFilter .= "AND temp > '" . $filter['higherTemp'] . "' ";
            }
            if (isset($filter['lowerTemp'])) {
                $selectFilter .= "AND temp < '" . $filter['lowerTemp'] . "' ";
            }
            if (isset($filter['passTime'])) {
                $selectFilter .= "AND passTime = '" . $filter['passTime'] . "' ";
            }
            if (isset($filter['beforeTime'])) {
                $selectFilter .= "AND passTime < '" . $filter['beforeTime'] . "' ";
            }
            if (isset($filter['afterTime'])) {
                $selectFilter .= "AND passTime > '" . $filter['afterTime'] . "' ";
            }
            if (isset($filter['betweenTime'])) {
                if (!is_array($filter['betweenTime'])) {
                    $filter['betweenTime'] = json_decode($filter['betweenTime'], true);
                }
                $selectFilter .= "AND passTime BETWEEN '" . $filter['betweenTime'][0] . "' AND '" . $filter['betweenTime'][1] . "' ";
            }
            if (isset($filter['equipmentId'])) {
                $selectFilter .= "AND equipmentId = '" . $filter['equipmentId'] . "' ";
            }
            if (isset($filter['equipmentName'])) {
                $selectFilter .= "AND equipmentName LIKE '%" . $filter['equipmentName'] . "%' ";
            }
            if (isset($filter['equipmentType'])) {
                $selectFilter .= "AND equipmentType = '" . $filter['equipmentType'] . "' ";
            }
            if (isset($filter['status'])) {
                $selectFilter .= "AND status = '" . $filter['status'] . "' ";
            }
            if (isset($filter['stationId'])) {
                $selectFilter .= "AND stationId = '" . $filter['stationId'] . "' ";
            }
            if (isset($filter['stationName'])) {
                $selectFilter .= "AND stationName LIKE '%" . $filter['stationName'] . "%' ";
            }
            if (isset($filter['placeType'])) {
                $selectFilter .= "AND placeType = '" . $filter['placeType'] . "' ";
            }
            if (isset($filter['identity'])) {
                $selectFilter .= "AND identity = '" . $filter['identity'] . "' ";
            }
            if (isset($filter['homePlace'])) {
                $selectFilter .= "AND homePlace LIKE '%" . $filter['homePlace'] . "%' ";
            }
            if (isset($filter['contact'])) {
                $selectFilter .= "AND contact LIKE '%" . $filter['contact'] . "%' ";
            }
            if (isset($filter['isConsist'])) {
                $selectFilter .= "AND isConsist = '" . $filter['isConsist'] . "' ";
            }
            if (isset($filter['higherCompareScore'])) {
                $selectFilter .= "AND compareScore > '" . $filter['higherCompareScore'] . "' ";
            }
            if (isset($filter['lowerCompareScore'])) {
                $selectFilter .= "AND compareScore < '" . $filter['lowerCompareScore'] . "' ";
            }
            if (isset($filter['openMode'])) {
                $selectFilter .= "AND openMode = '" . $filter['openMode'] . "' ";
            }
            if (isset($filter['visitReason'])) {
                $selectFilter .= "AND visitReason LIKE '%" . $filter['visitReason'] . "%' ";
            }
            if (isset($filter['mac'])) {
                $selectFilter .= "AND mac LIKE '%" . $filter['mac'] . "%' ";
            }
            if (isset($filter['imsi'])) {
                $selectFilter .= "AND imsi LIKE '%" . $filter['imsi'] . "%' ";
            }
            if (isset($filter['imei'])) {
                $selectFilter .= "AND imei LIKE '%" . $filter['imei'] . "%' ";
            }
            if (isset($filter['personImg'])) {
                $selectFilter .= "AND personImg = '" . $filter['personImg'] . "' ";
            }
            if (isset($filter['idCardImg'])) {
                $selectFilter .= "AND idCardImg = '" . $filter['idCardImg'] . "' ";
            }
            if (isset($filter['dareaName'])) {
                $selectFilter .= "AND dareaName LIKE '%" . $filter['dareaName'] . "%' ";
            }
        }

        $query = "SELECT * FROM $this->personnelTable WHERE 1=1 " . $selectPlan . $selectFilter;
        $count = "SELECT count(*) num FROM $this->personnelTable WHERE 1=1 " . $selectPlan . $selectFilter;

//        echo $query;
        $page = ($page - 1) * $num;


        $query .= " order by passTime DESC LIMIT $page,$num";

        $count = $this->db->database->query($count)->fetch_assoc()['num'];
        $json = Array('0' => $count);

//        die($query);

        $res = $this->db->database->query($query);
        $resNum = 0;

        while ($res->data_seek($resNum)) {
            $data = $res->fetch_assoc();
            array_push($json, $data);
            $resNum++;
        }
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        return $json;

//        if (!$this->rolePermission->checkSelectPersonnel()) {
//            return json_encode(Array('error' => '查询失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//        $page = ($page - 1) * $num;
////        $query = "SELECT pe.time,pe.name,a.name area,p.name place,pe.idCard,pe.come,pe.temp,d.name device FROM $this->personnelTable pe,$this->areaTable a,$this->placeTable p,$this->deviceTable d WHERE pe.area = a.code AND pe.place = p.id AND pe.area = p.area AND p.area = a.code AND d.area = pe.area AND d.place = pe.place AND pe.device = d.deviceID";
//
//        $query = "SELECT * FROM (SELECT pe.*,d.name device FROM (SELECT pe.time,pe.name,a.name area,p.name place,pe.idCard,pe.come,pe.temp,pe.device deviceID FROM $this->areaTable a,$this->placeTable p,$this->personnelTable pe WHERE pe.area = a.code AND pe.place = p.id AND pe.area = p.area AND p.area = a.code ";
//
//        if(strlen($filter) != 0)
//        {
//            switch ($filter){
//                case 'beforeTime': $query .= "AND pe.time < '".$arg."'";break;//
//                case 'afterTime': $query .= "AND pe.time > '".$arg."'";break;
//                case 'betweenTime':
//                    //  JSON格式{"startTime":"2020-03-02 0:0:0","endTime":"2020-03-03 0:0:0"}
//                    $json = json_decode($arg,true);
//                    if(!$json) return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//                    $query .= "AND pe.time > '".$json["startTime"]."' AND pe.time < '".$json["endTime"]."'";
//                    break;
//                case 'lowerTemp': $query .= "AND pe.temp < $arg";break;
//                case 'higherTemp': $query .= "AND pe.temp > $arg";break;
//                case 'idCard' : $query .= "AND pe.idCard LIKE '%$arg%'";break;
//                case 'come': $query .= "AND pe.come LIKE '%$arg%'";break;
//                case 'name': $query .= "AND pe.name LIKE '%$arg%'";break;
//                case 'area': $query .= "AND (a.name LIKE '%$arg%' OR p.name LIKE '%$arg%')";break;
//                case 'deviceName':
//                    $query .= "AND LENGTH(TRIM(pe.device)) != 0 AND pe.device is not null";
//                    $last_query = "WHERE pe.device LIKE '%$arg%'";
//                    break;
//                case 'areaLevel' :
//                    //  JSON格式{"area":"4405","place":"00000","device":"","startTime":"2020-03-02 00:00:00","endTime":"2020-03-03 23:59:59"}
//                    $json = json_decode($arg,true);
//                    if(!$json) return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//                    $tmp_query = "WHERE d.deviceID IN (SELECT deviceID FROM $this->deviceTable WHERE area LIKE '".$json['area']."%' AND place LIKE '".$json['place']."%')";
//                    if(isset($json['device']) && strlen($json['device'])!=0 )
//                    {
//                        $tmp_query .= " AND d.deviceId = '".$json['device']."'";
//                    }
//                    if(isset($json['name']) && strlen($json['name']) != 0)
//                    {
//                        $query .= " AND pe.name LIKE '%".$json['name']."%'";
//                    }
//                    if(isset($json['idCard']) && strlen($json['idCard']) != 0){
//                        $query .= " AND pe.idCard LIKE '%".$json['idCard']."%'";
//                    }
//                    if(isset($json['startTime']) && strlen($json['startTime']) != 0 && isset($json['endTime']) && strlen($json['endTime']) != 0)
//                    {
//                        $query .= " AND pe.time > '".$json["startTime"]."' AND pe.time < '".$json["endTime"]."'";
//                    }
//                    break;
//                default:
//                    return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//            }
//        }

//        $query.=" ) pe LEFT JOIN $this->deviceTable d ON pe.deviceID = d.deviceID";
//
//        if(isset($tmp_query))
//        {
//            $query .= ' ' . $tmp_query;
//        }
//
//        $query .= ") pe";
//        if(isset($last_query))
//        {
//            $query .= ' ' . $last_query;
//        }
//
//        $count = $this->db->database->query($query)->num_rows;
//        $json = Array('0'=>$count);
//
//        $query .=" order by pe.time DESC LIMIT $page,$num";
//
////        echo $query;
//        $res = $this->db->database->query($query);
//        $resNum = 0;
//
//        while ($res->data_seek($resNum)) {
//            $data = $res->fetch_assoc();
//            array_push($json, $data);
//            $resNum++;
//        }
//        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
//        return $json;
    }

    public function getCarList($area, $areaList, $darea, $dareaList, $page, $num, $filter)
    {

        if($this->user->getPermission()->fetch_assoc()['permission'] != '8')
        {
            if ((!$area || strlen($area) == 0) && (!$areaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (strlen($area) != 9 && (!$areaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if ((!$darea || strlen($darea) == 0) && (!$dareaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (strlen(strval($darea)) != 19 && (!$dareaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
        }


//      $areaList 格式 ["440511000","110000000"]

        $selectPlan = '';
        $selectFilter = '';

        $roleList = json_decode($this->getRoleList(), true);

        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            if (!$area || strlen($area) == 0) {
                if (!is_array($areaList)) {
                    $areaList = json_decode($areaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";

                foreach ($areaList as $a) {
                    $flag = false;
                    foreach ($roleList as $r) {
//                        if ($r['selectCar'] == 0) {
//                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($a) . '：' . $a . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
//                        }

                        if (in_array($a, $r)) {
                            $flag = true;
//                            if ($r['selectCar'] == 0) {
//                                return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($a) . '：' . $a . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
//                            }
                            $selectPlan .= "OR area = '$a' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($a) . '：' . $a . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                    }

                }
            } else {
                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($area, $r)) {
                        $flag = true;
//                        if ($r['selectCar'] == 0) {
//                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($area) . '：' . $area . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
//                        }
                        $selectPlan .= "AND (area = '$area' ";
                        break;
                    }
                }
                if (!$flag) {
                    return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($area) . '：' . $area . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                }
            }
            $selectPlan .= ") ";

            if (!$darea || strlen($darea) == 0) {
                if (!is_array($dareaList)) {
                    $dareaList = json_decode($dareaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";
                foreach ($dareaList as $d) {
                    $flag = false;
                    foreach ($roleList as $r) {
//                        if ($r['selectCar'] == 0) {
//                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($d) . '：' . $d . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
//                        }

                        if (in_array($d, $r)) {
                            $flag = true;
                            if ($r['selectCar'] == 0) {
                                return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($d) . '：' . $d . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                            }
                            $selectPlan .= "OR darea = '$d' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($d) . '：' . $d . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                    }

                }
            } else {
                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($darea, $r)) {
                        $flag = true;
                        if ($r['selectCar'] == 0) {
                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($darea) . '：' . $darea . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                        }
                        $selectPlan .= "AND (darea = '$darea' ";
                        break;
                    }
                }
                if (!$flag) {
                    return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($darea) . '：' . $darea . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                }
            }
            $selectPlan .= ") ";

        } else if (
            $this->user->getPermission()->fetch_assoc()['permission'] == '8' &&
            (strlen($area) != 0 || is_array($areaList) || strlen($areaList) !=0) &&
            (strlen($darea) != 0 || is_array($dareaList) || strlen($dareaList) !=0)
        ) {
            if (!$area || strlen($area) == 0) {
                if (!is_array($areaList)) {
                    $areaList = json_decode($areaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";

                foreach ($areaList as $a) {
                    $selectPlan .= "OR area = '$a' ";
                }
            } else {
                $selectPlan .= "AND (area = '$area' ";
            }
            $selectPlan .= ") ";

            if (!$darea || strlen($darea) == 0) {
                if (!is_array($dareaList)) {
                    $dareaList = json_decode($dareaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";
                foreach ($dareaList as $d) {
                    $selectPlan .= "OR darea = '$d' ";
                }
            } else {
                $selectPlan .= "AND (darea = '$darea' ";
            }
            $selectPlan .= ") ";

        }

        if ($filter) {
            if (!is_array($filter)) {
                $filter = json_decode($filter, true);
            }

            if (isset($filter['plateNum'])) {
                $selectFilter .= "AND plateNum LIKE '%" . $filter['plateNum'] . "%' ";
            }
            if (isset($filter['passTime'])) {
                $selectFilter .= "AND passTime = '" . $filter['passTime'] . "' ";
            }
            if (isset($filter['beforeTime'])) {
                $selectFilter .= "AND passTime < '" . $filter['beforeTime'] . "' ";
            }
            if (isset($filter['afterTime'])) {
                $selectFilter .= "AND passTime > '" . $filter['afterTime'] . "' ";
            }
            if (isset($filter['betweenTime'])) {
                if (!is_array($filter['betweenTime'])) {
                    $filter['betweenTime'] = json_decode($filter['betweenTime'], true);
                }
                $selectFilter .= "AND passTime BETWEEN '" . $filter['betweenTime'][0] . "' AND '" . $filter['betweenTime'][1] . "' ";
            }
            if (isset($filter['equipmentId'])) {
                $selectFilter .= "AND equipmentId = '" . $filter['equipmentId'] . "' ";
            }
            if (isset($filter['equipmentName'])) {
                $selectFilter .= "AND equipmentName LIKE '%" . $filter['equipmentName'] . "%' ";
            }
            if (isset($filter['equipmentType'])) {
                $selectFilter .= "AND equipmentType = '" . $filter['equipmentType'] . "' ";
            }
            if (isset($filter['status'])) {
                $selectFilter .= "AND status = '" . $filter['status'] . "' ";
            }
            if (isset($filter['stationId'])) {
                $selectFilter .= "AND stationId = '" . $filter['stationId'] . "' ";
            }
            if (isset($filter['stationName'])) {
                $selectFilter .= "AND stationName LIKE '%" . $filter['stationName'] . "%' ";
            }
            if (isset($filter['plateType'])) {
                $selectFilter .= "AND plateType = '" . $filter['plateType'] . "' ";
            }
            if (isset($filter['carType'])) {
                $selectFilter .= "AND carType = '" . $filter['carType'] . "' ";
            }
            if (isset($filter['plateImg'])) {
                $selectFilter .= "AND plateImg = '" . $filter['plateImg'] . "' ";
            }
            if (isset($filter['vehicleImg'])) {
                $selectFilter .= "AND vehicleImg = '" . $filter['vehicleImg'] . "' ";
            }
            if (isset($filter['dareaName'])) {
                $selectFilter .= "AND dareaName LIKE '%" . $filter['dareaName'] . "%' ";
            }
        }

        $query = "SELECT * FROM $this->carTable WHERE 1=1 " . $selectPlan . $selectFilter;
        $count = "SELECT count(*) num FROM $this->carTable WHERE 1=1 " . $selectPlan . $selectFilter;

        $page = ($page - 1) * $num;


        $query .= " order by passTime DESC LIMIT $page,$num";

        $count = $this->db->database->query($count)->fetch_assoc()['num'];
        $json = Array('0' => $count);


        $res = $this->db->database->query($query);
        $resNum = 0;

        while ($res->data_seek($resNum)) {
            $data = $res->fetch_assoc();
            array_push($json, $data);
            $resNum++;
        }
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        return $json;
    }

    public function getUserList($page, $num, $filter)
    {
        $permission = $this->user->getPermission()->fetch_assoc()['permission'];

        if ($permission != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        $selectFilter = '';

        if ($filter) {
            if (!is_array($filter)) {
                $filter = json_decode($filter, true);
            }

            if (isset($filter['id'])) {
                $selectFilter .= "AND id = '" . $filter['id'] . "' ";
            }
            if (isset($filter['userName'])) {
                $selectFilter .= "AND userName LIKE '%" . $filter['userName'] . "%' ";
            }
            if (isset($filter['name'])) {
                $selectFilter .= "AND name LIKE '%" . $filter['name'] . "%' ";
            }
            if (isset($filter['gender'])) {
                $selectFilter .= "AND gender = '" . $filter['gender'] . "' ";
            }
            if (isset($filter['email'])) {
                $selectFilter .= "AND email LIKE '%" . $filter['email'] . "%' ";
            }
            if (isset($filter['contact'])) {
                $selectFilter .= "AND contact LIKE '%" . $filter['contact'] . "%' ";
            }
            if (isset($filter['idCard'])) {
                $selectFilter .= "AND idCard = '" . $filter['idCard'] . "' ";
            }
            if (isset($filter['carType'])) {
                $selectFilter .= "AND carType = '" . $filter['carType'] . "' ";
            }
            if (isset($filter['permission'])) {
                $selectFilter .= "AND permission = '" . $filter['permission'] . "' ";
            }
        }

        $query = "SELECT id,userName,`name`,gender,contact,email,idCard,cardType,userImg,permission FROM $this->userTable WHERE 1=1 " . $selectFilter;
        $count = "SELECT count(*) num FROM $this->userTable WHERE 1=1 " . $selectFilter;

        $page = ($page - 1) * $num;


        $query .= " LIMIT $page,$num";

        $count = $this->db->database->query($count)->fetch_assoc()['num'];
        $json = Array('0' => $count);

        $res = $this->db->database->query($query);
        $resNum = 0;

        while ($res->data_seek($resNum)) {
            $data = $res->fetch_assoc();
            array_push($json, $data);
            $resNum++;
        }
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        return $json;
//        if (!$this->rolePermission->checkSelectUser()) {
//            return json_encode(Array('error' => '查询失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//        $page = ($page - 1) * $num;
//        $query = "SELECT u.id,u.userName,u.phone,u.address,u.email,r.name role,a.name area,p.name place,u.sex,u.realName,u.idCard FROM $this->userTable u,$this->areaTable a,$this->placeTable p,$this->roleTable r WHERE u.role = r.role AND a.code = u.area AND p.id = u.place AND a.code = p.area ";
//
//
//        if(strlen($filter) != 0)
//        {
//            switch ($filter){
//                case 'userName': $query .= "AND u.userName LIKE '%$arg%'";break;
//                case 'email': $query .= "AND u.email LIKE '%$arg%'";break;
//                case 'idCard': $query .= "AND u.idCard LIKE '%$arg%'";break;
//                case 'address': $query .= "AND u.address LIKE '%$arg%'";break;
//                case 'role': $query .= "AND r.name LIKE '%$arg%'";break;
//                case 'realName': $query .= "AND u.realName LIKE '%$arg%'";break;
//                case 'phone': $query .= "AND u.phone LIKE '%$arg%'";break;
//                case 'sex': $query .= "AND u.sex LIKE '%$arg%'";break;
//                case 'area': $query .= "AND (a.name LIKE '%$arg%' OR p.name LIKE '%$arg%')";break;
//                default:
//                    return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//            }
//        }
//
//        if(strlen($extend) != 0)
//        {
//            switch ($extend)
//            {
//                case 'notAdmin': $query .= " AND (u.role != '001' AND u.role != '000')"; break;
//                default:
//                    return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//            }
//        }
//
//        $count = $this->db->database->query($query)->num_rows;
//        $json = Array('0'=>$count);
//
//        $query .= " LIMIT $page,$num";
//
//        $res = $this->db->database->query($query);
//        $resNum = 0;
//
//        while ($res->data_seek($resNum)) {
//            $data = $res->fetch_assoc();
//            array_push($json, $data);
//            $resNum++;
//        }
//        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
//        return $json;
    }

    public function getEquipmentList($area, $areaList, $darea, $dareaList, $page, $num, $filter)
    {

        if($this->user->getPermission()->fetch_assoc()['permission'] != '8')
        {
            if ((!$area || strlen($area) == 0) && (!$areaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (strlen($area) != 9 && (!$areaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if ((!$darea || strlen($darea) == 0) && (!$dareaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (strlen(strval($darea)) != 19 && (!$dareaList)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
        }

//      $areaList 格式 ["440511000","110000000"]

        $selectPlan = '';
        $selectFilter = '';

        $roleList = json_decode($this->getRoleList(), true);
        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            if (!$area || strlen($area) == 0) {
                if (!is_array($areaList)) {
                    $areaList = json_decode($areaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";

                foreach ($areaList as $a) {
                    $flag = false;
                    foreach ($roleList as $r) {
//                        if ($r['selectEquipment'] == 0) {
//                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($a) . '：' . $a . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
//                        }

                        if (in_array($a, $r)) {
                            $flag = true;
                            $selectPlan .= "OR area = '$a' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($a) . '：' . $a . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                    }

                }
            } else {
                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($area, $r)) {
                        $flag = true;
//                        if ($r['selectEquipment'] == 0) {
////                            var_dump($area,$r);
//                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($area) . '：' . $area . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
//                        }
                        $selectPlan .= "AND (area = '$area' ";
                        break;
                    }
                }
                if (!$flag) {
                    return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getAreaName($area) . '：' . $area . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                }
            }
            $selectPlan .= ") ";

            if (!$darea || strlen($darea) == 0) {
                if (!is_array($dareaList)) {
                    $dareaList = json_decode($dareaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";
                foreach ($dareaList as $d) {
                    $flag = false;
                    foreach ($roleList as $r) {
//                        if ($r['selectEquipment'] == 0) {
//                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($d) . '：' . $d . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
//                        }

                        if (in_array($d, $r)) {
                            $flag = true;
                            $selectPlan .= "OR darea = '$d' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($d) . '：' . $d . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                    }

                }
            } else {
                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($darea, $r)) {
                        $flag = true;
                        if ($r['selectEquipment'] == 0) {
                            return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($darea) . '：' . $darea . ' 设备信息的权限1'), JSON_UNESCAPED_UNICODE);
                        }
                        $selectPlan .= "AND (darea = '$darea' ";
                        break;
                    }
                }
                if (!$flag) {
                    return json_encode(Array('error' => '查询失败，您没有查询 ' . $this->getDareaName($darea) . '：' . $darea . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                }
            }
            $selectPlan .= ") ";

        } else if ($this->user->getPermission()->fetch_assoc()['permission'] == '8'  &&
            (strlen($area) != 0 || is_array($areaList) || strlen($areaList) !=0) &&
            (strlen($darea) != 0 || is_array($dareaList) || strlen($dareaList) !=0)) {
            if (!$area || strlen($area) == 0) {
                if (!is_array($areaList)) {
                    $areaList = json_decode($areaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";

                foreach ($areaList as $a) {
                    $selectPlan .= "OR area = '$a' ";
                }
            } else {
                $selectPlan .= "AND (area = '$area' ";
            }
            $selectPlan .= ") ";

            if (!$darea || strlen($darea) == 0) {
                if (!is_array($dareaList)) {
                    $dareaList = json_decode($dareaList, true);
                }

                $selectPlan .= "AND ( 1 = 0 ";
                foreach ($dareaList as $d) {
                    $selectPlan .= "OR darea = '$d' ";
                }
            } else {
                $selectPlan .= "AND (darea = '$darea' ";
            }
            $selectPlan .= ") ";

        }

        if ($filter) {
            if (!is_array($filter)) {
                $filter = json_decode($filter, true);
            }

            if (isset($filter['beforeTime'])) {
                $selectFilter .= "AND checkTime < '" . $filter['beforeTime'] . "' ";
            }
            if (isset($filter['afterTime'])) {
                $selectFilter .= "AND checkTime > '" . $filter['afterTime'] . "' ";
            }
            if (isset($filter['betweenTime'])) {
                $selectFilter .= "AND checkTime BETWEEN '" . $filter['betweenTime'][0] . "' AND '" . $filter['betweenTime'][1] . "' ";
            }
            if (isset($filter['equipmentId'])) {
                $selectFilter .= "AND equipmentId LIKE '%" . $filter['equipmentId'] . "%' ";
            }
            if (isset($filter['equipmentName'])) {
                $selectFilter .= "AND equipmentName LIKE '%" . $filter['equipmentName'] . "%' ";
            }
            if (isset($filter['equipmentType'])) {
                $selectFilter .= "AND equipmentType = '" . $filter['equipmentType'] . "' ";
            }
            if (isset($filter['equipmentStatus'])) {
                $selectFilter .= "AND equipmentStatus = '" . $filter['equipmentStatus'] . "' ";
            }
        }

        $query = "SELECT * FROM $this->equipmentTable WHERE 1=1 " . $selectPlan . $selectFilter;
        $count = "SELECT count(*) num FROM $this->equipmentTable WHERE 1=1 " . $selectPlan . $selectFilter;

        $page = ($page - 1) * $num;

        $query .= " order by checkTime DESC LIMIT $page,$num";

        $count = $this->db->database->query($count)->fetch_assoc()['num'];
        $json = Array('0' => $count);


        $res = $this->db->database->query($query);
        $resNum = 0;

        while ($res->data_seek($resNum)) {
            $data = $res->fetch_assoc();
            array_push($json, $data);
            $resNum++;
        }
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        return $json;
//        if (!$this->rolePermission->checkSelectDevice()) {
//            return json_encode(Array('error' => '查询失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//        $page = ($page - 1) * $num;
//        $query = "SELECT d.deviceId,d.name,d.status,a.name area,p.name place,d.liveTime,d.ip FROM $this->deviceTable d,$this->areaTable a,$this->placeTable p WHERE d.area = a.code AND d.place = p.id AND d.area = p.area AND p.area = a.code ";
//
//        if(strlen($filter) != 0)
//        {
//            switch ($filter){
//                case 'beforeTime': $query .= "AND d.liveTime < '".$arg."'";break;//
//                case 'afterTime': $query .= "AND d.liveTime > '".$arg."'";break;
//                case 'betweenTime':
//                    //  JSON格式{"startTime":"2020-03-02 0:0:0","endTime":"2020-03-03 0:0:0"}
//                    $json = json_decode($arg,true);
//                    if(!$json) return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//                    $query .= "AND d.liveTime > '".$json["startTime"]."' AND d.liveTime < '".$json["endTime"]."'";
//                    break;
//                case 'ip': $query .= "AND d.ip LIKE '%$arg%'";break;
//                case 'status': $query .= "AND d.status LIKE '%$arg%'";break;
//                case 'name': $query .= "AND d.name LIKE '%$arg%'";break;
//                case 'area': $query .= "AND (a.name LIKE '%$arg%' OR p.name LIKE '%$arg%')";break;
//                case 'areaLevel' :
//                    //  JSON格式{"area":"4405","place":"00000"}
//                    $json = json_decode($arg,true);
//                    if(!$json) return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//                    $query .= "AND d.area LIKE '".$json['area']."%' AND d.place LIKE '%".$json['place']."%' AND d.name LIKE '%".$json['deviceName']."%'";
//                    break;
//                default:
//                    return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//            }
//        }
//
//        $count = $this->db->database->query($query)->num_rows;
//        $json = Array('0'=>$count);
//
//        $query .= " LIMIT $page,$num";
//        $res = $this->db->database->query($query);
//        $resNum = 0;
//
//        while ($res->data_seek($resNum)) {
//            $data = $res->fetch_assoc();
//            array_push($json, $data);
//            $resNum++;
//        }
//        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
//        return $json;
    }

    public function getPlanList($page, $num, $filter)
    {
        $selectFilter = "";

        if ($filter) {
            if (!is_array($filter)) {
                $filter = json_decode($filter, true);
            }

            if (isset($filter['lowerLevel'])) {
                $selectFilter .= "AND level < '" . $filter['lowerLevel'] . "' ";
            }
            if (isset($filter['higherLevel'])) {
                $selectFilter .= "AND level > '" . $filter['higherLevel'] . "' ";
            }
            if (isset($filter['id'])) {
                $selectFilter .= "AND id = '" . $filter['id'] . "' ";
            }
            if (isset($filter['level'])) {
                $selectFilter .= "AND level = '" . $filter['level'] . "' ";
            }
            if(isset($filter['fuzzy']))
            {
                $selectFilter .= "AND (areaName LIKE '%" . $filter['fuzzy'] . "%' ";
                $selectFilter .= "OR dareaName LIKE '%" . $filter['fuzzy'] . "%') ";
            }
            if (isset($filter['area'])) {
                $selectFilter .= "AND area = '" . $filter['area'] . "' ";
            }
            if (isset($filter['areaName'])) {
                $selectFilter .= "AND areaName LIKE '%" . $filter['areaName'] . "%' ";
            }
            if (isset($filter['darea'])) {
                $selectFilter .= "AND darea = '" . $filter['darea'] . "' ";
            }
            if (isset($filter['dareaName'])) {
                $selectFilter .= "AND dareaName LIKE '%" . $filter['dareaName'] . "%' ";
            }
            if (isset($filter['placeType'])) {
                $selectFilter .= "AND placeType = '" . $filter['placeType'] . "' ";
            }
        }

        $query = "SELECT * FROM $this->planTable WHERE 1=1 " . $selectFilter;
        $count = "SELECT count(*) num FROM $this->planTable WHERE 1=1 " . $selectFilter;

        $page = ($page - 1) * $num;

        $query .= " LIMIT $page,$num";

        $count = $this->db->database->query($count)->fetch_assoc()['num'];
        $json = Array('0' => $count);

        $res = $this->db->database->query($query);
        $resNum = 0;

        while ($res->data_seek($resNum)) {
            $data = $res->fetch_assoc();
            array_push($json, $data);
            $resNum++;
        }
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        return $json;
////        if(!$this->rolePermission->checkEditArea()){
////            return json_encode(Array('error'=>'查询失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
////        }
//        $page = ($page - 1) * $num;
//        $query = "SELECT code area,name,level,parentCode FROM $this->areaTable WHERE 1=1 ";
//        $count = "SELECT count(*) as num FROM $this->areaTable WHERE 1=1 ";
//
//        if(strlen($filter) != 0)
//        {
//            switch ($filter){
//                case 'name': $query .= "AND name LIKE '%$arg%'"; $count .= "AND name LIKE '%$arg%'";break;
//                case 'level': $query .= "AND level = $arg"; $count .= "AND level = $arg";break;
//                case 'areaLevel' :
//                    $json = json_decode($arg,true);
//                    if(!$json) return json_encode(Array('error' => '查询参数错误，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//                    $query .= " AND level = '".$json['level']."' AND parentCode LIKE '".$json['parentCode']."%'";
//                    $count .= " AND level = '".$json['level']."' AND parentCode LIKE '".$json['parentCode']."%'";
//                    break;
//                default:
//                    return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//            }
//        }
//
//        $count = $this->db->database->query($count)->fetch_assoc()['num'];
//        $json = Array('0'=>intval($count));
//
//        $query .= " LIMIT $page,$num";
//        $res = $this->db->database->query($query);
//        $resNum = 0;
//
//        while ($res->data_seek($resNum)) {
//            $data = $res->fetch_assoc();
//            array_push($json, $data);
//            $resNum++;
//        }
//        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
//        return $json;
    }

    public function getAreaList($page, $num, $filter)
    {
        $selectFilter = "";

        if ($filter) {
            if (!is_array($filter)) {
                $filter = json_decode($filter, true);
            }

            if (isset($filter['lowerLevel'])) {
                $selectFilter .= "AND level < '" . $filter['lowerLevel'] . "' ";
            }
            if (isset($filter['higherLevel'])) {
                $selectFilter .= "AND level > '" . $filter['higherLevel'] . "' ";
            }
            if (isset($filter['level'])) {
                $selectFilter .= "AND level = '" . $filter['level'] . "' ";
            }
            if (isset($filter['area'])) {
                $selectFilter .= "AND area = '" . $filter['area'] . "' ";
            }
            if (isset($filter['areaLike'])) {
                $selectFilter .= "AND area LIKE '" . $filter['areaLike'] . "%' ";
            }
            if (isset($filter['areaName'])) {
                $selectFilter .= "AND areaName LIKE '%" . $filter['areaName'] . "%' ";
            }
            if(isset($filter['fuzzy']))
            {
                $selectFilter .= "AND area LIKE '" . $filter['fuzzy'] . "%' ";
            }
        }

        $query = "SELECT DISTINCT area,areaName,`level` FROM $this->planTable WHERE 1=1 " . $selectFilter;
        $count = "SELECT count(*) num FROM $this->planTable WHERE 1=1 " . $selectFilter;

        $page = ($page - 1) * $num;

        $query .= " LIMIT $page,$num";

        $count = $this->db->database->query($count)->fetch_assoc()['num'];
        $json = Array('0' => $count);

        $res = $this->db->database->query($query);
        $resNum = 0;

        while ($res->data_seek($resNum)) {
            $data = $res->fetch_assoc();
            array_push($json, $data);
            $resNum++;
        }
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        return $json;
////        if(!$this->rolePermission->checkEditArea()){
////            return json_encode(Array('error'=>'查询失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
////        }
//        $page = ($page - 1) * $num;
//        $query = "SELECT code area,name,level,parentCode FROM $this->areaTable WHERE 1=1 ";
//        $count = "SELECT count(*) as num FROM $this->areaTable WHERE 1=1 ";
//
//        if(strlen($filter) != 0)
//        {
//            switch ($filter){
//                case 'name': $query .= "AND name LIKE '%$arg%'"; $count .= "AND name LIKE '%$arg%'";break;
//                case 'level': $query .= "AND level = $arg"; $count .= "AND level = $arg";break;
//                case 'areaLevel' :
//                    $json = json_decode($arg,true);
//                    if(!$json) return json_encode(Array('error' => '查询参数错误，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//                    $query .= " AND level = '".$json['level']."' AND parentCode LIKE '".$json['parentCode']."%'";
//                    $count .= " AND level = '".$json['level']."' AND parentCode LIKE '".$json['parentCode']."%'";
//                    break;
//                default:
//                    return json_encode(Array('error' => '查询失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//            }
//        }
//
//        $count = $this->db->database->query($count)->fetch_assoc()['num'];
//        $json = Array('0'=>intval($count));
//
//        $query .= " LIMIT $page,$num";
//        $res = $this->db->database->query($query);
//        $resNum = 0;
//
//        while ($res->data_seek($resNum)) {
//            $data = $res->fetch_assoc();
//            array_push($json, $data);
//            $resNum++;
//        }
//        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
//        return $json;
    }


    /*
     * 添加记录
     */
    public function addCar($area, $darea, $data)
    {

        if (strlen($area) != 9) {
            return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen(strval($darea)) != 19) {
            return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if ($area !== substr($darea, 0, 9)) {
            return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            $f = new FileManager();
            if (!isset($data['passTime'])) {
                return json_encode(Array('error' => 'passTime 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['plateNum'])) {
                return json_encode(Array('error' => 'plateNum 参数错误'), JSON_UNESCAPED_UNICODE);
            } else {
                if (isset($data['vehicleImg']) && strlen($data['vehicleImg']) != 0) {
                    $data['vehicleImg'] = json_decode($f->uploadImage($data['vehicleImg'], 'car', $data['passTime'], $data['plateNum'].'_车辆'), true)[0];
                }
                if (isset($data['plateImg']) && strlen($data['plateImg']) != 0) {
                    $data['plateImg'] = json_decode($f->uploadImage($data['plateImg'], 'car', $data['passTime'], $data['plateNum'].'_车牌'), true)[0];
                }
            }
            if (!isset($data['equipmentId'])) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if ($area !== substr($data['equipmentId'], 0, 9)) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['status']) || strlen($data['status']) != 1) {
                return json_encode(Array('error' => 'status 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['visitor']) && is_array($data['visitor'])) {
                $data['visitor'] = json_encode($data['visitor'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['driverData']) && is_array($data['driverData'])) {
                $data['driverData'] = json_encode($data['driverData'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['passengerData']) && is_array($data['passengerData'])) {
                $data['passengerData'] = json_encode($data['passengerData'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        $roleList = json_decode($this->getRoleList(), true);

        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            $flag = false;
            foreach ($roleList as $r) {
                if (in_array($area, $r)) {
                    $flag = true;
                    if ($r['addCar'] == 0) {
                        return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getAreaName($area) . '：' . $area . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                    $areaName = $this->getAreaName($area);
                    break;
                }
            }
            if (!$flag) {
                return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getAreaName($area) . '：' . $area . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
            }

            $flag = false;
            foreach ($roleList as $r) {
                if (in_array($darea, $r)) {
                    $flag = true;
                    if ($r['addCar'] == 0) {
                        return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getDareaName($darea) . '：' . $darea . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                    $dareaName = $this->getDareaName($darea);
                    break;
                }
            }

            if (!$flag) {
                return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getDareaName($darea) . '：' . $darea . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
            }
        } else {
            $areaName = $this->getAreaName($area);
            $dareaName = $this->getDareaName($darea);
        }

        if (strlen(mb_split(':', $dareaName)[1]) == 0) {
            return json_encode(Array('error' => '操作失败，该场所不存在'), JSON_UNESCAPED_UNICODE);
        }

        $query = "INSERT INTO $this->carTable(`passTime`, `plateNum`, `plateColor`, `vehicleType`, `vehicleImg`, `area`, `x`, `y`, `equipmentId`, `equipmentName`, `equipmentType`, `stationId`, `stationName`, `location`, `vehicleColor`, `status`, `darea`, `dareaName`, `placeType`, `carType`, `visitReason`, `visitor`, `driverData`, `passengerData`,`plateImg`) VALUES ('{$data['passTime']}', '{$data['plateNum']}', '{$data['plateColor']}', '{$data['vehicleType']}', '{$data['vehicleImg']}', '{$area}', '{$data['x']}', '{$data['y']}', '{$data['equipmentId']}', '{$data['equipmentName']}', '{$data['equipmentType']}', '{$data['stationId']}', '{$data['stationName']}', '{$data['location']}', '{$data['vehicleColor']}', '{$data['status']}', '{$darea}', '{$dareaName}', '{$data['placeType']}', '{$data['carType']}', '{$data['visitReason']}', '{$data['visitor']}', '{$data['driverData']}', '{$data['passengerData']}', '{$data['plateImg']}')";

        $res = $this->db->database->query($query);
        $err = $this->db->database->errno;

        if ($err == 1062) {
            if (isset($data['vehicleImg']) && strlen($data['vehicleImg']) != 0) {
                $f->deleteImage($data['vehicleImg'], 'car');
            }
            if (isset($data['plateImg']) && strlen($data['plateImg']) != 0) {
                $f->deleteImage($data['plateImg'], 'car');
            }
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }

        if ($res && $this->db->database->affected_rows == 1) {

            $carRecord = $this->db->database->query("SELECT count(*) num FROM $this->carTable")->fetch_assoc()['num'];

            if($carRecord > $this->max_record)
            {
                $maxNum = intval(($carRecord - $this->max_record));

                $maxPage = ceil(($maxNum)/5000);

                for($page=0;$page<$maxPage;$page++)
                {
                    if($maxNum>5000)
                    {
                        $num = 5000;
                        $maxNum -= 5000;
                    }
                    else{
                        $num = $maxNum;
                    }

                    $carData = Array();
                    $res = $this->db->database->query("SELECT vehicleImg,plateImg FROM $this->carTable WHERE 1=1 ORDER BY passTime ASC LIMIT $page,$num");

                    $resNum = 0;
                    while ($res->data_seek($resNum)) {
                        $data = $res->fetch_assoc();
                        array_push($carData, $data);
                        $resNum++;
                    }

                    $res = $this->db->database->query("DELETE FROM $this->carTable WHERE 1=1 ORDER BY passTime ASC LIMIT $num");
                    if ($res && $this->db->database->affected_rows >= 1) {
                        $f = new FileManager();
                        foreach ($carData as $t) {
                            if (isset($t['vehicleImg']) && strlen($t['vehicleImg']) != 0) {
                                $f->deleteImage($t['vehicleImg'], 'car');
                            }
                            if (isset($t['plateImg']) && strlen($t['plateImg']) != 0) {
                                $f->deleteImage($t['plateImg'], 'car');
                            }
                        }
                    }
                }

            }

            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            if (isset($data['vehicleImg']) && strlen($data['vehicleImg']) != 0) {
                $f->deleteImage($data['vehicleImg'], 'car');
            }
            if (isset($data['plateImg']) && strlen($data['plateImg']) != 0) {
                $f->deleteImage($data['plateImg'], 'car');
            }
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function addPersonnel($area, $darea, $data)
    {

        if (strlen($area) != 9) {
            return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen(strval($darea)) != 19) {
            return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if ($area !== substr($darea, 0, 9)) {
            return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            $f = new FileManager();
            if (!isset($data['passTime'])) {
                return json_encode(Array('error' => 'passTime 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['name'])) {
                return json_encode(Array('error' => 'name 参数错误'), JSON_UNESCAPED_UNICODE);
            } else {
                if (isset($data['personImg']) && strlen($data['personImg']) != 0) {
                    $data['personImg'] = json_decode($f->uploadImage($data['personImg'], 'personnel', $data['passTime'], $data['name'] . '_现场'), true)[0];
                }
            }
            if (!isset($data['idCard'])) {
                return json_encode(Array('error' => 'idCard 参数错误'), JSON_UNESCAPED_UNICODE);
            } else {
                if (isset($data['idCardImg']) && strlen($data['idCardImg']) != 0) {
                    $data['idCardImg'] = json_decode($f->uploadImage($data['idCardImg'], 'personnel', $data['passTime'], $data['name'] . '_证件'), true)[0];
                }
            }
            if (isset($data['countryOrAreaCode']) && (strlen($data['countryOrAreaCode']) != 3)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['equipmentId'])) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else if ($area !== substr($data['equipmentId'], 0, 9)) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['status']) || strlen($data['status']) != 1) {
                return json_encode(Array('error' => 'status 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['placeType']) && (strlen($data['placeType']) != 2 || $data['placeType'] !== substr($darea, 13, 2))) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['visitor']) && is_array($data['visitor'])) {
                $data['visitor'] = json_encode($data['visitor'], JSON_UNESCAPED_UNICODE);
            }

        } else {
            return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
        }

        $roleList = json_decode($this->getRoleList(), true);

        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            $flag = false;
            foreach ($roleList as $r) {
                if (in_array($area, $r)) {
                    $flag = true;
                    if ($r['addPersonnel'] == 0) {
                        return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getAreaName($area) . '：' . $area . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                    $areaName = $this->getAreaName($area);
                    break;
                }
            }
            if (!$flag) {
                return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getAreaName($area) . '：' . $area . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
            }

            $flag = false;
            foreach ($roleList as $r) {
                if (in_array($darea, $r)) {
                    $flag = true;
                    if ($r['addPersonnel'] == 0) {
                        return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getDareaName($darea) . '：' . $darea . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                    $dareaName = $this->getDareaName($darea);
                    break;
                }
            }

            if (!$flag) {
                return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getDareaName($darea) . '：' . $darea . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
            }
        } else {
            $areaName = $this->getAreaName($area);
            $dareaName = $this->getDareaName($darea);
        }

        if (strlen(mb_split(':', $dareaName)[1]) == 0) {
            return json_encode(Array('error' => '操作失败，该场所不存在'), JSON_UNESCAPED_UNICODE);
        }

        $query = "INSERT INTO $this->personnelTable(`passTime`, `name`, `gender`, `nation`, `idCard`, `cardType`, `countryOrAreaCode`, `countryOrAreaName`, `cardVersion`, `currentApplyOrgCode`, `signNum`, `birthDay`, `address`, `authority`, `validtyStart`, `validtyEnd`, `personImg`, `idCardImg`, `temp`, `area`, `x`, `y`, `equipmentId`, `equipmentName`, `equipmentType`, `stationId`, `stationName`, `location`, `status`, `darea`, `dareaName`, `placeType`, `identity`, `homePlace`, `contact`, `isConsist`, `compareScore`, `openMode`, `visitReason`, `mac`, `imsi`, `imei`, `visitor`) VALUES ('{$data['passTime']}', '{$data['name']}', '{$data['gender']}', '{$data['nation']}', '{$data['idCard']}', '{$data['cardType']}', '{$data['countryOrAreaCode']}', '{$data['countryOrAreaName']}', '{$data['cardVersion']}', '{$data['currentApplyOrgCode']}', '{$data['signNum']}', '{$data['birthDay']}', '{$data['address']}', '{$data['authority']}', '{$data['validtyStart']}', '{$data['validtyEnd']}', '{$data['personImg']}', '{$data['idCardImg']}', '{$data['temp']}', '{$area}', '{$data['x']}', '{$data['y']}', '{$data['equipmentId']}', '{$data['equipmentName']}', '{$data['equipmentType']}', '{$data['stationId']}', '{$data['stationName']}', '{$data['location']}', '{$data['status']}', '{$darea}', '{$dareaName}', '{$data['placeType']}', '{$data['identity']}', '{$data['homePlace']}', '{$data['contact']}', '{$data['isConsist']}', '{$data['compareScore']}', '{$data['openMode']}', '{$data['visitReason']}', '{$data['mac']}', '{$data['imsi']}', '{$data['imei']}', '{$data['visitor']}')";

//        die($query);
        $res = $this->db->database->query($query);
        $err = $this->db->database->errno;

        if ($err == 1062) {
            if (isset($data['personImg']) && strlen($data['personImg']) != 0) {
                $f->deleteImage($data['personImg'], 'personnel');
            }
            if (isset($data['idCardImg']) && strlen($data['idCardImg']) != 0) {
                $f->deleteImage($data['idCardImg'], 'personnel');
            }
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows == 1) {

            $personnelRecord = $this->db->database->query("SELECT count(*) num FROM $this->personnelTable")->fetch_assoc()['num'];

            if($personnelRecord > $this->max_record)
            {


                $maxNum = intval(($personnelRecord - $this->max_record));

                $maxPage = ceil(($maxNum)/5000);

                for($page=0;$page<$maxPage;$page++)
                {
                    if($maxNum>5000)
                    {
                        $num = 5000;
                        $maxNum -= 5000;
                    }
                    else{
                        $num = $maxNum;
                    }

                    $personnelData = Array();
                    $res = $this->db->database->query("SELECT personImg,idCardImg FROM $this->personnelTable WHERE 1=1 ORDER BY passTime ASC LIMIT $page,$num");

                    $resNum = 0;
                    while ($res->data_seek($resNum)) {
                        $data = $res->fetch_assoc();
                        array_push($personnelData, $data);
                        $resNum++;
                    }

                    $res = $this->db->database->query("DELETE FROM $this->personnelTable WHERE 1=1 ORDER BY passTime ASC LIMIT $num");
                    if ($res && $this->db->database->affected_rows >= 1) {
                        $f = new FileManager();
                        foreach ($personnelData as $t) {
                            if (isset($t['personImg']) && strlen($t['personImg']) != 0) {
                                $f->deleteImage($t['personImg'], 'personnel');
                            }
                            if (isset($t['idCardImg']) && strlen($t['idCardImg']) != 0) {
                                $f->deleteImage($t['idCardImg'], 'personnel');
                            }
                        }
                    }
                }

            }
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            if (isset($data['personImg']) && strlen($data['personImg']) != 0) {
                $f->deleteImage($data['personImg'], 'personnel');
            }
            if (isset($data['idCardImg']) && strlen($data['idCardImg']) != 0) {
                $f->deleteImage($data['idCardImg'], 'personnel');
            }
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function addEquipment($area, $darea, $data)
    {

        if (strlen($area) != 9) {
            return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen(strval($darea)) != 19) {
            return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if ($area !== substr($darea, 0, 9)) {
            return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if (!isset($data['equipmentId']) || strlen($data['equipmentId']) != 18) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if ($area != substr($data['equipmentId'], 0, 9)) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['equipmentName'])) {
                return json_encode(Array('error' => 'equipmentName 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['equipmentType']) || strlen($data['equipmentType']) != 2 || $data['equipmentType'] !== substr($data['equipmentId'], 13, 2)) {
                return json_encode(Array('error' => 'equipmentType 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['equipmentStatus']) || strlen($data['equipmentStatus']) != 1) {
                return json_encode(Array('error' => 'status 参数错误'), JSON_UNESCAPED_UNICODE);
            }

        } else {
            return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
        }

        $roleList = json_decode($this->getRoleList(), true);

        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            $flag = false;
            foreach ($roleList as $r) {
                if (in_array($area, $r)) {
                    $flag = true;
                    if ($r['addEquipment'] == 0) {
                        return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getAreaName($area) . '：' . $area . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                    $areaName = $this->getAreaName($area);
                    break;
                }
            }
            if (!$flag) {
                return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getAreaName($area) . '：' . $area . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
            }

            $flag = false;
            foreach ($roleList as $r) {
                if (in_array($darea, $r)) {
                    $flag = true;
                    if ($r['addEquipment'] == 0) {
                        return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getDareaName($darea) . '：' . $darea . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                    $dareaName = $this->getDareaName($darea);
                    break;
                }
            }

            if (!$flag) {
                return json_encode(Array('error' => '操作失败，您没有添加 ' . $this->getDareaName($darea) . '：' . $darea . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
            }
        } else {
            $areaName = $this->getAreaName($area);
            $dareaName = $this->getDareaName($darea);
        }

        if (strlen(mb_split(':', $dareaName)[1]) == 0) {
            return json_encode(Array('error' => '操作失败，该场所不存在'), JSON_UNESCAPED_UNICODE);
        }

        $query = "INSERT INTO $this->equipmentTable(`equipmentId`, `equipmentName`, `equipmentType`, `area`, `areaName`, `darea`, `dareaName`, `equipmentStatus`, `checkTime`, `remark`,`address`) VALUES ('{$data['equipmentId']}', '{$data['equipmentName']}', '{$data['equipmentType']}', '{$area}', '{$areaName}', '{$darea}', '{$dareaName}', '{$data['equipmentStatus']}', '0000-00-00 00:00:00', '{$data['remark']}','{$data['address']}')";
//        die($query);

        $res = $this->db->database->query($query);
        $err = $this->db->database->errno;

        if ($err == 1062) {
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows == 1) {
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }


    }

//    public function addDevice($id, $name, $area, $place, $ip)
//    {
//        if (!$this->rolePermission->checkAddDevice()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "INSERT INTO $this->deviceTable(`deviceID`, `name`, `status`, `area`, `place`, `liveTime`, `ip`) VALUES ('$id', '$name', 0, '$area', '$place',from_unixtime(" . time() . ") , '$ip')";
//
//        $res = $this->db->database->query($query);
//
//        $err = $this->db->database->errno;
//        if ($err == 1062) {
//            return json_encode(Array('error' => '操作失败,设备id与已有设备重复'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($err == 1452) {
//            return json_encode(Array('error' => '操作失败,区域 或 场所不存在'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }

    public function addUser($data)
    {

        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if (!isset($data['userName'])) {
                return json_encode(Array('error' => 'userName 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['password'])) {
                return json_encode(Array('error' => 'password 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['permission']) || strlen($data['permission']) != 1) {
                return json_encode(Array('error' => 'permission 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['idCard'])) {
                return json_encode(Array('error' => 'idCard 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['cardType']) || strlen($data['cardType']) != 1) {
                return json_encode(Array('error' => 'cardType 参数错误'), JSON_UNESCAPED_UNICODE);
            }
        }

        $id = trim(guid(), '{}');

        $salt = ''; // 随机加密密钥
        while (strlen($salt) < 6) {
            $x = mt_rand(0, 9);
            $salt .= $x;
        }

        $data['password'] = sha1($data['password'] . $salt); // sha1哈希加密

        $f = new FileManager();
        if (strlen($data['userImg']) != 0) {
            $data['userImg'] = json_decode($f->uploadUserImage($data['userImg']), true)[0];
        }

        $query = "INSERT INTO " . $this->userTable . "(`id`, `userName`, `salt`, `password`, `name`, `gender`, `contact`, `idCard`,`cardType`,`userImg`,`email`,`permission`) VALUES ('{$id}', '{$data['userName']}', '{$salt}', '{$data['password']}', '{$data['name']}', '{$data['gender']}', '{$data['contact']}', '{$data['idCard']}', '{$data['cardType']}','{$data['userImg']}', '{$data['email']}','{$data['permission']}')";

        $res = $this->db->database->query($query);

        $err = $this->db->database->errno;

        if ($err == 1062) {
            if (isset($data['userImg']) && strlen($data['userImg']) != 0) {
                $f->deleteImage($data['userImg'], 'user');
            }
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows == 1) {
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            if (isset($data['userImg']) && strlen($data['userImg']) != 0) {
                $f->deleteImage($data['userImg'], 'user');
            }
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function addRole($area, $darea, $data)
    {

        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen($area) != 9) {
            return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen(strval($darea)) != 19) {
            return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if ($area !== substr($darea, 0, 9)) {
            return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if (!isset($data['id']) || strlen($data['id']) != 36) {
                if (json_decode($this->getUserList(1, 1, ['id' => $data['id']]), true)[0] == 0) {
                    return json_encode(Array('error' => '此 id 不存在'), JSON_UNESCAPED_UNICODE);
                }
                return json_encode(Array('error' => 'id 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['addPersonnel']) || strlen($data['addPersonnel']) != 1) {
                return json_encode(Array('error' => 'addPersonnel 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['delPersonnel']) || strlen($data['delPersonnel']) != 1) {
                return json_encode(Array('error' => 'delPersonnel 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['updatePersonnel']) || strlen($data['updatePersonnel']) != 1) {
                return json_encode(Array('error' => 'updatePersonnel 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['selectPersonnel']) || strlen($data['selectPersonnel']) != 1) {
                return json_encode(Array('error' => 'selectPersonnel 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['addCar']) || strlen($data['addCar']) != 1) {
                return json_encode(Array('error' => 'addCar 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['delCar']) || strlen($data['delCar']) != 1) {
                return json_encode(Array('error' => 'delCar 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['updateCar']) || strlen($data['updateCar']) != 1) {
                return json_encode(Array('error' => 'updateCar 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['selectCar']) || strlen($data['selectCar']) != 1) {
                return json_encode(Array('error' => 'selectCar 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['addEquipment']) || strlen($data['addEquipment']) != 1) {
                return json_encode(Array('error' => 'addEquipment 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['delEquipment']) || strlen($data['delEquipment']) != 1) {
                return json_encode(Array('error' => 'delEquipment 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['updateEquipment']) || strlen($data['updateEquipment']) != 1) {
                return json_encode(Array('error' => 'updateEquipment 参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['selectEquipment']) || strlen($data['selectEquipment']) != 1) {
                return json_encode(Array('error' => 'selectEquipment 参数错误'), JSON_UNESCAPED_UNICODE);
            }
        }

        $areaName = $this->getAreaName($area);
        $dareaName = $this->getDareaName($darea);

        if (strlen(mb_split(':', $dareaName)[1]) == 0) {
            return json_encode(Array('error' => '操作失败，该场所不存在'), JSON_UNESCAPED_UNICODE);
        }

        $query = "INSERT INTO $this->roleTable(`id`, `areaCode`, `areaName`, `dareaCode`, `dareaName`, `addPersonnel`, `delPersonnel`, `updatePersonnel`, `selectPersonnel`, `addCar`, `delCar`, `updateCar`, `selectCar`, `addEquipment`, `updateEquipment`, `delEquipment`, `selectEquipment`) VALUES ('{$data['id']}', '{$area}', '{$areaName}', '{$darea}', '{$dareaName}', '{$data['addPersonnel']}', '{$data['delPersonnel']}', '{$data['updatePersonnel']}', '{$data['selectPersonnel']}', '{$data['addCar']}', '{$data['delCar']}', '{$data['updateCar']}', '{$data['selectCar']}', '{$data['addEquipment']}', '{$data['updateEquipment']}', '{$data['delEquipment']}', '{$data['selectEquipment']}');";
//        if (!$this->rolePermission->checkEditRole()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($id) != 3) {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "INSERT INTO $this->roleTable(`role`, `name`) VALUES ('$id', '$name')";
//        $addRoleRes = $this->db->database->query($query);
//        $query = "INSERT INTO $this->rolePermissionTable(`role`, `login`, `addUser`, `deleteUser`, `updateUser`, `SelectUser`, `addDevice`, `deleteDevice`, `updateDevice`, `SelectDevice`, `addPersonnel`, `deletePersonnel`, `updatePersonnel`, `SelectPersonnel`, `addAdmin`, `deleteAdmin`, `editArea`, `editPlace`, `editRole`, `editRolePermission`, `addCar`, `deleteCar`, `updateCar`, `SelectCar`) VALUES ('$id', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)";
//        $addRolePermissionRes = $this->db->database->query($query);
//
//        if ($this->db->database->errno == 1062) {
//            return json_encode(Array('error' => '操作失败，id 或 名称重复'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($addRoleRes && $addRolePermissionRes) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }

        $res = $this->db->database->query($query);

        $err = $this->db->database->errno;

        if ($err == 1062) {
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows == 1) {
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function addPlan($data)
    {
        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if (!isset($data['area']) || strlen($data['area']) != 9) {
                return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['darea']) || strlen($data['darea']) != 19) {
                return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
            }
            if ($data['area'] !== substr($data['darea'], 0, 9)) {
                return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
            }

            if (isset($data['addArea'])) {
                if (!isset($data['areaName']) || strlen($data['areaName']) == 0) {
                    return json_encode(Array('error' => 'areaName 参数错误'), JSON_UNESCAPED_UNICODE);
                }
                if (!isset($data['level']) || strlen($data['level']) != 1) {
                    return json_encode(Array('error' => 'level 参数错误'), JSON_UNESCAPED_UNICODE);
                }
                $data['placeType'] = '99';
                if ($data['placeType'] !== substr($data['darea'], 13, 2)) {
                    return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
                }
                if(json_decode($this->getAreaList(1, 0, ['area' => $data['area']]), true)[0] != 0)
                {
                    return json_encode(Array('error' => '该区域代码已存在'), JSON_UNESCAPED_UNICODE);
                }
                $data['dareaName'] = '默认';
            } else if (isset($data['addDarea'])) {
                $areaData = json_decode($this->getAreaList(1, 1, ['area' => $data['area']]), true)[1];
                $data['areaName'] = $areaData['areaName'];
                $data['level'] = $areaData['level'];
                if (!isset($data['placeType']) || $data['placeType'] !== substr($data['darea'], 13, 2)) {
                    return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
                }
                if (!isset($data['dareaName']) || strlen($data['dareaName']) == 0) {
                    return json_encode(Array('error' => '场所名称不能为空'), JSON_UNESCAPED_UNICODE);
                }
            } else {
                return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if (isset($data['id'])) {
                if (strlen($data['id']) != 36) {
                    return json_encode(Array('error' => 'id 错误'), JSON_UNESCAPED_UNICODE);
                }

                if (json_decode($this->getUserList(1, 1, ['id' => $data['id']]), true)[0] == 0) {
                    return json_encode(Array('error' => '此 id 不存在'), JSON_UNESCAPED_UNICODE);
                }
            }

        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }


        $query = "INSERT INTO $this->planTable(`area`, `areaName`, `level`, `darea`, `dareaName`, `placeType`, `id`,`person`,`personContact`) VALUES ('{$data['area']}', '{$data['areaName']}', '{$data['level']}', '{$data['darea']}', '{$data['dareaName']}', '{$data['placeType']}', '{$data['id']}','{$data['person']}','{$data['personContact']}')";

        $res = $this->db->database->query($query);

//        echo $query;

        $err = $this->db->database->errno;

        if ($err == 1062) {
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows == 1) {
            if(isset($data['id'])){
                $this->addRole($data['area'],$data['darea'],
                    [
                        'id'=>$data['id'],
                        'selectPersonnel'=>'1',
                        'addPersonnel'=>'1',
                        'updatePersonnel'=>'1',
                        'delPersonnel'=>'1',
                        'selectCar'=>'1',
                        'addCar'=>'1',
                        'updateCar'=>'1',
                        'delCar'=>'1',
                        'selectEquipment'=>'1',
                        'addEquipment'=>'1',
                        'updateEquipment'=>'1',
                        'delEquipment'=>'1',
                    ]
                );
            }

            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }
//        if (!$this->rolePermission->checkEditPlace()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($id) != 5) {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//        $query = "INSERT INTO $this->placeTable(`id`, `area`, `name`) VALUES ('$id', '$area', '$name');";
//
//        $res = $this->db->database->query($query);
//        $err = $this->db->database->errno;
//
//        if ($err == 1452) {
//            return json_encode(Array('error' => '操作失败,区域不存在'), JSON_UNESCAPED_UNICODE);
//        }
//        if ($err == 1062) {
//            return json_encode(Array('error' => '操作失败,场所id已被使用'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
    }

    /*
     * 更新记录
     */
//    public function updateRolePermission($role,$login,$addUser,$deleteUser,$updateUser,$SelectUser,$addDevice,$deleteDevice,$updateDevice,$SelectDevice,$addPersonnel,$deletePersonnel,$updatePersonnel,$SelectPersonnel,$addAdmin,$deleteAdmin,$editArea,$editPlace,$editRole,$editRolePermission,$addCar,$deleteCar,$updateCar,$SelectCar)
//    {
//        if (!$this->rolePermission->checkEditRolePermission()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if($role == '000'){
//            return json_encode(Array('error' => '操作失败，不可修改超级管理员的权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "SELECT role FROM $this->userTable WHERE id='".$_SESSION['uid']."'";
//        $res = $this->db->database->query($query)->fetch_assoc()['role'];
//
//        if($res == $role && ($res != '000' || $res != '001'))
//        {
//            return json_encode(Array('error' => '操作失败，你无权修改自身用户组的权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if($addUser == '1' && !$this->rolePermission->checkAddUser())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }
//        else if($deleteUser == '1' && !$this->rolePermission->checkDeleteUser())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($updateUser == '1' && !$this->rolePermission->checkUpdateUser())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($SelectUser == '1' && !$this->rolePermission->checkSelectUser())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($addDevice == '1' && !$this->rolePermission->checkAddDevice())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($deleteDevice == '1' && !$this->rolePermission->checkDeleteDevice())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($updateDevice == '1' && !$this->rolePermission->checkUpdateDevice())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($SelectDevice == '1' && !$this->rolePermission->checkSelectDevice())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($addPersonnel == '1' && !$this->rolePermission->checkAddPersonnel())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($deletePersonnel == '1' && !$this->rolePermission->checkDeletePersonnel())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($updatePersonnel == '1' && !$this->rolePermission->checkUpdatePersonnel())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($SelectPersonnel == '1' && !$this->rolePermission->checkSelectPersonnel())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($addAdmin == '1' && !$this->rolePermission->checkAddAdmin())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($deleteAdmin == '1' && !$this->rolePermission->checkDeleteAdmin())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($editArea == '1' && !$this->rolePermission->checkEditArea())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($editPlace == '1' && !$this->rolePermission->checkEditPlace())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($editRole == '1' && !$this->rolePermission->checkEditRole())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($editRolePermission == '1' && !$this->rolePermission->checkEditRolePermission())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($addCar == '1' && !$this->rolePermission->checkAddCar())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($deleteCar == '1' && !$this->rolePermission->checkDeleteCar())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($updateCar == '1' && !$this->rolePermission->checkUpdateCar())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }else if($SelectCar == '1' && !$this->rolePermission->checkSelectCar())
//        {
//            return json_encode(Array('error' => '操作失败，你无法给予比你用户组权限更大的权限'), JSON_UNESCAPED_UNICODE);
//        }
//
////        $role = $this->db->database->query("SELECT r.role FROM $this->roleTable r,$this->rolePermissionTable rp WHERE r.name = '$role'")->fetch_assoc()['role'];
//
//        $query = "UPDATE $this->rolePermissionTable SET `login` = $login, `addUser` = $addUser, `deleteUser` = $deleteUser, `updateUser` = $updateUser, `SelectUser` = $SelectUser, `addDevice` = $addDevice, `deleteDevice` = $deleteDevice, `updateDevice` = $updateDevice, `SelectDevice` = $SelectDevice, `addPersonnel` = $addPersonnel, `deletePersonnel` = $deletePersonnel, `updatePersonnel` = $updatePersonnel, `SelectPersonnel` = $SelectPersonnel, `addAdmin` = $addAdmin, `deleteAdmin` = $deleteAdmin, `editArea` = $editArea, `editPlace` = $editPlace, `editRole` = $editRole, `editRolePermission` = $editRolePermission, `addCar` = $addCar, `deleteCar` = $deleteCar, `updateCar` = $updateCar, `SelectCar` = $SelectCar WHERE `role` = '$role'";
//
//        $res = $this->db->database->query($query);
//
//        if ($res && ($this->db->database->affected_rows == 0)) {
//            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && ($this->db->database->affected_rows == 1)) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//    }

    public function updateRole($data)
    {
        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if (!isset($data['area']) || strlen($data['area']) != 9) {
                return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['darea']) || strlen($data['darea']) != 19) {
                return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
            }
            if ($data['area'] !== substr($data['darea'], 0, 9)) {
                return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
            }

            if (isset($data['id'])) {
                if (strlen($data['id']) != 36) {
                    return json_encode(Array('error' => 'id 错误'), JSON_UNESCAPED_UNICODE);
                }

                if (json_decode($this->getUserList(1, 1, ['id' => $data['id']]), true)[0] == 0) {
                    return json_encode(Array('error' => '此 id 不存在'), JSON_UNESCAPED_UNICODE);
                }
            }

            if (!isset($data['addPersonnel']) || strlen($data['addPersonnel']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['delPersonnel']) || strlen($data['selectPersonnel']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['updatePersonnel']) || strlen($data['selectPersonnel']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['selectPersonnel']) || strlen($data['selectPersonnel']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['addCar']) || strlen($data['addCar']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['delCar']) || strlen($data['selectCar']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['updateCar']) || strlen($data['selectCar']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['selectCar']) || strlen($data['selectCar']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['addEquipment']) || strlen($data['addEquipment']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['delEquipment']) || strlen($data['selectEquipment']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['updateEquipment']) || strlen($data['selectEquipment']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }
            if (!isset($data['selectEquipment']) || strlen($data['selectEquipment']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            }

        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        if (json_decode($this->getRoleList(['area' => $data['area'], 'darea' => $data['darea'], 'id' => $data['id'],'page'=>'1','num'=>'9999999999999']), true)[0] == 0) {
            return json_encode(Array('error' => '该用户无此地区/场所权限，请先添加后再进行此操作'), JSON_UNESCAPED_UNICODE);
        }

        $areaName = $this->getAreaName($data['area']);
        $dareaName = $this->getDareaName($data['darea']);


        $query = "UPDATE $this->roleTable SET `areaName` = '{$areaName}', `dareaName` = '{$dareaName}', `addPersonnel` = '${data["addPersonnel"]}', `delPersonnel` = '{$data['delPersonnel']}', `updatePersonnel` = '{$data['updatePersonnel']}', `selectPersonnel` = '{$data['selectPersonnel']}', `addCar` = '{$data['addCar']}', `delCar` = '{$data['delCar']}', `updateCar` = '{$data['updateCar']}', `selectCar` = '{$data['selectCar']}', `addEquipment` = '{$data['addEquipment']}', `updateEquipment` = '{$data['updateEquipment']}', `delEquipment` = '{$data['delEquipment']}', `selectEquipment` = '{$data['selectEquipment']}' WHERE `id` = '{$data['id']}' AND `areaCode` = '{$data['area']}' AND `dareaCode` = '{$data['darea']}'";

        $res = $this->db->database->query($query);

        $err = $this->db->database->errno;

        if ($err == 1062) {
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows == 1) {
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('info' => '数据未变更'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function updateUser($data)
    {
        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        $f = new FileManager();

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            $query = "UPDATE $this->userTable SET ";
            $queryArg = "";
            $queryWhere = "WHERE 1=1 ";

            if (!isset($data['id'])) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else {
                if (strlen($data['id']) != 36) {
                    return json_encode(Array('error' => 'id 错误'), JSON_UNESCAPED_UNICODE);
                } else {
                    $userData = json_decode($this->getUserList(1, 1, ['id' => $data['id']]), true);
                    if ($userData[0] == 0) {
                        return json_encode(Array('error' => '此 id 不存在'), JSON_UNESCAPED_UNICODE);
                    } else {
                        $queryWhere .= "AND id = '" . $userData[1]['id'] . "' ";

                        if (!isset($data['userName'])) {
                            return json_encode(Array('error' => 'userName 参数错误'), JSON_UNESCAPED_UNICODE);
                        } else {
                            if ($data['userName'] !== $userData[1]['userName']) {
                                return json_encode(Array('error' => 'userName 参数错误'), JSON_UNESCAPED_UNICODE);
                            } else {
                                $queryWhere .= "AND userName = '" . $userData[1]["userName"] . "' ";


                                if (isset($data['password']) && strlen($data['password']) == 0) {
                                    return json_encode(Array('error' => 'password 不能为空'), JSON_UNESCAPED_UNICODE);
                                } else if (isset($data['password'])) {

                                    $salt = ''; // 随机加密密钥
                                    while (strlen($salt) < 6) {
                                        $x = mt_rand(0, 9);
                                        $salt .= $x;
                                    }

                                    $data['password'] = sha1($data['password'] . $salt); // sha1哈希加密

                                    $queryArg .= "`salt` = '$salt', `password` = '{$data["password"]}', ";

                                }

                                if (isset($data['permission']) && strlen($data['permission']) != 1) {
                                    return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
                                } else if (isset($data['permission'])) {
                                    $queryArg .= "`permission` = '{$data["permission"]}', ";
                                }

                                if (isset($data['idCard'])) {
                                    $queryArg .= "`idCard` = '{$data["idCard"]}', ";
                                }

                                if (isset($data['cardType']) && strlen($data['cardType']) != 1) {
                                    return json_encode(Array('error' => 'cardType 参数错误'), JSON_UNESCAPED_UNICODE);
                                } else if (isset($data['cardType'])) {
                                    $queryArg .= "`cardType` = '{$data["cardType"]}', ";
                                }

                                if (isset($data['gender'])) {
                                    $queryArg .= "`gender` = '{$data["gender"]}', ";
                                }

                                if (isset($data['contact'])) {
                                    $queryArg .= "`contact` = '{$data["contact"]}', ";
                                }

                                if (isset($data['email'])) {
                                    $queryArg .= "`email` = '{$data["email"]}', ";
                                }

                                if (isset($data['name'])) {
                                    $queryArg .= "`name` = '{$data["name"]}', ";
                                }

                                if (isset($data['cardType']) && strlen($data['cardType']) != 1) {
                                    return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
                                } else if (isset($data['cardType'])) {
                                    $queryArg .= "`cardType` = '{$data["cardType"]}', ";
                                }


                                if (isset($data['userImg']) && strlen($data['userImg']) != 0) {
                                    $data['userImg'] = json_decode($f->uploadUserImage($data['userImg']), true)[0];
                                    $queryArg .= "`userImg` = '{$data["userImg"]}', ";
                                }
                            }
                        }
                    }
                }
            }

        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen($queryArg) == 0) {
            return json_encode(Array('info' => '数据未变更'), JSON_UNESCAPED_UNICODE);
        }
        $query .= rtrim(rtrim($queryArg, ' '), ',') . ' ' . $queryWhere;

//        die($query);

        $res = $this->db->database->query($query);

        $err = $this->db->database->errno;

        if ($err == 1062) {
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows == 1) {

            if (isset($data['userImg']) && strlen($data['userImg']) != 0) {
                if ($data['userImg'] != $userData[1]['userImg']) {
                    $f->deleteImage($userData[1]['userImg'], 'user');
                }
            }

            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            if (isset($data['userImg']) && strlen($data['userImg']) != 0) {
                $f->deleteImage($data['userImg'], 'user');
            }
            return json_encode(Array('info' => '数据未变更'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateCar($area, $darea, $data)
    {
        if (strlen($area) != 9) {
            return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen(strval($darea)) != 19) {
            return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if ($area !== substr($darea, 0, 9)) {
            return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            $roleList = json_decode($this->getRoleList(), true);

            if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($area, $r)) {
                        $flag = true;
                        if ($r['updateCar'] == 0) {
                            return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getAreaName($area) . '：' . $area . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                        }
                        $areaName = $this->getAreaName($area);
                        break;
                    }
                }
                if (!$flag) {
                    return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getAreaName($area) . '：' . $area . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                }

                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($darea, $r)) {
                        $flag = true;
                        if ($r['updateCar'] == 0) {
                            return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getDareaName($darea) . '：' . $darea . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                        }
                        $dareaName = $this->getDareaName($darea);
                        break;
                    }
                }

                if (!$flag) {
                    return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getDareaName($darea) . '：' . $darea . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                }
            } else {
                $areaName = $this->getAreaName($area);
                $dareaName = $this->getDareaName($darea);
            }

            if (strlen(mb_split(':', $dareaName)[1]) == 0) {
                return json_encode(Array('error' => '操作失败，该场所不存在'), JSON_UNESCAPED_UNICODE);
            }

            $f = new FileManager();

            $query = "UPDATE $this->carTable SET ";
            $queryArg = "";
            $queryWhere = "WHERE 1=1 ";


            $queryArg .= "`dareaName` = '$dareaName', ";

            $queryArg .= "`placeType` = '" . json_decode($this->getPlanList(1, 1, ['area' => $area, 'darea' => $darea]), true)[1]['placeType'] . "', ";

            $equipmentData = json_decode($this->getEquipmentList($area, '', $darea, '', 1, 1, ['equipmentId' => $data['equipmentId']]), true);

            if (!isset($data['passTime'])) {
                return json_encode(Array('error' => 'passTime 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if (!isset($data['plateNum'])) {
                return json_encode(Array('error' => 'plateNum 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if (!isset($data['equipmentId'])) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if ($area !== substr($data['equipmentId'], 0, 9)) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if ($equipmentData[0] == 0) {
                return json_encode(Array('error' => '该设备不存在'), JSON_UNESCAPED_UNICODE);
            } else {
                $queryArg .= "`equipmentName` = '" . $equipmentData[1]['equipmentName'] . "', ";
                $queryArg .= "`equipmentType` = '" . $equipmentData[1]['equipmentType'] . "', ";
            }

            $carData = json_decode($this->getCarList($area, '', $darea, '', 1, 1, ['passTime' => $data['passTime'], 'equipmentId' => $data['equipmentId']]), true);

            if ($carData[0] == 0) {
                return json_encode(Array('error' => '该信息不存在'), JSON_UNESCAPED_UNICODE);
            }

            if (isset($data['vehicleImg']) && strlen($data['vehicleImg']) != 0) {
                $data['vehicleImg'] = json_decode($f->uploadImage($data['vehicleImg'], 'car', $data['passTime'], $data['plateNum'].'_车辆'), true)[0];
                $queryArg .= "`vehicleImg` = '{$data["vehicleImg"]}', ";
            }
            if (isset($data['plateImg']) && strlen($data['plateImg']) != 0) {
                $data['plateImg'] = json_decode($f->uploadImage($data['plateImg'], 'car', $data['passTime'], $data['plateNum'].'_车牌'), true)[0];
                $queryArg .= "`plateImg` = '{$data["plateImg"]}', ";
            }

            if (isset($data['plateColor'])) {
                $queryArg .= "`plateColor` = '{$data["plateColor"]}', ";
            }

            if (isset($data['vehicleType'])) {
                $queryArg .= "`vehicleType` = '{$data["vehicleType"]}', ";
            }

            if (isset($data['x'])) {
                $queryArg .= "`x` = '{$data["x"]}', ";
            }

            if (isset($data['y'])) {
                $queryArg .= "`y` = '{$data["y"]}', ";
            }

            if (isset($data['stationName'])) {
                $queryArg .= "`stationName` = '{$data["stationName"]}', ";
            }

            if (isset($data['stationId'])) {
                $queryArg .= "`stationId` = '{$data["stationId"]}', ";
            }

            if (isset($data['location'])) {
                $queryArg .= "`location` = '{$data["location"]}', ";
            }

            if (isset($data['vehicleColor'])) {
                $queryArg .= "`vehicleColor` = '{$data["vehicleColor"]}', ";
            }

            if (isset($data['carType']) && strlen($data['carType']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['carType'])) {
                $queryArg .= "`carType` = '{$data["carType"]}', ";
            }

            if (isset($data['visitReason'])) {
                $queryArg .= "`visitReason` = '{$data["visitReason"]}', ";
            }

            if (isset($data['status']) && strlen($data['status']) != 1) {
                return json_encode(Array('error' => 'status 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['status'])) {
                $queryArg .= "`status` = '{$data['status']}', ";
            }

            if (isset($data['visitor']) && is_array($data['visitor'])) {
                $data['visitor'] = json_encode($data['visitor'], JSON_UNESCAPED_UNICODE);
                $queryArg .= "`visitor` = '{$data['visitor']}', ";
            } else if (isset($data['visitor'])) {
                $queryArg .= "`visitor` = '{$data['visitor']}', ";
            }

            if (isset($data['driverData']) && is_array($data['driverData'])) {
                $data['driverData'] = json_encode($data['driverData'], JSON_UNESCAPED_UNICODE);
                $queryArg .= "`driverData` = '{$data['driverData']}', ";
            } else if (isset($data['driverData'])) {
                $queryArg .= "`driverData` = '{$data['driverData']}', ";
            }

            if (isset($data['passengerData']) && is_array($data['passengerData'])) {
                $data['passengerData'] = json_encode($data['passengerData'], JSON_UNESCAPED_UNICODE);
                $queryArg .= "`passengerData` = '{$data['passengerData']}', ";
            } else if (isset($data['passengerData'])) {
                $queryArg .= "`passengerData` = '{$data['passengerData']}', ";
            }
        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen($queryArg) == 0) {
            return json_encode(Array('info' => '数据未变更'), JSON_UNESCAPED_UNICODE);
        }

        $queryWhere .= "AND area = '$area' AND darea = '$darea' AND plateNum = '{$data['plateNum']}' AND passTime = '{$data['passTime']}' AND equipmentId = '{$data['equipmentId']}' ";
        $query .= rtrim(rtrim($queryArg, ' '), ',') . ' ' . $queryWhere;

//        die($query);

        $res = $this->db->database->query($query);
        $err = $this->db->database->errno;

        if ($err == 1062) {
            if (isset($data['vehicleImg']) && strlen($data['vehicleImg']) != 0) {
                $f->deleteImage($data['vehicleImg'], 'car');
            }
            if (isset($data['plateImg']) && strlen($data['plateImg']) != 0) {
                $f->deleteImage($data['plateImg'], 'car');
            }
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

//        var_dump($query);
        if ($res && $this->db->database->affected_rows == 1) {
            if (isset($data['vehicleImg']) && strlen($data['vehicleImg']) != 0) {
                if ($data['vehicleImg'] != $carData[1]['vehicleImg']) {
                    $f->deleteImage($carData[1]['vehicleImg'], 'car');
                }
            }
            if (isset($data['plateImg']) && strlen($data['plateImg']) != 0) {
                if ($data['plateImg'] != $carData[1]['plateImg']) {
                    $f->deleteImage($carData[1]['plateImg'], 'car');
                }
            }
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            if (isset($data['vehicleImg']) && strlen($data['vehicleImg']) != 0) {
                $f->deleteImage($data['vehicleImg'], 'car');
            }
            if (isset($data['plateImg']) && strlen($data['plateImg']) != 0) {
                $f->deleteImage($data['plateImg'], 'car');
            }
            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
        }
//        if (!$this->rolePermission->checkUpdateCar()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $time = $time;
//
//        if ( strlen($time) == 0|| strlen($licensePlate) < 7 || strlen($area) == 0 || strlen($place) == 0 || strlen($come) == 0 ) {
//            return json_encode(Array('success' => '操作失败，请检查信息是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "UPDATE $this->carTable SET `time` = '$time',`area` = '$area', `place` = '$place', `come` = $come WHERE `time` = '$time' AND `licensePlate` = '$licensePlate'";
//
//        $res = $this->db->database->query($query);
//
//        $err = $this->db->database->errno;
//        if ($err == 1452) {
//            return json_encode(Array('error' => '操作失败,请先选择区域或场所'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && ($this->db->database->affected_rows == 0)) {
//            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
    }

    public function updatePersonnel($area, $darea, $data)
    {
        if (strlen($area) != 9) {
            return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen(strval($darea)) != 19) {
            return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if ($area !== substr($darea, 0, 9)) {
            return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            $roleList = json_decode($this->getRoleList(), true);

            if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($area, $r)) {
                        $flag = true;
                        if ($r['updatePersonnel'] == 0) {
                            return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getAreaName($area) . '：' . $area . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                        }
                        $areaName = $this->getAreaName($area);
                        break;
                    }
                }
                if (!$flag) {
                    return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getAreaName($area) . '：' . $area . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                }

                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($darea, $r)) {
                        $flag = true;
                        if ($r['updatePersonnel'] == 0) {
                            return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getDareaName($darea) . '：' . $darea . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                        }
                        $dareaName = $this->getDareaName($darea);
                        break;
                    }
                }

                if (!$flag) {
                    return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getDareaName($darea) . '：' . $darea . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                }
            } else {
                $areaName = $this->getAreaName($area);
                $dareaName = $this->getDareaName($darea);
            }

            if (strlen(mb_split(':', $dareaName)[1]) == 0) {
                return json_encode(Array('error' => '操作失败，该场所不存在'), JSON_UNESCAPED_UNICODE);
            }

            $f = new FileManager();

            $query = "UPDATE $this->personnelTable SET ";
            $queryArg = "";
            $queryWhere = "WHERE 1=1 ";


            $queryArg .= "`dareaName` = '$dareaName', ";

            $queryArg .= "`placeType` = '" . json_decode($this->getPlanList(1, 1, ['area' => $area, 'darea' => $darea]), true)[1]['placeType'] . "', ";

            $equipmentData = json_decode($this->getEquipmentList($area, '', $darea, '', 1, 1, ['equipmentId' => $data['equipmentId']]), true);

            if (!isset($data['passTime'])) {
                return json_encode(Array('error' => 'passTime 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if (!isset($data['name'])) {
                return json_encode(Array('error' => 'name 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            if (!isset($data['equipmentId'])) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if ($area !== substr($data['equipmentId'], 0, 9)) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if ($equipmentData[0] == 0) {
                return json_encode(Array('error' => '该设备不存在'), JSON_UNESCAPED_UNICODE);
            } else {
                $queryArg .= "`equipmentName` = '" . $equipmentData[1]['equipmentName'] . "', ";
                $queryArg .= "`equipmentType` = '" . $equipmentData[1]['equipmentType'] . "', ";
            }

            $personnelData = json_decode($this->getPersonnelList($area, '', $darea, '', 1, 1, ['passTime' => $data['passTime'], 'equipmentId' => $data['equipmentId']]), true);

            if ($personnelData[0] == 0) {
                return json_encode(Array('error' => '该信息不存在'), JSON_UNESCAPED_UNICODE);
            }

            if (isset($data['personImg']) && strlen($data['personImg']) != 0) {
                $data['personImg'] = json_decode($f->uploadImage($data['personImg'], 'personnel', $data['passTime'], $data['name'] . '_现场'), true)[0];
                $queryArg .= "`personImg` = '{$data["personImg"]}', ";
            }

            if (isset($data['idCardImg']) && strlen($data['idCardImg']) != 0) {
                $data['idCardImg'] = json_decode($f->uploadImage($data['idCardImg'], 'personnel', $data['passTime'], $data['name'] . '_证件'), true)[0];
                $queryArg .= "`idCardImg` = '{$data["idCardImg"]}', ";
            }

            if (isset($data['gender'])) {
                $queryArg .= "`gender` = '{$data["gender"]}', ";
            }

            if (isset($data['nation'])) {
                $queryArg .= "`nation` = '{$data["nation"]}', ";
            }

            if (isset($data['idCard'])) {
                $queryArg .= "`idCard` = '{$data["idCard"]}', ";
            }

            if (isset($data['cardType']) && strlen($data['cardType']) != 1) {
                return json_encode(Array('error' => 'cardType 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['cardType'])) {
                $queryArg .= "`cardType` = '{$data["cardType"]}', ";
            }

            if (isset($data['countryOrAreaCode']) && strlen($data['countryOrAreaCode']) != 3) {
                return json_encode(Array('error' => 'countryOrAreaCode 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['countryOrAreaCode'])) {
                $queryArg .= "`countryOrAreaCode` = '{$data["countryOrAreaCode"]}', ";
            }

            if (isset($data['countryOrAreaName'])) {
                $queryArg .= "`countryOrAreaName` = '{$data["countryOrAreaName"]}', ";
            }

            if (isset($data['cardVersion'])) {
                $queryArg .= "`cardVersion` = '{$data["cardVersion"]}', ";
            }

            if (isset($data['currentApplyOrgCode'])) {
                $queryArg .= "`currentApplyOrgCode` = '{$data["currentApplyOrgCode"]}', ";
            }

            if (isset($data['signNum'])) {
                $queryArg .= "`signNum` = '{$data["signNum"]}', ";
            }

            if (isset($data['birthDay'])) {
                $queryArg .= "`birthDay` = '{$data["birthDay"]}', ";
            }

            if (isset($data['address'])) {
                $queryArg .= "`address` = '{$data["address"]}', ";
            }

            if (isset($data['authority'])) {
                $queryArg .= "`authority` = '{$data["authority"]}', ";
            }

            if (isset($data['validtyStart'])) {
                $queryArg .= "`validtyStart` = '{$data["validtyStart"]}', ";
            }

            if (isset($data['validtyEnd'])) {
                $queryArg .= "`validtyEnd` = '{$data["validtyEnd"]}', ";
            }

            if (isset($data['temp'])) {
                $queryArg .= "`temp` = '{$data["temp"]}', ";
            }

            if (isset($data['x'])) {
                $queryArg .= "`x` = '{$data["x"]}', ";
            }

            if (isset($data['y'])) {
                $queryArg .= "`y` = '{$data["y"]}', ";
            }

            if (isset($data['stationName'])) {
                $queryArg .= "`stationName` = '{$data["stationName"]}', ";
            }

            if (isset($data['stationId'])) {
                $queryArg .= "`stationId` = '{$data["stationId"]}', ";
            }

            if (isset($data['location'])) {
                $queryArg .= "`location` = '{$data["location"]}', ";
            }

            if (isset($data['identity']) && strlen($data['identity']) != 1) {
                return json_encode(Array('error' => '参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['identity'])) {
                $queryArg .= "`identity` = '{$data["identity"]}', ";
            }

            if (isset($data['homePlace'])) {
                $queryArg .= "`homePlace` = '{$data["homePlace"]}', ";
            }

            if (isset($data['contact'])) {
                $queryArg .= "`contact` = '{$data["contact"]}', ";
            }

            if (isset($data['isConsist'])) {
                $queryArg .= "`isConsist` = '{$data["isConsist"]}', ";
            }

            if (isset($data['compareScore'])) {
                $queryArg .= "`compareScore` = '{$data["compareScore"]}', ";
            }

            if (isset($data['openMode']) && strlen($data['openMode']) != 1) {
                return json_encode(Array('error' => 'openMode 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['openMode'])) {
                $queryArg .= "`openMode` = '{$data["openMode"]}', ";
            }

            if (isset($data['visitReason'])) {
                $queryArg .= "`visitReason` = '{$data["visitReason"]}', ";
            }

            if (isset($data['mac'])) {
                $queryArg .= "`mac` = '{$data["mac"]}', ";
            }

            if (isset($data['imsi'])) {
                $queryArg .= "`imsi` = '{$data["imsi"]}', ";
            }

            if (isset($data['imei'])) {
                $queryArg .= "`imei` = '{$data["imei"]}', ";
            }

            if (isset($data['status']) && strlen($data['status']) != 1) {
                return json_encode(Array('error' => 'status 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['status'])) {
                $queryArg .= "`status` = '{$data['status']}', ";
            }

            if (isset($data['visitor']) && is_array($data['visitor'])) {
                $data['visitor'] = json_encode($data['visitor'], JSON_UNESCAPED_UNICODE);
                $queryArg .= "`visitor` = '{$data['visitor']}', ";
            } else if (isset($data['visitor'])) {
                $queryArg .= "`visitor` = '{$data['visitor']}', ";
            }

        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen($queryArg) == 0) {
            return json_encode(Array('info' => '数据未变更'), JSON_UNESCAPED_UNICODE);
        }

        $queryWhere .= "AND area = '$area' AND darea = '$darea' AND name = '{$data['name']}' AND passTime = '{$data['passTime']}' AND equipmentId = '{$data['equipmentId']}' ";
        $query .= rtrim(rtrim($queryArg, ' '), ',') . ' ' . $queryWhere;

//        die($query);

        $res = $this->db->database->query($query);
        $err = $this->db->database->errno;

        if ($err == 1062) {
            if (isset($data['personImg']) && strlen($data['personImg']) != 0) {
                $f->deleteImage($data['personImg'], 'personnel');
            }
            if (isset($data['idCardImg']) && strlen($data['idCardImg']) != 0) {
                $f->deleteImage($data['idCardImg'], 'personnel');
            }
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows == 1) {
            if (isset($data['idCardImg']) && strlen($data['idCardImg']) != 0) {
                if ($data['idCardImg'] != $personnelData[1]['idCardImg']) {
                    $f->deleteImage($personnelData[1]['idCardImg'], 'personnel');
                }
            }
            if (isset($data['personImg']) && strlen($data['personImg']) != 0) {
                if ($data['personImg'] != $personnelData[1]['personImg']) {
                    $f->deleteImage($personnelData[1]['personImg'], 'personnel');
                }
            }
//            var_dump($personnelData[1]['personImg']);
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            if (isset($data['personImg']) && strlen($data['personImg']) != 0) {
                $f->deleteImage($data['personImg'], 'personnel');
            }
            if (isset($data['idCardImg']) && strlen($data['idCardImg']) != 0) {
                $f->deleteImage($data['idCardImg'], 'personnel');
            }
            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateEquipment($area, $darea, $data)
    {
        if (strlen($area) != 9) {
            return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen(strval($darea)) != 19) {
            return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
        }

        if ($area !== substr($darea, 0, 9)) {
            return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            $roleList = json_decode($this->getRoleList(), true);

            if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($area, $r)) {
                        $flag = true;
                        if ($r['updateEquipment'] == 0) {
                            return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getAreaName($area) . '：' . $area . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                        }
                        $areaName = $this->getAreaName($area);
                        break;
                    }
                }
                if (!$flag) {
                    return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getAreaName($area) . '：' . $area . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                }

                $flag = false;
                foreach ($roleList as $r) {
                    if (in_array($darea, $r)) {
                        $flag = true;
                        if ($r['updateEquipment'] == 0) {
                            return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getDareaName($darea) . '：' . $darea . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                        }
                        $dareaName = $this->getDareaName($darea);
                        break;
                    }
                }

                if (!$flag) {
                    return json_encode(Array('error' => '操作失败，您没有更新 ' . $this->getDareaName($darea) . '：' . $darea . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                }
            } else {
                $areaName = $this->getAreaName($area);
                $dareaName = $this->getDareaName($darea);
            }

            if (strlen(mb_split(':', $dareaName)[1]) == 0) {
                return json_encode(Array('error' => '操作失败，该场所不存在'), JSON_UNESCAPED_UNICODE);
            }

            $query = "UPDATE $this->equipmentTable SET ";
            $queryArg = "";
            $queryWhere = "WHERE 1=1 ";


            $queryArg .= "`areaName` = '$areaName', ";
            $queryArg .= "`dareaName` = '$dareaName', ";


            $equipmentData = json_decode($this->getEquipmentList($area, '', $darea, '', 1, 1, ['equipmentId' => $data['equipmentId']]), true);


            if (!isset($data['equipmentId'])) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if ($area !== substr($data['equipmentId'], 0, 9)) {
                return json_encode(Array('error' => 'equipmentId 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if ($equipmentData[0] == 0) {
                return json_encode(Array('error' => '该设备不存在'), JSON_UNESCAPED_UNICODE);
            } else {
                $queryArg .= "`equipmentType` = '" . $equipmentData[1]['equipmentType'] . "', ";
            }

            if (isset($data['equipmentName']) && strlen($data['equipmentName']) != 0) {
                $queryArg .= "`equipmentName` = '" . $data['equipmentName'] . "', ";
            }

            if (isset($data['status']) && strlen($data['status']) != 1) {
                return json_encode(Array('error' => 'status 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['equipmentStatus'])) {
                $queryArg .= "`equipmentStatus` = '" . $data['equipmentStatus'] . "', ";
            }

            if (isset($data['remark']) && strlen($data['remark']) != 0) {
                $queryArg .= "`remark` = '" . $data['remark'] . "', ";
            }

            if (isset($data['address']) && strlen($data['address']) != 0) {
                $queryArg .= "`address` = '" . $data['address'] . "', ";
            }

        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        if (strlen($queryArg) == 0) {
            return json_encode(Array('info' => '数据未变更'), JSON_UNESCAPED_UNICODE);
        }

        $queryWhere .= "AND area = '$area' AND darea = '$darea' AND equipmentId = '{$data['equipmentId']}' ";
        $query .= rtrim(rtrim($queryArg, ' '), ',') . ' ' . $queryWhere;

//        die($query);

        $res = $this->db->database->query($query);
        $err = $this->db->database->errno;

        if ($err == 1062) {
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows == 1) {
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
        }
//        if (!$this->rolePermission->checkUpdateCar()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $time = $time;
//
//        if ( strlen($time) == 0|| strlen($licensePlate) < 7 || strlen($area) == 0 || strlen($place) == 0 || strlen($come) == 0 ) {
//            return json_encode(Array('success' => '操作失败，请检查信息是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "UPDATE $this->carTable SET `time` = '$time',`area` = '$area', `place` = '$place', `come` = $come WHERE `time` = '$time' AND `licensePlate` = '$licensePlate'";
//
//        $res = $this->db->database->query($query);
//
//        $err = $this->db->database->errno;
//        if ($err == 1452) {
//            return json_encode(Array('error' => '操作失败,请先选择区域或场所'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && ($this->db->database->affected_rows == 0)) {
//            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
    }


//    public function updateDevice($id, $name, $area, $place, $ip)
//    {
//        if (!$this->rolePermission->checkUpdateDevice()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($id) == 0 || strlen($name) == 0 || strlen($area) == 0 || strlen($place) == 0 || strlen($ip) == 0) {
//            return json_encode(Array('success' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "UPDATE $this->deviceTable SET `area` = '$area', `place` = '$place', `name` = '$name',`ip` = '$ip' WHERE `deviceId` = '$id'";
//
//        $res = $this->db->database->query($query);
//
////        $err = $this->db->database->errno;
////        if ($err == 1452) {
////            return json_encode(Array('error' => '操作失败,请先选择区域或场所'), JSON_UNESCAPED_UNICODE);
////        }
//
//        if ($res && ($this->db->database->affected_rows == 0)) {
//            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }

//    public function updatePersonnel($time, $name, $idCard, $area, $place, $come, $temp)
//    {
//        if (!$this->rolePermission->checkUpdatePersonnel()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $time = $time;
//
////        if (strlen($time) == 0 || strlen($name) == 0 || floatval($temp) <= 0 || floatval($temp) >= 100 || strlen($idCard) == 0 || strlen($area) == 0 || strlen($place) == 0 || strlen($come) == 0 || intval($come) < 0 || intval($come) > 1) {
////            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
////        }
//
//        $query = "UPDATE $this->personnelTable SET `time` = '$time',`area` = '$area', `place` = '$place', `come` = $come, `temp` = $temp WHERE `time` = '$time' AND `name` = '$name' AND `idCard` = '$idCard'";
//
//        $res = $this->db->database->query($query);
//
//        $err = $this->db->database->errno;
//        if ($err == 1452) {
//            return json_encode(Array('error' => '操作失败,请先选择区域或场所'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && ($this->db->database->affected_rows == 0)) {
//            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }


//    public function updateRole($id, $name)
//    {
//        if (!$this->rolePermission->checkEditRole()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($id) != 3) {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "UPDATE $this->roleTable SET `name` = '$name' WHERE `role` = '$id'";
//
//        $res = $this->db->database->query($query);
//
//        if ($this->db->database->errno == 1062) {
//            return json_encode(Array('error' => '操作失败，id 或 名称重复'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && ($this->db->database->affected_rows == 0)) {
//            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }

    public function updatePlan($data)
    {
        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if (!isset($data['area']) || strlen($data['area']) != 9) {
                return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
            }

            if (isset($data['darea']) && strlen($data['darea']) != 19) {
                return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['darea']) && $data['area'] !== substr($data['darea'], 0, 9)) {
                return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
            }

            if (isset($data['id'])) {
                if (strlen($data['id']) != 36) {
                    return json_encode(Array('error' => 'id 错误'), JSON_UNESCAPED_UNICODE);
                }

                if (json_decode($this->getUserList(1, 1, ['id' => $data['id']]), true)[0] == 0) {
                    return json_encode(Array('error' => '此 id 不存在'), JSON_UNESCAPED_UNICODE);
                }
            }

            $query = "UPDATE $this->planTable SET ";
            $queryArg = "";
            $queryWhere = "WHERE 1=1 ";

            if (isset($data['areaName']) && strlen($data['areaName']) != 0) {
                $queryArg .= "`areaName` = '{$data['areaName']}', ";
            }

            if (isset($data['darea']) && isset($data['dareaName']) && strlen($data['dareaName']) != 0) {
                $queryArg .= "`dareaName` = '{$data['dareaName']}', ";
            }

            if (isset($data['id']) && strlen($data['id']) != 0) {
                $queryArg .= "`id` = '{$data['id']}', ";
            }

            if (isset($data['person']) && strlen($data['person']) != 0) {
                $queryArg .= "`person` = '{$data['person']}', ";
            }

            if (isset($data['personContact']) && strlen($data['personContact']) != 0) {
                $queryArg .= "`personContact` = '{$data['personContact']}', ";
            }

        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        if (isset($data['darea'])) {
            $queryWhere .= "AND area = '{$data['area']}' AND darea = '{$data['darea']}'";

        } else {
            $queryWhere .= "AND area = '{$data['area']}'";
        }

        $query .= rtrim(rtrim($queryArg, ' '), ',') . ' ' . $queryWhere;

        $oId = $this->db->database->query("SELECT id FROM $this->planTable WHERE area = '{$data['area']}' AND darea = '{$data['darea']}'")->fetch_assoc()['id'];

        $res = $this->db->database->query($query);

        $err = $this->db->database->errno;

        if ($err == 1062) {
            return json_encode(Array('error' => '该数据已存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($res && $this->db->database->affected_rows > 0) {
            if (isset($data['id']) && strlen($data['id']) != 0) {

                $this->delRole(['area'=>$data['area'],'darea'=>$data['darea'],'id'=>$oId]);
                $this->addRole($data['area'],$data['darea'],
                    [
                        'id'=>$data['id'],
                        'selectPersonnel'=>'1',
                        'addPersonnel'=>'1',
                        'updatePersonnel'=>'1',
                        'delPersonnel'=>'1',
                        'selectCar'=>'1',
                        'addCar'=>'1',
                        'updateCar'=>'1',
                        'delCar'=>'1',
                        'selectEquipment'=>'1',
                        'addEquipment'=>'1',
                        'updateEquipment'=>'1',
                        'delEquipment'=>'1',
                    ]
                );
            }
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('info' => '数据未变更'), JSON_UNESCAPED_UNICODE);
        }
    }

//    public function updatePlace($id, $area, $name)
//    {
//        if (!$this->rolePermission->checkEditPlace()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($id) != 5) {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//        $query = "UPDATE $this->placeTable SET `name` = '$name' WHERE `id` = '$id' AND `area` = '$area'";
//
//        $res = $this->db->database->query($query);
//        $err = $this->db->database->errno;
//
//        if ($err == 1452) {
//            return json_encode(Array('error' => '操作失败,区域不存在'), JSON_UNESCAPED_UNICODE);
//        }
//        if ($err == 1062) {
//            return json_encode(Array('error' => '操作失败,场所id已被使用'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && ($this->db->database->affected_rows == 0)) {
//            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }

//    public function updateArea($code, $name, $level, $parentCode)
//    {
//        if (!$this->rolePermission->checkEditArea()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($code == '0') {
//            return json_encode(Array('error' => '操作失败，该区域为根级区域，不可修改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($code) != 12 || strlen($parentCode) == 0 || strlen($name) == 0 || strlen($level) == 0 || intval($level) <= 0 || intval($level) > 5) {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "UPDATE $this->areaTable SET `name` = '$name' WHERE `code` = '$code' AND `level` = '$level' AND `parentCode` = '$parentCode'";
//
//        $res = $this->db->database->query($query);
//
//        if ($res && ($this->db->database->affected_rows == 0)) {
//            return json_encode(Array('info' => '数据未更改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }

    /*
     * 删除记录
     */
    public function delCar($data)
    {
        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if (!(isset($data['area']) || isset($data['areaList'])) && (isset($data['darea']) || isset($data['dareaList']))) {
                return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            $query = "DELETE FROM $this->carTable WHERE 1=1 ";
            $selectPlan = "";
            $selectFilter = '';

            $roleList = json_decode($this->getRoleList(), true);

            if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
                if (!$data['area'] || strlen($data['area']) == 0) {
                    if (!is_array($data['areaList'])) {
                        $data['areaList'] = json_decode($data['areaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";

                    foreach ($data['areaList'] as $a) {
                        $flag = false;
                        foreach ($roleList as $r) {
                            if ($r['delCar'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($a) . '：' . $a . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                            }

                            if (in_array($a, $r)) {
                                $flag = true;
                                if ($r['delCar'] == 0) {
                                    return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($a) . '：' . $a . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                                }
                                $selectPlan .= "OR area = '$a' ";
                                break;
                            }
                        }
                        if (!$flag) {
                            return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($a) . '：' . $a . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                        }

                    }
                } else {
                    $flag = false;
                    foreach ($roleList as $r) {
                        if (in_array($data['area'], $r)) {
                            $flag = true;
                            if ($r['delCar'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($data['area']) . '：' . $data['area'] . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                            }
                            $selectPlan .= "AND (area = '{$data['area']}' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($data['area']) . '：' . $data['area'] . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                }
                $selectPlan .= ") ";

                if (!$data['darea'] || strlen($data['darea']) == 0) {
                    if (!is_array($data['dareaList'])) {
                        $dareaList = json_decode($data['dareaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";
                    foreach ($dareaList as $d) {
                        $flag = false;
                        foreach ($roleList as $r) {
                            if ($r['delCar'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($d) . '：' . $d . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                            }

                            if (in_array($d, $r)) {
                                $flag = true;
                                if ($r['delCar'] == 0) {
                                    return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($d) . '：' . $d . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                                }
                                $selectPlan .= "OR darea = '$d' ";
                                break;
                            }
                        }
                        if (!$flag) {
                            return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($d) . '：' . $d . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                        }

                    }
                } else {
                    $flag = false;
                    foreach ($roleList as $r) {
                        if (in_array($data['darea'], $r)) {
                            $flag = true;
                            if ($r['delCar'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($data['darea']) . '：' . $data['darea'] . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                            }
                            $selectPlan .= "AND (darea = '{$data['darea']}' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($data['darea']) . '：' . $data['darea'] . ' 车辆信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                }
                $selectPlan .= ") ";

            } else if ($this->user->getPermission()->fetch_assoc()['permission'] == '8') {
                if (!$data['area'] || strlen($data['area']) == 0) {
                    if (!is_array($data['areaList'])) {
                        $data['areaList'] = json_decode($data['areaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";

                    foreach ($data['areaList'] as $a) {
                        $selectPlan .= "OR area = '$a' ";
                    }
                } else {
                    $selectPlan .= "AND (area = '${data['area']}' ";
                }
                $selectPlan .= ") ";

                if (!$data['darea'] || strlen($data['darea']) == 0) {
                    if (!is_array($data['dareaList'])) {
                        $data['dareaList'] = json_decode($data['dareaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";
                    foreach ($data['dareaList'] as $d) {
                        $selectPlan .= "OR darea = '$d' ";
                    }
                } else {
                    $selectPlan .= "AND (darea = '{$data['darea']}' ";
                }
                $selectPlan .= ") ";
            }
        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        $selectFilterArray = Array();

        if (isset($data['plateNum'])) {
            $selectFilter .= "AND plateNum LIKE '%" . $data['plateNum'] . "%' ";
            $selectFilterArray['plateNum'] = $data['plateNum'];
        }
        if (isset($data['passTime'])) {
            $selectFilter .= "AND passTime = '" . $data['passTime'] . "' ";
            $selectFilterArray['passTime'] = $data['passTime'];
        }
        if (isset($data['beforeTime'])) {
            $selectFilter .= "AND passTime < '" . $data['beforeTime'] . "' ";
            $selectFilterArray['beforeTime'] = $data['beforeTime'];
        }
        if (isset($data['afterTime'])) {
            $selectFilter .= "AND passTime > '" . $data['afterTime'] . "' ";
            $selectFilterArray['afterTime'] = $data['afterTime'];
        }
        if (isset($data['betweenTime'])) {
            $selectFilter .= "AND passTime BETWEEN '" . $data['betweenTime'][0] . "' AND '" . $data['betweenTime'][1] . "' ";
            $selectFilterArray['betweenTime'] = $data['betweenTime'];
        }
        if (isset($data['equipmentId'])) {
            $selectFilter .= "AND equipmentId = '" . $data['equipmentId'] . "' ";
            $selectFilterArray['equipmentId'] = $data['equipmentId'];
        }
        if (isset($data['equipmentName'])) {
            $selectFilter .= "AND equipmentName LIKE '%" . $data['equipmentName'] . "%' ";
            $selectFilterArray['equipmentName'] = $data['equipmentName'];
        }
        if (isset($data['equipmentType'])) {
            $selectFilter .= "AND equipmentType = '" . $data['equipmentType'] . "' ";
            $selectFilterArray['equipmentType'] = $data['equipmentType'];
        }
        if (isset($data['status'])) {
            $selectFilter .= "AND status = '" . $data['status'] . "' ";
            $selectFilterArray['status'] = $data['status'];
        }
        if (isset($data['stationId'])) {
            $selectFilter .= "AND stationId = '" . $data['stationId'] . "' ";
            $selectFilterArray['stationId'] = $data['stationId'];
        }
        if (isset($data['stationName'])) {
            $selectFilter .= "AND stationName LIKE '%" . $data['stationName'] . "%' ";
            $selectFilterArray['stationName'] = $data['stationName'];
        }
        if (isset($data['plateType'])) {
            $selectFilter .= "AND plateType = '" . $data['plateType'] . "' ";
            $selectFilterArray['plateType'] = $data['plateType'];
        }
        if (isset($data['carType'])) {
            $selectFilter .= "AND carType = '" . $data['carType'] . "' ";
            $selectFilterArray['carType'] = $data['carType'];
        }


//        if (strlen($data['area']) == 0 && strlen($data['darea']) == 0) {
//            $carData = json_decode($this->getCarList('', $data['$areaList'], '', $data['dareaList'], '1', '9999999999999', $selectFilterArray), true);
//        } else {
//            $carData = json_decode($this->getCarList($data['area'], '', $data['darea'], '', '1', '9999999999999', $selectFilterArray), true);
//        }
//
//        if ($carData[0] == 0) {
//            return json_encode(Array('info' => '所选条件范围内无记录'), JSON_UNESCAPED_UNICODE);
//        }
//
//
//        if (strlen($selectFilter) == 0) {
//            return json_encode(Array('error' => '条件 参数错误'), JSON_UNESCAPED_UNICODE);
//        }
//        $query .= $selectPlan . $selectFilter;
//
//        $res = $this->db->database->query($query);
//
//        if ($res && $this->db->database->affected_rows >= 1) {
//            $f = new FileManager();
//            foreach (array_splice($carData, 1) as $t) {
//                if (isset($t['vehicleImg']) && strlen($t['vehicleImg']) != 0) {
//                    $f->deleteImage($t['vehicleImg'], 'car');
//                }
//            }
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }

        if (strlen($data['area']) == 0 && strlen($data['darea']) == 0) {
            $carDataNum = json_decode($this->getCarList('', $data['areaList'], '', $data['dareaList'], '1', '0', $selectFilterArray), true)[0];

        } else {
            $carDataNum = json_decode($this->getCarList($data['area'], '', $data['darea'], '', '1', '0', $selectFilterArray), true)[0];
        }

        if ($carDataNum == 0) {
            return json_encode(Array('info' => '所选条件范围内无记录'), JSON_UNESCAPED_UNICODE);
        }


        if (strlen($selectFilter) == 0) {
            return json_encode(Array('error' => '条件 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        $query .= $selectPlan . $selectFilter . ' LIMIT 5000';
        for($page = 1;(5000*($page-1))<$carDataNum;$page++){
            if (strlen($data['area']) == 0 && strlen($data['darea']) == 0) {
                $carData =
                    array_slice(
                        json_decode($this->getCarList('', $data['areaList'], '', $data['dareaList'], $page, '5000', $selectFilterArray), true)
                        ,1);
            } else {
                $carData =
                    array_slice(
                        json_decode($this->getCarList($data['area'], '', $data['darea'], '', $page, '5000', $selectFilterArray), true)
                        ,1);
            }

            $res = $this->db->database->query($query);

            if ($res && $this->db->database->affected_rows >= 1) {
                $f = new FileManager();
                foreach ($carData as $t) {
                    if (isset($t['vehicleImg']) && strlen($t['vehicleImg']) != 0) {
                        $f->deleteImage($t['vehicleImg'], 'car');
                    }
                    if (isset($t['plateImg']) && strlen($t['plateImg']) != 0) {
                        $f->deleteImage($t['plateImg'], 'car');
                    }
                }
            }
            else {
                return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
            }
        }
        return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);

    }
//    public function deleteCar($time, $licensePlate)
//    {
//        if (!$this->rolePermission->checkDeletePersonnel()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $time = $time;
//
//        if (strlen($time) == 0 || strlen($licensePlate) < 7) {
//            return json_encode(Array('success' => '操作失败，请检查信息是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "DELETE FROM $this->carTable WHERE `time` = '$time' AND `licensePlate` = '$licensePlate'";
//
//        $res = $this->db->database->query($query);
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }
    public function delPersonnel($data)
    {
        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if (!(isset($data['area']) || isset($data['areaList'])) && (isset($data['darea']) || isset($data['dareaList']))) {
                return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            $query = "DELETE FROM $this->personnelTable WHERE 1=1 ";
            $selectPlan = "";
            $selectFilter = '';

            $roleList = json_decode($this->getRoleList(), true);

            if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
                if (!$data['area'] || strlen($data['area']) == 0) {
                    if (!is_array($data['areaList'])) {
                        $data['areaList'] = json_decode($data['areaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";

                    foreach ($data['areaList'] as $a) {
                        $flag = false;
                        foreach ($roleList as $r) {
                            if ($r['delPersonnel'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($a) . '：' . $a . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                            }

                            if (in_array($a, $r)) {
                                $flag = true;
                                if ($r['delPersonnel'] == 0) {
                                    return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($a) . '：' . $a . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                                }
                                $selectPlan .= "OR area = '$a' ";
                                break;
                            }
                        }
                        if (!$flag) {
                            return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($a) . '：' . $a . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                        }

                    }
                } else {
                    $flag = false;
                    foreach ($roleList as $r) {
                        if (in_array($data['area'], $r)) {
                            $flag = true;
                            if ($r['delPersonnel'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($data['area']) . '：' . $data['area'] . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                            }
                            $selectPlan .= "AND (area = '{$data['area']}' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($data['area']) . '：' . $data['area'] . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                }
                $selectPlan .= ") ";

                if (!$data['darea'] || strlen($data['darea']) == 0) {
                    if (!is_array($data['dareaList'])) {
                        $dareaList = json_decode($data['dareaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";
                    foreach ($dareaList as $d) {
                        $flag = false;
                        foreach ($roleList as $r) {
                            if ($r['delPersonnel'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($d) . '：' . $d . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                            }

                            if (in_array($d, $r)) {
                                $flag = true;
                                if ($r['delPersonnel'] == 0) {
                                    return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($d) . '：' . $d . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                                }
                                $selectPlan .= "OR darea = '$d' ";
                                break;
                            }
                        }
                        if (!$flag) {
                            return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($d) . '：' . $d . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                        }

                    }
                } else {
                    $flag = false;
                    foreach ($roleList as $r) {
                        if (in_array($data['darea'], $r)) {
                            $flag = true;
                            if ($r['delPersonnel'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($data['darea']) . '：' . $data['darea'] . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                            }
                            $selectPlan .= "AND (darea = '{$data['darea']}' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($data['darea']) . '：' . $data['darea'] . ' 人员信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                }
                $selectPlan .= ") ";

            } else if ($this->user->getPermission()->fetch_assoc()['permission'] == '8') {
                if (!$data['area'] || strlen($data['area']) == 0) {
                    if (!is_array($data['areaList'])) {
                        $data['areaList'] = json_decode($data['areaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";

                    foreach ($data['areaList'] as $a) {
                        $selectPlan .= "OR area = '$a' ";
                    }
                } else {
                    $selectPlan .= "AND (area = '${data['area']}' ";
                }
                $selectPlan .= ") ";

                if (!$data['darea'] || strlen($data['darea']) == 0) {
                    if (!is_array($data['dareaList'])) {
                        $data['dareaList'] = json_decode($data['dareaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";
                    foreach ($data['dareaList'] as $d) {
                        $selectPlan .= "OR darea = '$d' ";
                    }
                } else {
                    $selectPlan .= "AND (darea = '{$data['darea']}' ";
                }
                $selectPlan .= ") ";
            }
        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }


        $selectFilterArray = Array();

        if (isset($data['name'])) {
            $selectFilter .= "AND name LIKE '%" . $data['name'] . "%' ";
            $selectFilterArray['name'] = $data['name'];
        }
        if (isset($data['gender'])) {
            $selectFilter .= "AND gender = '" . $data['gender'] . "' ";
            $selectFilterArray['gender'] = $data['gender'];
        }
        if (isset($data['nation'])) {
            $selectFilter .= "AND nation LIKE '%" . $data['nation'] . "%' ";
            $selectFilterArray['nation'] = $data['nation'];
        }
        if (isset($data['idCard'])) {
            $selectFilter .= "AND idCard LIKE '%" . $data['idCard'] . "%' ";
            $selectFilterArray['idCard'] = $data['idCard'];
        }
        if (isset($data['cardType'])) {
            $selectFilter .= "AND cardType = '" . $data['cardType'] . "' ";
            $selectFilterArray['cardType'] = $data['cardType'];
        }
        if (isset($data['countryOrAreaCode'])) {
            $selectFilter .= "AND countryOrAreaCode = '" . $data['countryOrAreaCode'] . "' ";
            $selectFilterArray['countryOrAreaCode'] = $data['countryOrAreaCode'];
        }
        if (isset($data['countryOrAreaName'])) {
            $selectFilter .= "AND countryOrAreaName LIKE '%" . $data['countryOrAreaName'] . "%' ";
            $selectFilterArray['countryOrAreaName'] = $data['countryOrAreaName'];
        }
        if (isset($data['cardVersion'])) {
            $selectFilter .= "AND cardVersion LIKE '%" . $data['cardVersion'] . "%' ";
            $selectFilterArray['cardVersion'] = $data['cardVersion'];
        }
        if (isset($data['currentApplyOrgCode'])) {
            $selectFilter .= "AND currentApplyOrgCode LIKE '%" . $data['currentApplyOrgCode'] . "%' ";
            $selectFilterArray['currentApplyOrgCode'] = $data['currentApplyOrgCode'];
        }
        if (isset($data['signNum'])) {
            $selectFilter .= "AND signNum = '" . $data['signNum'] . "' ";
            $selectFilterArray['signNum'] = $data['signNum'];
        }
        if (isset($data['address'])) {
            $selectFilter .= "AND address LIKE '%" . $data['address'] . "%' ";
            $selectFilterArray['address'] = $data['address'];
        }
        if (isset($data['higherTemp'])) {
            $selectFilter .= "AND temp > '" . $data['higherTemp'] . "' ";
            $selectFilterArray['higherTemp'] = $data['higherTemp'];
        }
        if (isset($data['lowerTemp'])) {
            $selectFilter .= "AND temp < '" . $data['lowerTemp'] . "' ";
            $selectFilterArray['lowerTemp'] = $data['lowerTemp'];
        }
        if (isset($data['passTime'])) {
            $selectFilter .= "AND passTime = '" . $data['passTime'] . "' ";
            $selectFilterArray['passTime'] = $data['passTime'];
        }
        if (isset($data['beforeTime'])) {
            $selectFilter .= "AND passTime < '" . $data['beforeTime'] . "' ";
            $selectFilterArray['beforeTime'] = $data['beforeTime'];
        }
        if (isset($data['afterTime'])) {
            $selectFilter .= "AND passTime > '" . $data['afterTime'] . "' ";
            $selectFilterArray['afterTime'] = $data['afterTime'];
        }
        if (isset($data['betweenTime'])) {
            $selectFilter .= "AND passTime BETWEEN '" . $data['betweenTime'][0] . "' AND '" . $data['betweenTime'][1] . "' ";
            $selectFilterArray['betweenTime'] = $data['betweenTime'];
        }
        if (isset($data['equipmentId'])) {
            $selectFilter .= "AND equipmentId = '" . $data['equipmentId'] . "' ";
            $selectFilterArray['equipmentId'] = $data['equipmentId'];
        }
        if (isset($data['equipmentName'])) {
            $selectFilter .= "AND equipmentName LIKE '%" . $data['equipmentName'] . "%' ";
            $selectFilterArray['equipmentName'] = $data['equipmentName'];
        }
        if (isset($data['equipmentType'])) {
            $selectFilter .= "AND equipmentType = '" . $data['equipmentType'] . "' ";
            $selectFilterArray['equipmentType'] = $data['equipmentType'];
        }
        if (isset($data['status'])) {
            $selectFilter .= "AND status = '" . $data['status'] . "' ";
            $selectFilterArray['status'] = $data['status'];
        }
        if (isset($data['stationId'])) {
            $selectFilter .= "AND stationId = '" . $data['stationId'] . "' ";
            $selectFilterArray['stationId'] = $data['stationId'];
        }
        if (isset($data['stationName'])) {
            $selectFilter .= "AND stationName LIKE '%" . $data['stationName'] . "%' ";
            $selectFilterArray['stationName'] = $data['stationName'];
        }
        if (isset($data['placeType'])) {
            $selectFilter .= "AND plateType = '" . $data['placeType'] . "' ";
            $selectFilterArray['placeType'] = $data['placeType'];
        }
        if (isset($data['identity'])) {
            $selectFilter .= "AND identity = '" . $data['identity'] . "' ";
            $selectFilterArray['identity'] = $data['identity'];
        }
        if (isset($data['homePlace'])) {
            $selectFilter .= "AND homePlace LIKE '%" . $data['homePlace'] . "%' ";
            $selectFilterArray['homePlace'] = $data['homePlace'];
        }
        if (isset($data['contact'])) {
            $selectFilter .= "AND contact LIKE '%" . $data['contact'] . "%' ";
            $selectFilterArray['contact'] = $data['contact'];
        }
        if (isset($data['isConsist'])) {
            $selectFilter .= "AND isConsist = '" . $data['isConsist'] . "' ";
            $selectFilterArray['isConsist'] = $data['isConsist'];
        }
        if (isset($data['higherCompareScore'])) {
            $selectFilter .= "AND compareScore > '" . $data['higherCompareScore'] . "' ";
            $selectFilterArray['higherCompareScore'] = $data['higherCompareScore'];
        }
        if (isset($data['lowerCompareScore'])) {
            $selectFilter .= "AND compareScore < '" . $data['lowerCompareScore'] . "' ";
            $selectFilterArray['lowerCompareScore'] = $data['lowerCompareScore'];
        }
        if (isset($data['openMode'])) {
            $selectFilter .= "AND openMode = '" . $data['openMode'] . "' ";
            $selectFilterArray['openMode'] = $data['openMode'];
        }
        if (isset($data['visitReason'])) {
            $selectFilter .= "AND visitReason LIKE '%" . $data['visitReason'] . "%' ";
            $selectFilterArray['visitReason'] = $data['visitReason'];
        }
        if (isset($data['mac'])) {
            $selectFilter .= "AND mac LIKE '%" . $data['mac'] . "%' ";
            $selectFilterArray['mac'] = $data['mac'];
        }
        if (isset($data['imsi'])) {
            $selectFilter .= "AND imsi LIKE '%" . $data['imsi'] . "%' ";
            $selectFilterArray['imsi'] = $data['imsi'];
        }
        if (isset($data['imei'])) {
            $selectFilter .= "AND imei LIKE '%" . $data['imei'] . "%' ";
            $selectFilterArray['imei'] = $data['imei'];
        }

        // error_reporting(E_ERROR | E_WARNING | E_PARSE);


        if (strlen($data['area']) == 0 && strlen($data['darea']) == 0) {
            $personnelDataNum = json_decode($this->getPersonnelList('', $data['areaList'], '', $data['dareaList'], '1', '0', $selectFilterArray), true)[0];

        } else {
            $personnelDataNum = json_decode($this->getPersonnelList($data['area'], '', $data['darea'], '', '1', '0', $selectFilterArray), true)[0];
        }

        if ($personnelDataNum == 0) {
            return json_encode(Array('info' => '所选条件范围内无记录'), JSON_UNESCAPED_UNICODE);
        }


        if (strlen($selectFilter) == 0) {
            return json_encode(Array('error' => '条件 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        $query .= $selectPlan . $selectFilter . ' LIMIT 5000';
        for($page = 1;(5000*($page-1))<$personnelDataNum;$page++){
            if (strlen($data['area']) == 0 && strlen($data['darea']) == 0) {
                $personnelData = 
                    array_slice(
                        json_decode($this->getPersonnelList('', $data['areaList'], '', $data['dareaList'], $page, '5000', $selectFilterArray), true)
                    ,1);
            } else {
                $personnelData = 
                    array_slice(
                        json_decode($this->getPersonnelList($data['area'], '', $data['darea'], '', $page, '5000', $selectFilterArray), true)
                    ,1);
            }
            
            $res = $this->db->database->query($query);

            if ($res && $this->db->database->affected_rows >= 1) {
                $f = new FileManager();
                foreach ($personnelData as $t) {
                    if (isset($t['personImg']) && strlen($t['personImg']) != 0) {
                        $f->deleteImage($t['personImg'], 'personnel');
                    }
                    if (isset($t['idCardImg']) && strlen($t['idCardImg']) != 0) {
                        $f->deleteImage($t['idCardImg'], 'personnel');
                    }
                }
            } 
            else {
                return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
            }
        }
        return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);

    }
    /*
     *
     */
//    public function deleteDevice($id, $name)
//    {
//        if (!$this->rolePermission->checkDeleteDevice()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($id) == 0 || strlen($name) == 0) {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "DELETE FROM $this->deviceTable WHERE `deviceId` = '$id' AND `name` = '$name'";
//
//        $res = $this->db->database->query($query);
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }
    public function delEquipment($data)
    {
        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            if (!(isset($data['area']) || isset($data['areaList'])) && (isset($data['darea']) || isset($data['dareaList']))) {
                return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            $query = "DELETE FROM $this->equipmentTable WHERE 1=1 ";
            $selectPlan = "";
            $selectFilter = '';

            $roleList = json_decode($this->getRoleList(), true);

            if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
                if (!$data['area'] || strlen($data['area']) == 0) {
                    if (!is_array($data['areaList'])) {
                        $data['areaList'] = json_decode($data['areaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";

                    foreach ($data['areaList'] as $a) {
                        $flag = false;
                        foreach ($roleList as $r) {
                            if ($r['delEquipment'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($a) . '：' . $a . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                            }

                            if (in_array($a, $r)) {
                                $flag = true;
                                if ($r['delEquipment'] == 0) {
                                    return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($a) . '：' . $a . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                                }
                                $selectPlan .= "OR area = '$a' ";
                                break;
                            }
                        }
                        if (!$flag) {
                            return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($a) . '：' . $a . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                        }

                    }
                } else {
                    $flag = false;
                    foreach ($roleList as $r) {
                        if (in_array($data['area'], $r)) {
                            $flag = true;
                            if ($r['delEquipment'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($data['area']) . '：' . $data['area'] . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                            }
                            $selectPlan .= "AND (area = '{$data['area']}' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getAreaName($data['area']) . '：' . $data['area'] . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                }
                $selectPlan .= ") ";

                if (!$data['darea'] || strlen($data['darea']) == 0) {
                    if (!is_array($data['dareaList'])) {
                        $dareaList = json_decode($data['dareaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";
                    foreach ($dareaList as $d) {
                        $flag = false;
                        foreach ($roleList as $r) {
                            if ($r['delEquipment'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($d) . '：' . $d . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                            }

                            if (in_array($d, $r)) {
                                $flag = true;
                                if ($r['delEquipment'] == 0) {
                                    return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($d) . '：' . $d . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                                }
                                $selectPlan .= "OR darea = '$d' ";
                                break;
                            }
                        }
                        if (!$flag) {
                            return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($d) . '：' . $d . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                        }

                    }
                } else {
                    $flag = false;
                    foreach ($roleList as $r) {
                        if (in_array($data['darea'], $r)) {
                            $flag = true;
                            if ($r['delEquipment'] == 0) {
                                return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($data['darea']) . '：' . $data['darea'] . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                            }
                            $selectPlan .= "AND (darea = '{$data['darea']}' ";
                            break;
                        }
                    }
                    if (!$flag) {
                        return json_encode(Array('error' => '删除失败，您没有删除 ' . $this->getDareaName($data['darea']) . '：' . $data['darea'] . ' 设备信息的权限'), JSON_UNESCAPED_UNICODE);
                    }
                }
                $selectPlan .= ") ";

            } else if ($this->user->getPermission()->fetch_assoc()['permission'] == '8') {
                if (!$data['area'] || strlen($data['area']) == 0) {
                    if (!is_array($data['areaList'])) {
                        $data['areaList'] = json_decode($data['areaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";

                    foreach ($data['areaList'] as $a) {
                        $selectPlan .= "OR area = '$a' ";
                    }
                } else {
                    $selectPlan .= "AND (area = '${data['area']}' ";
                }
                $selectPlan .= ") ";

                if (!$data['darea'] || strlen($data['darea']) == 0) {
                    if (!is_array($data['dareaList'])) {
                        $data['dareaList'] = json_decode($data['dareaList'], true);
                    }

                    $selectPlan .= "AND ( 1 = 0 ";
                    foreach ($data['dareaList'] as $d) {
                        $selectPlan .= "OR darea = '$d' ";
                    }
                } else {
                    $selectPlan .= "AND (darea = '{$data['darea']}' ";
                }
                $selectPlan .= ") ";
            }
        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }

        $selectFilterArray = Array();

        if (isset($data['beforeTime'])) {
            $selectFilter .= "AND checkTime < '" . $data['beforeTime'] . "' ";
            $selectFilterArray['beforeTime'] = $data['beforeTime'];
        }
        if (isset($data['afterTime'])) {
            $selectFilter .= "AND checkTime > '" . $data['afterTime'] . "' ";
            $selectFilterArray['afterTime'] = $data['afterTime'];
        }
        if (isset($data['betweenTime'])) {
            $selectFilter .= "AND checkTime BETWEEN '" . $data['betweenTime'][0] . "' AND '" . $data['betweenTime'][1] . "' ";
            $selectFilterArray['betweenTime'] = $data['betweenTime'];
        }
        if (isset($data['equipmentId'])) {
            $selectFilter .= "AND equipmentId = '" . $data['equipmentId'] . "' ";
            $selectFilterArray['equipmentId'] = $data['equipmentId'];
        }
        if (isset($data['equipmentName'])) {
            $selectFilter .= "AND equipmentName LIKE '%" . $data['equipmentName'] . "%' ";
            $selectFilterArray['equipmentName'] = $data['equipmentName'];
        }
        if (isset($data['equipmentType'])) {
            $selectFilter .= "AND equipmentType = '" . $data['equipmentType'] . "' ";
            $selectFilterArray['equipmentType'] = $data['equipmentType'];
        }
        if (isset($data['status'])) {
            $selectFilter .= "AND equipmentStatus = '" . $data['status'] . "' ";
            $selectFilterArray['status'] = $data['status'];
        }


        if (strlen($data['area']) == 0 && strlen($data['darea']) == 0) {
            $equipmentData = json_decode($this->getEquipmentList('', $data['$areaList'], '', $data['dareaList'], '1', '0', $selectFilterArray), true);
        } else {
            $equipmentData = json_decode($this->getEquipmentList($data['area'], '', $data['darea'], '', '1', '0', $selectFilterArray), true);
        }

        if ($equipmentData[0] == 0) {
            return json_encode(Array('info' => '所选条件范围内无记录'), JSON_UNESCAPED_UNICODE);
        }


        if (strlen($selectFilter) == 0) {
            return json_encode(Array('error' => '条件 参数错误'), JSON_UNESCAPED_UNICODE);
        }
        $query .= $selectPlan . $selectFilter;

//        die($query);

        $res = $this->db->database->query($query);

        if ($res && $this->db->database->affected_rows >= 1) {
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

//        if (strlen($data['area']) == 0 && strlen($data['darea']) == 0) {
//            $equipmentDataNum = json_decode($this->getEquipmentList('', $data['areaList'], '', $data['dareaList'], '1', '0', $selectFilterArray), true)[0];
//
//        } else {
//            $equipmentDataNum = json_decode($this->getEquipmentList($data['area'], '', $data['darea'], '', '1', '0', $selectFilterArray), true)[0];
//        }
//
//        if ($equipmentDataNum == 0) {
//            return json_encode(Array('info' => '所选条件范围内无记录'), JSON_UNESCAPED_UNICODE);
//        }
//
//
//        if (strlen($selectFilter) == 0) {
//            return json_encode(Array('error' => '条件 参数错误'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query .= $selectPlan . $selectFilter . ' LIMIT 5000';
//        for($page = 1;$equipmentDataNum - (5000*($page-1))>0;$page++){
//            if (strlen($data['area']) == 0 && strlen($data['darea']) == 0) {
//                $equipmentData =
//                    array_slice(
//                        json_decode($this->getEquipmentList('', $data['areaList'], '', $data['dareaList'], $page, '5000', $selectFilterArray), true)
//                        ,1);
//            } else {
//                $equipmentData =
//                    array_slice(
//                        json_decode($this->getEquipmentList($data['area'], '', $data['darea'], '', $page, '5000', $selectFilterArray), true)
//                        ,1);
//            }
//
//            $res = $this->db->database->query($query);
//
//            if ($res && $this->db->database->affected_rows >= 1) {
//                continue;
//            }
//            else {
//                return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//            }
//        }
//        return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);

    }
//    public function deletePersonnel($time, $name, $idCard)
//    {
//        if (!$this->rolePermission->checkDeletePersonnel()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//
//        if (strlen($time) == 0 || strlen($name) == 0 || strlen($idCard) == 0) {
//            return json_encode(Array('error' => '操作失败，请检查信息是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "SELECT personImg,idCardImg FROM $this->personnelTable WHERE `time` = '$time' AND `name` = '$name' AND `idCard` = '$idCard'";
//
//        $res = $this->db->database->query($query)->fetch_assoc();
//
//        $f = new FileManager();
//
//        if($res['personImg'] != null && strlen($res['personImg'])!=0)
//        {
//            $f->deleteImage($res['personImg'],'personnel');
//        }
//
//        if($res['idCardImg'] != null && strlen($res['idCardImg'])!=0)
//        {
//            $f->deleteImage($res['idCardImg'],'personnel');
//        }
//
//        $query = "DELETE FROM $this->personnelTable WHERE `time` = '$time' AND `name` = '$name' AND `idCard` = '$idCard'";
//
//        $res = $this->db->database->query($query);
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }
    public function delRole($data)
    {
        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            $query = "DELETE FROM $this->roleTable WHERE 1=1 ";

            $selectFilter = '';
            $selectFilterArray = Array();

            if (isset($data['id']) && strlen($data['id']) != 36) {
                return json_encode(Array('error' => 'id 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['id'])) {
                $selectFilter .= "AND id = '" . $data['id'] . "' ";
                $selectFilterArray['id'] = $data['id'];
            }

            if (isset($data['area']) && isset($data['darea'])) {
                if (isset($data['area']) && strlen($data['area']) != 9) {
                    return json_encode(Array('error' => '行政区域代码错误'), JSON_UNESCAPED_UNICODE);
                }
                if (isset($data['darea']) && strlen($data['darea']) != 19) {
                    return json_encode(Array('error' => '场所代码错误'), JSON_UNESCAPED_UNICODE);
                }
                if ($data['area'] !== substr($data['darea'], 0, 9)) {
                    return json_encode(Array('error' => '地区与场所不符'), JSON_UNESCAPED_UNICODE);
                }

                $selectFilter .= "AND areaCode = '" . $data['area'] . "' AND dareaCode = '{$data['darea']}' ";
                $selectFilterArray['area'] = $data['area'];
                $selectFilterArray['darea'] = $data['darea'];
            } else if (isset($data['area'])) {
                $selectFilter .= "AND areaCode = '" . $data['area'] . "' ";
                $selectFilterArray['area'] = $data['area'];
            }

//            var_dump(array_merge($selectFilterArray,['page'=>1,'num'=>1]));
            $roleData = json_decode($this->getRoleList(array_merge($selectFilterArray,['page'=>1,'num'=>1])), true);
//            var_dump($roleData);

            if ($roleData[0] == 0) {
                return json_encode(Array('info' => '所选条件范围内无记录'), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($selectFilter) == 0) {
                return json_encode(Array('error' => '条件 参数错误'), JSON_UNESCAPED_UNICODE);
            }

            $query .= $selectFilter;
//            die($query);
            $res = $this->db->database->query($query);

            if ($res && $this->db->database->affected_rows >= 1) {
//                $f = new FileManager();
//                foreach (array_splice($personnelData, 1) as $t) {
//                    if (isset($t['userImg']) && strlen($t['userImg']) != 0) {
//                        $f->deleteImage($t['userImg'], 'user');
//                    }
//                }
                return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
            } else {
                return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
            }
        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function delUser($data)
    {
        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            $query = "DELETE FROM $this->userTable WHERE 1=1 ";
            $selectFilter = "";
            $selectFilterArray = Array();

            if (isset($data['id']) && strlen($data['id']) != 36) {
                if (json_decode($this->getUserList(1, 1, ['id' => $data['id']]), true)[0] == 0) {
                    return json_encode(Array('error' => '此 id 不存在'), JSON_UNESCAPED_UNICODE);
                }
                return json_encode(Array('error' => 'id 参数错误'), JSON_UNESCAPED_UNICODE);
            } else if (isset($data['id'])) {
                $selectFilter .= "AND id = '{$data['id']}' ";
                $selectFilterArray['id'] = $data['id'];
            }

        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }


        if (isset($data['name'])) {
            $selectFilter .= "AND name LIKE '%" . $data['name'] . "%' ";
            $selectFilterArray['name'] = $data['name'];
        }
        if (isset($data['gender'])) {
            $selectFilter .= "AND gender = '" . $data['gender'] . "' ";
            $selectFilterArray['gender'] = $data['gender'];
        }
        if (isset($data['email'])) {
            $selectFilter .= "AND email LIKE '%" . $data['email'] . "%' ";
            $selectFilterArray['email'] = $data['email'];
        }
        if (isset($data['contact'])) {
            $selectFilter .= "AND contact LIKE '%" . $data['contact'] . "%' ";
            $selectFilterArray['contact'] = $data['contact'];
        }
        if (isset($data['idCard'])) {
            $selectFilter .= "AND idCard = '" . $data['idCard'] . "' ";
            $selectFilterArray['idCard'] = $data['idCard'];
        }
        if (isset($data['carType'])) {
            $selectFilter .= "AND carType = '" . $data['carType'] . "' ";
            $selectFilterArray['carType'] = $data['carType'];
        }
        if (isset($data['permission'])) {
            $selectFilter .= "AND permission = '" . $data['permission'] . "' ";
            $selectFilterArray['permission'] = $data['permission'];
        }

        $userData = json_decode($this->getUserList('1', '9999999999999', $selectFilterArray), true);


        if ($userData[0] == 0) {
            return json_encode(Array('info' => '所选条件范围内无记录'), JSON_UNESCAPED_UNICODE);
        }


        if (strlen($selectFilter) == 0) {
            return json_encode(Array('error' => '条件 参数错误'), JSON_UNESCAPED_UNICODE);
        }
        $query .= $selectFilter;

        $this->delRole(['id' => $data['id']]);
//        die($query);
        $res = $this->db->database->query($query);

        if ($res && $this->db->database->affected_rows >= 1) {
            $f = new FileManager();

            foreach (array_splice($userData, 1) as $t) {

                if (isset($t['userImg']) && strlen($t['userImg']) != 0) {
                    $f->deleteImage($t['userImg'], 'user');
                }
            }
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function delPlan($data){

        if ($this->user->getPermission()->fetch_assoc()['permission'] != '8') {
            return json_encode(Array('error' => '您没有该权限'), JSON_UNESCAPED_UNICODE);
        }

        if ($data) {
            if (!is_array($data)) {
                $data = json_decode($data, true);
            }

            $query = "DELETE FROM $this->planTable WHERE 1=1 ";
            $selectFilter = "";
            $selectFilterArray = Array();


            if (isset($data['area'])) {
                $selectFilter .= "AND area = '" . $data['area'] . "' ";
            }
            if (isset($data['darea'])) {
                $selectFilter .= "AND darea = '" . $data['darea'] . "' ";

                $carData = json_decode($this->getCarList($data['area'],'',$data['darea'],'','1','9999999999999',''),true);
                $personnelData = json_decode($this->getPersonnelList($data['area'],'',$data['darea'],'','1','9999999999999',''),true);
                $equipmentData = json_decode($this->getEquipmentList($data['area'],'',$data['darea'],'','1','9999999999999',''),true);
            }
            else{
                $planData = json_decode($this->getPlanList('1','9999999999999',['area'=>$data['area']]),true);
//                var_dump($planData);
                $dareaList = Array();
                foreach (array_splice($planData,1) as $t)
                {
                    array_push($dareaList,$t['darea']);
                }
                $carData = json_decode($this->getCarList($data['area'],'','',$dareaList,'1','9999999999999',''),true);
                $personnelData = json_decode($this->getPersonnelList($data['area'],'','',$dareaList,'1','9999999999999',''),true);
                $equipmentData = json_decode($this->getEquipmentList($data['area'],'','',$dareaList,'1','9999999999999',''),true);

                $selectFilter .= "AND ( 1 = 0 ";
                foreach ($dareaList as $d) {
                    $selectFilter .= "OR darea = '$d' ";
                }
                $selectFilter .= ") ";
            }


            if(intval($carData[0]) + intval($personnelData[0]) + intval($equipmentData[0]) != 0)
            {
                return json_encode(Array('error' => '此区域 / 场所 已有数据，请先删除其相关数据再进行此操作'), JSON_UNESCAPED_UNICODE);
            }

            $query .= $selectFilter;

            $res = $this->db->database->query($query);

            if ($res && $this->db->database->affected_rows >= 1) {
                $this->delRole(['area'=>$data['area']]);
                return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
            } else {
                return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
            }
        } else {
            return json_encode(Array('error' => 'data 参数错误'), JSON_UNESCAPED_UNICODE);
        }
    }
}

//    public function deleteUser($id, $user)
//    {
//        if (!$this->rolePermission->checkDeleteUser()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "SELECT role FROM $this->userTable WHERE id='$id' AND userName = '$user'";
//
//        $res = $this->db->database->query($query)->fetch_assoc()['role'];
//
//
//        if ($res == '000') {
//            return json_encode(Array('error' => '操作失败，不可删除超级管理员用户'), JSON_UNESCAPED_UNICODE);
//        }
//        if ($res == '001') {
//            if (!$this->rolePermission->checkDeleteAdmin()) {
//                return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//            }
//        }
//
//        $res = $this->db->database->query("DELETE FROM $this->userTable WHERE id = '$id' AND userName = '$user'");
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//    }

//    public function deletePlace($id, $area)
//    {
//        if (!$this->rolePermission->checkEditPlace()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($id) != 5) {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($id == '00000') {
//            return json_encode(Array('error' => '操作失败，该场所为系统自动生成，不可修改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $area = $this->db->database->query("SELECT p.area FROM $this->areaTable a,$this->placeTable p WHERE p.area = a.code AND a.name = '$area' AND p.id = '$id'")->fetch_assoc()['area'];
//
//        $query = "DELETE FROM $this->placeTable WHERE id = '$id' AND area = '$area'";
//
//        $res = $this->db->database->query($query);
//
//        if ($this->db->database->errno == 1451) {
//            return json_encode(Array('error' => '操作失败，该场所已有用户或设备，请先修改其场所后再进行此操作'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//    }

//    public function deleteRole($id)
//    {
//        if (!$this->rolePermission->checkEditRole()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($id) != 3) {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($id == '000' || $id == '888' || $id == '001' || $id == '002') {
//            return json_encode(Array('error' => '操作失败，该用户组为系统用户组，不可修改'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "DELETE FROM $this->rolePermissionTable WHERE role = '$id'";
//
//        $deleteRolePermissionRes = $this->db->database->query($query);
//
//        if ($this->db->database->errno == 1451) {
//            return json_encode(Array('error' => '操作失败，该用户组已有用户，请先修改其用户组后再进行此操作'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "DELETE FROM $this->roleTable WHERE role = '$id'";
//
//        $deleteRoleRes = $this->db->database->query($query);
//
//        if ($deleteRolePermissionRes && $deleteRoleRes && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//    }

//    public function deleteArea($code, $name, $level, $parentCode)
//    {
//        if (!$this->rolePermission->checkEditArea()) {
//            return json_encode(Array('error' => '操作失败，该用户组无此权限'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($code == '0') {
//            return json_encode(Array('error' => '操作失败，该区域为根级区域，不可删除'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if (strlen($code) != 12 || strlen($parentCode) == 0 || strlen($name) == 0 || strlen($level) == 0 || intval($level) <= 0 || intval($level) > 5) {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "SELECT code FROM $this->areaTable WHERE level = " . (intval($level) + 1) . " AND parentCode = '$code'";
//        $res = $this->db->database->query($query)->fetch_assoc();
//
//        if ($res) {
//            return json_encode(Array('error' => '操作失败，该区域已有子级，请先删除子级后再进行此操作'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $query = "DELETE FROM $this->areaTable WHERE `code` = '$code' AND `name` = '$name' AND `level` = '$level' AND `parentCode` = '$parentCode'";
//
//        $res = $this->db->database->query($query);
//        $err = $this->db->database->errno;
//
//        if ($err == 1451) {
//            return json_encode(Array('error' => '操作失败，该区域已被使用，请先修改后再进行此操作'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if ($res && $this->db->database->affected_rows == 1) {
//            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
//        } else {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//    }
//}