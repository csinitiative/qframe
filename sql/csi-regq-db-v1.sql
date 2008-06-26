-- 
-- Collaborative Software Initiative
-- Regulatory Questionnaire database schema creation script for MySQL 5.0
-- version:  1.3
-- Author:  Evan Bauer
/**
 * This file is part of the CSI RegQ.
 *
 * The CSI RegQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI RegQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
--
-- Revision History:
--
--   v1.0 created 4-September-2007
--   v1.1 updated 6-October-2007
--   v1.2 updated 31-October-2007
--   v1.3 updated 3-November-2007
--     Database name changed to "regq"
--     Table name corrected from questionPrompts to questionPrompt
--     AUTO_INCREMENT removed from structural elements to simplify loading
--   v1.4 updated 25-November-2007
--     External document references have been generalized out of the section
--       and question tables into reference, referenceDetail and link records
--     xmlSchemaLocation added to the instrument table to document the source
--       of the questionnaire format
--   v1.5 updated 13-December-2007
--     Question text (qtext) field in question table changed from varchar(200)
--       to text -- allowing for some very long question fields (up to 64k 
--       characters.
--     Added tabReference to support external reference docs at the tab level 
--     Added attachment table to support attachments at the response level


-- References are supplemental documents used to explain or illuminate tabs
-- sections, questionGroups, and questions. The reference table describes
-- the source document, the referenceDetail table the specific document element
-- (section, paragraph, item.) Shortname is the prmary key for reference and
-- is the common acronym for the reference (e.g. UAP, ISO, PCI, ...)
drop table if exists reference;
create table reference (
  instanceID int NOT NULL,
  shortName char(8) NOT NULL,
  referenceName char(80),
  primary key (instanceID, shortName)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

drop table if exists referenceDetail;
create table referenceDetail (
  referenceDetailID int NOT NULL AUTO_INCREMENT,
  instanceID int NOT NULL,
  shortName char(8) NOT NULL,
  item varchar (80),
  referenceText text,
  referenceURL varchar (255),
  primary key (referenceDetailID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- The following six tables define the hierarchy that describes a compliance
-- reporting instrument and response to the web application:
--   instrument
--      version
--         tab
--           section
--             questionGroup
--               question
--                 response
-- The contents of all except the response will be read from an XML file 
-- conforming to the csiq.xsd schema definition.

-- seqNumber is used in all screen-displayed elements to allow the preservation
-- of element ordering found in the paper, document, or spreadsheet reference
-- implementations of the instruments.

-- Instrument is the top of the content hierarchy, allows a single database to
-- be used for more than one repoting task. Initially the web application will
-- only need to provide support for one instrument at a time, but should allow
-- for expansion to multi-instrument support. The BITS Shared Assessment SIG is
-- one instance of instrument. 

-- The combination of instrumentName and instrumentVersion must be unique. 

drop table if exists instrument;
create table instrument (
  instrumentID int NOT NULL AUTO_INCREMENT,
  instrumentName varchar(100),
  instrumentVersion varchar(20),
  revision int NOT NULL,
  signature char(32),
  PRIMARY KEY (instrumentID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';


-- An instrument may have multiple versions over time. The SIG is currently at
-- version 2 and will rev by the of the year to version 3. Scripts may be 
-- needed to migrate data from version to version -- feasibility will depend
-- on the amount of structural change to an instrument from version-to-version.
-- Need to make the combination of instrumentID and instanceName unique.
drop table if exists instance;
create table instance (
  instrumentID int NOT NULL,
  instanceID int NOT NULL AUTO_INCREMENT,
  instanceName varchar (100),
  instanceCurrent char(1),
  instanceDate datetime,
  numQuestions int NOT NULL DEFAULT "0",
  numComplete int NOT NULL DEFAULT "0",
  numApproved int NOT NULL DEFAULT "0",
  PRIMARY KEY (instanceID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- A tab is a repeatable set of questions in an instrument -- a grouping of 
-- questions (some of whom may be organized in sections.) It serves as
-- a demarcation of responsibility as well as an organizing element for the
-- formatted instrument response. Note that some responses will include 
-- multiple instances of a tab, for example one per facility for the physical
-- security tab in the SIG. This corresponds to a single tab in the SIG
-- spreadsheet. The tabMasterID field is used in the case of a repeated
-- instance of a tab to indicate the original master tab from which the 
-- question definitions are copied. In the first, or unique instance of a
-- tab, the tabMasterID equals the tabID. The tabRepeatSeq column defines
-- the order of repetition for tabs with a common tabMasterID. 
-- The header and footer text columns should contain formatting html (no 
-- scripts) to rovide for instructions and footnotes.
drop table if exists tab;
create table tab (
  instrumentID int NOT NULL,
  instanceID int NOT NULL,
  tabID bigint NOT NULL AUTO_INCREMENT,
  tabMasterID bigint DEFAULT "0",
  tabGUID int NOT NULL,
  seqNumber int NOT NULL,
  tabHeader char(30),
  description varchar(80),
  headerText text,
  footerText text,
  required boolean NOT NULL DEFAULT "1",
  cloneable boolean NOT NULL DEFAULT "0",
  defaultTabHidden boolean NOT NULL DEFAULT "0",
  numQuestions int NOT NULL DEFAULT "0",
  numComplete int NOT NULL DEFAULT "0",
  numApproved int NOT NULL DEFAULT "0",
  disableCount int NOT NULL DEFAULT "0",
  PRIMARY KEY (tabID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- tabReference links a tab to the documentation detail in
-- referenceDetail
drop table if exists tabReference;
create table tabReference (
  tabID int NOT NULL,
  referenceDetailID int NOT NULL,
  instanceID int NOT NULL,
  primary key (tabID, referenceDetailID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';


-- A section is a subset of questions wihin an instance of a tab used for 
-- conceptual subsetting of the response. In this instance, each occurs once.
-- In future releases of the application it is possible to use this as a 
-- collection device for repeating groups of questions within a tab.
drop table if exists section;
create table section (
  instanceID int NOT NULL,
  tabID int NOT NULL,
  sectionID int NOT NULL AUTO_INCREMENT,
  sectionGUID int,
  seqNumber int NOT NULL,
  sectionHeader char(80),
  description varchar(128),
  required boolean NOT NULL DEFAULT "1",
  cloneable boolean NOT NULL DEFAULT "0",
  defaultSectionHidden boolean NOT NULL DEFAULT "0",
  disableCount int NOT NULL DEFAULT "0",
  PRIMARY KEY (sectionID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- sectionReference links a section to the documentation detail in
-- referenceDetail
drop table if exists sectionReference;
create table sectionReference (
  sectionID int NOT NULL,
  referenceDetailID int NOT NULL,
  instanceID int NOT NULL,
  primary key (sectionID, referenceDetailID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- The questionType table is used to provide generic instructions to both users
-- and the questionnaire form generator on how a question is to be answered.
drop table if exists questionType;
create table questionType (
  questionTypeID bigint NOT NULL AUTO_INCREMENT,
  instanceID int NOT NULL,
  format char (20) NOT NULL DEFAULT "T:A-Z0-9",
  maxLength int,
  PRIMARY KEY (questionTypeID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- The questionPrompt table provides the allowed values for a multiple
-- question.
drop table if exists questionPrompt;
create table questionPrompt (
  promptID bigint NOT NULL AUTO_INCREMENT,
  instanceID int NOT NULL,
  questionTypeID bigint NOT NULL,
  value char (25) NOT NULL,
  requireAddlInfo boolean NOT NULL DEFAULT "0",
  PRIMARY KEY (promptID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- A question is the basic element definition for an atomic response. It 
-- acorresponds to one row in the SIG spreadsheet implementation. 
-- Note that when a spreadsheet row contains multiple response cells, they are
-- treated as separate questions, grouped for formatting by the web 
-- application. 
-- Which of the resonse elements are required is controlled by 
-- the questionType foreign key.
-- the questionGUID field is a unique global identifier for questions that
-- should hold across intruments and versions, allowing for persistence of
-- information in mapping responses across compliance instruments.
--
-- The questionNumber is used to contain whatever kind of alphanumeric
-- numbering scheme is used in the display and export of the instrument.
--
-- parentID is used to link questions into question groups by identifying the
-- parent question.
drop table if exists question;
create table question (
  questionID bigint NOT NULL AUTO_INCREMENT,
  instrumentID int NOT NULL,
  instanceID int NOT NULL,
  tabID bigint NOT NULL,
  sectionID bigint NOT NULL,
  questionGUID int NOT NULL,
  questionNumber char(50),
  seqNumber int NOT NULL,
  questionTypeID bigint NOT NULL,
  qText text,
  required boolean NOT NULL DEFAULT "1",
  parentID bigint NOT NULL DEFAULT "0",
  cloneable boolean NOT NULL DEFAULT "0",
  defaultQuestionHidden boolean NOT NULL DEFAULT "0",
  disableCount int NOT NULL DEFAULT "0",
  PRIMARY KEY (questionID)  
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';


-- questionReference links a question to the documentation detail in
-- referenceDetail
drop table if exists questionReference;
create table questionReference (
  questionID bigint NOT NULL,
  referenceDetailID bigint NOT NULL,
  instanceID int NOT NULL,
  tabID bigint NOT NULL,
  sectionID bigint NOT NULL,
  primary key (questionID, referenceDetailID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- A response is an instance of an answer to a question. Responses can be 
-- modified until approved, once approved they should be archived, tagged 
-- with the date and author of the modification. 
--
-- approverComments is a 1.0b1 hack, eventually there will need to be a
-- a separate table to allow a history of responses and comments by 
-- reviewer username.

drop table if exists response;
create table response (
  responseID bigint NOT NULL AUTO_INCREMENT,
  instanceID int NOT NULL,
  tabID int NOT NULL,
  sectionID int NOT NULL,
  questionID bigint NOT NULL,
  responseDate timestamp,
  responseEndDate datetime DEFAULT NULL,
  responseText text,
  additionalInfo text,
  approverComments text,
  externalReference varchar(80),
  state int NOT NULL DEFAULT "1",
  PRIMARY KEY (responseID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- The attachment table provides the metadata needed to attach supporting 
-- files to docment a specifc response.
drop table if exists attachment;
create table attachment (
  attachmentID int NOT NULL AUTO_INCREMENT,
  instanceID int DEFAULT NULL,
  objectType varchar (256) NOT NULL,
  objectID varchar (256) NOT NULL,
  filename varchar (256) NOT NULL,
  mime varchar (32),
  content longblob,
  created timestamp,
  PRIMARY KEY (attachmentID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';


-- The following tables provide the structure for defining application users,
-- the role(s) a user can hold in building questionnaire responses, and the 
-- requirements and actions to define a simple workflow facility for assigning
-- and tracking completion of the full response document.

-- A dbUser is a user of the application who can answer or approve a response.
-- dbUserPW is sized to hold a SHA1 hash plus salt. Note that with the addition
-- of dbUserFullName, dbUserName has been shrunk to char (20). dbUserActive
-- indicates whether the user account is enabled in the system.
drop table if exists dbUser;
create table dbUser (
  dbUserID int NOT NULL AUTO_INCREMENT,
  dbUserName char(20) NOT NULL,
  dbUserPW char(50) NOT NULL,
  dbUserFullName char(50),
  dbUserActive char (1) NOT NULL default 'Y',
  ACLstring longtext,
  PRIMARY KEY (dbUserID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';
-- insert default admin user (admin/admin)
insert into dbUser(dbUserName, dbUserPW, dbUserFullName) values
  ('admin', 'n4wjt4zqsx45f53b790c9e83342c46e35c585d1e822c5030c5', 'Administrator'),
  ('user', 'eup4qebrim3c331f4c8752d6b97c8672ea68f0fffb4ffdcf0f', 'User');

-- The role table describes the relationship of a dbUser to a question and its
-- responses
drop table if exists role;
create table role (
  roleID int NOT NULL AUTO_INCREMENT,
  roleDescription varchar (128),
  ACLstring longtext,
  PRIMARY KEY (roleID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';
-- insert default role for administrators
insert into role(roleDescription, ACLstring) values
  ('Administrators', 'O:8:\"Zend_Acl\":3:{s:16:\"\0*\0_roleRegistry\";O:22:\"Zend_Acl_Role_Registry\":1:{s:9:\"\0*\0_roles\";a:4:{s:4:\"view\";a:3:{s:8:\"instance\";O:13:\"Zend_Acl_Role\":1:{s:10:\"\0*\0_roleId\";s:4:\"view\";}s:7:\"parents\";a:0:{}s:8:\"children\";a:0:{}}s:4:\"edit\";a:3:{s:8:\"instance\";O:13:\"Zend_Acl_Role\":1:{s:10:\"\0*\0_roleId\";s:4:\"edit\";}s:7:\"parents\";a:0:{}s:8:\"children\";a:0:{}}s:7:\"approve\";a:3:{s:8:\"instance\";O:13:\"Zend_Acl_Role\":1:{s:10:\"\0*\0_roleId\";s:7:\"approve\";}s:7:\"parents\";a:0:{}s:8:\"children\";a:0:{}}s:10:\"administer\";a:3:{s:8:\"instance\";O:13:\"Zend_Acl_Role\":1:{s:10:\"\0*\0_roleId\";s:10:\"administer\";}s:7:\"parents\";a:0:{}s:8:\"children\";a:0:{}}}}s:13:\"\0*\0_resources\";a:1:{s:6:\"GLOBAL\";a:3:{s:8:\"instance\";O:17:\"Zend_Acl_Resource\":1:{s:14:\"\0*\0_resourceId\";s:6:\"GLOBAL\";}s:6:\"parent\";N;s:8:\"children\";a:0:{}}}s:9:\"\0*\0_rules\";a:2:{s:12:\"allResources\";a:2:{s:8:\"allRoles\";a:2:{s:13:\"allPrivileges\";a:2:{s:4:\"type\";s:9:\"TYPE_DENY\";s:6:\"assert\";N;}s:13:\"byPrivilegeId\";a:0:{}}s:8:\"byRoleId\";a:0:{}}s:12:\"byResourceId\";a:1:{s:6:\"GLOBAL\";a:1:{s:8:\"byRoleId\";a:4:{s:4:\"view\";a:2:{s:13:\"byPrivilegeId\";a:0:{}s:13:\"allPrivileges\";a:2:{s:4:\"type\";s:10:\"TYPE_ALLOW\";s:6:\"assert\";N;}}s:4:\"edit\";a:2:{s:13:\"byPrivilegeId\";a:0:{}s:13:\"allPrivileges\";a:2:{s:4:\"type\";s:10:\"TYPE_ALLOW\";s:6:\"assert\";N;}}s:7:\"approve\";a:2:{s:13:\"byPrivilegeId\";a:0:{}s:13:\"allPrivileges\";a:2:{s:4:\"type\";s:10:\"TYPE_ALLOW\";s:6:\"assert\";N;}}s:10:\"administer\";a:2:{s:13:\"byPrivilegeId\";a:0:{}s:13:\"allPrivileges\";a:2:{s:4:\"type\";s:10:\"TYPE_ALLOW\";s:6:\"assert\";N;}}}}}}}');

-- An assignment is the object that grants a dbUser a role for a question. 
-- As of this time assignments are made at the question level, the application
-- may provide a means to manipulate those assignments at larger levels of
-- granularity but they are applied at this level.  assignment serves as the
-- owner of both requirement (the link table to questions) and action (the
-- link table to responses.) As such, it a a workflow element as well.
drop table if exists assignment;
create table assignment (
  dbUserID int NOT NULL,
  roleID int NOT NULL,
  assignmentID int NOT NULL AUTO_INCREMENT,
  comments text, 
  PRIMARY KEY (assignmentID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';
insert into assignment(dbUserID,roleID) values(1,1);

-- A requirement links a user and role tuple (assignment) to a question.
drop table if exists requirement;
create table requirement (
  assignmentID int NOT NULL,
  questionID bigint NOT NULL,
  complete boolean NOT NULL,
  dueDate date,
  PRIMARY KEY (assignmentID, questionID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- An action is the fulfillment or completion of a requirement 
-- it too links a user and role tuple (assignment) to a question.
drop table if exists action;
create table action (
  assignmentID int NOT NULL,
  questionID bigint NOT NULL,
  complete boolean NOT NULL,
  dueDate date,
  PRIMARY KEY (assignmentID, questionID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- The state table is used to define the possible states (recorded in the
-- state integer field) of each response.
drop table if exists state;
create table state (
  state int NOT NULL AUTO_INCREMENT,
  stateDescription char (10),
  PRIMARY KEY (state)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- The rules table is used to provide persistence for simple rules on the 
-- requirement to complete subsets of the questions based on answers to
-- other questions. The initial implementation will support, as an example
-- the "High-Level Questions' refinement capability of the BITS SIG v3.
drop table if exists rule;
create table rule (
  ruleID bigint NOT NULL AUTO_INCREMENT,
  instrumentID int NOT NULL,
  instanceID int NOT NULL,
  sourceID bigint NOT NULL,
  targetID bigint NOT NULL,
  targetGUID int NOT NULL,
  enabled char(1) NOT NULL default 'N',
  type varchar (50) NOT NULL,
  PRIMARY KEY (ruleID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';

-- The locks table provides the facility to coordinate long-running 
-- application-level locks on response 
-- elements to preclude inadvertant data-loss through
-- conflicting updates. Table is names "locks" as "lock" is a SQL2 
-- reserved word.
drop table if exists locks;
create table locks (
  lockID int NOT NULL AUTO_INCREMENT,
  dbUserID int NOT NULL,
  className varchar (255) NOT NULL,
  objectID char (255) NOT NULL,
  obtained timestamp,
  expiration datetime NOT NULL,
  PRIMARY KEY (lockID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';


-- The crypto table provides the keys and metadata for encrypting files
drop table if exists crypto;
create table crypto (
  cryptoID int NOT NULL AUTO_INCREMENT,
  name varchar (255) NOT NULL,
  type varchar (50) NOT NULL,
  cryptoKey tinytext NOT NULL,
  secret tinytext,
  PRIMARY KEY (cryptoID)
  ) ENGINE InnoDB DEFAULT CHARSET='utf8';
