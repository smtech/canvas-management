-- phpMyAdmin SQL Dump
-- version 3.5.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 20, 2014 at 12:58 PM
-- Server version: 5.5.37-0ubuntu0.12.04.1
-- PHP Version: 5.3.10-1ubuntu3.11

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `grading-analytic`
--
CREATE DATABASE `grading-analytic` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `grading-analytic`;

-- --------------------------------------------------------

--
-- Table structure for table `course_statistics`
--
-- Creation: Apr 24, 2014 at 10:40 AM
--

CREATE TABLE IF NOT EXISTS `course_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` text NOT NULL COMMENT 'When this statistic was collected',
  `course[id]` int(11) NOT NULL COMMENT 'Canvas course ID',
  `course[name]` text NOT NULL COMMENT 'Human-readable course name, as listed in Canvas',
  `course[account_id]` int(11) NOT NULL COMMENT 'The account_id associated with this course (i.e. the department) for departmental aggregation of data.',
  `account[name]` text NOT NULL COMMENT 'Human-readable department name',
  `teacher[id]s` text NOT NULL COMMENT 'A serialized array of Canvas user IDs for the teacher(s) of the course.',
  `teacher[sortable_name]s` int(11) NOT NULL COMMENT 'Serialized list of human-readable teacher names',
  `assignments_due_count` int(11) NOT NULL COMMENT 'The overall number of assignments with due dates prior to the timestamp, including both graded and ungraded assignments and zero-point assignments.',
  `dateless_assignment_count` int(11) NOT NULL COMMENT 'Assignments that lack due dates.',
  `gradeable_assignment_count` int(11) NOT NULL COMMENT 'The number of gradeable, non-zero-point, assignments posted in this course with due dates prior to this statistic collection timestamp',
  `graded_assignment_count` int(11) NOT NULL COMMENT 'The number of graded, non-zero-point assignments with due dates prior to the timstamp of this statistic for which at least one submission has been graded',
  `oldest_ungraded_assignment_due_date` text NOT NULL COMMENT 'Due date of the oldest graded, non-zero-point assignment due prior to the timestamp of this statistic for which no submissions have grades entered.',
  `oldest_ungraded_assignment_url` text NOT NULL COMMENT 'URL of the oldest ungraded assignment',
  `average_grading_turn_around` text NOT NULL COMMENT 'Of those assignments that were due prior to the timestamp of this statistic for which at least one submission has been graded, what is the average turn-around time (in days) for those submission grades?',
  `oldest_ungraded_assignment_name` text NOT NULL COMMENT 'Human-readable name of the oldest ungraded assignment',
  `zero_point_assignment_count` int(11) NOT NULL COMMENT 'Many zero-point assignments suggest that the teacher is not using the "not graded" option',
  `average_submissions_graded` float NOT NULL COMMENT 'Of assignments due prior to this statistic timestamp, for which at least one submission has been graded, what percentage of the overall submissions for each assignment have been graded?',
  `gradebook_url` text NOT NULL COMMENT 'URL of the course gradebook',
  `student_count` int(11) NOT NULL COMMENT 'The number of student enrollments in this course. (Not yet filtering out the Test Student enrollments.)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=30387 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
