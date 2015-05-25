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
		$id = $this->_CI->session->id;

		if($id) {
			
			return $id;
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
		// Test for valid username and password
		$this->_CI->unit->run($username, 'is_string', 'Username is a string');
		$this->_CI->unit->run($password, 'is_string', 'Password is a string');
		
		if (!$username || !$password) {
			return false;
		}

		// Get the user if they exist
		$query = $this->_CI->db->limit(1)->where('username', $username)->where('active', 1)->get('users');

		$error = $this->_CI->db->error();
		$this->_CI->unit->run($error['code'], 0, 'Query on db for user', $error['message']);
		if(0 != $error['code'])
		{
			// we have a database error that needs reporting
			return false;
		}

		if($query) {

			$user = $query->row();

			// If we have a user then we can check the password
			if ($user) 
			{

				if( password_verify($password, $user->password))
				{
					return $user;
				}
			}
		}
		return false;

	}

	public function create_user_session($user)
	{
		$this->_CI->unit->run($user, 'is_object', 'User object for setting session');
		
		$data = array( 
			'last_logged_in' => date('Y-m-d H:i:s'),
		);
		$query = $this->_CI->db->where('id', $user->id)->update('users', $data);
		
		// Destroy all previous sessions and start from scratch
		$this->_CI->session->unset_userdata(array('id', 'username', 'email'));

		// Set session for user
		$this->_CI->session->set_userdata(array('id' => $user->id, 'username' => $user->username, 'email' => $user->email));
		$id = $this->_CI->session->id;
		$this->_CI->unit->run($id, 'is_numeric', 'Session has been set for user');
		return true;
	}

	/**
	 * function to destroy user session and log them out
	 *
	 * @return boolean
	 */
	public function user_logout()
	{
		$this->_CI->session->sess_destroy();
		return true;
	}

	/**
	 * Function to check if user exists and then generate lost password link;
	 *
	 * @param string email of user
	 * @return object user or error
	 */
	public function create_lostpassword_email($email = null)
	{
		if (!$email)
		{
			$error = array('error' => 'No email address passed to function');
			return $error;
		}

		$query = $this->_CI->db->where('email', $email)->where('active', 1)->limit(1)->get('users');
		$error = $this->_CI->db->error();
		$this->_CI->unit->run($error['code'], 0, 'Test db for create lostpassword email', $error['message']);
		if(0 != $error['code'])
		{
			// bail out if we have a database error
			return false;
		}

		$user = $query->row();

		$this->_CI->unit->run($user, 'is_object', 'Does the email match a valid user');

		if ($user)
		{
			
			// Lets create unique link
			$id = md5( $this->_CI->config->item('encryption_key') . uniqid() . $email );
			
			$date = date('Y-m-d H:i:s', time());

			$data = array(
				'password_token' => $id,
				'token_date' => $date
			);
			$this->_CI->db->where('id', $user->id);
			$this->_CI->db->update('users', $data);

			$error = $this->_CI->db->error();
			$this->_CI->unit->run($error['code'], 0, 'DB update password token');

			if(0 != $error['code'])
			{
				// We have a database error so bail out
				return false;
			}
			else
			{
				$user->link = $id;
				return $user;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Function to check reset hash
	 */
	public function check_reset_hash($hash = null)
	{
		if (!$hash)
		{
			return false;
		}
		$hash = $this->_CI->security->xss_clean($hash);

		$query = $this->_CI->db->where('password_token', $hash)->where('active', 1)->get('users');
		$error = $this->_CI->db->error();
		$this->_CI->unit->run($error['code'], 0, 'Database error searching for password token', $error['message']);
		if(0 != $error['code'])
		{
			// Problem with the sql query - bail out
			return false;
		}

		$user = $query->row();
		$this->_CI->unit->run($user->id, 'is_numeric', 'User found for hashed code');
		if (!$user)
		{
			return false;
		}
		return $user;
	}

	function password_token_date($token_date)
	{
		$this->_CI->unit->run($token_date, 'is_string', 'Date passed to password token date function');

		$current_date = date('Y-m-d H:i:s', strtotime('-2 hours'));

		// It's been more than two hours
		if( $current_date > $token_date)
		{
			return false;
		}
		return true;

	}


}