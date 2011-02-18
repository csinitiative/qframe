-- 
-- Collaborative Software Initiative
-- Regulatory Questionnaire database schema creation script for MySQL 5.0
-- version:  1.3
-- Author:  Evan Bauer
/**
 * This file is part of QFrameme.
 *
 * ThQFramerame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * QFrameQFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
--
-- Revision History:
--
--   v1.0 created 12-January-2008
--   v1.5 created 29-June-2008
--     changed all instances of the word "instrument" with the word "questionnaire"
--
--  The following secondary indices are intended to speed the performance of  
--  traversing the questionnaire hierarchy by creating hashed indices on the
--  foreign keys defining the hierarchy.
--
--  Testing will determine the efficacy of hashed vs b-tree indices.
 
create index instanceQuestionnaireIDx
  using hash
  on instance (questionnaireID);

create index pageInstanceIDx
  using hash
  on page (instanceID);

create index sectionPageIDx
  using hash
  on section (pageID);

create index sectionInstanceIDx
  using hash
  on section (instanceID);

create index sectionReferenceInstanceIDx
  using hash
  on section_reference (instanceID);

create index pageReferenceInstanceIDx
  using hash
  on page_reference (instanceID);

create index questionSectionIDx
  using hash
  on question (sectionID);

create index questionPageIDx
  using hash
  on question (pageID);

create index questionInstanceIDx
  using hash
  on question (instanceID);

create index responseQuestionIDx
  using hash
  on response (questionID);

create index responseInstanceIDx
  using hash
  on response (instanceID);

create index ruleTargetIDx
  using hash
  on rule (targetID);

create index ruleSourceIDx
  using hash
  on rule (sourceID);

create index ruleInstanceIDx
  using hash
  on rule (instanceID);

create index attachmentObjectIDx
  using hash
  on attachment (objectID);

create index attachmentInstanceIDx
  using hash
  on attachment (instanceID);

create index locksObjectIDx
  using hash
  on locks (objectID);

create index assignmentRoleDbUserIDx
  using hash
  on assignment (dbUserID,roleID);

create index dbUserNamex
  using hash
  on db_user (dbUserName);

-- The following section defines indices to support the other foreign key 
-- relationships with sufficient table cardinality to benefit from indexed
-- retrieval.

create index referenceDetailShortNamex
  using hash
  on reference_detail (shortName);

create index referenceDetailInstanceIDx
  using hash
  on reference_detail (instanceID);

create index QuestionReferenceInstanceIDx
  using hash
  on question_reference (instanceID);

create index QuestionTypeInstanceIDx
  using hash
  on question_type(instanceID);

create index questionPromptQuestionTypeIDx
  using hash
  on question_prompt (questionTypeID);

create index questionPromptInstanceIDx
  using hash
  on question_prompt (instanceID);
