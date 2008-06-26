<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2002-2007, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2007 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id: SeleniumTestCase.php 1848 2007-12-08 09:24:34Z sb $
 * @link       http://www.phpunit.de/
 * @since      File available since Release 3.0.0
 */

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Util/Log/Database.php';
require_once 'PHPUnit/Util/Filter.php';
require_once 'PHPUnit/Util/Test.php';
require_once 'PHPUnit/Util/XML.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

/**
 * TestCase class that uses Selenium to provide
 * the functionality required for web testing.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2007 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 3.2.5
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 3.0.0
 */
abstract class PHPUnit_Extensions_SeleniumTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var    string
     * @access protected
     */
    protected $browser;

    /**
     * @var    string
     * @access protected
     */
    protected $browserName;

    /**
     * @var    string
     * @access protected
     */
    protected $browserUrl;

    /**
     * @var    string
     * @access protected
     */
    protected $host = 'localhost';

    /**
     * @var    integer
     * @access protected
     */
    protected $port = 4444;

    /**
     * @var    integer
     * @access protected
     */
    protected $timeout = 30000;

    /**
     * @var    array
     * @access protected
     */
    protected static $sessionId = array();

    /**
     * @var    integer
     * @access protected
     */
    protected $sleep = 0;

    /**
     * @var    boolean
     * @access protected
     */
    protected $autoStop = TRUE;

    /**
     * @var    boolean
     * @access protected
     */
    protected $collectCodeCoverageInformation = FALSE;

    /**
     * @var    string
     * @access protected
     */
    protected $coverageScriptUrl = '';

    /**
     * @var    string
     * @access protected
     */
    protected $testId;

    /**
     * @var    boolean
     * @access protected
     */
    protected $inDefaultAssertions = FALSE;

    /**
     * @param  string $name
     * @param  array  $browser
     * @throws InvalidArgumentException
     * @access public
     */
    public function __construct($name = NULL, array $data = array(), array $browser = array())
    {
        parent::__construct($name, $data);

        if (isset($browser['name'])) {
            if (!is_string($browser['name'])) {
                throw new InvalidArgumentException;
            }
        } else {
            $browser['name'] = '';
        }

        if (isset($browser['browser'])) {
            if (!is_string($browser['browser'])) {
                throw new InvalidArgumentException;
            }
        } else {
            $browser['browser'] = '';
        }

        if (isset($browser['host'])) {
            if (!is_string($browser['host'])) {
                throw new InvalidArgumentException;
            }
        } else {
            $browser['host'] = 'localhost';
        }

        if (isset($browser['port'])) {
            if (!is_int($browser['port'])) {
                throw new InvalidArgumentException;
            }
        } else {
            $browser['port'] = 4444;
        }

        if (isset($browser['timeout'])) {
            if (!is_int($browser['timeout'])) {
                throw new InvalidArgumentException;
            }
        } else {
            $browser['timeout'] = 30000;
        }

        $this->browserName = $browser['name'];
        $this->browser     = $browser['browser'];
        $this->host        = $browser['host'];
        $this->port        = $browser['port'];
        $this->timeout     = $browser['timeout'];
    }

    /**
     * @param  string $className
     * @return PHPUnit_Framework_TestSuite
     * @access public
     */
    public static function suite($className)
    {
        $suite = new PHPUnit_Framework_TestSuite;
        $suite->setName($className);

        $class            = new ReflectionClass($className);
        $classGroups      = PHPUnit_Util_Test::getGroups($class);
        $staticProperties = $class->getStaticProperties();

        // Create tests from Selenese/HTML files.
        if (isset($staticProperties['seleneseDirectory']) &&
            is_dir($staticProperties['seleneseDirectory'])) {
            $files = new PHPUnit_Util_FilterIterator(
              new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                  $staticProperties['seleneseDirectory']
                )
              ),
              '.htm'
            );

            // Create tests from Selenese/HTML files for multiple browsers.
            if (isset($staticProperties['browsers'])) {
                foreach ($staticProperties['browsers'] as $browser) {
                    $browserSuite = new PHPUnit_Framework_TestSuite;
                    $browserSuite->setName($className . ': ' . $browser['name']);

                    foreach ($files as $file) {
                        $browserSuite->addTest(
                          new $className((string)$file, array(), $browser)
                        );
                    }

                    $suite->addTest($browserSuite, $classGroups);
                }
            }

            // Create tests from Selenese/HTML files for single browser.
            else {
                foreach ($files as $file) {
                    $suite->addTest(new $className((string)$file), $classGroups);
                }
            }
        }

        // Create tests from test methods for multiple browsers.
        if (isset($staticProperties['browsers'])) {
            foreach ($staticProperties['browsers'] as $browser) {
                $browserSuite = new PHPUnit_Framework_TestSuite;
                $browserSuite->setName($className . ': ' . $browser['name']);

                foreach ($class->getMethods() as $method) {
                    if (PHPUnit_Framework_TestSuite::isPublicTestMethod($method)) {
                        $name   = $method->getName();
                        $data   = PHPUnit_Util_Test::getProvidedData($className, $name);
                        $groups = PHPUnit_Util_Test::getGroups($method, $classGroups);

                        // Test method with @dataProvider.
                        if (is_array($data) || $data instanceof Iterator) {
                            $dataSuite = new PHPUnit_Framework_TestSuite(
                              $className . '::' . $name
                            );

                            foreach ($data as $_data) {
                                $dataSuite->addTest(
                                  new $className($name, $_data, $browser),
                                  $groups
                                );
                            }

                            $browserSuite->addTest($dataSuite);
                        }

                        // Test method without @dataProvider.
                        else {
                            $browserSuite->addTest(
                              new $className($name, array(), $browser), $groups
                            );
                        }
                    }
                }

                $suite->addTest($browserSuite);
            }
        }

        // Create tests from test methods for single browser.
        else {
            foreach ($class->getMethods() as $method) {
                if (PHPUnit_Framework_TestSuite::isPublicTestMethod($method)) {
                    $name   = $method->getName();
                    $data   = PHPUnit_Util_Test::getProvidedData($className, $name);
                    $groups = PHPUnit_Util_Test::getGroups($method, $classGroups);

                    // Test method with @dataProvider.
                    if (is_array($data) || $data instanceof Iterator) {
                        $dataSuite = new PHPUnit_Framework_TestSuite(
                          $className . '::' . $name
                        );

                        foreach ($data as $_data) {
                            $dataSuite->addTest(
                              new $className($name, $_data),
                              $groups
                            );
                        }

                        $suite->addTest($dataSuite);
                    }

                    // Test method without @dataProvider.
                    else {
                        $suite->addTest(
                          new $className($name), $groups
                        );
                    }
                }
            }
        }

        return $suite;
    }

    /**
     * Runs the test case and collects the results in a TestResult object.
     * If no TestResult object is passed a new one will be created.
     *
     * @param  PHPUnit_Framework_TestResult $result
     * @return PHPUnit_Framework_TestResult
     * @throws InvalidArgumentException
     * @access public
     */
    public function run(PHPUnit_Framework_TestResult $result = NULL)
    {
        if ($result === NULL) {
            $result = $this->createResult();
        }

        $this->collectCodeCoverageInformation = $result->getCollectCodeCoverageInformation();

        $result->run($this);

        if ($this->collectCodeCoverageInformation) {
            $result->appendCodeCoverageInformation(
              $this, $this->getCodeCoverage()
            );
        }

        return $result;
    }

    /**
     * @access protected
     */
    protected function runTest()
    {
        $this->start();

        if (!is_file($this->name)) {
            parent::runTest();
        } else {
            $this->runSelenese($this->name);
        }

        if ($this->autoStop) {
            try {
                $this->stop();
            }

            catch (RuntimeException $e) {
            }
        }
    }

    /**
     * If you want to override tearDown() make sure to either call stop() or
     * parent::tearDown(). Otherwise the Selenium RC session will not be
     * closed upon test failure.
     *
     * @access protected
     */
    protected function tearDown()
    {
        if ($this->autoStop) {
            try {
                $this->stop();
            }

            catch (RuntimeException $e) {
            }
        }
    }

    /**
     * Returns a string representation of the test case.
     *
     * @return string
     * @access public
     */
    public function toString()
    {
        $buffer = parent::toString();

        if (!empty($this->browserName)) {
            $buffer .= ' with browser ' . $this->browserName;
        }

        return $buffer;
    }

    /**
     * @return string
     * @access public
     */
    public function start()
    {
        if (!isset(self::$sessionId[$this->host][$this->port][$this->browser])) {
            self::$sessionId[$this->host][$this->port][$this->browser] = $this->getString(
              'getNewBrowserSession',
              array($this->browser, $this->browserUrl)
            );

            $this->doCommand('setTimeout', array($this->timeout));
        }


        $this->testId = md5(uniqid(rand(), TRUE));

        return self::$sessionId[$this->host][$this->port][$this->browser];
    }

    /**
     * @access public
     */
    public function stop()
    {
        if (!isset(self::$sessionId[$this->host][$this->port][$this->browser])) {
            return;
        }

        $this->doCommand('testComplete');

        unset(self::$sessionId[$this->host][$this->port][$this->browser]);
    }

    /**
     * @param  boolean $autoStop
     * @throws InvalidArgumentException
     * @access public
     */
    public function setAutoStop($autoStop)
    {
        if (!is_bool($autoStop)) {
            throw new InvalidArgumentException;
        }

        $this->autoStop = $autoStop;
    }

    /**
     * @param  string $browser
     * @throws InvalidArgumentException
     * @access public
     */
    public function setBrowser($browser)
    {
        if (!is_string($browser)) {
            throw new InvalidArgumentException;
        }

        $this->browser = $browser;
    }

    /**
     * @param  string $browserUrl
     * @throws InvalidArgumentException
     * @access public
     */
    public function setBrowserUrl($browserUrl)
    {
        if (!is_string($browserUrl)) {
            throw new InvalidArgumentException;
        }

        $this->browserUrl = $browserUrl;
    }

    /**
     * @param  string $host
     * @throws InvalidArgumentException
     * @access public
     */
    public function setHost($host)
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException;
        }

        $this->host = $host;
    }

    /**
     * @param  integer $port
     * @throws InvalidArgumentException
     * @access public
     */
    public function setPort($port)
    {
        if (!is_int($port)) {
            throw new InvalidArgumentException;
        }

        $this->port = $port;
    }

    /**
     * @param  integer $timeout
     * @throws InvalidArgumentException
     * @access public
     */
    public function setTimeout($timeout)
    {
        if (!is_int($timeout)) {
            throw new InvalidArgumentException;
        }

        $this->timeout = $timeout;
    }

    /**
     * @param  integer $seconds
     * @throws InvalidArgumentException
     * @access public
     */
    public function setSleep($seconds)
    {
        if (!is_int($seconds)) {
            throw new InvalidArgumentException;
        }

        $this->sleep = $seconds;
    }

    /**
     * Runs a test from a Selenese (HTML) specification.
     *
     * @param string $filename
     * @access public
     */
    public function runSelenese($filename)
    {
        $document = PHPUnit_Util_XML::load($filename, TRUE);
        $xpath    = new DOMXPath($document);
        $rows     = $xpath->query('body/table/tbody/tr');

        foreach ($rows as $row)
        {
            $action    = NULL;
            $arguments = array();
            $columns   = $xpath->query('td', $row);

            foreach ($columns as $column)
            {
                if ($action === NULL) {
                    $action = $column->nodeValue;
                } else {
                    $arguments[] = $column->nodeValue;
                }
            }

            $this->__call($action, $arguments);
        }
    }

    /**
     * This method implements the Selenium RC protocol.
     *
     * @param  string $command
     * @param  array  $arguments
     * @return mixed
     * @access public
     */
    public function __call($command, $arguments)
    {
        switch ($command) {
            case 'addLocationStrategy':
            case 'addSelection':
            case 'allowNativeXpath':
            case 'altKeyDown':
            case 'altKeyUp':
            case 'answerOnNextPrompt':
            case 'assignId':
            case 'captureScreenshot':
            case 'check':
            case 'chooseCancelOnNextConfirmation':
            case 'click':
            case 'clickAt':
            case 'close':
            case 'controlKeyDown':
            case 'controlKeyUp':
            case 'createCookie':
            case 'deleteCookie':
            case 'doubleClick':
            case 'doubleClickAt':
            case 'dragAndDrop':
            case 'dragAndDropToObject':
            case 'dragDrop':
            case 'fireEvent':
            case 'getSpeed':
            case 'goBack':
            case 'highlight':
            case 'keyDown':
            case 'keyPress':
            case 'keyUp':
            case 'metaKeyDown':
            case 'metaKeyUp':
            case 'mouseDown':
            case 'mouseDownAt':
            case 'mouseMove':
            case 'mouseMoveAt':
            case 'mouseOut':
            case 'mouseOver':
            case 'mouseUp':
            case 'mouseUpAt':
            case 'open':
            case 'openWindow':
            case 'refresh':
            case 'removeAllSelections':
            case 'removeSelection':
            case 'select':
            case 'selectFrame':
            case 'selectWindow':
            case 'setContext':
            case 'setCursorPosition':
            case 'setMouseSpeed':
            case 'setSpeed':
            case 'shiftKeyDown':
            case 'shiftKeyUp':
            case 'submit':
            case 'type':
            case 'typeKeys':
            case 'uncheck':
            case 'windowFocus':
            case 'windowMaximize': {
                // Pre-Command Actions
                switch ($command) {
                    case 'open':
                    case 'openWindow': {
                        if ($this->collectCodeCoverageInformation) {
                            $this->deleteCookie('PHPUNIT_SELENIUM_TEST_ID', '/');

                            $this->createCookie(
                              'PHPUNIT_SELENIUM_TEST_ID=' . $this->testId,
                              'path=/'
                            );
                        }
                    }
                    break;
                }

                $this->doCommand($command, $arguments);

                // Post-Command Actions
                switch ($command) {
                    case 'addLocationStrategy':
                    case 'allowNativeXpath':
                    case 'assignId':
                    case 'captureScreenshot': {
                        // intentionally empty
                    }
                    break;

                    default: {
                        if ($this->sleep > 0) {
                            sleep($this->sleep);
                        }

                        $this->runDefaultAssertions($command);
                    }
                }
            }
            break;

            case 'getWhetherThisFrameMatchFrameExpression':
            case 'getWhetherThisWindowMatchWindowExpression':
            case 'isAlertPresent':
            case 'isChecked':
            case 'isConfirmationPresent':
            case 'isEditable':
            case 'isElementPresent':
            case 'isOrdered':
            case 'isPromptPresent':
            case 'isSomethingSelected':
            case 'isTextPresent':
            case 'isVisible': {
                return $this->getBoolean($command, $arguments);
            }
            break;

            case 'getCursorPosition':
            case 'getElementHeight':
            case 'getElementIndex':
            case 'getElementPositionLeft':
            case 'getElementPositionTop':
            case 'getElementWidth':
            case 'getMouseSpeed':
            case 'getXpathCount': {
                return $this->getNumber($command, $arguments);
            }
            break;

            case 'getAlert':
            case 'getAttribute':
            case 'getBodyText':
            case 'getConfirmation':
            case 'getCookie':
            case 'getEval':
            case 'getExpression':
            case 'getHtmlSource':
            case 'getLocation':
            case 'getLogMessages':
            case 'getPrompt':
            case 'getSelectedId':
            case 'getSelectedIndex':
            case 'getSelectedLabel':
            case 'getSelectedValue':
            case 'getTable':
            case 'getText':
            case 'getTitle':
            case 'getValue': {
                return $this->getString($command, $arguments);
            }
            break;

            case 'getAllButtons':
            case 'getAllFields':
            case 'getAllLinks':
            case 'getAllWindowIds':
            case 'getAllWindowNames':
            case 'getAllWindowTitles':
            case 'getAttributeFromAllWindows':
            case 'getSelectedIds':
            case 'getSelectedIndexes':
            case 'getSelectedLabels':
            case 'getSelectedValues':
            case 'getSelectOptions': {
                return $this->getStringArray($command, $arguments);
            }
            break;

            case 'clickAndWait': {
                $this->doCommand('click', $arguments);
                $this->doCommand('waitForPageToLoad', array($this->timeout));

                if ($this->sleep > 0) {
                    sleep($this->sleep);
                }

                $this->runDefaultAssertions($command);
            }
            break;

            case 'waitForCondition':
            case 'waitForPopUp': {
                if (count($arguments) == 1) {
                    $arguments[] = $this->timeout;
                }

                $this->doCommand($command, $arguments);
                $this->runDefaultAssertions($command);
            }
            break;

            case 'waitForPageToLoad': {
                if (empty($arguments)) {
                    $arguments[] = $this->timeout;
                }

                $this->doCommand($command, $arguments);
                $this->runDefaultAssertions($command);
            }
            break;

            default: {
                $this->stop();

                throw new BadMethodCallException(
                  "Method $command not defined."
                );
            }
        }
    }

    /**
     * Asserts that an alert is present.
     *
     * @access public
     */
    public function assertAlertPresent()
    {
        $this->assertTrue(
          $this->isAlertPresent(),
          'No alert present.'
        );
    }

    /**
     * Asserts that no alert is present.
     *
     * @access public
     */
    public function assertNoAlertPresent()
    {
        $this->assertFalse(
          $this->isAlertPresent(),
          'Alert present.'
        );
    }

    /**
     * Asserts that an option is checked.
     *
     * @param  string  $locator
     * @access public
     */
    public function assertChecked($locator)
    {
        $this->assertTrue(
          $this->isChecked($locator),
          sprintf(
            '"%s" not checked.',
            $locator
          )
        );
    }

    /**
     * Asserts that an option is not checked.
     *
     * @param  string  $locator
     * @access public
     */
    public function assertNotChecked($locator)
    {
        $this->assertFalse(
          $this->isChecked($locator),
          sprintf(
            '"%s" checked.',
            $locator
          )
        );
    }

    /**
     * Assert that a confirmation is present.
     *
     * @access public
     */
    public function assertConfirmationPresent()
    {
        $this->assertTrue(
          $this->isConfirmationPresent(),
          'No confirmation present.'
        );
    }

    /**
     * Assert that no confirmation is present.
     *
     * @access public
     */
    public function assertNoConfirmationPresent()
    {
        $this->assertFalse(
          $this->isConfirmationPresent(),
          'Confirmation present.'
        );
    }

    /**
     * Asserts that an input field is editable.
     *
     * @param  string  $locator
     * @access public
     */
    public function assertEditable($locator)
    {
        $this->assertTrue(
          $this->isEditable($locator),
          sprintf(
            '"%s" not editable.',
            $locator
          )
        );
    }

    /**
     * Asserts that an input field is not editable.
     *
     * @param  string  $locator
     * @access public
     */
    public function assertNotEditable($locator)
    {
        $this->assertFalse(
          $this->isEditable($locator),
          sprintf(
            '"%s" editable.',
            $locator
          )
        );
    }

    /**
     * Asserts that an element's value is equal to a given string.
     *
     * @param  string  $locator
     * @param  string  $text
     * @access public
     */
    public function assertElementValueEquals($locator, $text)
    {
        $this->assertEquals($text, $this->getValue($locator));
    }

    /**
     * Asserts that an element's value is not equal to a given string.
     *
     * @param  string  $locator
     * @param  string  $text
     * @access public
     */
    public function assertElementValueNotEquals($locator, $text)
    {
        $this->assertNotEquals($text, $this->getValue($locator));
    }

    /**
     * Asserts that an element contains a given string.
     *
     * @param  string  $locator
     * @param  string  $text
     * @access public
     */
    public function assertElementContainsText($locator, $text)
    {
        $this->assertContains($text, $this->getValue($locator));
    }

    /**
     * Asserts that an element does not contain a given string.
     *
     * @param  string  $locator
     * @param  string  $text
     * @access public
     */
    public function assertElementNotContainsText($locator, $text)
    {
        $this->assertNotContains($text, $this->getValue($locator));
    }

    /**
     * Asserts than an element is present.
     *
     * @param  string  $locator
     * @access public
     */
    public function assertElementPresent($locator)
    {
        $this->assertTrue(
          $this->isElementPresent($locator),
          sprintf(
            'Element "%s" not present.',
            $locator
          )
        );
    }

    /**
     * Asserts than an element is not present.
     *
     * @param  string  $locator
     * @access public
     */
    public function assertElementNotPresent($locator)
    {
        $this->assertFalse(
          $this->isElementPresent($locator),
          sprintf(
            'Element "%s" present.',
            $locator
          )
        );
    }

    /**
     * Asserts that the location is equal to a specified one.
     *
     * @param  string  $location
     * @access public
     */
    public function assertLocationEquals($location)
    {
        $this->assertEquals($location, $this->getLocation());
    }

    /**
     * Asserts that the location is not equal to a specified one.
     *
     * @param  string  $location
     * @access public
     */
    public function assertLocationNotEquals($location)
    {
        $this->assertNotEquals($location, $this->getLocation());
    }

    /**
     * Asserts than a prompt is present.
     *
     * @access public
     */
    public function assertPromptPresent()
    {
        $this->assertTrue(
          $this->isPromptPresent(),
          'No prompt present.'
        );
    }

    /**
     * Asserts than no prompt is present.
     *
     * @access public
     */
    public function assertNoPromptPresent()
    {
        $this->assertFalse(
          $this->isPromptPresent(),
          'Prompt present.'
        );
    }

    /**
     * Asserts that a select element has a specific option.
     *
     * @param  string $selectLocator
     * @param  string $option
     * @access public
     * @since  Method available since Release 3.2.0
     */
    public function assertSelectHasOption($selectLocator, $option)
    {
        $this->assertContains($option, $this->getSelectOptions($selectLocator));
    }

    /**
     * Asserts that a select element does not have a specific option.
     *
     * @param  string $selectLocator
     * @param  string $option
     * @access public
     * @since  Method available since Release 3.2.0
     */
    public function assertSelectNotHasOption($selectLocator, $option)
    {
        $this->assertNotContains($option, $this->getSelectOptions($selectLocator));
    }

    /**
     * Asserts that a specific label is selected.
     *
     * @param  string  $selectLocator
     * @param  string  $value
     * @access public
     * @since  Method available since Release 3.2.0
     */
    public function assertSelected($selectLocator, $option)
    {
        $this->assertEquals(
          $option, $this->getSelectedLabel($selectLocator),
          sprintf(
            '%s not selected in "%s".',
            $option, $selectLocator
          )
        );
    }

    /**
     * Asserts that a specific label is not selected.
     *
     * @param  string  $selectLocator
     * @param  string  $value
     * @access public
     * @since  Method available since Release 3.2.0
     */
    public function assertNotSelected($selectLocator, $option)
    {
        $this->assertNotEquals(
          $option, $this->getSelectedLabel($selectLocator),
          sprintf(
            '%s selected in "%s".',
            $option, $selectLocator
          )
        );
    }

    /**
     * Asserts that a specific value is selected.
     *
     * @param  string  $selectLocator
     * @param  string  $value
     * @access public
     */
    public function assertIsSelected($selectLocator, $value)
    {
        $this->assertEquals(
          $value, $this->getSelectedValue($selectLocator),
          sprintf(
            '%s not selected in "%s".',
            $value, $selectLocator
          )
        );
    }

    /**
     * Asserts that a specific value is not selected.
     *
     * @param  string  $selectLocator
     * @param  string  $value
     * @access public
     */
    public function assertIsNotSelected($selectLocator, $value)
    {
        $this->assertNotEquals(
          $value, $this->getSelectedValue($selectLocator),
          sprintf(
            '%s selected in "%s".',
            $value, $selectLocator
          )
        );
    }

    /**
     * Asserts that something is selected.
     *
     * @param  string  $selectLocator
     * @access public
     */
    public function assertSomethingSelected($selectLocator)
    {
        $this->assertTrue(
          $this->isSomethingSelected($selectLocator),
          sprintf(
            'Nothing selected from "%s".',
            $selectLocator
          )
        );
    }

    /**
     * Asserts that nothing is selected.
     *
     * @param  string  $selectLocator
     * @access public
     */
    public function assertNothingSelected($selectLocator)
    {
        $this->assertFalse(
          $this->isSomethingSelected($selectLocator),
          sprintf(
            'Something selected from "%s".',
            $selectLocator
          )
        );
    }

    /**
     * Asserts that a given text is present.
     *
     * @param  string  $pattern
     * @access public
     */
    public function assertTextPresent($pattern)
    {
        $this->assertTrue(
          $this->isTextPresent($pattern),
          sprintf(
            '"%s" not present.',
            $pattern
          )
        );
    }

    /**
     * Asserts that a given text is not present.
     *
     * @param  string  $pattern
     * @access public
     */
    public function assertTextNotPresent($pattern)
    {
        $this->assertFalse(
          $this->isTextPresent($pattern),
          sprintf(
            '"%s" present.',
            $pattern
          )
        );
    }

    /**
     * Asserts that the title is equal to a given string.
     *
     * @param  string  $title
     * @access public
     */
    public function assertTitleEquals($title)
    {
        $this->assertEquals($title, $this->getTitle());
    }

    /**
     * Asserts that the title is not equal to a given string.
     *
     * @param  string  $title
     * @access public
     */
    public function assertTitleNotEquals($title)
    {
        $this->assertNotEquals($title, $this->getTitle());
    }

    /**
     * Asserts that something is visible.
     *
     * @param  string  $locator
     * @access public
     */
    public function assertVisible($locator)
    {
        $this->assertTrue(
          $this->isVisible($locator),
          sprintf(
            '"%s" not visible.',
            $locator
          )
        );
    }

    /**
     * Asserts that something is not visible.
     *
     * @param  string  $locator
     * @access public
     */
    public function assertNotVisible($locator)
    {
        $this->assertFalse(
          $this->isVisible($locator),
          sprintf(
            '"%s" visible.',
            $locator
          )
        );
    }

    /**
     * Template Method that is called after Selenium actions.
     *
     * @param  string $action
     * @access protected
     * @since  Method available since Release 3.1.0
     */
    protected function defaultAssertions($action)
    {
    }

    /**
     * Send a command to the Selenium RC server.
     *
     * @param  string $command
     * @param  array  $arguments
     * @return string
     * @access protected
     * @author Shin Ohno <ganchiku@gmail.com>
     * @author Bjoern Schotte <schotte@mayflower.de>
     * @since  Method available since Release 3.1.0
     */
    protected function doCommand($command, array $arguments = array())
    {
        $url = sprintf(
          'http://%s:%s/selenium-server/driver/?cmd=%s',
          $this->host,
          $this->port,
          urlencode($command)
        );

        for ($i = 0; $i < count($arguments); $i++) {
            $argNum = strval($i + 1);
            $url .= sprintf('&%s=%s', $argNum, urlencode(trim($arguments[$i])));
        }

        if (isset(self::$sessionId[$this->host][$this->port][$this->browser])) {
            $url .= sprintf('&%s=%s', 'sessionId', self::$sessionId[$this->host][$this->port][$this->browser]);
        }

        if (!$handle = @fopen($url, 'r')) {
            throw new RuntimeException(
              'Could not connect to the Selenium RC server.'
            );
        }

        stream_set_blocking($handle, 1);
        stream_set_timeout($handle, 0, $this->timeout);

        $info     = stream_get_meta_data($handle);
        $response = '';

        while ((!feof($handle)) && (!$info['timed_out'])) {
            $response .= fgets($handle, 4096); 
            $info = stream_get_meta_data($handle); 
        }

        fclose($handle);

        if (!preg_match('/^OK/', $response)) {
            $this->stop();

            throw new RuntimeException(
              'The response from the Selenium RC server is invalid: ' . $response
            );
        }

        return $response;
    }

    /**
     * Send a command to the Selenium RC server and treat the result
     * as a boolean.
     *
     * @param  string $command
     * @param  array  $arguments
     * @return boolean
     * @access protected
     * @author Shin Ohno <ganchiku@gmail.com>
     * @author Bjoern Schotte <schotte@mayflower.de>
     * @since  Method available since Release 3.1.0
     */
    protected function getBoolean($command, array $arguments)
    {
        $result = $this->getString($command, $arguments);

        switch ($result) {
            case 'true':  return TRUE;

            case 'false': return FALSE;

            default: {
                $this->stop();

                throw new RuntimeException(
                  'Result is neither "true" nor "false": ' . PHPUnit_Util_Type::toString($result, TRUE)
                );
            }
        }
    }

    /**
     * Send a command to the Selenium RC server and treat the result
     * as a number.
     *
     * @param  string $command
     * @param  array  $arguments
     * @return numeric
     * @access protected
     * @author Shin Ohno <ganchiku@gmail.com>
     * @author Bjoern Schotte <schotte@mayflower.de>
     * @since  Method available since Release 3.1.0
     */
    protected function getNumber($command, array $arguments)
    {
        $result = $this->getString($command, $arguments);

        if (!is_numeric($result)) {
            $this->stop();

            throw new RuntimeException(
              'Result is not numeric: ' . PHPUnit_Util_Type::toString($result, TRUE)
            );
        }

        return $result;
    }

    /**
     * Send a command to the Selenium RC server and treat the result
     * as a string.
     *
     * @param  string $command
     * @param  array  $arguments
     * @return string
     * @access protected
     * @author Shin Ohno <ganchiku@gmail.com>
     * @author Bjoern Schotte <schotte@mayflower.de>
     * @since  Method available since Release 3.1.0
     */
    protected function getString($command, array $arguments)
    {
        try {
            $result = $this->doCommand($command, $arguments);
        }

        catch (RuntimeException $e) {
            $this->stop();

            throw $e;
        }

        return (strlen($result) > 3) ? substr($result, 3) : '';
    }

    /**
     * Send a command to the Selenium RC server and treat the result
     * as an array of strings.
     *
     * @param  string $command
     * @param  array  $arguments
     * @return array
     * @access protected
     * @author Shin Ohno <ganchiku@gmail.com>
     * @author Bjoern Schotte <schotte@mayflower.de>
     * @since  Method available since Release 3.1.0
     */
    protected function getStringArray($command, array $arguments)
    {
        $csv     = $this->getString($command, $arguments);
        $token   = '';
        $tokens  = array();
        $letters = preg_split('//', $csv, -1, PREG_SPLIT_NO_EMPTY);
        $count   = count($letters);

        for ($i = 0; $i < $count; $i++) {
            $letter = $letters[$i];

            switch($letter) {
                case '\\': {
                    $letter = $letters[++$i];
                    $token .= $letter;
                }
                break;

                case ',': {
                    $tokens[] = $token;
                    $token    = '';
                }
                break;

                default: {
                    $token .= $letter;
                }
            }
        }

        $tokens[] = $token;

        return $tokens;
    }

    /**
     * @return array
     * @access protected
     * @since  Method available since Release 3.2.0
     */
    protected function getCodeCoverage()
    {
        if (!empty($this->coverageScriptUrl)) {
            $url = sprintf(
              '%s?PHPUNIT_SELENIUM_TEST_ID=%s',
              $this->coverageScriptUrl,
              $this->testId
            );

            return eval(
              'return ' . file_get_contents($url) . ';'
            );
        } else {
            return array();
        }
    }

    /**
     * @param  string $action
     * @access private
     * @since  Method available since Release 3.2.0
     */
    private function runDefaultAssertions($action)
    {
        if (!$this->inDefaultAssertions) {
            $this->inDefaultAssertions = TRUE;
            $this->defaultAssertions($action);
            $this->inDefaultAssertions = FALSE;
        }
    }
}
?>
