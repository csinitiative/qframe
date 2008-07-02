<?php
/**
 * This file is part of the CSI SIG.
 *
 * The CSI SIG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI SIG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   Sig
 * @package    Sig_Auth
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   Sig
 * @package    Sig_Auth
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class QFrame_Auth_Adapter implements Zend_Auth_Adapter_Interface {
  
  /**
   * Stores the username we are authenticating
   * @var string
   */
  private $username = null;

  /**
   * Stores the password we are authenticating
   * @var string
   */
  private $password = null;

  /**
   * Class constructor
   *
   * Constructs a new auth adapter with a given username and password
   *
   * @param string username
   * @param string password
   */
  public function __construct($username, $password) {
    $this->username = $username;
    $this->password = $password;
  }
  
  /**
   * Authenticates the user specified in the constructor
   *
   * @return Zend_Auth_Result
   */
  public function authenticate() {
    $user = DbUserModel::findByUsername($this->username);
    if(is_null($user))
      return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND, null);
    
    if(!$user->authenticate($this->password)) {
      return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
    }
    return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->username);
  }
}