<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clockin extends MY_Controller {

	function appendDTR() {
		if ($this->check_authorization()) {
	        $isClockin		= $this->input->post('isClockin', true);
	        $clock_in		= $this->input->post('clock_in', true);
	        $clock_out		= $this->input->post('clock_out', true);
	        $entry_date		= $this->input->post('entry_date', true);
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('dtr');
				$this->load->model('auth');
				if ($dtr_stat = $this->dtr->appendDTR($user_id, $clock_in, $clock_out, $entry_date, $isClockin)) {
					$user = $this->auth->getUserDetails($user_id);
					echo json_encode(array(
						"user" => $user,
						"dtr_stat" => $dtr_stat,
						"isClockin" => $isClockin,
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

    function log_this($log_e) {
        date_default_timezone_set("Asia/Hong_Kong");
        $log  = date("Y-m-d")." ".date("l")." (".date("h:i:sa").") : ".$log_e.PHP_EOL;
        file_put_contents('./application/logs/custom/log-'.date("Y-m-d").'.php', $log, FILE_APPEND);
    }
}
