<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cmauth {

	// Codeigniter instance
	private $_CI;

	public function __construct()
	{
		$this->_CI =& get_instance();
		$this->_CI->load->library(array('session'));
	}

	public static function is_user_logged_in()
	{
		$user = $this->_CI->session->userdata('user');

		if($user) {
			die('we have a user');
		}
		return false;
	}
}