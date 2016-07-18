<?php 
class MY_Controller extends CI_Controller {

    public function __construct(){
        parent::__construct();
		parent::__construct();
		$this->load->library('ion_auth');
		$this->lang->load('auth');        
    }

    public function check_authorization(){

    	if(!$this->ion_auth->logged_in()){
			// echo json_encode(array("status"=>false, "message"=>"Unauthorized request"));
			return false;
		} else {
			return true;
		}
    }
}