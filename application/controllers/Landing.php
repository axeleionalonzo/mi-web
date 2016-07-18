<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Landing extends MY_Controller {

	function getUserDetails() {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('auth');
				if ($user = $this->auth->getUserDetails($user_id)) {
					echo json_encode(array(
						"user" => $user,
						"success" => true
					));
				}
			}
		} else {
			echo json_encode(array(
				"success" => false
			));
		}
	}

	function connect() {
		// @set_time_limit(0);
		$this->load->model('connection');
		if ($connection=$this->connection->checkConnection()) {
			echo json_encode(array(
				"success" => true,
				"status" => $connection
			));
		} else {
			echo json_encode(array(
				"success" => false,
				"status" => $connection
			));
		}
	}

	function doesEmailExists() {
		// @set_time_limit(0);
		$email = $this->input->post("email", true);
		// $this->log_this($email);
		$this->load->model('auth');
		if ($user=$this->auth->checkEmail($email)) {
			echo json_encode(array(
				"success" => true,
				"user" => $user
			));
		} else {
			echo json_encode(array(
				"success" => false
			));
		}
	}

    function log_this($log_e) {
        date_default_timezone_set("Asia/Hong_Kong");
        $log  = date("Y-m-d")." ".date("l")." (".date("h:i:sa").") : ".$log_e.PHP_EOL;
        file_put_contents('./application/logs/custom/log-'.date("Y-m-d").'.php', $log, FILE_APPEND);
    }
}
