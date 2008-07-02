<?php
require_once 'PHPUnit/Framework.php';
 
class QFrame_Test_Listener implements PHPUnit_Framework_TestListener {
  private $failures = 0;
  private $tests = 0;
  private $errors = 0;
  private $totalTime = 0;
  private $errorMessages = array();
  private $failureMessages = array();
  
  public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
    $this->errorMessages[] = sprintf("Error in %s\noccured in '%s' at line %d\n%s",
        $test->getName(), $e->getFile(), $e->getLine(), $e->getMessage());
    $this->errors++;
  }
 
  public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
    $location = $e->getLocation();
    $this->failureMessages[] = sprintf("Failure in %s\noccured in '%s' at line %d\n%s",
        $test->getName(), $location['file'], $location['line'], $e);
    $this->failures++;
  }
 
  public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
    // No real need to do anything here
  }
 
  public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
    // No real need to do anything here
  }
 
  public function startTest(PHPUnit_Framework_Test $test) {
    // No real need to do anything here
  }
 
  public function endTest(PHPUnit_Framework_Test $test, $time) {
    $this->tests++;
    switch($test->getStatus()) {
      case PHPUnit_Runner_BaseTestRunner::STATUS_PASSED:
        echo '.';
        break;
      case PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE:
        echo 'F';
        break;
      case PHPUnit_Runner_BaseTestRunner::STATUS_ERROR:
        echo 'E';
        break;
      case PHPUnit_Runner_BaseTestRunner::STATUS_INCOMPLETE:
        echo 'I';
        break;
      case PHPUnit_Runner_BaseTestRunner::STATUS_SKIPPED:
        echo 'S';
        break;
    }
    if($this->tests % 60 == 0) echo "\n";
    $this->totalTime += $time;
  }
 
  public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
    $this->printUnlessIgnore($suite->getName(), sprintf("loaded suite %s.\n\n", $suite->getName()));
  }
 
  public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
    $this->printUnlessIgnore($suite->getName(), sprintf(" Finished in %.6fs", $this->totalTime));
  
    $msgIndex = 1;
    foreach(array_merge($this->errorMessages, $this->failureMessages) as $message) {
      $this->printUnlessIgnore($suite->getName(), "\n\n" . $msgIndex++ . ") " . $message);
    }
  
    if($this->errors || $this->failures) $color = "[0;31m";
    else $color = "[0;32m";
    $results = sprintf("\n\n%s\n%d tests, ? assertions, %d failures, %d errors\n\n",
        str_repeat('=', 80), $this->tests, $this->failures, $this->errors);
    $this->printUnlessIgnore($suite->getName(), chr(27) . $color . $results . chr(27) . chr(27) . "[0m");
  }
  
  private function printUnlessIgnore($name, $string) {
    if($name != 'IGNORE' && $name != '') echo $string;
  }
}