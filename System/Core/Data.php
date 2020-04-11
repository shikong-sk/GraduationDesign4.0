<?php
require_once(dirname(__FILE__) . '/Class/Abstract/DataClass.php');

header('Content-Type:application/json; charset=utf-8');

/*
 * 设备上传数据
 */

if(isset($_POST['equipmentId']) && isset($_POST['equipmentName']) && isset($_POST['areaCode']) && isset($_POST['dareaCode']) )
{

    $equipment = new DataClass($_POST['equipmentId'],$_POST['equipmentName'],$_POST['areaCode'],$_POST['dareaCode']);
    /*
     * 来访人员数据提交
     */
    if(isset($_POST['pushPersonnel']) && isset($_POST['passTime']) && isset($_POST['name']) && isset($_POST['idCard']) && isset($_POST['status'])){

        die($equipment->pushPersonnel(
        $_POST['passTime'],$_POST['name'],$_POST['gender'],$_POST['nation'],$_POST['idCard'],$_POST['cardType'],$_POST['countryOrAreaCode'],$_POST['countryOrAreaName'],$_POST['cardVersion'],
        $_POST['currentApplyOrgCode'],$_POST['signNum'],$_POST['birthDay'],$_POST['address'],$_POST['authority'],$_POST['validtyStart'],$_POST['validtyEnd'],
        $_POST['personImg'],$_POST['idCardImg'],$_POST['temp'],$_POST['areaCode'],$_POST['x'],$_POST['y'],$_POST['equipmentId'],$_POST['equipmentName'],$_POST['equipmentType'],$_POST['stationId'],$_POST['stationName'],
        $_POST['location'],$_POST['status'],$_POST['dareaName'],$_POST['dareaCode'],$_POST['dareaType'],$_POST['identity'],$_POST['homedarea'],$_POST['contact'],$_POST['isConsist'],$_POST['compareScore'],
        $_POST['openMode'],$_POST['visitReason'],$_POST['mac'],$_POST['imsi'],$_POST['imei'],$_POST['visitor']
        ));
    }

    /*
     * 来访车辆数据提交
     */
    if(isset($_POST['pushCar']) && isset($_POST['passTime']) && isset($_POST['plateNum']) && isset($_POST['status'])){
        die($equipment->pushCar($_POST['passTime'],$_POST['plateNum'],$_POST['plateColor'],$_POST['vehicleType'],$_POST['areaCode'],$_POST['x'],$_POST['y'],$_POST['equipmentId'],$_POST['equipmentName'],$_POST['equipmentType'],$_POST['stationId'],$_POST['stationName'],
            $_POST['location'],$_POST['vehicleColor'],$_POST['status'],$_POST['dareaName'],$_POST['dareaCode'],$_POST['placeType'],$_POST['carType'],$_POST['visitReason'],$_POST['visitor'],$_POST['driverData'],$_POST['passengerData'],$_POST['vehicleImg'],$_POST['plateImg']));
    }
    die(json_encode(Array('error' => '操作失败，参数错误'), JSON_UNESCAPED_UNICODE));
}
else{
    die(json_encode(Array('error' => '操作失败，参数错误 '), JSON_UNESCAPED_UNICODE));
}