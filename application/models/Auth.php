<?php

class Auth extends CI_Model {
    function __construct() {
        parent::__construct();
    }

    function checkEmail($email) {
        $query = $this->db->query("
                SELECT `users`.`username`, `users`.`email`
                FROM `users` WHERE `users`.`email` = '$email' LIMIT 1");

        if ($query) {
            return $query->row();
        } else return false;
    }

    function getUserDetails($id) {
        date_default_timezone_set("Asia/Hong_Kong");
        $date_now = date("Y-m-d");
        $date_yesterday = date("Y-m-d", strtotime("-1 days"));
        // $this->log_this($date_yesterday);
        $query = $this->db->query("
                SELECT  `u`.username, 
                        `u`.email, 
                        `u`.team_id, 
                        `u`.organization_id, 
                        `u`.token, 
                        `ud`.users_id, 
                        `ud`.clock_in, 
                        `ud`.clock_out, 
                        `ud`.entry_date 
                FROM users u 
                LEFT JOIN users_dtr ud ON `ud`.users_id = '$id' AND `ud`.entry_date IN ('$date_now', '$date_yesterday') 
                WHERE `u`.id = '$id' ORDER BY `ud`.clock_in DESC");
        if ($query) {
            // return $query->row();
            return $query->result();
        } else return false;
    }

    function log_this($log_e) {
        date_default_timezone_set("Asia/Hong_Kong");
        $log  = date("Y-m-d")." ".date("l")." (".date("h:i:sa").") : ".$log_e.PHP_EOL;
        file_put_contents('./application/logs/custom/log-'.date("Y-m-d").'.php', $log, FILE_APPEND);
    }

}