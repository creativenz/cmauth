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
		$this->_CI->load->library(array('session', 'unit_test'));
	}

	/**
	 * function to check if the current user is logged in by check their session variable
	 *
	 * @return boolean
	 */
	public function is_user_logged_in()
	{
		$user = $this->_CI->session->userdata('user');

		if($user) {
			
			return $user;
		}
		return false;
	}

	/**
	 * Function to log user in against username and password
	 * NOTE the use of the new php password_verify method
	 *
	 * @param string username
	 * @param string password (clear)
	 *
	 * @return array|boolean user object or false
	 */
	public function log_user_in($username = null, $password = null)
	{

		// Unit tests - username and password have been passed to function
		$this->_CI->unit->run($username, 'is_string', 'Login username exists');
		$this->_CI->unit->run($password, 'is_string', 'Login Password exists');


		if ($username && $password)
		{
			// Get the user if they exist
			$query = $this->_CI->db->limit(1)->where('username', $username)->where('active', 1)->get('users');

			/**** Unit test for query is it should return an array ****/
			$error = $this->_CI->db->error();
			$this->_CI->unit->run($query, 'is_object', 'Login db query for username and password', $error['message']);
			/**** end unit test ****/

			if($query) {

				$user = $query->row_array();

				// If we have a user then we can check the password
				if ($user) 
				{
					if( password_verify($password, $user['password']))
					{
						// We have a user and the password matches so lets update last logged in
						$data = array( 
							'last_logged_in' => date('Y-m-d H:i:s'),
						);
						$query = $this->_CI->db->where('id', $user['id'])->update('users', $data);
						$rows_affected = $this->_CI->db->affected_rows();

						/**** Unit testing ****/ 

						// Did the db query work
						$error = $this->_CI->db->error();
						$this->_CI->unit->run($query, 'is_true', 'Valid db query to update last login', $error['message']);

						// Has a row been updated
						$this->_CI->unit->run($rows_affected, 1, 'Updated db last login', 'User last login ' . $user['username']);

						/**** End UNit Testing ****/

						// Destroy all previous sessions and start from scratch
						$this->_CI->session->sess_destroy();

						// Set up a generic session for user.  Access control session should be set up
						// as an application session as it would be different for each web site
						$this->_CI->session->set_userdata($user);

						/**** Unit Testing - check that the user session has been set ****/
						$this->_CI->unit->run($this->_CI->session->id, 'is_numeric', 'User session set', 'Has the user id session been set');
						/**** end unit testing ****/

						return $user;
					}
				}
			}
			return false;
		}
	}
}
