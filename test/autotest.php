<?php
/**
 * This file is part of the CSI QFrame.
 *
 * The CSI QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI QFrame is distributed in the hope that it will be useful,
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
 

define('QFRAME_ENV', 'test');
require_once(dirname(__FILE__) . '/../core/load.php');
Zend_Session::start();

$monitoredPaths = array('application', 'html', 'library', 'test');
$tree = buildCodeTree(PROJECT_PATH, $monitoredPaths);
addTestsToCodeTree($tree);
$stat = new QFrame_Test_Stat($monitoredPaths, PROJECT_PATH);
$failures = runTests($tree, 'all');

function relativize($path) {
  return preg_replace('/^' . preg_quote(PROJECT_PATH . '/', '/') . '|\.php$/', '', $path);
}

while(1) {
  $changes = array_map('relativize', $stat->monitor());
  
  $tree = buildCodeTree(PROJECT_PATH, $monitoredPaths);
  addTestsToCodeTree($tree);
  
  $changedTests = false;
  foreach($changes as $change) {
    if(preg_match('/^test\//', $change)) {
      $changedTests = true;
      break;
    }
  }
  
  if($changedTests) {
    $failures = runTests($tree, 'all');
  }
  elseif($failures <= 0) {
    $failures = runTests($tree, $changes);
  }
  else {
    if($changes == $previous_changes) {
      $failures = runTests($tree, $changes);
      if($failures <= 0) $failures = runTests($tree, 'all');
    }
    else {
      $failures = runTests($tree, 'all');
    }
  }
  
  if($failures > 0) $previous_changes = $changes;
}

function buildCodeTree($base, $paths) {
  $tree = array();
  foreach($paths as $path) {
    $tree[$path] = array('files' => array(), 'dirs' => array());
    foreach(scandir($base . '/' . $path) as $file) {
      if($file != '.' && $file != '..' && $file != '.svn') {
        if(is_dir($base . '/' . $path . '/' . $file)) $tree[$path]['dirs'][] = $file;
        else $tree[$path]['files'][$file] = null;
      }
    }
    $tree[$path]['dirs'] = buildCodeTree($base . '/' . $path, $tree[$path]['dirs']);
  }
  return $tree;
}

function addTestsToCodeTree(&$tree) {
  foreach(scandir(TEST_PATH . '/' . 'unit') as $file) {
    /*
     * Add all basic unit tests (should correspond 1 to 1 to model classes)
     * to the code tree.  Any tests that do not correspond will be added to
     * the models directory instead (meaning they will be run for all model
     * changes).
     */
    if(substr($file, -4, 4) == '.php') {
      $code_file = preg_replace('/^(.*?)Test(\.php)$/', '\1\2', $file);
      if(file_exists(APPLICATION_PATH . '/models/' . $code_file)) {
        $tree['application']['dirs']['models']['files'][$code_file] = array($file);
      }
      else {
        $tree['application']['dirs']['models']['tests'] = array($file);
      }
    }
  }
}

function runTests($tree, $branches) {
  $pid = pcntl_fork();
  if($pid != 0) {
    pcntl_waitpid($pid, $status);
    $failures = file_get_contents(PROJECT_PATH . '/tmp/.autotest');
    return intval($failures);
  }
  
  /*
   * Prepare the database...
   */
  require(_path(CORE_PATH, 'database.php'));
  $db = Zend_Db_Table_Abstract::getDefaultAdapter();
  foreach($db->listTables() as $table)
    $db->getConnection()->exec("TRUNCATE TABLE {$table}");
    
  if(is_array($branches)) {
    $tests = array();
    $suite_name = implode(' && ', $branches);
    foreach($branches as $branch) {
      $tests = array_merge($tests, collectBranchTests($tree, $branch));
    }
    $tests = array_unique($tests);
    if(count($tests) <= 0) return;
  }
  elseif($branches == 'all') {
    $suite_name = 'all';
    $tests = collectAllTests($tree);
  }
  
  $suite = new PHPUnit_Framework_TestSuite($suite_name);
  foreach($tests as $test) {
    require_once(TEST_PATH . '/unit/' . $test);
    $class_name = preg_replace('/\.php$/', '', $test);
    $suite_name = preg_replace('/^' . preg_quote(PROJECT_PATH . '/', '/') . '|\.php$/', '', 'IGNORE');
    $suite->addTestSuite(new PHPUnit_Framework_TestSuite('Test_Unit_' . $class_name, $suite_name));
  }
  $result = new PHPUnit_Framework_TestResult;
  $result->addListener(new QFrame_Test_Listener);
  $suite->run($result);
  file_put_contents(PROJECT_PATH . '/tmp/.autotest', count($result->failures()));
  exit;
}

function collectBranchTests($tree, $branch) {
  $tests = array();
  $branch_parts = explode(DIRECTORY_SEPARATOR, $branch);
  $tree = $tree[array_shift($branch_parts)];
  foreach($branch_parts as $part) {
    if(isset($tree['tests'])) $tests = array_merge($tests, $tree['tests']);
    
    if(array_key_exists($part . '.php', $tree['files'])) {
      if(!is_null($tree['files'][$part . '.php'])) $tests = array_merge($tests, $tree['files'][$part . '.php']);
      break;
    }
    elseif(isset($tree['dirs'][$part])) $tree = $tree['dirs'][$part];
    else throw new Exception(sprintf("Invalid branch part %s in %s", $part, $branch));
  }
  return $tests;
}

function collectAllTests($tree, &$tests = array()) {
  if(!is_array($tree) || count($tree) <= 0) return;
  foreach($tree as $dir) {
    if(isset($dir['tests'])) $tests = array_merge($tests, $dir['tests']);
    foreach($dir['files'] as $file => $file_tests) {
      if(!is_null($file_tests)) $tests = array_merge($tests, $file_tests);
    }
    if(isset($dir['dirs'])) collectAllTests($dir['dirs'], $tests);
  }
  return $tests;
}