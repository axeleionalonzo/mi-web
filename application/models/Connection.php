<?php

class Connection extends CI_Model {
    function __construct() {
        parent::__construct();
    }

    function checkConnection() {
        $query   = $this->db->simple_query("
            SELECT `surveys`.`id`
            FROM `surveys`
            WHERE `surveys`.`deleted` = 0 LIMIT 1");
        if ($query) {
            return "Database CONNECTED";
        } else {
            return "Database DISCONNECTED";
        }
    }
}