<?php
class User_model extends CI_Model {
    private $response = "";
    function __construct() {
        parent::__construct();
        $this->load->helper('security');
        $this->load->library('ion_auth');
    }
    function list_all($user_id) {
        if ($user = $this->get($user_id)) {
            // $position = $page_no * $items_per_page;
//             $query    = $this->db->query("
// 				SELECT `users`.`id`, 
// 						`users`.`first_name`, 
// 						`users`.`last_name`, 
// 						`users`.`active`,
// 						(SELECT COUNT(*) FROM `user_surveys` WHERE `user_id` = `users`.`id` AND `user_surveys`.`deleted` = 0) as surveysLinked 
// 				FROM `users` 
// 				WHERE `users`.organization_id = " . $user->organization_id . " 
// 						AND `users`.`deleted` = 0
// 				LIMIT " . $items_per_page . "
// 				OFFSET " . $position . "
// 			");
            
            
            if($this->ion_auth->in_group("admin")){
            	$query = $this->db->query("
            			SELECT `u`.`id`, 
						`u`.`first_name`, 
						`u`.`last_name`, 
						`u`.`active`,
						`u`.`team_id`,
						`t`.`organization_id`,
            			g.`id` as group_id
						FROM `users` u
						JOIN teams t ON u.`team_id` = t.`id` AND t.`active` = 1
						JOIN user_organizations uo ON uo.`organization_id` = t.`organization_id` AND uo.`user_id` = ".$user_id." AND uo.active = 1 
						JOIN users_groups ug ON ug.`user_id` = u.`id`
						JOIN groups g ON ug.`group_id` = g.`id` AND g.`name` = 'surveyor'
            			WHERE `u`.`deleted` = 0");
            }elseif($this->ion_auth->in_group("superadmin")){
            	$query = $this->db->query("
            			SELECT `u`.`id`, 
						`u`.`first_name`, 
						`u`.`last_name`, 
						`u`.`active`,
						`u`.`team_id`,
						`uo`.`organization_id`,
                        g.`id` as group_id
						FROM `users` u
						LEFT JOIN user_organizations uo ON uo.`user_id` = u.`id` AND uo.active = 1 
						JOIN users_groups ug ON ug.`user_id` = u.`id`
						JOIN groups g ON ug.`group_id` = g.`id` AND g.`name` != 'superadmin'
						WHERE `u`.`deleted` = 0 
            			ORDER BY u.id"
            	); // (SELECT COUNT(*) FROM `user_surveys` WHERE `user_id` = `u`.`id` AND `user_surveys`.`deleted` = 0) AS surveysLinked 
                    // removed surveys_linked AA: 01252016
                    // 03072016 AA: display user details
                        // 03312016 AA: removed user details
                        // (SELECT name FROM `organizations` WHERE `id` = `uo`.`organization_id`) AS org_name,
                        // (SELECT description FROM `groups` WHERE `id` = g.`id`) AS group_name,
                        // (SELECT name FROM `teams` WHERE `id` = `u`.`team_id`) AS team_name
            }
            
            $results  = $query->result();
            foreach ($results as &$res) {
//                 if ($this->ion_auth->in_group("admin", $res->id)) {
//                     $res->role = "Administrator";
//                 }
                if ($res->active == 0) {
                    $res->active = false;
                } else {
                    $res->active = true;
                }
            }

            unset($res);
            
            $results = json_decode(json_encode($results), true);
            
            //var_dump($results);
            
            $userIDs = array();
            $users = array();
            //$count = count($results);
            //if($this->ion_auth->in_group("superadmin")){
            	foreach($results as $res){
            		if(in_array($res["id"],$userIDs)){
            			array_push($users[count($users)-1]["organizations"],$res["organization_id"]);
            		}else{
            			array_push($userIDs,$res["id"]);
            			array_push($users,$res);
            			$users[count($users)-1]["organizations"] = array($res["organization_id"]);
            		}
            		 
            	}
            	
            	$results = $users;
            //}

            return array(
                "users" => $results
            );
        }
    }

    function groups($user_id) { // AA: 01182016
        $query_group = $this->db->query("SELECT group_id FROM `users_groups` where user_id = ".$user_id."");
        $get_group = $query_group->result();


        if($get_group[0]->group_id == 1)
            $get_group[0]->type = false;
        if($get_group[0]->group_id == 4)
            $get_group[0]->type = true;

        return array(
                "type" => $get_group[0]->type // AA: 01182016
        );

    }

    function update($user_id, $user, $data) {
        $now            = date('Y-m-d H:i:s');
        $this->response = array();

        if ($user_id == 0) {
            $username = $data['first_name'] . '' . $data['last_name'];
            $password = $data['newpassword'];
            unset($data['password']);
            unset($data['newpassword']);
            unset($data['retypepassword']);
            $email = $data['email'];
            $useri = $this->get_user_by_email($email, $user_id);
            if ($useri) {
                $this->response['errors'] = 'This email is already being used!';
                return false;
            } else {
                $additional_data = array(
                    //'organization_id' => $user->organization_id,
                    'active' => 1,
                    'deleted' => 0,
                    'created_date' => $now
                );
                $additional_data = array_merge($additional_data, $data);
                $this->db->trans_start();
                $group = array(
                    $data["type"]//3
                );
                
                $add   = $this->ion_auth->register($username, $password, $email, $additional_data, $group);
                if ($add) {
                    // $this->load->model('survey_model', 'survey');
                    // $all_organization_surveys = $this->survey->list_all_organization_surveys($user->organization_id);
                    // foreach ($all_organization_surveys['surveys'] as $survey) {
                    //     $user_access = $this->survey->get_user_accounts($survey->id, $user);
                    //     if (count($user_access['users']) - 1 == $user_access['users_count']) {
                    //         $user_params = array(
                    //             "user_id" => $add,
                    //             "survey_id" => $survey->id,
                    //             "deleted" => 0,
                    //             "created_date" => $now
                    //         );
                    //         $this->db->insert("user_surveys", $user_params);
                    //     }
                    // }

                	if($user_id!=$user->id){
                		$organizations = explode(",",$data["organizations"]);
                		
                		foreach ($organizations as $org){
                			$user_org_params = array(
                					'user_id'=>$add,
                					'organization_id'=>$org,
                					'created_by'=>$user->id,
                					'active'=>1,
                					'created_date'=>$now
                			);
                			$this->db->insert("user_organizations", $user_org_params);
                		}
                	}
                	
                	if(isset($data["survey_id"])){
                		$user_survey = array(
                				'user_id'=>$add,
                				'survey_id'=>$data["survey_id"],
                				'deleted'=>0,
                				'created_date'=>$now
                		);
                		
                		$this->db->insert('user_surveys', $user_survey);
                	}
                	
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        $this->response['errors'] = "Could not save the user. Please try again later.";
                        $this->ion_auth->delete_user($add);
                        return false;
                    } else {
                        return true;
                    }
                } else {
                    $this->response['errors'] = "Could not save the user. Please try again later.";
                    return false;
                }
            }
        } else {
            $data["updated_date"]   = $now;
            $data['`update_count`'] = '`update_count`+1';
            $data['username']       = $data['first_name'] . '' . $data['last_name'];
            $data['password']       = $data['newpassword'];
            unset($data['newpassword']);
            unset($data['retypepassword']);
            $id    = $user_id;
            $email = $data['email'];
            $user  = $this->get_user_by_email($email, $id);
            if ($user) {
                $this->response['errors'] = 'This email is already being used!';
                return false;
            } else {
            	
            	$this->db->trans_start();
            	
            	if($data["type"]==1){
            		$data["team_id"] = NULL;
            	}
            	
                $update = $this->ion_auth->update($id, $data);
                if ($update) {
                	$user = $this->user->get($this->ion_auth->get_user_id());

                    // log_message("debug", "user_id = ".$user_id." : user->id = ".$user->id);
                    // log_message("debug", "organizations = ".$data["organizations"]);
                    // log_message("debug", "user_id!=user->id = ".$user_id!=$user->id);

                	if($user_id!=$user->id){
                		$organizations = explode(",",$data["organizations"]);
                		
                		$query = $this->db->query("SELECT * FROM user_organizations WHERE user_id = ".$user_id);
                		
                		$results  = $query->result();
                		
                		//var_dump($results);
                		
                		foreach ($results as &$res){
                			$res->updated = false;
                		}
                		
                		unset($res);
                		
                		foreach ($organizations as $org){
                        // log_message("debug", "foreach (organizations as org)");
                			
                			$isOrgExist = false;
                			
                			foreach ($results as &$res){
                                // log_message("debug", "foreach (results as res)");
                				if($res->organization_id == $org){
                                    // log_message("debug", "res->organization_id == org");
                					$isOrgExist = true;
                                    // log_message("debug", "res->active == 0 :: ".$res->active == 0);
                					if($res->active == 0){
                						$this->db->update("user_organizations", array("active"=>1),array("id"=>$res->id));
                					}
                					$res->updated = true;
                				}
                			}
                			
                			unset($res);
                			
                            // log_message("debug", "isOrgExist: ".$isOrgExist. "\n-----------------------\n");
                			if(!$isOrgExist){
                				$user_org_params = array(
                						'user_id'=>$user_id,
                						'organization_id'=>$org,
                						'created_by'=>$user->id,
                						'active'=>1,
                						'created_date'=>$now
                				);
                				$this->db->insert("user_organizations", $user_org_params);
                			}
                			

                		}
                		
                		foreach ($results as $res){
                			if($res->active == 1 && !$res->updated){
                				$this->db->update("user_organizations",array("active"=>0),array("id"=>$res->id));
                			}
                		}
                		
                		$this->db->update("users_groups",array("group_id"=>$data["type"]),array("user_id"=>$user_id));
                	}
                	
                	
                	$this->db->trans_complete();
                	if ($this->db->trans_status() === FALSE) {
                		$this->db->trans_rollback();
                		$this->response['errors'] = "Could not update the user. Please try again later.";
                		return false;
                	} else {
                		return true;
                	}
                	
                    //return true;
                } else {
                    $this->response['errors'] = $this->ion_auth->errors();
                    return false;
                }
            }
        }
    }
    function get($user_id) {
        $query = $this->db->query("
            SELECT `users`.`id`, 
					`users`.`first_name` as name, 
                    `users`.`last_name` as surname,
					`users`.`email`,
					`users`.`organization_id`,
					`users`.`active`,
					`users`.`language`,
        			`users`.`team_id`,
        			`users`.`comment`,
        			`ug`.`group_id`,
        			`uo`.`organization_id`,
					(SELECT COUNT(*) FROM `user_surveys` WHERE `user_id` = `users`.`id` AND `user_surveys`.`deleted` = 0) as surveysLinked ,
					(SELECT `organizations`.`name` FROM `organizations` WHERE `organizations`.`id` = `users`.`organization_id`) as `organization` 
        			FROM `users` 
        			JOIN users_groups ug ON ug.`user_id` = `users`.`id`	
        			LEFT JOIN user_organizations uo ON uo.`user_id` = `users`.`id` AND uo.active = 1 
        			WHERE `users`.`id` = " . $user_id);
        if ($query->num_rows != 0) {
            $result         = $query->result();
            $result[0]->active = $result[0]->active == 1 ? true : false;

            // log_message("debug", $user_id. " " .$result[0]->group_id);
            // $result[0]->organization == "superadmin" ? true : false;
            // if($result[0]->organization == 1)
            //     $result[0]->type = false;
            // if($result[0]->organization == 4)
            //     $result[0]->type = true;

            if($result[0]->group_id == 1)
            	$result[0]->type = false;
            if($result[0]->group_id == 4)
            	$result[0]->type = true;
            
            $result = json_decode(json_encode($result), true);
            $result[0]["organizations"]=array();
            //if(count($result)>1){
            	foreach ($result as $res){
            		array_push($result[0]["organizations"],$res["organization_id"]);
            	}
            //}
            
            return json_decode(json_encode($result[0]));
        } else {
            return false;
        }
    }
    public function get_user_by_email($email, $id = '') {
        if ($id) {
            $this->db->where('id !=', $id);
        }
        $this->db->where('email', $email)->where('active', 1)->where('deleted', 0)->limit(1);
        $q = $this->db->get('users');
        if ($q->num_rows() === 1) {
            return $q->row_array();
        } else {
            return false;
        }
    }
    public function get_response() {
        return $this->response;
    }
    
    function get_user_orgs($active = true){
        $orgs;
        $user_id = $this->ion_auth->get_user_id();
        // if($this->ion_auth->in_group("admin")){
            $query = $this->db->query("select o.id, o.name, o.active from organizations o
                    join user_organizations uo on uo.organization_id = o.id AND uo.user_id = " . $user_id . " and uo.active = 1 
                     ".($active==true?"where o.active = 1 ":"")." order by o.name");
        // }elseif($this->ion_auth->in_group("superadmin")){
        //     $query = $this->db->query("select id, name, active from organizations ".($active==true?"where active = 1 ":"")." order by name");
        // }
    
        $orgs = $query->result();
        if(count($orgs)>0)
            $orgs[0]->first = true;
        foreach ($orgs as &$res) {

            // log_message("debug", $res->name);
            if ($res->active == 0) {
                $res->active = false;
            } else {
                $res->active = true;
            }
        }

    
        return array("user_orgs" => $orgs);
    
    }
    
    function get_user_teams($active = false){
    	$teams;
    	$user_id = $this->ion_auth->get_user_id();
    	if($this->ion_auth->in_group("admin")){
    		$query = $this->db->query("select t.* from teams t
    				join  organizations o on o.id = t.organization_id
    				join user_organizations uo on uo.organization_id = o.id AND uo.user_id = " . $user_id . " and uo.active = 1
    				where o.active = 1 ".($active==true?"and t.active = 1":"")." order by o.name");
    	}elseif($this->ion_auth->in_group("superadmin")){
    		$query = $this->db->query("select * from teams ".($active==true?"where active = 1":"")." order by name");
    	}
    	
    	$teams = $query->result();
    	if(count($teams)>0)
    		$teams[0]->first = true;
    	
    	foreach ($teams as &$res) {
    		if ($res->active == 0) {
    			$res->active = false;
    		} else {
    			$res->active = true;
    		}
    	}
    	
    	
    	return array("user_teams" => $teams);
    }

    function get_users($team_id){ // 214120011_12152015 AA: able to view users on team management
        $team;
        $query = $this->db->query("
            select u.first_name, u.last_name from users u where u.team_id = ".$team_id
            );
        $team = $query->result();
        // $users = count($team);

        $users = count($team) === 0 ? false : true;

        // log_message("debug", "users=".$users);

        return array("team_users" => $team, "users" => $users);
    }

    function get_teams($org_id){ // 214120012_12152015 AA: added team list view under organization management
        $org;
        $query = $this->db->query("
            select t.name from teams t where t.organization_id = ".$org_id
            );

        $org = $query->result();

        $teams = count($org) === 0 ? false : true;
        // log_message("debug", count($org));

        return array("org_teams" => $org, "teams" => $teams);
    }
    
    function get_user_projects(){
    	$projects;
    	$user_id = $this->ion_auth->get_user_id();
    	if($this->ion_auth->in_group("admin")){
    		$query = $this->db->query("SELECT DISTINCT(s.code), s.organization_id FROM surveys s
				JOIN organizations o  ON s.`organization_id` = o.`id` AND o.`active` = 1 
				JOIN user_organizations uo ON o.`id` = uo.`organization_id` AND uo.`user_id` = " . $user_id . " AND uo.`active` = 1
                WHERE s.`active` = 1 AND s.`deleted` = 0 AND s.code != 'N/A' and s.code not like '% %'
				ORDER BY s.`code`"); // 211120001_12142015 AA: added s.`deleted` = 0
            // 03312016 AA: dont show codes with spaces
    	}elseif($this->ion_auth->in_group("superadmin")){
    		$query = $this->db->query("SELECT DISTINCT(s.code), s.organization_id FROM surveys s
				JOIN organizations o  ON s.`organization_id` = o.`id` AND o.`active` = 1 
				WHERE s.`active` = 1 AND s.`deleted` = 0 AND s.code != 'N/A' and s.code not like '% %'
				ORDER BY s.`code`"); // 211120001_12142015 AA: added s.`deleted` = 0
                // 03312016 AA: dont show codes with spaces
    	}
    	 
    	$projects = $query->result();
    	if(count($projects)>0)
    		$projects[0]->first = true;
    	
//     	foreach ($projects as &$res) {
//     		if ($res->active == 0) {
//     			$res->active = false;
//     		} else {
//     			$res->active = true;
//     		}
//     	}
    	 
    	 
    	return array("user_projects" => $projects);
    }
    
    function get_user_types(){
    	$types;
    	
    	$query = $this->db->query("SELECT * FROM groups where visible=1");
    	
    	$types = $query->result();
    	if(count($types)>0)
    		$types[0]->first = true;
    	
    	return array("user_types" => $types);
    }
}
?>