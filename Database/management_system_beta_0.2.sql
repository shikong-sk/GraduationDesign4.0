-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 2020-04-10 20:07:22
-- 服务器版本： 5.5.29-log
-- PHP Version: 5.6.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `management_system`
--

-- --------------------------------------------------------

--
-- 表的结构 `ms_class`
--

CREATE TABLE IF NOT EXISTS `ms_class` (
  `classId` char(8) NOT NULL COMMENT '班级编号',
  `grade` char(2) NOT NULL COMMENT '年级',
  `years` char(1) NOT NULL COMMENT '学制',
  `departmentId` char(2) NOT NULL COMMENT '院系编号',
  `departmentName` varchar(255) NOT NULL COMMENT '院系名称',
  `majorId` char(2) NOT NULL COMMENT '专业编号',
  `majorName` varchar(255) NOT NULL COMMENT '专业名称',
  `class` char(1) NOT NULL COMMENT '班号',
  `studentNum` char(2) NOT NULL COMMENT '学生人数',
  UNIQUE KEY `uniqueClassData` (`classId`,`grade`,`years`,`departmentId`,`majorId`,`class`),
  KEY `classId` (`classId`),
  KEY `grade` (`grade`),
  KEY `years` (`years`),
  KEY `departmentId` (`departmentId`),
  KEY `majorId` (`majorId`),
  KEY `class` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ms_course`
--

CREATE TABLE IF NOT EXISTS `ms_course` (
  `courseId` char(36) NOT NULL COMMENT '课程id',
  `courseName` varchar(255) NOT NULL COMMENT '课程名',
  `teacherId` char(36) NOT NULL COMMENT '教工id',
  `teacherName` varchar(255) NOT NULL COMMENT '教工姓名',
  `startTime` date NOT NULL COMMENT '课程开始时间',
  `endTime` date NOT NULL COMMENT '课程结束时间',
  `public` tinyint(1) NOT NULL COMMENT '公选课',
  PRIMARY KEY (`courseId`),
  KEY `courseId` (`courseId`),
  KEY `teacherId` (`teacherId`),
  KEY `public` (`public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ms_department`
--

CREATE TABLE IF NOT EXISTS `ms_department` (
  `departmentId` char(2) NOT NULL,
  `departmentName` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`departmentId`) USING BTREE,
  UNIQUE KEY `departmentId` (`departmentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `ms_department`
--

INSERT INTO `ms_department` (`departmentId`, `departmentName`, `active`) VALUES
('01', '人文社科系', 1),
('02', '外语系', 1),
('03', '经管系', 1),
('04', '机电工程系', 1),
('05', '计算机系', 1),
('06', '艺术体育系', 1),
('07', '自然科学系', 1),
('08', '学前教育系', 1);

-- --------------------------------------------------------

--
-- 表的结构 `ms_grade`
--

CREATE TABLE IF NOT EXISTS `ms_grade` (
  `grade` char(2) NOT NULL COMMENT '年级',
  `departmentId` char(2) NOT NULL COMMENT '院系编号',
  `departmentName` varchar(255) NOT NULL COMMENT '院系名称',
  `majorId` char(2) NOT NULL COMMENT '专业编号',
  `majorName` varchar(255) NOT NULL COMMENT '专业名称',
  `classNum` char(1) NOT NULL COMMENT '班级数量',
  UNIQUE KEY `uniqueGradeData` (`grade`,`departmentId`,`majorId`) USING BTREE,
  KEY `departmentId` (`departmentId`),
  KEY `majorId` (`majorId`),
  KEY `grade` (`grade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ms_major`
--

CREATE TABLE IF NOT EXISTS `ms_major` (
  `majorId` char(2) NOT NULL,
  `majorName` varchar(255) NOT NULL,
  `departmentId` char(2) NOT NULL,
  `departmentName` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`majorId`,`departmentId`),
  UNIQUE KEY `uniqueMajorData` (`majorId`,`departmentId`) USING BTREE,
  KEY `majorId` (`majorId`),
  KEY `departmentId` (`departmentId`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ms_score`
--

CREATE TABLE IF NOT EXISTS `ms_score` (
  `studentId` char(10) NOT NULL COMMENT '学生Id',
  `studentName` varchar(255) NOT NULL COMMENT '学生姓名',
  `courseId` char(36) NOT NULL COMMENT '课程Id',
  `courseName` varchar(255) NOT NULL COMMENT '课程名称',
  `teacherId` char(36) NOT NULL COMMENT '教工Id',
  `teacherName` varchar(255) NOT NULL COMMENT '教工姓名',
  `score` char(3) NOT NULL COMMENT '分数',
  `flag` tinyint(1) NOT NULL COMMENT '0:正考分数 1:补考分数 2:重修分数',
  UNIQUE KEY `uniqueScoreData` (`studentId`,`courseId`,`teacherId`,`flag`) USING BTREE,
  KEY `courseId` (`courseId`),
  KEY `teacherId` (`teacherId`),
  KEY `flag` (`flag`),
  KEY `studentId` (`studentId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `ms_score`
--

INSERT INTO `ms_score` (`studentId`, `studentName`, `courseId`, `courseName`, `teacherId`, `teacherName`, `score`, `flag`) VALUES
('', '', '', '', '', '', '', 8);

-- --------------------------------------------------------

--
-- 表的结构 `ms_student`
--

CREATE TABLE IF NOT EXISTS `ms_student` (
  `studentId` char(10) NOT NULL COMMENT '学号',
  `studentName` varchar(15) NOT NULL COMMENT '学生姓名',
  `gender` char(1) NOT NULL COMMENT '性别',
  `both` date NOT NULL COMMENT '出生日期',
  `salt` char(6) NOT NULL COMMENT '加密盐值',
  `password` char(40) NOT NULL COMMENT '密码hash',
  `contact` char(20) NOT NULL COMMENT '联系方式',
  `grade` char(2) NOT NULL COMMENT '入学年级',
  `years` char(1) NOT NULL COMMENT '学制',
  `deparment` char(2) NOT NULL COMMENT '院系编号',
  `departmentName` varchar(255) NOT NULL COMMENT '院系名称',
  `major` char(2) NOT NULL COMMENT '专业编号',
  `majorName` varchar(255) NOT NULL COMMENT '专业名称',
  `class` char(1) NOT NULL COMMENT '班级编号',
  `seat` char(2) NOT NULL COMMENT '座号',
  `active` tinyint(1) NOT NULL COMMENT '激活状态',
  `idCard` char(18) NOT NULL COMMENT '身份证号码',
  `address` varchar(255) NOT NULL COMMENT '家庭住址',
  `studentImg` varchar(255) NOT NULL COMMENT '学生照片',
  PRIMARY KEY (`studentId`),
  UNIQUE KEY `studentId` (`studentId`),
  UNIQUE KEY `uniqueStudentData` (`studentId`,`grade`,`years`,`deparment`,`major`,`class`,`seat`),
  KEY `gender` (`gender`),
  KEY `grade` (`grade`),
  KEY `deparment` (`deparment`),
  KEY `major` (`major`),
  KEY `class` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ms_teacher`
--

CREATE TABLE IF NOT EXISTS `ms_teacher` (
  `teacherId` char(36) NOT NULL COMMENT '教工Id',
  `teacherName` varchar(255) NOT NULL COMMENT '教工姓名',
  `departmentId` char(2) NOT NULL COMMENT '院系Id',
  `departmentName` varchar(255) NOT NULL COMMENT '院系名称',
  `teacherImg` varchar(255) NOT NULL COMMENT '教工照片',
  `contact` char(20) NOT NULL COMMENT '联系方式',
  `gender` char(1) NOT NULL COMMENT '性别',
  `salt` char(6) NOT NULL COMMENT '加密盐值',
  `password` char(40) NOT NULL COMMENT '密码hash',
  `idCard` char(18) NOT NULL COMMENT '身份证号码',
  `email` varchar(50) NOT NULL COMMENT '邮箱地址',
  `permission` char(1) NOT NULL COMMENT '教工权限 0:超级管理员 1:管理员 2:普通教工',
  `address` varchar(255) NOT NULL COMMENT '联系地址',
  PRIMARY KEY (`teacherId`),
  UNIQUE KEY `uniqueTeacherData` (`teacherId`,`departmentId`) USING BTREE,
  KEY `teacherId` (`teacherId`),
  KEY `departmentId` (`departmentId`),
  KEY `permission` (`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
