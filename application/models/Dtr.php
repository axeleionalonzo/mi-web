<?php

class Dtr extends CI_Model {
    function __construct() {
        parent::__construct();
    }

    function appendDTR($user_id, $clock_in, $clock_out, $entry_date, $isClockin) {

        // $this->log_this($user_id+" : "+$clock_in+" : "+$clock_out+" : "+$entry_date+" : "+$isClockin);
        if (($clock_in == "" || $clock_out == "") && $entry_date == "") {
            return false;
        } else if ($clock_in !== "0000-00-00 00:00:00.000000" && $clock_out === "0000-00-00 00:00:00.000000") {
            $query = $this->db->query("
                INSERT INTO `users_dtr` 
                (`users_id`, `clock_in`, `clock_out`,`entry_date`) 
                VALUES ('" . $user_id . "', '" . $clock_in . "', '" . $clock_out . "', '" . $entry_date . "')");
        } else if ($clock_in !== "0000-00-00 00:00:00.000000" && $clock_out !== "0000-00-00 00:00:00.000000") {
            $query = $this->db->query("
                UPDATE `users_dtr` 
                SET `clock_out` = '" .$clock_out. "' 
                WHERE `users_id` = '" .$user_id. "' AND `clock_in` = '" .$clock_in. "' AND `entry_date` = '" .$entry_date. "'
                ");
        }

        if ($query) {
            return true;
        } else return false;
    }

    function log_this($log_e) {
        date_default_timezone_set("Asia/Hong_Kong");
        $log  = date("Y-m-d")." ".date("l")." (".date("h:i:sa").") : ".$log_e.PHP_EOL;
        file_put_contents('./application/logs/custom/log-'.date("Y-m-d").'.php', $log, FILE_APPEND);
    }

}