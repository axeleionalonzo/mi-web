<?php

class Main extends CI_Model {
    function __construct() {
        parent::__construct();
    }

    function getUserDetails($id) {
        // $this->log_this($id);
        $query = $this->db->query("
                SELECT  `u`.username, 
                        `u`.email, 
                        `u`.token, 
                        `u`.first_name, 
                        `u`.last_name  
                FROM users u 
                WHERE `u`.id = '$id' ORDER BY `u`.id DESC LIMIT 1");

        if ($query) {
            return $query->row();
            // return $query->result();
        } else return false;
    }

    function log_this($log_e) {
        date_default_timezone_set("Asia/Hong_Kong");
        $log  = date("Y-m-d")." ".date("l")." (".date("h:i:sa").") : ".$log_e.PHP_EOL;
        file_put_contents('./application/logs/custom/log-'.date("Y-m-d").'.php', $log, FILE_APPEND);
    }

}