<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class ApiValidation {

    public function validate($field, $type = "string", $optional = false)
    {
		$field = trim($field);
		$type = explode(" ", $type);
		$itsGood = false;
		
		if(in_array("string", $type)){
			
			if($optional && (!isset($field) || empty($field))){
				$itsGood = true;
				return $itsGood;
			}
		
			if($field && !empty($field)){
				$itsGood = true;
			}

			if(in_array("device_token", $type)){
				if(strlen($field) < 15){ //uses IMEI of the device
					$itsGood = false;
				}
			}
			
			if(in_array("device_type", $type)){
				if(strtolower($field) != "web" && strtolower($field) != "ios" && strtolower($field) != "android"){
					$itsGood = false;
				}
			}
			
			if(in_array("token", $type)){
				if(strlen($field) < 35){
					$itsGood = false;
				}
			}
			
			if(in_array("date", $type)){
				if($field != "0000-00-00 00:00:00"){
					$date = date_parse($field);
					if (!checkdate($date["month"], $date["day"], $date["year"]))
						$itsGood = false;
				}
			}
		} else if(in_array("int", $type)){

			if($optional && (!isset($field) || empty($field))){
				$itsGood = true;
				return $itsGood;
			}
			$itsGood = is_numeric($field);
		} else if(in_array("json_array", $type)){
			if($optional && (!isset($field) || empty($field))){
				$itsGood = true;
				return $itsGood;
			}
			
			if(!is_array(json_decode($field))){
				$itsGood = false;
			}
		}
		
		return $itsGood;
    }
}

/* End of file Someclass.php */