<?php
/*
 * A page is a repeatable set of questions in an questionnaire  * a grouping of 
 * questions (some of whom may be organized in sections.) It serves as
 * a demarcation of responsibility as well as an organizing element for the
 * formatted questionnaire response. Note that some responses will include 
 * multiple instances of a page, for example one per facility for the physical
 * security page in the SIG. This corresponds to a single page in the SIG
 * spreadsheet. The pageMasterID field is used in the case of a repeated
 * instance of a page to indicate the original master page from which the 
 * question definitions are copied. In the first, or unique instance of a
 * page, the pageMasterID equals the pageID. The pageRepeatSeq column defines
 * the order of repetition for pages with a common pageMasterID. 
 * The header and footer text columns should contain formatting html (no 
 * scripts) to rovide for instructions and footnotes.
 */

class CreateTablePage extends Migration {

  public function up() {
    $this->createTable('page', array('primary' => 'pageID'), array(
      array('questionnaireID', 'integer'),
      array('instanceID', 'integer'),
      array('pageID', 'integer', array('limit' => 20)),
      array('pageMasterID', 'integer', array('default' => 0, 'null' => true, 'limit' => 20)),
      array('pageGUID', 'integer'),
      array('seqNumber', 'integer'),
      array('pageHeader', 'string', array('limit' => 30, 'null' => true)),
      array('description', 'string', array('limit' => 80, 'null' => true)),
      array('headerText', 'text', array('null' => true)),
      array('footerText', 'text', array('null' => true)),
      array('required', 'boolean', array('default' => 1)),
      array('cloneable', 'boolean', array('default' => 0)),
      array('defaultPageHidden', 'boolean', array('default' => 0)),
      array('numQuestions', 'integer', array('default' => 0)),
      array('numComplete', 'integer', array('default' => 0)),
      array('numApproved', 'integer', array('default' => 0)),
      array('disableCount', 'integer', array('default' => 0))
    ));
    $this->createIndex('page', array('instanceID'));
  }

  public function down() {
    $this->dropTable('page');
  }
}
