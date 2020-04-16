<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

class CourseClass
{

    var $db;

    var $studentTable;

    var $teacherTable;
    var $courseTable;

    var $classTable;

    var $scoreTable;


    var $model = array(
        "classId" => null,
        'teacherId' => null,
        'teacherName' => null,
        "courseId" => null,
        "courseName" => null,
        'startTime' => null,
        'endTime' => null,
        'public' => null,
    );

    var $filter = array(
        'page' => null,
        'num' => null,
    );

    public function __construct()
    {
        $this->db = new SqlHelper();

        $this->teacherTable = $this->db->db_table_prefix . "_" . SqlHelper::TEACHER;

        $this->classTable = $this->db->db_table_prefix . "_" . SqlHelper::CLASSES;
        $this->courseTable = $this->db->db_table_prefix . "_" . SqlHelper::COURSE;

        $this->scoreTable = $this->db->db_table_prefix . "_" . SqlHelper::SCORE;

    }

    public function getCourseList($data, $filter)
    {
        if (!is_array($data)) {
            if (!is_string($data)) {
                die(json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            if (strlen($data) == 0) {
                $data = array();
            }

            $data = json_decode($data, true);
            if (!$data) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        if (!is_array($filter)) {
            if (!is_string($filter)) {
                return json_encode(array("error" => "JSON filter 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($filter) != 0) {
                $filter = json_decode($filter, true);
                if (!$filter) {
                    return json_encode(array("error" => "JSON filter 解析失败"), JSON_UNESCAPED_UNICODE);
                }
            } else {
                return json_encode(array('error' => '请传入查询参数'), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }

        foreach ($filter as $k => $v) {
            if (!array_key_exists($k, $this->filter)) {
                unset($filter[$k]);
            }
        }

        $page = 1;
        $num = 1;

        if (isset($filter['page'])) {
            $page = intval($filter['page']);

            if (isset($filter['num'])) {
                $num = intval($filter['num']);
            } else {
                $num = 10;
            }
        }

        if ($page == null || $num == null) {
            return json_encode(array('error' => 'filter page 或 num 参数不合法'), JSON_UNESCAPED_UNICODE);
        }

        $query = $this->db->selectQuery('*', $this->courseTable);

        if (isset($data['courseName'])) {
            $query->andLikeQuery('courseName', "%{$data['courseName']}%");
            unset($data['courseName']);
        }
        if (isset($data['teacherName'])) {
            $query->andLikeQuery('teacherName', "%{$data['teacherName']}%");
            unset($data['teacherName']);
        }
        if (isset($data['classId'])) {
            $query->andLikeQuery('classId', "%{$data['classId']}%");
            unset($data['classId']);
        }

        $query->andQueryList($data);


        return $query->orderBy('classId DESC,public', 1)->selectLimit($page, $num)->getFetchAssocNumJson();

    }

    public function addCourse($data)
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            return json_encode(array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE);
        } else if ($_SESSION['ms_identity'] != 'Teacher') {
            return json_encode(array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('permission', $this->teacherTable)->andQueryList(array('teacherId' => $_SESSION['ms_id']))
                ->andLeftTripQuery_Or()->orQueryList(array('permission' => '0'))->orQueryList(array('permission' => '1'))->andRightTripQuery_Or()
                ->getSelectNum() == 0) {
            return json_encode(array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                die(json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            if (strlen($data) == 0) {
                $data = array();
            }

            $data = json_decode($data, true);
            if (!$data) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }

        $model = $this->model;

        unset($model["courseId"]);
        unset($model["teacherName"]);

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            } else {
                switch ($k) {
                    case "courseName":
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => 'data courseName 参数错误'), JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case 'public':
                        if ($data[$k] != '0' && $data[$k] != '1') {
                            return json_encode(array('error' => 'data public 参数错误'), JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case "classId":
                        if (strlen($data[$k]) != 8) {
                            return json_encode(array('error' => 'data classId 参数错误,classId 参数需要8个字符 例：17305021'), JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery("*", $this->classTable)->andQueryList(array("classId" => $data[$k]))->getSelectNum() == 0) {
                            return json_encode(array('error' => "该班级不存在"), JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case "teacherId":
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => 'data teacherId 参数错误'), JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery('*', $this->teacherTable)->andQueryList(array('teacherId' => $data[$k]))->getSelectNum() == 0) {
                            return json_encode(array('error' => "该教工不存在"), JSON_UNESCAPED_UNICODE);
                        } else {
                            $data["teacherName"] =
                                $this->db->selectQuery("teacherName", $this->teacherTable)->andQueryList(array('teacherId' => $data[$k]))->selectLimit(1, 1)->getFetchAssoc()[0]['teacherName'];
                        }
                        break;
                    case "startTime":
                        if (strlen($data[$k]) != 10) {
                            return json_encode(array('error' => 'data startTime 参数错误,startTime 参数需要10个字符 例：2020-01-01'), JSON_UNESCAPED_UNICODE);
                        } else if (!preg_match("/^\d{4}-\d{2}-\d{2}/", $data[$k])) {
                            return json_encode(array('error' => 'data startTime 参数错误,startTime 参数需要10个字符 例：2020-01-01'), JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case "endTime":
                        if (strlen($data[$k]) != 10) {
                            return json_encode(array('error' => 'data endTime 参数错误,endTime 参数需要10个字符 例：2020-01-01'), JSON_UNESCAPED_UNICODE);
                        } else if (!preg_match("/^\d{4}-\d{2}-\d{2}/", $data[$k])) {
                            return json_encode(array('error' => 'data endTime 参数错误,endTime 参数需要10个字符 例：2020-01-01'), JSON_UNESCAPED_UNICODE);
                        }
                        break;
                }
            }
        }

        if (strtotime($data["endTime"]) < strtotime($data["startTime"])) {
            return json_encode(array('error' => '结束时间不能早于开始时间'), JSON_UNESCAPED_UNICODE);
        }

        if($this->db->selectQuery("*",$this->courseTable)->andQueryList(array("classId"=>$data["classId"],"courseName"=>$data["courseName"],"teacherId"=>$data["teacherId"]))->getSelectNum() != 0)
        {
            return json_encode(array('error' => '该班级已开设此课程'), JSON_UNESCAPED_UNICODE);
        }

        $data["courseId"] = trim(guid(), '{}');

        if ($this->db->insertQuery($this->courseTable, $data)->insertExecute()->getAffectedRows() == 1) {
            return json_encode(array('success' => '课程添加成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('error' => '课程添加失败,请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function updateCourse($data)
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            return json_encode(array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE);
        } else if ($_SESSION['ms_identity'] != 'Teacher') {
            return json_encode(array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('permission', $this->teacherTable)->andQueryList(array('teacherId' => $_SESSION['ms_id']))
                ->andLeftTripQuery_Or()->orQueryList(array('permission' => '0'))->orQueryList(array('permission' => '1'))->andRightTripQuery_Or()
                ->getSelectNum() == 0) {
            return json_encode(array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                die(json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            if (strlen($data) == 0) {
                $data = array();
            }

            $data = json_decode($data, true);
            if (!$data) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }

        unset($data["teacherName"]);

        if (!isset($data['courseId'])) {
            return json_encode(array('error' => 'courseId 为必填参数'), JSON_UNESCAPED_UNICODE);
        }

        $model = array(
            "courseId" => null,
            "classId"=>null
        );

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            } else {
                switch ($k) {
                    case 'courseId':
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => '课程编号不能为空'), JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    case 'classId':
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => '班级编号不能为空'), JSON_UNESCAPED_UNICODE);
                        }
                        break;
                }
            }
        }

        if ($this->db->selectQuery('*',$this->courseTable)->andQueryList(array("courseId"=>$data["courseId"],"classId"=>$data["classId"]))->getSelectNum() == 0)
        {
            return json_encode(array('error' => '该课程不存在'), JSON_UNESCAPED_UNICODE);
        }

        foreach ($data as $k => $v)
        {
            switch ($k)
            {
                case "courseName":
                    if(strlen($v) == 0)
                    {
                        return json_encode(array('error' => '课程名称不能为空'), JSON_UNESCAPED_UNICODE);
                    }
                    else if($this->db->selectQuery("*",$this->courseTable)->andQueryList(array("classId"=>$data["classId"],"courseName"=>$data["courseName"]))->getSelectNum() != 0)
                    {
                        return json_encode(array('error' => '该班级已开设此课程'), JSON_UNESCAPED_UNICODE);
                    }
                    break;
                case "teacherId":
                    if (strlen($data[$k]) == 0) {
                        return json_encode(array('error' => 'data teacherId 参数错误'), JSON_UNESCAPED_UNICODE);
                    } else if ($this->db->selectQuery('*', $this->teacherTable)->andQueryList(array('teacherId' => $data[$k]))->getSelectNum() == 0) {
                        return json_encode(array('error' => "该教工不存在"), JSON_UNESCAPED_UNICODE);
                    } else {
                        $data["teacherName"] =
                            $this->db->selectQuery("teacherName", $this->teacherTable)->andQueryList(array('teacherId' => $data[$k]))->selectLimit(1, 1)->getFetchAssoc()[0]['teacherName'];
                    }
                    break;
                case "startTime":
                    if (strlen($data[$k]) != 10) {
                        return json_encode(array('error' => 'data startTime 参数错误,startTime 参数需要10个字符 例：2020-01-01'), JSON_UNESCAPED_UNICODE);
                    } else if (!preg_match("/^\d{4}-\d{2}-\d{2}/", $data[$k])) {
                        return json_encode(array('error' => 'data startTime 参数错误,startTime 参数需要10个字符 例：2020-01-01'), JSON_UNESCAPED_UNICODE);
                    }
                    break;
                case "endTime":
                    if (strlen($data[$k]) != 10) {
                        return json_encode(array('error' => 'data endTime 参数错误,endTime 参数需要10个字符 例：2020-01-01'), JSON_UNESCAPED_UNICODE);
                    } else if (!preg_match("/^\d{4}-\d{2}-\d{2}/", $data[$k])) {
                        return json_encode(array('error' => 'data endTime 参数错误,endTime 参数需要10个字符 例：2020-01-01'), JSON_UNESCAPED_UNICODE);
                    }
                    break;
            }
        }


        $courseId = $data["courseId"];
        unset($data["courseId"]);
        $classId = $data["classId"];
        unset($data["classId"]);

        if(isset($data["startTime"]) && !isset($data["endTime"]))
        {
            $data["endTime"] = $this->db->selectQuery("endTime",$this->courseTable)->andQueryList(array("courseId"=>$courseId,"classId"=>$classId))->selectLimit(1,1)->getFetchAssoc()[0]["endTime"];
        }
        else if(!isset($data["startTime"]) && isset($data["endTime"]))
        {
            $data["startTime"] = $this->db->selectQuery("startTime",$this->courseTable)->andQueryList(array("courseId"=>$courseId,"classId"=>$classId))->selectLimit(1,1)->getFetchAssoc()[0]["startTime"];
        }
        else if(isset($data["startTime"]) && isset($data["endTime"])){
            if (strtotime($data["endTime"]) < strtotime($data["startTime"])) {
                return json_encode(array('error' => '结束时间不能早于开始时间'), JSON_UNESCAPED_UNICODE);
            }
        }


        if ($this->db->updateQuery($this->courseTable, $data)->andQueryList(array("courseId"=>$courseId,"classId"=>$classId))->updateLimit(1)->updateExecute()->getAffectedRows() == 1) {
            if(isset($data["courseName"]))
            {
                $this->db->updateQuery($this->scoreTable,array("courseName"=>$data["courseName"]))->andQueryList(array("courseId"=>$courseId))->updateExecute();
            }
            if(isset($data["teacherName"]))
            {
                $this->db->updateQuery($this->scoreTable,array("teacherId"=>$data["teacherId"],"teacherName"=>$data["teacherName"]))->andQueryList(array("courseId"=>$courseId))->updateExecute();
            }
            return json_encode(array('success' => '课程信息更新成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('info' => '课程信息未更改'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function deleteCourse($data)
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            return json_encode(array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE);
        } else if ($_SESSION['ms_identity'] != 'Teacher') {
            return json_encode(array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('permission', $this->teacherTable)->andQueryList(array('teacherId' => $_SESSION['ms_id']))
                ->andLeftTripQuery_Or()->orQueryList(array('permission' => '0'))->orQueryList(array('permission' => '1'))->andRightTripQuery_Or()
                ->getSelectNum() == 0) {
            return json_encode(array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                die(json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            if (strlen($data) == 0) {
                $data = array();
            }

            $data = json_decode($data, true);
            if (!$data) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }
        if (!isset($data['courseId'])) {
            return json_encode(array('error' => 'courseId 为必填参数'), JSON_UNESCAPED_UNICODE);
        } else if (!isset($data['classId'])) {
            return json_encode(array('error' => 'classId 为必填参数'), JSON_UNESCAPED_UNICODE);
        } else if (!isset($data['teacherId'])) {
            return json_encode(array('error' => 'teacherId 为必填参数'), JSON_UNESCAPED_UNICODE);
        } else {
            if ($this->db->selectQuery('*', $this->courseTable)->andQueryList(array('courseId' => $data['courseId'], 'classId' => $data["classId"],"teacherId"=>$data["teacherId"]))->getSelectNum() == 0) {
                return json_encode(array('error' => '此课程不存在'), JSON_UNESCAPED_UNICODE);
            } else if ($this->db->selectQuery('*', $this->scoreTable)->andQueryList(array('courseId' => $data['courseId'], 'teacherId' => $data["teacherId"]))->getSelectNum() != 0) {
                return json_encode(array('error' => '该课程已登记成绩，请先删除其对应的信息后再进行此操作'), JSON_UNESCAPED_UNICODE);
            } else {
                if ($this->db->deleteQuery($this->courseTable)->andQueryList(array('courseId' => $data['courseId'], 'teacherId' => $data["teacherId"]))->deleteExecute()->getAffectedRows() == 1) {
                    return json_encode(array('success' => '课程删除成功'), JSON_UNESCAPED_UNICODE);
                } else {
                    return json_encode(array('error' => '课程删除失败'), JSON_UNESCAPED_UNICODE);
                }
            }
        }
    }
}