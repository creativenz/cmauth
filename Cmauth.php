<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @file
 */
class Cmauth {

	// Codeigniter instance
	private $_CI;

	public function __construct()
	{
		$this->_CI =& get_instance();
		$this->_CI->load->library(array('session'));
	}

	public function is_user_logged_in()
	{
		$user = $this->_CI->session->userdata('user');

		if($user) {
			
			return $user;
		}
		return false;
	}

	public function log_user_in($username, $password)
	{
		return false;
		
	}
}
