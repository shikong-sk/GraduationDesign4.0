<?php
//phpinfo();
//require_once($_SERVER['DOCUMENT_ROOT'] . '/System/Core/Class/RolePermissionClass.php');
require_once(dirname(__FILE__). '/Class/RoleClass.php');

//require_once($_SERVER['DOCUMENT_ROOT'] . '/System/Core/Class/Abstract/UserClass.php');
require_once(dirname(__FILE__) . '/Class/Abstract/UserClass.php');

//require_once($_SERVER['DOCUMENT_ROOT'] . '/System/Core/Class/ManagementClass.php');
require_once(dirname(__FILE__). '/Class/ManagementClass.php');

session_start();

header('Content-Type:application/json; charset=utf-8');

//$User = new User();
//echo $User->login('1','123+AbC');

//
//$Role = new rolePermissionClass($User->getRole());
//
////if(isset($_POST['']))
//
//echo $Role->getRolePermissionList();

$Management = new ManagementClass();


/*
 * 相关信息查询
 */
if(isset($_POST['getAdminList']))
{
    die($Management->getAdminList());
}

if(isset($_POST['getRoleList']))
{
    die($Management->getRoleList($_POST['data']));
}

if(isset($_POST['getCarList']) && isset($_POST['page']) && isset($_POST['num']))
{
    die($Management->getCarList($_POST['area'],$_POST['areaList'],$_POST['darea'],$_POST['dareaList'],$_POST['page'],$_POST['num'],$_POST['filter']));
}

if(isset($_POST['getPersonnelList']) && isset($_POST['page']) && isset($_POST['num']))
{
    die($Management->getPersonnelList($_POST['area'],$_POST['areaList'],$_POST['darea'],$_POST['dareaList'],$_POST['page'], $_POST['num'],$_POST['filter']));
}

if(isset($_POST['getUserList']) && isset($_POST['page']) && isset($_POST['num']))
{
    die($Management->getUserList($_POST['page'],$_POST['num'],$_POST['filter']));
}

if(isset($_POST['getEquipmentList']) && isset($_POST['page']) && isset($_POST['num']))
{
    die($Management->getEquipmentList($_POST['area'],$_POST['areaList'],$_POST['darea'],$_POST['dareaList'],$_POST['page'], $_POST['num'],$_POST['filter']));
}

if(isset($_POST['getAreaList']) && isset($_POST['page']) && isset($_POST['num']) && isset($_POST['filter'])) {
    die($Management->getAreaList($_POST['page'], $_POST['num'],$_POST['filter']));
}

if(isset($_POST['getPlanList']) && isset($_POST['page']) && isset($_POST['num']) && isset($_POST['filter'])) {
    die($Management->getPlanList($_POST['page'], $_POST['num'],$_POST['filter']));
}

/*
 * 添加记录
 */
if ( isset($_POST['addCar']) && isset($_POST['area']) && isset($_POST['darea']) && isset($_POST['data']))
{
    die($Management->addCar($_POST['area'], $_POST['darea'], $_POST['data']));
}

if(isset($_POST['addPersonnel']) && isset($_POST['area']) && isset($_POST['darea']) && isset($_POST['data']))
{
    die($Management->addPersonnel($_POST['area'], $_POST['darea'], $_POST['data']));
}

if(isset($_POST['addEquipment']) && isset($_POST['area']) && isset($_POST['darea']) && isset($_POST['data']))
{
    die($Management->addEquipment($_POST['area'], $_POST['darea'], $_POST['data']));
}

//if(isset($_POST['addDevice']) && isset($_POST['id']) && isset($_POST['name']) && isset($_POST['area']) && isset($_POST['place']) && isset($_POST['ip']))
//{
//    die($Management->addDevice($_POST['id'],$_POST['name'],$_POST['area'],$_POST['place'],$_POST['ip']));
//}

if(isset($_POST['addUser']) && isset($_POST['data']))
{
    die($Management->addUser($_POST['data']));
}

if(isset($_POST['addRole'])  && isset($_POST['area']) && isset($_POST['darea']) && isset($_POST['data']))
{
    die($Management->addRole($_POST['area'], $_POST['darea'], $_POST['data']));
}

if(isset($_POST['addPlan']) && isset($_POST['data']))
{
    die($Management->addPlan($_POST['data']));
}

//if(isset($_POST['addArea']) && isset($_POST['code']) && isset($_POST['name']) && isset($_POST['level']) && isset($_POST['parentCode']))
//{
//    die($Management->addArea($_POST['code'],$_POST['name'],$_POST['level'],$_POST['parentCode']));
//}


/*
 * 更新记录
 */
if(isset($_POST['updateRole']) && isset($_POST['data']))
{
    die($Management->updateRole($_POST['data']));
}

if(isset($_POST['updateUser']) && isset($_POST['data']))
{
    die($Management->updateUser($_POST['data']));
}

if ( isset($_POST['updateCar']) && isset($_POST['area']) && isset($_POST['darea']) && isset($_POST['data']))
{
    die($Management->updateCar($_POST['area'], $_POST['darea'], $_POST['data']));
}

if ( isset($_POST['updatePersonnel']) && isset($_POST['area']) && isset($_POST['darea']) && isset($_POST['data']))
{
    die($Management->updatePersonnel($_POST['area'], $_POST['darea'], $_POST['data']));
}

if ( isset($_POST['updateEquipment']) && isset($_POST['area']) && isset($_POST['darea']) && isset($_POST['data']))
{
    die($Management->updateEquipment($_POST['area'], $_POST['darea'], $_POST['data']));
}

if(isset($_POST['updatePlan']) && isset($_POST['data']))
{
    die($Management->updatePlan($_POST['data']));
}

//if ( isset($_POST['updateCar']) && isset($_POST['time']) && isset($_POST['licensePlate']) && isset($_POST['area']) && isset($_POST['place']) && isset($_POST['come']))
//{
//    die($Management->updateCar($_POST['time'],$_POST['licensePlate'], $_POST['area'], $_POST['place'], $_POST['come']));
//}


//if(isset($_POST['updateDevice']) && isset($_POST['id']) && isset($_POST['name']) && isset($_POST['area']) && isset($_POST['place']) && isset($_POST['ip']))
//{
//    die($Management->updateDevice($_POST['id'],$_POST['name'],$_POST['area'],$_POST['place'],$_POST['ip']));
//}



//if(isset($_POST['updatePersonnel']) && isset($_POST['time']) && isset($_POST['name']) && isset($_POST['idCard']) && isset($_POST['area']) && isset($_POST['place']) && isset($_POST['come']) && isset($_POST['temp']))
//{
//    die($Management->updatePersonnel($_POST['time'],$_POST['name'],$_POST['idCard'],$_POST['area'],$_POST['place'],$_POST['come'],$_POST['temp']));
//}

//if(isset($_POST['addAdmin']) && isset($_POST['id']) && isset($_POST['name']))
//{
//    die($Management->addAdmin($_POST['id'],$_POST['name']));
//}
//
//if(isset($_POST['deleteAdmin']) && isset($_POST['id']) && isset($_POST['name']))
//{
//    die($Management->deleteAdmin($_POST['id'],$_POST['name']));
//}
//
//if(isset($_POST['updateRole']) && isset($_POST['id']) && isset($_POST['name']))
//{
//    die($Management->updateRole($_POST['id'], $_POST['name']));
//}
//
//if(isset($_POST['updatePlace']) && isset($_POST['id']) && isset($_POST['area']) && isset($_POST['name']))
//{
//    die($Management->updatePlace($_POST['id'],$_POST['area'],$_POST['name']));
//}
//
//if(isset($_POST['updateArea']) && isset($_POST['code']) && isset($_POST['name']) && isset($_POST['level']) && isset($_POST['parentCode']))
//{
//    die($Management->updateArea($_POST['code'],$_POST['name'],$_POST['level'],$_POST['parentCode']));
//}


/*
 * 删除记录
 */

if(isset($_POST['delCar']) && isset($_POST['data']))
{
    die($Management->delCar($_POST['data']));
}

if(isset($_POST['delPersonnel']) && isset($_POST['data']))
{
    die($Management->delPersonnel($_POST['data']));
}

if(isset($_POST['delEquipment']) && isset($_POST['data']))
{
    die($Management->delEquipment($_POST['data']));
}

if(isset($_POST['delRole']) && isset($_POST['data']))
{
    die($Management->delRole($_POST['data']));
}

if(isset($_POST['delUser']) && isset($_POST['data']))
{
    die($Management->delUser($_POST['data']));
}

if(isset($_POST['delPlan']) && isset($_POST['data']))
{
    die($Management->delPlan($_POST['data']));
}

//if(isset($_POST['deleteCar']) && isset($_POST['time']) && isset($_POST['licensePlate']) ){
//    die($Management->deleteCar($_POST['time'], $_POST['licensePlate']));
//}

//if(isset($_POST['deleteDevice']) && isset($_POST['id']) && isset($_POST['name']))
//{
//    die($Management->deleteDevice($_POST['id'],$_POST['name']));
//}

//if(isset($_POST['deletePersonnel']) && isset($_POST['time']) && isset($_POST['name']) && isset($_POST['idCard']))
//{
//    die($Management->deletePersonnel($_POST['time'],$_POST['name'],$_POST['idCard']));
//}

//if(isset($_POST['deleteUser']) && isset($_POST['id']) && isset($_POST['user']))
//{
//    die($Management->deleteUser($_POST['id'],$_POST['user']));
//}
//
//if(isset($_POST['deleteRole']) && isset($_POST['id']))
//{
//    die($Management->deleteRole($_POST['id']));
//}
//
//if(isset($_POST['deletePlace']) && isset($_POST['id']) && isset($_POST['area']))
//{
//    die($Management->deletePlace($_POST['id'],$_POST['area']));
//}
//
//if(isset($_POST['deleteArea']) && isset($_POST['code']) && isset($_POST['name']) && isset($_POST['level']) && isset($_POST['parentCode']))
//{
//    die($Management->deleteArea($_POST['code'],$_POST['name'],$_POST['level'],$_POST['parentCode']));
//}
