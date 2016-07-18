<?php
class Survey_model extends CI_Model {
    var $id = '';
    var $title = '';
    var $content = '';
    var $date = '';
    var $total_page = '';
    function __construct() {
        parent::__construct();
        $this->load->library('ion_auth');
    }
    private $status;
    function get_status() {
        return $this->status;
    }
    function list_all_organization_surveys($organization_id) {
        $query   = $this->db->query("
            SELECT `surveys`.`id`
            FROM `surveys`
			WHERE `surveys`.`deleted` = 0");
        $results = $query->result();
        return array(
            "surveys" => $results
        );
    }
    function surveyor_listAll($user_id) {

        $query_string = "
            SELECT *
            FROM `user_surveys` 
            INNER JOIN `surveys` ON `user_surveys`.survey_id = `surveys`.id 
            WHERE `user_surveys`.user_id = " . $user_id . " 
                    AND `surveys`.`active` = 1 
                    AND `surveys`.`deleted` = 0 
                    AND `user_surveys`.`deleted` = 0 ";
        // if ($time != null) {
        //     $query_string .= " AND (`surveys`.created_date > '" . $time . "' OR `surveys`.updated_date > '" . $time . "') ";
        // }
        $query_string .= " ORDER BY `surveys`.`order` ASC";

        $query   = $this->db->query($query_string);
        $results = $query->result();

        if (count($results) > 0) {
            $surveys = array();
            foreach ($results as $res) {
                $surveys[] = $this->get($res->id);
            }
            $this->status = array(
                "status_message" => "Surveys have been fetched successfully.",
                "status_code" => 50,
                "status" => true
            );
            return $surveys;
        } else {
            $this->status = array(
                "status_message" => "There are no surveys for this user.",
                "status_code" => -50,
                "status" => false
            );
            return false;
        }
    }
    function list_all($user_id) {
    	$results;
    	if($this->ion_auth->in_group("admin")){
    		$query = $this->db->query("
    				SELECT `surveys`.`id`,
    				`surveys`.`title`,
    				`surveys`.`active`,
    				`surveys`.`order`,
    				`surveys`.`organization_id`
    				FROM `surveys`
    				JOIN `user_organizations` ON `user_organizations`.organization_id = surveys.organization_id and `user_organizations`.active = 1 and `user_organizations`.user_id = " . $user_id . "
    				WHERE `surveys`.`in_live` = 1 AND `surveys`.`deleted` = 0 ORDER BY `surveys`.`updated_date` DESC"); // 01262016 AA: ordered survey to be the updated first

                    // 03012016 AA: ommitted accountsLinked from list_all() : (SELECT COUNT(*) FROM `user_surveys` WHERE `user_surveys`.`survey_id` = `surveys`.`id` AND `user_surveys`.`deleted` = 0) as accountsLinked

    	}elseif($this->ion_auth->in_group("superadmin")){
    		$query = $this->db->query("
    				SELECT `surveys`.`id`,
    				`surveys`.`title`,
    				`surveys`.`active`,
    				`surveys`.`order`,
    				`surveys`.`organization_id`
    				FROM `surveys`
    				WHERE `surveys`.`in_live` = 1 AND `surveys`.`deleted` = 0 ORDER BY `surveys`.`updated_date` DESC"); // 01262016 AA: ordered survey to be the updated first

                    // 03012016 AA: ommitted accountsLinked from list_all() : (SELECT COUNT(*) FROM `user_surveys` WHERE `user_surveys`.`survey_id` = `surveys`.`id` AND `user_surveys`.`deleted` = 0) as accountsLinked

    	}

    	$query_group = $this->db->query("SELECT group_id FROM `users_groups` where user_id = ".$user_id."");
        $get_group = $query_group->result();

        if($get_group[0]->group_id == 1)
            $get_group[0]->type = false;
        if($get_group[0]->group_id == 4)
            $get_group[0]->type = true;

        // log_message("debug", $user_id);

    	$results = $query->result();
    	foreach ($results as &$res) {
    		if ($res->active == 0) {
    			$res->active = false;
    		} else {
    			$res->active = true;
    		}
    	}
    	
    	return array(
                "surveys" => $results,
                "type" => $get_group[0]->type // AA: 01182016
    	);

    }

    function get($survey_id) {
        $this->db->select("surveys.`id`,
            surveys.`order`,
            surveys.`title`,
            surveys.`active`,
            surveys.`welcome_message`,
            surveys.`closing_message`,
            surveys.`run_in_kiosk_mode`,
            surveys.`language`,
            surveys.`background` as `bg`,
            surveys.`logo`,
            surveys.`run_in_gps_mode`,
            surveys.`code`,
            surveys.`version`,
            surveys.`organization_id`,
            surveys.`isbackcheck` as back_check, 
            surveys.`backcheckreference`, 
            surveys.`backcheckfile` as back_check_name,
            surveys.`survey_image`,
            surveys.`parent_id`,
            surveys.`in_live` 

            ");
        
        $this->db->from('surveys');

        if ($survey_id) {
            $this->db->where('surveys.`id`', $survey_id);
            $this->db->limit(1);
            $query                       = $this->db->get();
            $survey                      = $query->row_array();
            // $questions                   = $this->get_questions(null, $survey_id);
        } 
        
        $survey['active']            = ($survey['active'] == 1) ? true : false;
        $survey['run_in_kiosk_mode'] = ($survey['run_in_kiosk_mode'] == 1) ? true : false;
        $survey['run_in_gps_mode']   = ($survey['run_in_gps_mode'] == 1) ? true : false;
        $survey['back_check']        = ($survey['back_check'] == 1) ? true : false;
        // $survey['items']             = $questions;

        $this->status = array(
            "status_message" => "Survey have been fetched successfully.",
            "status_code" => 50,
            "status" => true
        );
        return $survey;
    }
    function count_survey_questions() {
        $query   = $this->db->query("SELECT id as count FROM `survey_questions` order by id desc LIMIT 1");
        return $results = $query->result();
    }
    function reorderQuestion($questionNumber, $order, $questionId, $survey_id) { // 04062016 AA: added updateQuestionOrder function to update order when loading survey
        $now = date('Y-m-d H:i:s');
        $query = $this->db->query("UPDATE `survey_questions` 
                    SET `order`=".$questionNumber.", 
                    `update_count` = `update_count` + 1, `updated_date` = '" . $now . "' 
                    WHERE `id`=".$questionId." AND `survey_id`=".$survey_id);
        if ($query) {
            return $query;
        } else return false;
        // log_message("debug", mysql_error());
    }
    function check_validation($survey_id, $intro) {

        // log_message("debug", "intro=".$intro." survey_id=".$survey_id);

        $this->db->select("survey_questions.`intro`
            ");
        $this->db->from('survey_questions');
        $this->db->where('survey_questions.`intro`', $intro);
        $this->db->where('survey_questions.`survey_id`', $survey_id);
        $this->db->where('survey_questions.`deleted`', '0');
        $query = $this->db->get();
        $validity = $query->num_rows;
        // log_message("debug", "val=".$query->num_rows);
        // log_message("debug", "val_mod=".$validity);
        // log_message("debug", "validity_model=".$validity);
        if ($validity>0) {
            // log_message("debug", "validity=false:".$validity);
            $validity = "false";
        } else {
            // log_message("debug", "validity=true:".$validity);
            $validity = "true";
        }
        // log_message("debug", "val_mod=".$validity);
        return $validity;
    }
    function get_question($id, $s_id) { // AA:10222015
        $query   = $this->db->query("
            SELECT *
            FROM `survey_questions` 
            WHERE `id` = " . $id . "
                AND `survey_id` = " .$s_id. " AND `deleted` = 0
            LIMIT 1
            ");
        $result = $query->result();
        foreach ($result as &$q) {
            
            if ($q) {

                $has_answers = array(
                    "multiple_choice_labels",
                    "multiple_choice_list",
                    "prioritise",
                    "text_page",
                    "rating",
                    "gps",
                    "pic_capture",
                    "sliderq"
                );

                if (in_array($q->type, $has_answers)) {
                    $q->answers = $this->get_answers($q->id);
                    if (($q->type == "multiple_choice_list" || $q->type == "multiple_choice_labels" || $q->type == "text_page") && $q->check_prev_ans != 0) {
                        $q->extras = $this->get_extras($q->id);
                    }
                    if (($q->type == "multiple_choice_list" || $q->type == "multiple_choice_labels") && $q->skip_multiple != 0) {
                        $q->conditions = $this->get_conditions($q->id);
                    }
                } else if ($q->type == "contact_page") {
                    $this->db->select("*");
                    $this->db->from('survey_contact_questions');
                    $this->db->where('survey_contact_questions.`survey_question_id`', $q->id);
                    $query = $this->db->get();
                    if ($query->num_rows() > 0) {
                        $p                          = $query->row();
                        $p->ask_name                = $p->ask_name === '1' ? true : false;
                        $p->ask_email               = $p->ask_email === '1' ? true : false;
                        $p->ask_phone               = $p->ask_phone === '1' ? true : false;
                        $p->ask_address_street      = $p->ask_address_street === '1' ? true : false;
                        $p->ask_address_postal_code = $p->ask_address_postal_code === '1' ? true : false;
                        $p->ask_address_city        = $p->ask_address_city === '1' ? true : false;
                        $p->ask_address_state       = $p->ask_address_state === '1' ? true : false;
                        $p->ask_address_country     = $p->ask_address_country === '1' ? true : false;
                        $p->ask_extra_field         = $p->ask_extra_field === '1' ? true : false;
                        $q                          = (object) array_merge((array) $q, (array) $p);
                    }
                } else if ($q->type == "gender_age") {
                    $this->db->select("survey_questions.`id`,
                            survey_questions.`question`,
                            survey_questions.`type`,
                            survey_questions.`active`,
                            survey_questions.`mandatory`
                            ");
                    $this->db->from('survey_questions');
                    $this->db->where('survey_questions.`parent_id`', $q->id);
                    if ($query = $this->db->get()) {
                        $sub_questions = $query->result();
                        foreach ($sub_questions as $sub_q) {
                            if ($sub_q->type == "age") {
                                $q->age_question  = $sub_q->question;
                                $q->ask_age       = $sub_q->active == 1 ? true : false;
                                $q->mandatory_age = $sub_q->mandatory == 1 ? true : false;
                                $q->age_answers   = $this->get_answers($sub_q->id);
                            } else if ($sub_q->type == "gender") {
                                $q->gender_question  = $sub_q->question;
                                $q->ask_gender       = $sub_q->active == 1 ? true : false;
                                $q->mandatory_gender = $sub_q->mandatory == 1 ? true : false;
                                $q->gender_answers   = $this->get_answers($sub_q->id);
                            }
                        }
                    }
                }
                if ($q->type != "multiple_choice_labels" && $q->type != "multiple_choice_list") {
                    unset($q->allow_multiple);
                } else {
                    $q->allow_multiple = $q->allow_multiple === '1' ? true : false;
                }
                if (($q->type == "open_question" || $q->type == "sliderq" || $q->type == "text_page") && $q->check_prev_ans != 0) {
                    $q->extras = $this->get_extras($q->id);
                }
                if ($q->type != "multiple_choice_labels" && $q->type != "multiple_choice_list" && $q->type != "sliderq") {
                    unset($q->show_prev_ans);
                } else {
                    $q->show_prev_ans = $q->show_prev_ans === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_labels" && $q->type != "multiple_choice_list" && $q->type != "open_question" && $q->type != "sliderq" && $q->type != "text_page") {
                    unset($q->check_prev_ans);
                } else {
                    $q->check_prev_ans = $q->check_prev_ans === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_labels" && $q->type != "multiple_choice_list" && $q->type != "open_question" && $q->type != "sliderq") {
                    unset($q->dependent);
                } else {
                    $q->dependent = $q->dependent === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
                    unset($q->skip_multiple);
                } else {
                    $q->skip_multiple = $q->skip_multiple === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_labels") {
                    unset($q->show_result_SD);
                } else {
                    $q->show_result_SD = $q->show_result_SD === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->enable_listing);
                } else {
                    $q->enable_listing = $q->enable_listing === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->text_confirm);
                } else {
                    $q->text_confirm = $q->text_confirm === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_list") {
                    unset($q->enable_otheropt);
                } else {
                    $q->enable_otheropt = $q->enable_otheropt === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->enable_alternative);
                } else {
                    $q->enable_alternative = $q->enable_alternative === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_list") {
                    unset($q->enable_noneabove);
                } else {
                    $q->enable_noneabove = $q->enable_noneabove === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->enable_dont_know);
                } else {
                    $q->enable_dont_know = $q->enable_dont_know === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->change_input);
                } else {
                    $q->change_input = $q->change_input === '1' ? true : false;
                }
                if ($q->type != "open_question" && $q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
                    unset($q->multiple_dependent);
                } else {
                    $q->multiple_dependent = $q->multiple_dependent === '1' ? true : false;
                }
                // 05022016 AA: added multiple_dependent_AND
                // if ($q->type != "open_question" && $q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
                //     unset($q->multiple_dependent_and);
                // } else {
                //     $q->multiple_dependent_and = $q->multiple_dependent === '1' ? true : false;
                // }
                if ($q->type != "open_question") {
                    unset($q->input_type);
                }
                if ($q->type != "rating") {
                    unset($q->rating_type);
                }
                if ($q->type != "open_question") {
                    unset($q->back_reference);
                } else {
                    $q->back_reference = $q->back_reference === '1' ? true : false;
                }
                if ($q->type != "open_question" && $q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
                    unset($q->picture_caption_toggle); // 02012016 AA: picture_caption_toggle
                } else {
                    $q->picture_caption_toggle = $q->picture_caption_toggle === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_list") {
                    unset($q->picture_caption_question);
                } else {
                    $q->picture_caption_question = $q->picture_caption_question === '1' ? true : false; // 02192016 AA: added picture_caption_question
                }
								
				//157019_08062015 Tony
				if($q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
					unset($q->selected_answer);
				} else {
					$q->selected_answer	=	$q->selected_answer  === '1' ? true: false;
				}
				
				//157019_08062015 Tony
				if($q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
					unset($q->unselected_answer);
				} else {
					$q->unselected_answer	=	$q->unselected_answer  === '1' ? true: false;
				}

                unset($q->parent_id);
                $no_skip_question = array(
                    "contact_page",
                    "open_question",
                    "gps",
                    "pic_capture",
                    "sliderq"
                );
                if (in_array($q->type, $no_skip_question)) {
                    unset($q->skip_question);
                } else {
                    $q->skip_question = $q->skip_question === '1' ? true : false;
                }
                
                return $result;
            } else {
                return null;
            }
        }
    }
    function create($title, $org_id, $user) {
    	if($this->ion_auth->in_group("admin")){
    		$query   = $this->db->query("select o.id, o.name from organizations o
    				join user_organizations uo on uo.organization_id = o.id AND uo.user_id = " . $user->id . " and uo.active = 1
    				where o.active = 1 and o.id = ".$org_id);
    	}elseif($this->ion_auth->in_group("superadmin")){
    		$query   = $this->db->query("select o.id, o.name from organizations o 
    				where o.active = 1 and o.id = ".$org_id);
    	}

    	
    	$org = $query->result();
    	
    	if(!$org){
    		return null;
    	}
    	
        $order = $this->get_new_order(0, $org_id);
        
        $now   = date('Y-m-d H:i:s');
        $data  = array(
            "title" => $title,
            "order" => $order,
            "organization_id" => $org_id,
            "created_by" => $user->id,
            "code" => "N/A" , // 215110011_12142015 AA: "N/A" on survey code
            "deleted" => 0,
            "active" => 0,
            "created_date" => $now
        );
        if ($this->db->insert('surveys', $data)) {
            $survey_id = $this->db->insert_id();
            return $survey_id;
        } else {
            return null;
        }
    }
    function duplicate($survey_id, $user, $new_version = false) {
        $this->db->from('surveys');
        $this->db->where('surveys.`id`', $survey_id);
        $this->db->limit(1);
        $query  = $this->db->get();
        $survey = $query->row();
        $this->db->from('survey_questions');
        $this->db->where('survey_questions.`survey_id`', $survey_id);
        $this->db->where('survey_questions.`deleted`', 0);
        $query             = $this->db->get();
        $survey_questions  = $query->result();
        $survey->questions = $survey_questions;
        foreach ($survey->questions as &$question) {
            $this->db->from('survey_question_answers');
            $this->db->where('survey_question_answers.`question_id`', $question->id);
            $this->db->where('survey_question_answers.`deleted`', 0);
            $query                   = $this->db->get();
            $survey_question_answers = $query->result();
            $question->answers       = $survey_question_answers;
            if ($question->type == "contact_page") {
                $this->db->from('survey_contact_questions');
                $this->db->where('survey_contact_questions.`survey_question_id`', $question->id);
                $query                     = $this->db->get();
                $survey_contact_questions  = $query->row_array();
                $question->contact_details = $survey_contact_questions;
            }
        }
        unset($question);
        $this->db->from('user_surveys');
        $this->db->where('user_surveys.`survey_id`', $survey_id);
        $this->db->where('user_surveys.`deleted`', 0);
        $query                = $this->db->get();
        $survey_users         = $query->result();
        $survey->survey_users = $survey_users;
        $this->db->trans_start();
        $order = $this->get_new_order(0, $survey->organization_id);
        $now   = date('Y-m-d H:i:s');
        $data  = array(
            "organization_id" => $survey->organization_id,
            "created_by" => $user->id,
            "order" => $order,
            "title" => "Copy of " . $survey->title,
            "code" => "N/A" , // 215110011_12142015 AA: "N/A" on survey code when duplicating
            "background" => $survey->background,
            "logo" => $survey->logo,
            "language" => $survey->language,
            "date_range" => $survey->date_range,
            "date_from" => $survey->date_from,
            "activated_date" => $survey->activated_date,
            "isbackcheck" => $survey->isbackcheck,
            "backcheckreference" => $survey->backcheckreference,
            "survey_image" => $survey->survey_image,
            "backcheckfile" => $survey->backcheckfile,
            "closing_message" => $survey->closing_message,
            "deleted" => 0,
            "active" => 0,
            "created_date" => $now
        );
        if ($new_version) {
            $data["parent_id"] = $survey->parent_id != null ? $survey->parent_id : $survey_id;
            $data["in_live"]   = 0;
            $this->db->where('id', $survey->parent_id != null ? $survey->parent_id : $survey_id);
            $this->db->or_where('parent_id', $survey->parent_id != null ? $survey->parent_id : $survey_id);
            $this->db->from('surveys');
            $version = $this->db->count_all_results();
            $version++;
            $query           = $this->db->query("SELECT title, code FROM surveys WHERE in_live = 1 AND (id = " . $data["parent_id"] . " OR parent_id = " . $data["parent_id"] . ") LIMIT 1 ");
            $title_code      = $query->row();
            $data["title"]   = $title_code->title . " (draft v" . $version . ")";
            $data["code"]    = $title_code->code . " (draft v" . $version . ")";  // 215110011_12142015 AA: change code on publish
            $data["version"] = $version;
            $data["active"]  = $survey->active;
            $data["order"]   = $survey->order;
        }
        $this->db->insert('surveys', $data);
        $new_survey_id                  = $this->db->insert_id();
        $question_ids_old_to_new        = array();
        $question_answer_ids_old_to_new = array();
        foreach ($survey->questions as $question) {
            $data = array(
                "survey_id" => $new_survey_id,
                "order" => $question->order,
                "parent_id" => $question->parent_id,
                "intro" => $question->intro,
                "type" => $question->type,
                "rating_type" => $question->rating_type,
                "skip_question" => $question->skip_question,
                "show_prev_ans" => $question->show_prev_ans,
                "prev_ques_id" => $question->prev_ques_id,
                "check_prev_ans" => $question->check_prev_ans,
                "check_prev_ans_id" => $question->check_prev_ans_id,
                "dependent" => $question->dependent,
                "dependent_question_id" => $question->dependent_question_id,
                "dependent_answer_id" => $question->dependent_answer_id,
                "multiple_dependent" => $question->multiple_dependent,
                "back_reference" => $question->back_reference,
                "picture_caption_toggle" => $question->picture_caption_toggle, // 02012016 AA: picture_caption_toggle
                "picture_caption_question" => $question->picture_caption_question, // 02192016 AA: added picture_caption_question
                "picture_caption" => $question->picture_caption, // 02112016 AA: picture_caption
                "m_dependent_question_id" => $question->m_dependent_question_id,
                "md_logic_handler" => $question->md_logic_handler, // 05252016 AA: add logic handler
                "m_dependent_answers" => $question->m_dependent_answers,
                "skip_multiple" => $question->skip_multiple,
                "updated_date" => $question->updated_date,
                "show_result_SD" => $question->show_result_SD,
                "dbname_SD" => $question->dbname_SD,
                "dbfilter_SD" => $question->dbfilter_SD,
                "enable_listing" => $question->enable_listing,
                "text_confirm" => $question->text_confirm,
                "enable_otheropt" => $question->enable_otheropt,
                "enable_alternative" => $question->enable_alternative,
                "enable_noneabove" => $question->enable_noneabove,
                "noneabove_text" => $question->noneabove_text,
                "enable_dont_know" => $question->enable_dont_know,
                "dontknow_text" => $question->dontknow_text,
                "none_next_question_id" => $question->none_next_question_id,
                "dont_know_next_question_id" => $question->dont_know_next_question_id,
                "change_input" => $question->change_input,
                "input_type" => $question->input_type,
                "text_limit" => $question->text_limit,
                "slider_min" => $question->slider_min,
            	"slider_max" => $question->slider_max,
                "slider_interval" => $question->slider_interval,
				"question" => $question->question,
                "allow_multiple" => $question->allow_multiple,
                "active" => $question->active,
                "deleted" => $question->deleted,
                "created_date" => $now,
                // 05192016 AA: added multiple dependent logic (or/and)
                "m_dependent_logic" => $question->m_dependent_logic,
                // 05022016 AA: added multiple_dependent_AND
                // "multiple_dependent_and" => $question->multiple_dependent_and,
                // "m_dependent_question_id_and" => $question->m_dependent_question_id_and,
                // "m_dependent_answers_and" => $question->m_dependent_answers_and,
				"selected_answer" => $question->selected_answer, //03282016Tony Selected , Unselected mcl
                "selected_answer_id" => $question->selected_answer_id,
				"unselected_answer" => $question->unselected_answer,
                "unselected_answer_id" => $question->unselected_answer_id,
            );
            $this->db->insert('survey_questions', $data);
            $new_question_id = $this->db->insert_id();
            $question_ids_old_to_new += array(
                $question->id => $new_question_id
            );
            foreach ($question->answers as $answer) {
                $data = array(
                    "question_id" => $new_question_id,
                    "order" => $answer->order,
                    "answer" => $answer->answer,
                    "next_question_id" => $answer->next_question_id,
                    "extra" => $answer->extra,
                    "extra_id" => $answer->extra_id,
                    "extra_next_question_id" => $answer->extra_next_question_id,
                    "m_skip_ids" => $answer->m_skip_ids,
                    "m_skip_condition" => $answer->m_skip_condition,
                    "m_skip_next_question" => $answer->m_skip_next_question,
                    "picture_caption" => $answer->picture_caption,
                    "deleted" => $answer->deleted,
                    "created_date" => $now
                ); // 02032016 AA: picture_caption
                $this->db->insert('survey_question_answers', $data);
                $new_question_answer_id = $this->db->insert_id();
                $question_answer_ids_old_to_new += array(
                    $answer->id => $new_question_answer_id
                );
            }
            if ($question->type == "contact_page") {
                $data                       = array();
                $data["survey_question_id"] = $new_question_id;
                $data                       = array_merge($question->contact_details, $data);
                $this->db->insert('survey_contact_questions', $data);
            }
        }
        if (count($question_ids_old_to_new) != 0) {
            $this->db->from("survey_questions");
            $this->db->where_in('id', $question_ids_old_to_new);
            $query                = $this->db->get();
            $survey_questions_new = $query->result();
            foreach ($survey_questions_new as &$value) {
                if (array_key_exists($value->parent_id, $question_ids_old_to_new)) {
                    $value->parent_id = $question_ids_old_to_new[$value->parent_id];
                }
                if (array_key_exists($value->prev_ques_id, $question_ids_old_to_new)) {
                    $value->prev_ques_id = $question_ids_old_to_new[$value->prev_ques_id];
                }
                if (array_key_exists($value->check_prev_ans_id, $question_ids_old_to_new)) {
                    $value->check_prev_ans_id = $question_ids_old_to_new[$value->check_prev_ans_id];
                }
                if (array_key_exists($value->dependent_question_id, $question_ids_old_to_new)) {
                    $value->dependent_question_id = $question_ids_old_to_new[$value->dependent_question_id];
                }
                if (array_key_exists($value->dependent_answer_id, $question_answer_ids_old_to_new)) {
                    $value->dependent_answer_id = $question_answer_ids_old_to_new[$value->dependent_answer_id];
                }
                if (array_key_exists($value->none_next_question_id, $question_ids_old_to_new)) {
                    $value->none_next_question_id = $question_ids_old_to_new[$value->none_next_question_id];
                }
                if (array_key_exists($value->dont_know_next_question_id, $question_ids_old_to_new)) {
                    $value->dont_know_next_question_id = $question_ids_old_to_new[$value->dont_know_next_question_id];
                }
                if ($value->m_dependent_question_id != "0" && $value->m_dependent_question_id != "") {
                    $ids                            = explode(",", rtrim($value->m_dependent_question_id, ","));
                    $value->m_dependent_question_id = "";
                    foreach ($ids as $id) {
                        $value->m_dependent_question_id .= array_key_exists($id, $question_ids_old_to_new) ? $question_ids_old_to_new[$id] . "," : $id . ",";
                    }
                    $value->m_dependent_question_id = rtrim($value->m_dependent_question_id, ",");
                }
                if ($value->m_dependent_answers != "0" && $value->m_dependent_answers != "") {
                    $ids                        = explode(",", rtrim($value->m_dependent_answers, ","));
                    $value->m_dependent_answers = "";
                    foreach ($ids as $id) {
                        $value->m_dependent_answers .= array_key_exists($id, $question_answer_ids_old_to_new) ? $question_answer_ids_old_to_new[$id] . "," : $id . ",";
                    }
                    $value->m_dependent_answers = rtrim($value->m_dependent_answers, ",");
                }
                // 05022016 AA: added multiple_dependent_AND
                // if ($value->m_dependent_question_id_and != "0" && $value->m_dependent_question_id_and != "") {
                //     $ids                            = explode(",", rtrim($value->m_dependent_question_id_and, ","));
                //     $value->m_dependent_question_id_and = "";
                //     foreach ($ids as $id) {
                //         $value->m_dependent_question_id_and .= array_key_exists($id, $question_ids_old_to_new) ? $question_ids_old_to_new[$id] . "," : $id . ",";
                //     }
                //     $value->m_dependent_question_id_and = rtrim($value->m_dependent_question_id_and, ",");
                // }

                // 05192016 AA: added multiple dependent logic (or/and)
                if ($value->m_dependent_logic != "0" && $value->m_dependent_logic != "") {
                    $ids                            = explode(",", rtrim($value->m_dependent_logic, ","));
                    $value->m_dependent_logic = "";
                    foreach ($ids as $id) {
                        $value->m_dependent_logic .= array_key_exists($id, $question_ids_old_to_new) ? $question_ids_old_to_new[$id] . "," : $id . ",";
                    }
                    $value->m_dependent_logic = rtrim($value->m_dependent_logic, ",");
                }
                // if ($value->m_dependent_answers_and != "0" && $value->m_dependent_answers_and != "") {
                //     $ids                        = explode(",", rtrim($value->m_dependent_answers_and, ","));
                //     $value->m_dependent_answers_and = "";
                //     foreach ($ids as $id) {
                //         $value->m_dependent_answers_and .= array_key_exists($id, $question_answer_ids_old_to_new) ? $question_answer_ids_old_to_new[$id] . "," : $id . ",";
                //     }
                //     $value->m_dependent_answers_and = rtrim($value->m_dependent_answers_and, ",");
                // }
				
				//03282016Tony Selected
				 if ($value->selected_answer_id != "0" && $value->selected_answer_id != "") {
                    $ids                            = explode(",", rtrim($value->selected_answer_id, ","));
                    $value->selected_answer_id = "";
                    foreach ($ids as $id) {
                        $value->selected_answer_id .= array_key_exists($id, $question_ids_old_to_new) ? $question_ids_old_to_new[$id] . "," : $id . ",";
                    }
                    $value->selected_answer_id = rtrim($value->selected_answer_id, ",");
                }
                if ($value->selected_answer != "0" && $value->selected_answer != "") {
                    $ids                        = explode(",", rtrim($value->selected_answer, ","));
                    $value->selected_answer = "";
                    foreach ($ids as $id) {
                        $value->selected_answer .= array_key_exists($id, $question_answer_ids_old_to_new) ? $question_answer_ids_old_to_new[$id] . "," : $id . ",";
                    }
                    $value->selected_answer = rtrim($value->selected_answer, ",");
                }
						
				//03282016Tony UnSelected
				 if ($value->unselected_answer_id != "0" && $value->unselected_answer_id != "") {
                    $ids                            = explode(",", rtrim($value->unselected_answer_id, ","));
                    $value->unselected_answer_id = "";
                    foreach ($ids as $id) {
                        $value->unselected_answer_id .= array_key_exists($id, $question_ids_old_to_new) ? $question_ids_old_to_new[$id] . "," : $id . ",";
                    }
                    $value->unselected_answer_id = rtrim($value->unselected_answer_id, ",");
                }
                if ($value->unselected_answer != "0" && $value->unselected_answer != "") {
                    $ids                        = explode(",", rtrim($value->unselected_answer, ","));
                    $value->unselected_answer = "";
                    foreach ($ids as $id) {
                        $value->unselected_answer .= array_key_exists($id, $question_answer_ids_old_to_new) ? $question_answer_ids_old_to_new[$id] . "," : $id . ",";
                    }
                    $value->unselected_answer = rtrim($value->unselected_answer, ",");
                }
				
                $this->db->where('id', $value->id);
                $this->db->update('survey_questions', $value);
            }
            unset($value);
        }
        if (count($question_answer_ids_old_to_new) != 0) {
            $this->db->from("survey_question_answers");
            $this->db->where_in('id', $question_answer_ids_old_to_new);
            $query                       = $this->db->get();
            $survey_question_answers_new = $query ? $query->result() : array();
            foreach ($survey_question_answers_new as &$value) {
                if (array_key_exists($value->next_question_id, $question_ids_old_to_new)) {
                    $value->next_question_id = $question_ids_old_to_new[$value->next_question_id];
                }
                if (array_key_exists($value->extra_id, $question_answer_ids_old_to_new)) {
                    $value->extra_id = $question_answer_ids_old_to_new[$value->extra_id];
                }
                if (array_key_exists($value->extra_next_question_id, $question_ids_old_to_new)) {
                    $value->extra_next_question_id = $question_ids_old_to_new[$value->extra_next_question_id];
                }
                if ($value->m_skip_ids != "0") {
                    $ids               = explode(",", rtrim($value->m_skip_ids, ","));
                    $value->m_skip_ids = "";
                    foreach ($ids as $id) {
                        $value->m_skip_ids .= array_key_exists($id, $question_answer_ids_old_to_new) ? $question_answer_ids_old_to_new[$id] . "," : $id . ",";
                    }
                    $value->m_skip_ids = rtrim($value->m_skip_ids, ",");
                }
                if (array_key_exists($value->m_skip_next_question, $question_ids_old_to_new)) {
                    $value->m_skip_next_question = $question_ids_old_to_new[$value->m_skip_next_question];
                }
                $this->db->where('id', $value->id);
                $this->db->update('survey_question_answers', $value);
            }
            unset($value);
        }
        if (!$new_version) {
            foreach ($survey->survey_users as $user_survey) {
                $data = array(
                    "user_id" => $user_survey->user_id,
                    "survey_id" => $new_survey_id,
                    "deleted" => 0,
                    "created_date" => $now
                );
                $this->db->insert('user_surveys', $data);
            }
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return null;
        } else {
            return $new_survey_id;
        }
    }
    function generate_question($id, $data) { // 03092016 AA: integrating generation of questions
        if ($survey = $this->get_basic_survey($id)) {
            $now    = date('Y-m-d H:i:s');
            $am = 0;
            if (isset($data['allow_multiple']) && $data['allow_multiple'] == "true") {
                $am = 1;
            }
            $sq = 0;
            if (isset($data['skip_question']) && $data['skip_question'] == "true") {
                $sq = 1;
            }
            $spa = 0;
            if (isset($data['show_prev_ans']) && $data['show_prev_ans'] == "true") {
                $spa = 1;
            }
            $cpa = 0;
            if (isset($data['check_prev_ans']) && $data['check_prev_ans'] == "true") {
                $cpa = 1;
            }
            $d = 0;
            if (isset($data['dependent']) && $data['dependent'] == "true") {
                $d = 1;
            }
            $md = 0;
            if (isset($data['multiple_dependent']) && $data['multiple_dependent'] == "true") {
                $md = 1;
            }
            // 05022016 AA: added multiple_dependent_AND
            // $md_and = 0;
            // if (isset($data['multiple_dependent_and']) && $data['multiple_dependent_and'] == "true") {
            //     $md_and = 1;
            // }
            $br = 0;
            if (isset($data['back_reference']) && $data['back_reference'] == "true") {
                $br = 1;
            }
            $pcaption = 0;
            if (isset($data['picture_caption_toggle']) && $data['picture_caption_toggle'] == "true") { // 02012016 AA: picture_caption_toggle
                $pcaption = 1;
            }
            $pcquestion = 0;
            if (isset($data['picture_caption_question']) && $data['picture_caption_question'] == "true") { // 02192016 AA: added picture_caption_question
                $pcquestion = 1;
            }
            $sm = 0;
            if (isset($data['skip_multiple']) && $data['skip_multiple'] == "true") {
                $sm = 1;
            }
            $rsd = 0;
            if (isset($data['show_result_SD']) && $data['show_result_SD'] == "true") {
                $rsd = 1;
            }
            $cit = 0;
            if (isset($data['change_input']) && $data['change_input'] == "true") {
                $cit = 1;
            }
            $inputtype = "";
            if ($data['input_type'] == 0) {
                $inputtype = "Text";
            } else if ($data['input_type'] == 1) {
                $inputtype = "Number";
            } else if ($data['input_type'] == 2) {
                $inputtype = "Phone";
            } else if ($data['input_type'] == 3) {
                $inputtype = "Date Time";
            } else if ($data['input_type'] == 4) {
                $inputtype = "Decimal";
            }
            $elsd = 0;
            if (isset($data['enable_listing']) && $data['enable_listing'] == "true") {
                $elsd = 1;
            }
            $txtc = 0;
            if (isset($data['text_confirm']) && $data['text_confirm'] == "true") {
                $txtc = 1;
            }
            $eother = 0;
            if (isset($data['enable_otheropt']) && $data['enable_otheropt'] == "true") {
                $eother = 1;
            }
            $ealt = 0;
            if (isset($data['enable_alternative']) && $data['enable_alternative'] == "true") {
                $ealt = 1;
            }
            $enone = 0;
            if (isset($data['enable_noneabove']) && $data['enable_noneabove'] == "true") {
                $enone = 1;
            }
            $edk = 0;
            if (isset($data['enable_dont_know']) && $data['enable_dont_know'] == "true") {
                $edk = 1;
            }
			
			//157019_08062015 Tony
			$saq = 0;
			if(isset($data['selected_answer']) && $data['selected_answer'] == "true"){
				$saq = 1;
			}
			
			//157019_08062015 Tony
			$usaq = 0;
			if(isset($data['unselected_answer']) && $data['unselected_answer'] == "true"){
				$usaq = 1;
			}
			
            $rating_type = "";
            if ($data['rating_type'] == 1) {
                $rating_type = "stars";
            } else if ($data['rating_type'] == 2) {
                $rating_type = "hearts";
            } else if ($data['rating_type'] == 3) {
                $rating_type = "emoticons";
            }
            // log_message("debug", "created order=".$data['order']);
            $params = array(
                "survey_id" => $survey->id,
                "intro" => $data['intro'],
                "question" => $data['question'],
                "type" => $data['type'],
                "order" => $data['order'],
                "rating_type" => $rating_type,
                "allow_multiple" => $am,
                "skip_question" => $sq,
                "show_prev_ans" => $spa,
                "prev_ques_id" => $data['prev_ques_id'],
                "check_prev_ans" => $cpa,
                "check_prev_ans_id" => $data['check_prev_ans_id'],
                "dependent" => $d,
                "dependent_question_id" => $data['dependent_question_id'],
                "dependent_answer_id" => $data['dependent_answer_id'],
                "multiple_dependent" => $md,
                // 05192016 AA: added multiple dependent logic (or/and)
                "m_dependent_logic" => $data['m_dependent_logic'],
                // 05022016 AA: added multiple_dependent_AND
                // "multiple_dependent_and" => $md_and,
                // "m_dependent_question_id_and" => $data['m_dependent_question_id_and'],
                // "m_dependent_answers_and" => $data['m_dependent_answers_and'],
                "back_reference" => $br,
                "picture_caption_toggle" => $pcaption, // 02012016 AA: picture_caption_toggle
                "picture_caption_question" => $pcquestion, // 02192016 AA: added picture_caption_question
                "picture_caption" => basename($data['picture_caption']), // 02112016 AA: picture_caption
                "m_dependent_question_id" => $data['m_dependent_question_id'],
                "md_logic_handler" => $data['md_logic_handler'], // 05252016 AA: add logic handler
                "m_dependent_answers" => $data['m_dependent_answers'],
                "skip_multiple" => $sm,
                "active" => 1,
                "created_date" => $now,
                "updated_date" => $now,
                "show_result_SD" => $rsd,
                "dbname_SD" => $data['dbname_SD'],
                "dbfilter_SD" => $data['dbfilter_SD'],
                "enable_listing" => $elsd,
                "text_confirm" => $txtc,
                "enable_otheropt" => $eother,
                "enable_alternative" => $ealt,
                "enable_noneabove" => $enone,
                "noneabove_text" => $data['noneabove_text'],
                "enable_dont_know" => $edk,
                "dontknow_text" => $data['dontknow_text'],
                "none_next_question_id" => $data['none_next_question_id'],
                "dont_know_next_question_id" => $data['dont_know_next_question_id'],
                "change_input" => $cit,
                "input_type" => $inputtype,
                "text_limit" => $data['text_limit'],
                "slider_min" => $data['slider_min'],
                "slider_interval" => $data['slider_interval'],
                "slider_max" => $data['slider_max'],
				"selected_answer"				=> $saq,//157019_08062015 Tony
				"selected_answer_id"			=> $data['selected_answer_id'],
				"unselected_answer"				=> $usaq,
				"unselected_answer_id"			=> $data['unselected_answer_id']
            );
            $this->db->trans_start();
            $question_id = 0;
            if ($this->db->insert('survey_questions', $params)) {
                $question_id = $this->db->insert_id();
                if ($data['type'] == "contact_page") {
                    $params = array(
                        "survey_question_id" => $question_id
                    );
                    $this->db->insert('survey_contact_questions', $params);
                } else if ($data['type'] == "gender_age") {
                    $params = array(
                        "survey_id" => $survey->id,
                        "parent_id" => $question_id,
                        "order" => 2,
                        "type" => "age",
                        "active" => 0,
                        "deleted" => 0,
                        "created_date" => $now
                    );
                    if ($this->db->insert('survey_questions', $params)) {
                        $age_question_id  = $this->db->insert_id();
                        $defaultAgeValues = array(
                            "<18",
                            "18-35",
                            "36-55",
                            ">55"
                        );
                        $order            = 1;
                        foreach ($defaultAgeValues as $answer) {
                            $params = array(
                                "question_id" => $age_question_id,
                                "order" => $order,
                                "answer" => $answer,
                                "next_question_id" => 0,
                                "deleted" => 0,
                                "created_date" => $now
                            );
                            $this->db->insert('survey_question_answers', $params);
                            $order++;
                        }
                    }
                    $params = array(
                        "survey_id" => $survey->id,
                        "parent_id" => $question_id,
                        "type" => "gender",
                        "active" => 0,
                        "order" => 1,
                        "deleted" => 0,
                        "created_date" => $now
                    );
                    if ($this->db->insert('survey_questions', $params)) {
                        $gender_question_id  = $this->db->insert_id();
                        $defaultGenderValues = array(
                            "Male",
                            "Female"
                        );
                        $order               = 1;
                        foreach ($defaultGenderValues as $answer) {
                            $params = array(
                                "question_id" => $gender_question_id,
                                "order" => $order,
                                "answer" => $answer,
                                "next_question_id" => 0,
                                "deleted" => 0,
                                "created_date" => $now
                            );
                            $this->db->insert('survey_question_answers', $params);
                            $order++;
                        }
                    }
                }
            }
            $this->update_survey_updated_date($survey->id);
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return null;
            } else {
                return $this->get_questions($question_id, null, false);
            }
        } else {
            return null;
        }
    }
    function re_order_survey($survey_id, $user, $passing_survey_id) {
        if ($survey = $this->get_basic_survey($survey_id)) {
            $new_order = 1;
            if ($passing_survey = $this->get_basic_survey($passing_survey_id)) {
                $new_order = $passing_survey->order;
            }
            $old_order = $survey->order;
            $now       = date('Y-m-d H:i:s');
            if ($old_order > $new_order) {
                $query = "UPDATE `surveys` SET `order` = 
						(CASE `order` WHEN " . $old_order . " THEN " . $new_order . " ELSE `order` + 1 END),
						`update_count` = `update_count` + 1,
						`updated_date` = '" . $now . "' 
						WHERE `order` BETWEEN " . $new_order . " AND " . $old_order . " 
					AND `organization_id` = " . $survey->organization_id . "
					AND `active` = " . $survey->active;
            } else {
                $query = "UPDATE `surveys` SET `order` = 
						(CASE `order` WHEN " . $old_order . " THEN " . $new_order . " ELSE `order` - 1 END),
						`update_count` = `update_count` + 1,
						`updated_date` = '" . $now . "' 
						WHERE `order` BETWEEN " . $old_order . " AND " . $new_order . " 
					AND `organization_id` = " . $survey->organization_id . " 
					AND `active` = " . $survey->active;
            }
            if ($finally = $this->db->query($query)) {
                return true;
            }
        }
        return null;
    }
    function activate($survey_id, $activate, $user) {
        $survey = $this->get_basic_survey($survey_id);
        $now    = date('Y-m-d H:i:s');
        if ($activate == "false") {
            $data = array(
                "updated_date" => $now,
                "active" => 0,
                "order" => $this->get_new_order(0, $survey->organization_id)
            );
            $this->db->set('`update_count`', '`update_count`+1', FALSE);
            $this->db->where('id', $survey_id);
            return $this->db->update('surveys', $data);
        } else {
            $survey = $this->get($survey_id, $user, $page_no = 0, $items_per_page = 9999);
            if (count($survey['items']) > 0 && trim($survey['closing_message']) != "") {
                $data = array(
                    "updated_date" => $now,
                    "activated_date" => $now,
                    "active" => 1,
                    "order" => $this->get_new_order(1, $survey["organization_id"])
                );
                $this->db->set('`update_count`', '`update_count`+1', FALSE);
                $this->db->where('id', $survey_id);
                if ($this->db->update('surveys', $data)) {
                    return $now;
                }
            } else {
                return false;
            }
        }
    }

    function activateBackCheck($survey_id, $activate, $user) { // AA:11182015
        $survey = $this->get_basic_survey($survey_id);
        $now    = date('Y-m-d H:i:s');
        // log_message("debug", "activate=".$activate);
        if ($activate == "false") {
            $data = array(
                "updated_date" => $now,
                "isbackcheck" => 0,
                "order" => $this->get_new_order(0, $survey->organization_id)
            );
            $this->db->set('`update_count`', '`update_count`+1', FALSE);
            $this->db->where('id', $survey_id);
            return $this->db->update('surveys', $data);
        } else {
            $survey = $this->get($survey_id, $user, $page_no = 0, $items_per_page = 999999);
            if (count($survey['items']) > 0 && trim($survey['closing_message']) != "") {
                $data = array(
                    "updated_date" => $now,
                    "isbackcheck" => 1,
                    "order" => $this->get_new_order(1, $user->organization_id)
                );
                $this->db->set('`update_count`', '`update_count`+1', FALSE);
                $this->db->where('id', $survey_id);
                if ($this->db->update('surveys', $data)) {
                    return $now;
                }
            } else {
                return false;
            }
        }
    }
    function update($survey_id, $data) {
        $now                  = date('Y-m-d H:i:s');
        $data["updated_date"] = $now;
        $this->db->set('`update_count`', '`update_count`+1', FALSE);
        $this->db->where('id', $survey_id);
        $query = $this->db->update('surveys', $data);
        // log_message("debug", "query=".mysql_error());
        return $query;
    }
    function get_user_accounts($survey_id, $user) {
        // $this->db->select("`users`.id, `users`.first_name, `users`.last_name, `groups`.description as role, `user_surveys`.survey_id");
        // $this->db->from("users");
        // $this->db->join("`user_surveys`", "user_surveys.user_id = users.id AND user_surveys.deleted = 0 AND user_surveys.survey_id = " . $survey_id, "left");
        // $this->db->join("users_groups", "users_groups.user_id = users.id");
        // $this->db->join("groups", "groups.id = users_groups.group_id");
        // $this->db->where("`users`.active", 1);
        // $this->db->where("`users`.organization_id", $user->organization_id);
        // $query = $this->db->get();
        // if ($query) {
        //     $users = $query->result_array();
        //     $this->db->where('organization_id', $user->organization_id);
        //     $this->db->where('active', 1);
        //     $this->db->from("users");
        //     $this->db->join("`user_surveys`", "user_surveys.user_id = users.id");
        //     $this->db->where('`user_surveys`.survey_id', $survey_id);
        //     $this->db->where("`user_surveys`.deleted", "0");
        //     $count = $this->db->count_all_results();
        //     return array(
        //         "users" => $users,
        //         "users_count" => $count
        //     );
        // } else {
        //     return array(
        //         "success" => false
        //     );
        // }
        // 12072015 AA: added "and u.`team_id` is not null"
        $query = $this->db->query("
            SELECT `u`.id, `u`.first_name, `u`.last_name, `g`.description AS role, `us`.survey_id, `t`.name AS team_name, `t`.id as team_id
                FROM users u
			LEFT JOIN user_surveys us ON `us`.user_id = `u`.id AND `us`.deleted = 0 AND `us`.survey_id = ".$survey_id."
        	JOIN surveys s ON s.`id` = ".$survey_id."
			JOIN users_groups ug ON ug.user_id = u.id
			JOIN groups g ON g.id = ug.group_id AND g.`name` = 'surveyor'
			JOIN teams t ON t.id = u.team_id AND t.`active` = 1 AND t.`project` = s.`code`
			WHERE u.`active` = 1 and u.`team_id` is not null
			
            UNION 
            
            SELECT `u`.id, `u`.first_name, `u`.last_name, `g`.description AS role, `us`.survey_id, '' AS team_name, '' as team_id
                FROM users u
            LEFT JOIN user_surveys us ON `us`.user_id = `u`.id AND `us`.deleted = 0 AND `us`.survey_id = ".$survey_id."
            JOIN surveys s ON s.`id` = ".$survey_id."
            JOIN users_groups ug ON ug.user_id = u.id
            JOIN groups g ON g.id = ug.group_id AND g.`name` = 'surveyor'
            WHERE u.`active` = 1 and u.`team_id` is null
			
            UNION 

            SELECT `u`.id, `u`.first_name, `u`.last_name, '' AS role, `us`.survey_id, '(imported)' AS team_name, '' as team_id
                FROM users u
            LEFT JOIN user_surveys us ON `us`.user_id = `u`.id AND `us`.deleted = 0 AND `us`.survey_id = ".$survey_id."
            JOIN surveys s ON s.`id` = ".$survey_id."
            JOIN teams t ON  t.`active` = 1 AND t.`project` != s.`code`
            WHERE u.`active` = 1 and us.`survey_id` is not null and u.`team_id` = `t`.id

            UNION

			SELECT `u`.id, `u`.first_name, `u`.last_name, `g`.description AS role, `us`.survey_id, '' AS team_name, '' as team_id 
                FROM users u 
			LEFT JOIN user_surveys us ON `us`.user_id = `u`.id AND `us`.deleted = 0 AND `us`.survey_id = ".$survey_id."
			JOIN users_groups ug ON ug.user_id = u.id
			JOIN groups g ON g.id = ug.group_id AND g.`name` = 'admin'
			JOIN user_organizations uo ON uo.active = 1 AND u.`id` = uo.user_id
			WHERE u.`active` = 1 and u.`team_id` is not null
			
			UNION 
			
			SELECT `u`.id, `u`.first_name, `u`.last_name, `g`.description AS role, `us`.survey_id, '' AS team_name, '' as team_id 
                FROM users u 
			LEFT JOIN user_surveys us ON `us`.user_id = `u`.id AND `us`.deleted = 0 AND `us`.survey_id = ".$survey_id."
			JOIN users_groups ug ON ug.user_id = u.id
			JOIN groups g ON g.id = ug.group_id AND g.`name` = 'superadmin'
			WHERE u.`active` = 1 and u.`team_id` is not null");
        // 03082016 AA: displays user with no team in a survey and displays previous assign survey from older version
        //$query = $this->db->result();
        if ($query) {
        	$users = $query->result_array();
        	//$this->db->where('organization_id', $user->organization_id);
        	$this->db->where('active', 1);
        	$this->db->from("users");
        	$this->db->join("`user_surveys`", "user_surveys.user_id = users.id");
        	$this->db->where('`user_surveys`.survey_id', $survey_id);
        	$this->db->where("`user_surveys`.deleted", "0");
        	$count = $this->db->count_all_results();
        	return array(
        			"users" => $users,
        			"users_count" => $count
        	);
        } else {
        	return array(
        			"success" => false
        	);
        }
    }
    function update_user_access($survey_id, $user, $users) {
        if ($users == "allusers") {
            $now = date('Y-m-d H:i:s');
//             $this->db->select('users.id');
//             $this->db->from('users');
//             $this->db->join("`user_organizations`", "user_organizations.user_id = users.id and user_organizations.active=1");
//             $this->db->join("`surveys`", "surveys.organization_id = user_organizations.organization_id and surveys.id=".$survey_id);
//             //$this->db->where('organization_id', $user->organization_id);
            
            $query = $this->db->query("SELECT `u`.id FROM users u
            		LEFT JOIN user_surveys us ON `us`.user_id = `u`.id AND `us`.deleted = 0 AND `us`.survey_id = ".$survey_id."
		        	JOIN surveys s ON s.`id` = ".$survey_id."
					JOIN users_groups ug ON ug.user_id = u.id
					JOIN groups g ON g.id = ug.group_id AND g.`name` = 'surveyor'
					JOIN teams t ON t.id = u.team_id AND t.`active` = 1 AND t.`project` = s.`code`
            		WHERE u.`active` = 1
            			
            		UNION
            			
            		SELECT `u`.id FROM users u
					LEFT JOIN user_surveys us ON us.user_id = u.id AND us.deleted = 0 AND us.survey_id = ".$survey_id."
					JOIN users_groups ug ON ug.user_id = u.id
					JOIN groups g ON g.id = ug.group_id AND g.`name` = 'admin'
					JOIN user_organizations uo ON uo.active = 1 AND u.`id` = uo.user_id
					WHERE u.`active` = 1
            			
            		UNION
            			
            		SELECT `u`.id FROM users u
					LEFT JOIN user_surveys us ON us.user_id = u.id AND us.deleted = 0 AND us.survey_id = ".$survey_id."
					JOIN users_groups ug ON ug.user_id = u.id
					JOIN groups g ON g.id = ug.group_id AND g.`name` = 'superadmin'
					WHERE u.`active` = 1");
            
            $users = $query->result();
            //$query = $this->db->get();
            if (!$users) {
            	return false;
            }
        } else {
            $users = json_decode($users);
        }
        $now    = date('Y-m-d H:i:s');
        $params = array(
            "deleted" => 1,
            "deleted_date" => $now
        );
        $this->db->where("survey_id", $survey_id);
        if ($this->db->update('user_surveys', $params)) {
            $count = 0;
            foreach ($users as $user_id) {
                $this->db->where(array(
                    "user_id" => $user_id->id,
                    "survey_id" => $survey_id
                ));
                $query = $this->db->get("user_surveys");
                if ($query->num_rows > 0) {
                    $user_params = array(
                        "deleted" => 0,
                        "deleted_date" => "0000-00-00 00:00:00"
                    );
                    $this->db->where(array(
                        "user_id" => $user_id->id,
                        "survey_id" => $survey_id
                    ));
                    if ($this->db->update("user_surveys", $user_params)) {
                        $count++;
                    }
                } else {
                    $user_params = array(
                        "user_id" => $user_id->id,
                        "survey_id" => $survey_id,
                        "deleted" => 0,
                        "created_date" => $now
                    );
                    if ($this->db->insert("user_surveys", $user_params)) {
                        $count++;
                    }
                }
            }
            if ($this->update_survey_updated_date($survey_id)) {
                return $count;
            }
        } else {
            return false;
        }
    }
    function edit_question($id, $data) {
        $this->load->library('firephp');
        $this->firephp->log($data);
        // log_message("debug", "order=".$data['order']);
        if ($survey = $this->get_basic_survey($id)) {
            $now                = date('Y-m-d H:i:s');
            $has_intro_question = array(
                "multiple_choice_labels",
                "multiple_choice_list",
                "open_question",
                "text_page",
                "rating",
                "prioritise",
                "gender_age",
                "contact_page",
                "gps",
                "pic_capture",
                "sliderq"
            );
            $has_answers        = array(
                "multiple_choice_labels",
                "multiple_choice_list",
                "prioritise",
                "text_page",
                "rating"
            );
            if ($data['type'] == "closing") {
                $params = array(
                    $data['type'] . "_message" => $data['intro'],
                    "updated_date" => $now
                );
                $this->db->where('id', $survey->id);
                $this->db->set('update_count', 'update_count+1', FALSE);
                if ($this->db->update('surveys', $params)) {
                    return $data['intro'];
                } else {
                    return null;
                }
            }
            if (in_array($data['type'], $has_intro_question)) {
                $this->db->trans_start();
                $question      = $this->get_basic_question($data['id']);
                // $data['order'] = $question->order; // 02162016 AA: changed order data from basic to physical order on UI
                if ($question->active == 0) {
                    $new_order = $this->get_new_question_order($survey->id);
                    // if ($question->order != $new_order) { // 02162016 AA: changed order data from basic to physical order on UI
                    if ($data['order'] != $new_order) {
                        $data['order'] = $new_order;
                    }
                }
                $am = 0;
                if (isset($data['allow_multiple']) && $data['allow_multiple'] == "true") {
                    $am = 1;
                }
                $sq = 0;
                if (isset($data['skip_question']) && $data['skip_question'] == "true") {
                    $sq = 1;
                }
                $spa = 0;
                if (isset($data['show_prev_ans']) && $data['show_prev_ans'] == "true") {
                    $spa = 1;
                }
                $cpa = 0;
                if (isset($data['check_prev_ans']) && $data['check_prev_ans'] == "true") {
                    $cpa = 1;
                }
                $d = 0;
                if (isset($data['dependent']) && $data['dependent'] == "true") {
                    $d = 1;
                }
                $md = 0;
                if (isset($data['multiple_dependent']) && $data['multiple_dependent'] == "true") {
                    $md = 1;
                }
                // 05022016 AA: added multiple_dependent_AND
                // $md_and = 0;
                // if (isset($data['multiple_dependent_and']) && $data['multiple_dependent_and'] == "true") {
                //     $md_and = 1;
                // }
                $br = 0;
                if (isset($data['back_reference']) && $data['back_reference'] == "true") {
                    $br = 1;
                }
                $pcaption = 0;
                if (isset($data['picture_caption_toggle']) && $data['picture_caption_toggle'] == "true") { // 02012016 AA: picture_caption_toggle
                    $pcaption = 1;
                }
                $pcquestion = 0;
                if (isset($data['picture_caption_question']) && $data['picture_caption_question'] == "true") { // 02192016 AA: added picture_caption_question
                    $pcquestion = 1;
                }
                $sm = 0;
                if (isset($data['skip_multiple']) && $data['skip_multiple'] == "true") {
                    $sm = 1;
                }
                $rsd = 0;
                if (isset($data['show_result_SD']) && $data['show_result_SD'] == "true") {
                    $rsd = 1;
                }
                $cit = 0;
                if (isset($data['change_input']) && $data['change_input'] == "true") {
                    $cit = 1;
                }
				//157019_08062015 Tony
				$saq = 0;
				if(isset($data['selected_answer']) && $data['selected_answer'] == "true"){
					$saq = 1;
				}
				
				//157019_08062015 Tony
				$usaq = 0;
				if(isset($data['unselected_answer']) && $data['unselected_answer'] == "true"){
					$usaq = 1;
				}
				
                $inputtype = "";
                if ($data['input_type'] == 0) {
                    $inputtype = "Text";
                } else if ($data['input_type'] == 1) {
                    $inputtype = "Number";
                } else if ($data['input_type'] == 2) {
                    $inputtype = "Phone";
                } else if ($data['input_type'] == 3) {
                    $inputtype = "Date Time";
                } else if ($data['input_type'] == 4) {
                    $inputtype = "Decimal";
                }
                $elsd = 0;
                if (isset($data['enable_listing']) && $data['enable_listing'] == "true") {
                    $elsd = 1;
                }
                $txtc = 0;
                if (isset($data['text_confirm']) && $data['text_confirm'] == "true") {
                    $txtc = 1;
                }
                $eother = 0;
                if (isset($data['enable_otheropt']) && $data['enable_otheropt'] == "true") {
                    $eother = 1;
                }
                $ealt = 0;
                if (isset($data['enable_alternative']) && $data['enable_alternative'] == "true") {
                    $ealt = 1;
                }
                $enone = 0;
                if (isset($data['enable_noneabove']) && $data['enable_noneabove'] == "true") {
                    $enone = 1;
                }
                $edk = 0;
                if (isset($data['enable_dont_know']) && $data['enable_dont_know'] == "true") {
                    $edk = 1;
                }
                $rating_type = "";
                if ($data['rating_type'] == 1) {
                    $rating_type = "stars";
                } else if ($data['rating_type'] == 2) {
                    $rating_type = "hearts";
                } else if ($data['rating_type'] == 3) {
                    $rating_type = "emoticons";
                }
                $params = array(
                    "intro" => $data['intro'],
                    "question" => $data['question'],
                    "type" => $data['type'],
                    "order" => $data['order'],
                    "rating_type" => $rating_type,
                    "allow_multiple" => $am,
                    "skip_question" => $sq,
                    "show_prev_ans" => $spa,
                    "prev_ques_id" => $data['prev_ques_id'],
                    "check_prev_ans" => $cpa,
                    "check_prev_ans_id" => $data['check_prev_ans_id'],
                    "dependent" => $d,
                    "dependent_question_id" => $data['dependent_question_id'],
                    "dependent_answer_id" => $data['dependent_answer_id'],
                    "multiple_dependent" => $md,
                    // 05192016 AA: added multiple dependent logic (or/and)
                    "m_dependent_logic" => $data['m_dependent_logic'],
                    // 05022016 AA: added multiple_dependent_AND
                    // "multiple_dependent_and" => $md_and,
                    // "m_dependent_question_id_and" => $data['m_dependent_question_id_and'],
                    // "m_dependent_answers_and" => $data['m_dependent_answers_and'],
                    "back_reference" => $br,
                    "picture_caption_toggle" => $pcaption, // 02012016 AA: picture_caption_toggle
                    "picture_caption_question" => $pcquestion, // 02192016 AA: added picture_caption_question
                    "picture_caption" => basename($data['picture_caption']), // 02112016 AA: picture_caption
                    "m_dependent_question_id" => $data['m_dependent_question_id'],
                    "md_logic_handler" => $data['md_logic_handler'], // 05252016 AA: add logic handler
                    "m_dependent_answers" => $data['m_dependent_answers'],
                    "skip_multiple" => $sm,
                    "active" => 1,
                    "updated_date" => $now,
                    "show_result_SD" => $rsd,
                    "dbname_SD" => $data['dbname_SD'],
                    "dbfilter_SD" => $data['dbfilter_SD'],
                    "enable_listing" => $elsd,
                    "text_confirm" => $txtc,
                    "enable_otheropt" => $eother,
                    "enable_alternative" => $ealt,
                    "enable_noneabove" => $enone,
                    "noneabove_text" => $data['noneabove_text'],
                    "enable_dont_know" => $edk,
                    "dontknow_text" => $data['dontknow_text'],
                    "none_next_question_id" => $data['none_next_question_id'],
                    "dont_know_next_question_id" => $data['dont_know_next_question_id'],
                    "change_input" => $cit,
                    "input_type" => $inputtype,
                    "text_limit" => $data['text_limit'],
                    "slider_min" => $data['slider_min'],
                    "slider_interval" => $data['slider_interval'],
                    "slider_max" => $data['slider_max'],
					"selected_answer"				=> $saq,//157019_08062015 Tony
					"selected_answer_id"			=> $data['selected_answer_id'],
					"unselected_answer"				=> $usaq,
					"unselected_answer_id"			=> $data['unselected_answer_id']
                );
                $this->db->where('id', $data['id']);
                $this->db->set('update_count', 'update_count+1', FALSE);
                $this->db->update('survey_questions', $params);
                // log_message("debug", "check " . $data['type'] . " has answers=".(in_array($data['type'], $has_answers) && count($data['answers']) > 0));
                if (in_array($data['type'], $has_answers) && count($data['answers']) > 0) {
                    $this->update_answers($data);
                }
                if (($data['type'] == "open_question" || $data['type'] == "sliderq" || $data['type'] == "text_page") && ($data['check_prev_ans'] == "true" || $data['check_prev_ans'] == true)) {
                    $this->update_answers($data);
                }
                if ($data['type'] == "gender_age") {
                    $gender_age_params = $data['gender_age_params'];
                    $gender            = $gender_age_params['gender'];
                    $age               = $gender_age_params['age'];
                    $parent_id         = $data['id'];
                    $this->db->select("id, type");
                    $this->db->where('parent_id', $data['id']);
                    if ($query = $this->db->get("survey_questions")) {
                        $gender_age_questions = $query->result();
                        $gender_id            = 0;
                        $age_id               = 0;
                        foreach ($gender_age_questions as $g_a_q) {
                            if ($g_a_q->type == "gender") {
                                $gender_id = $g_a_q->id;
                            } else {
                                $age_id = $g_a_q->id;
                            }
                        }
                        $params = array(
                            "question" => $gender['question'],
                            "mandatory" => $gender['mandatory'],
                            "active" => $gender['ask'] == "true" ? 1 : 0,
                            "updated_date" => $now
                        );
                        $this->db->set('update_count', 'update_count+1', FALSE);
                        $this->db->where('id', $gender_id);
                        if ($this->db->update('survey_questions', $params)) {
                            $gender['id'] = $gender_id;
                            $this->update_answers($gender);
                        }
                        $params = array(
                            "question" => $age["question"],
                            "mandatory" => $age["mandatory"],
                            "active" => $age['ask'] == "true" ? 1 : 0,
                            "updated_date" => $now
                        );
                        $this->db->set('update_count', 'update_count+1', FALSE);
                        $this->db->where('id', $age_id);
                        if ($this->db->update('survey_questions', $params)) {
                            $age['id'] = $age_id;
                            $this->update_answers($age);
                        }
                    }
                }
                if ($data['type'] == "contact_page") {
                    $params = $data['contact_params'];
                    foreach ($params as $key => $value) {
                        if ($key != "extra_field_label")
                            $params[$key] = ($value == "true") ? 1 : 0;
                    }
                    $this->db->where('survey_question_id', $data['id']);
                    $this->db->update('survey_contact_questions', $params);
                }
                $this->update_survey_updated_date($id);
                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    return null;
                } else {
                    return $this->get_questions($data['id']);
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    function get_total_page($items_per_page, $survey_id) {
        $num_of_questions   = $this->get_question_number(null, $survey_id);
        $total_page         = ceil($num_of_questions / $items_per_page);
        // $total_page = $total_page === '1' ? true : false;
        // log_message("debug", "total_page=".$total_page);
        return $total_page;
    }
    function create_question($id, $data) {
        if ($survey = $this->get_basic_survey($id)) {
            $now    = date('Y-m-d H:i:s');
            $params = array(
                "survey_id" => $survey->id,
                "order" => $this->get_new_question_order($survey->id),
                "type" => $data['type'],
                "active" => 0,
                "deleted" => 0,
                "created_date" => $now
            );
            $this->db->trans_start();
            $question_id = 0;
            if ($this->db->insert('survey_questions', $params)) {
                $question_id = $this->db->insert_id();
                if ($data['type'] == "contact_page") {
                    $params = array(
                        "survey_question_id" => $question_id
                    );
                    $this->db->insert('survey_contact_questions', $params);
                } else if ($data['type'] == "gender_age") {
                    $params = array(
                        "survey_id" => $survey->id,
                        "parent_id" => $question_id,
                        "order" => 2,
                        "type" => "age",
                        "active" => 0,
                        "deleted" => 0,
                        "created_date" => $now
                    );
                    if ($this->db->insert('survey_questions', $params)) {
                        $age_question_id  = $this->db->insert_id();
                        $defaultAgeValues = array(
                            "<18",
                            "18-35",
                            "36-55",
                            ">55"
                        );
                        $order            = 1;
                        foreach ($defaultAgeValues as $answer) {
                            $params = array(
                                "question_id" => $age_question_id,
                                "order" => $order,
                                "answer" => $answer,
                                "next_question_id" => 0,
                                "deleted" => 0,
                                "created_date" => $now
                            );
                            $this->db->insert('survey_question_answers', $params);
                            $order++;
                        }
                    }
                    $params = array(
                        "survey_id" => $survey->id,
                        "parent_id" => $question_id,
                        "type" => "gender",
                        "active" => 0,
                        "order" => 1,
                        "deleted" => 0,
                        "created_date" => $now
                    );
                    if ($this->db->insert('survey_questions', $params)) {
                        $gender_question_id  = $this->db->insert_id();
                        $defaultGenderValues = array(
                            "Male",
                            "Female"
                        );
                        $order               = 1;
                        foreach ($defaultGenderValues as $answer) {
                            $params = array(
                                "question_id" => $gender_question_id,
                                "order" => $order,
                                "answer" => $answer,
                                "next_question_id" => 0,
                                "deleted" => 0,
                                "created_date" => $now
                            );
                            $this->db->insert('survey_question_answers', $params);
                            $order++;
                        }
                    }
                }
            }
            $this->update_survey_updated_date($survey->id);
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return null;
            } else {
                return $this->get_questions($question_id, null, false);
            }
        } else {
            return null;
        }
    }
    function re_order_question($id, $new_order) {
        if ($question = $this->get_basic_question($id)) {
            $old_order = $question->order;
            $now       = date('Y-m-d H:i:s');
            if ($old_order > $new_order) {
                $query = "UPDATE `survey_questions` SET `order` = 
						(CASE `order` WHEN " . $old_order . " THEN " . $new_order . " ELSE `order` + 1 END),
						`update_count` = `update_count` + 1,
						`updated_date` = '" . $now . "'
						WHERE `order` BETWEEN " . $new_order . " AND " . $old_order . " 
					AND `parent_id` IS NULL AND `survey_id` = " . $question->survey_id;
            } else {
                $query = "UPDATE `survey_questions` SET `order` = 
						(CASE `order` WHEN " . $old_order . " THEN " . $new_order . " ELSE `order` - 1 END),
						`update_count` = `update_count` + 1,
						`updated_date` = '" . $now . "'
						WHERE `order` BETWEEN " . $old_order . " AND " . $new_order . " 
					AND `parent_id` IS NULL AND `survey_id` = " . $question->survey_id;
            }
            if ($finally = $this->db->query($query)) {
                $this->update_survey_updated_date($question->survey_id);
                return true;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    function delete_question($id) {
        if ($question = $this->get_basic_question($id)) {
            $now = date('Y-m-d H:i:s');
            $this->db->trans_start();
            $data = array(
                "deleted_date" => $now,
                "deleted" => 1
            );
            $this->db->where('id', $question->id);
            $this->db->update('survey_questions', $data);
            $params = array(
                "updated_date" => $now
            );
            $this->db->set('`update_count`', '`update_count`+1', FALSE);
            $this->db->set('`order`', '`order`-1', FALSE);
            $this->db->where('order >', $question->order);
            $this->db->where('survey_id', $question->survey_id);
            $this->db->update('survey_questions', $params);
            $this->update_survey_updated_date($question->survey_id);
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return null;
            } else {
                return true;
            }
        } else {
            return null;
        }
    }
    function delete_entries($survey_id, $user_id) {
        $now  = date('Y-m-d H:i:s');
        $data = array(
            "deleted_date" => $now,
            "deleted_by" => $user_id,
            "deleted" => 1
        );
        $this->db->where('survey_id', $survey_id);
        if ($this->db->update('survey_entries', $data)) {
            return $this->update_survey_updated_date($survey_id);
        }
    }
    function delete_entry($survey_id, $entries, $user_id) {
        $now             = date('Y-m-d H:i:s');
        $unaffected_rows = array();
        if (count($entries) > 0) {
            foreach ($entries as $entry) {
                $data = array(
                    "deleted_date" => $now,
                    "deleted_by" => $user_id,
                    "deleted" => 1
                );
                $this->db->where('survey_id', $survey_id);
                $this->db->where('deleted', 0);
                $entry = trim($entry);
                if (is_numeric($entry)) {
                    $this->db->where('id', $entry);
                    if ($this->db->update('survey_entries', $data)) {
                        if ($this->db->affected_rows() != 1) {
                            $unaffected_rows[] = $entry;
                        }
                    } else {
                        $unaffected_rows[] = $entry;
                    }
                } else if ($entry != "") {
                    $unaffected_rows[] = $entry;
                }
            }
        }
        if (count($unaffected_rows) > 0) {
            return $unaffected_rows;
        } else {
            return true;
        }
    }
    function delete_survey($survey_id, $user) {
        if ($survey = $this->get_basic_survey($survey_id)) {
            $now = date('Y-m-d H:i:s');
            $this->db->trans_start();
            $data = array(
                "deleted_date" => $now,
                "deleted_by" => $user->id,
                "deleted" => 1
            );
            $this->db->where('id', $survey_id);
            $this->db->update('surveys', $data);
            $query  = $this->db->query("UPDATE `survey_question_answers` JOIN `survey_questions` ON `survey_questions`.`id` = `survey_question_answers`.`question_id` 
			SET `survey_question_answers`.`deleted_date` = '" . $now . "', `survey_question_answers`.`deleted` = 1 WHERE `survey_questions`.`survey_id` =  '" . $survey_id . "'");
            $params = array(
                "deleted_date" => $now,
                "active" => 0,
                "deleted" => 1
            );
            $this->db->where('survey_questions.survey_id', $survey_id);
            $this->db->update('survey_questions', $params);
            $params = array(
                "deleted_date" => $now,
                "deleted" => 1
            );
            $this->db->where('user_surveys.survey_id', $survey_id);
            $this->db->update('user_surveys', $params);
            $this->delete_entries($survey_id, $user->id);
            $params = array(
                "updated_date" => $now
            );
            $this->db->set('`update_count`', '`update_count`+1', FALSE);
            $this->db->set('`order`', '`order`-1', FALSE);
            $this->db->where('order >', $survey->order);
            $this->db->where('organization_id', $survey->organization_id);
            $this->db->update('surveys', $params);
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return null;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
    function get_new_order($active = 0, $organization_id) {
        $this->db->where('active', $active);
        $this->db->where('deleted', '0');
        $this->db->where('organization_id', $organization_id);
        $this->db->from('surveys');
        return $this->db->count_all_results() + 1;
    }
    function get_new_question_order($survey_id) {
        $this->db->where('active', '1');
        $this->db->where('deleted', '0');
        $this->db->where('survey_id', $survey_id);
        $this->db->where('`parent_id` IS NULL');
        $this->db->from('survey_questions');
        return $this->db->count_all_results() + 1;
    }
    function get_basic_survey($id) {
        $this->db->select('id, organization_id, order, active');
        $this->db->from('surveys');
        $this->db->where('id', $id);
        if ($survey = $this->db->get()) {
            return $survey->row();
        } else {
            return null;
        }
    }
    function get_basic_question($id) {
        $this->db->select('id, survey_id, order, active');
        $this->db->from('survey_questions');
        $this->db->where('id', $id);
        if ($survey = $this->db->get()) {
            return $survey->row();
        } else {
            return null;
        }
    }
    function get_question_number($id = null, $survey_id = null, $filter_active = true) {
        $this->db->select("*");
        $this->db->from('survey_questions');
        $this->db->where('survey_questions.`parent_id` is NULL');
        if ($id != null)
            $this->db->where('survey_questions.`id`', $id);
        if ($survey_id != null)
            $this->db->where('survey_questions.`survey_id`', $survey_id);
        if ($filter_active)
            $this->db->where('survey_questions.`active`', 1);
        $this->db->where('survey_questions.`deleted`', 0);
        $this->db->order_by('survey_questions.`order`');
        return $this->db->count_all_results();
    }
    function get_questions($id = null, $survey_id = null, $filter_active = true) {
        $this->db->select("survey_questions.`id`,
							survey_questions.`parent_id`,
							survey_questions.`order`,
							survey_questions.`intro`,
							survey_questions.`type`,
							survey_questions.`rating_type`,
							survey_questions.`allow_multiple`,
                            survey_questions.`back_reference`,
                            survey_questions.`picture_caption_toggle`,
                            survey_questions.`picture_caption_question`,
                            survey_questions.`picture_caption`,
							survey_questions.`skip_question`,
							survey_questions.`show_prev_ans`,
							survey_questions.`prev_ques_id`,
							survey_questions.`check_prev_ans`,
							survey_questions.`check_prev_ans_id`,
							survey_questions.`dependent`,
							survey_questions.`dependent_question_id`,
							survey_questions.`dependent_answer_id`,
							survey_questions.`skip_multiple`,
							
                            survey_questions.`multiple_dependent`,
                            survey_questions.`m_dependent_question_id`,
                            survey_questions.`md_logic_handler`,
							survey_questions.`m_dependent_answers`,
                            survey_questions.`m_dependent_logic`,
							survey_questions.`enable_otheropt`,
							survey_questions.`question`,
							survey_questions.`show_result_SD`,
							survey_questions.`dbname_SD`,
							survey_questions.`dbfilter_SD`,
							survey_questions.`enable_listing`,							
							survey_questions.`text_confirm`,
							survey_questions.`noneabove_text`,
							survey_questions.`enable_alternative`,
							survey_questions.`enable_noneabove`,
							survey_questions.`enable_dont_know`,
							survey_questions.`dontknow_text`,
							survey_questions.`none_next_question_id`,
							survey_questions.`dont_know_next_question_id`,
							survey_questions.`change_input`,
							survey_questions.`input_type`,
							survey_questions.`text_limit`,
							survey_questions.`slider_min`,
							survey_questions.`slider_interval`,
							survey_questions.`slider_max`,
							survey_questions.`selected_answer`,
							survey_questions.`selected_answer_id`,
							survey_questions.`unselected_answer`,
							survey_questions.`unselected_answer_id`
							
							");
                            // 02012016 AA: picture_capture_toggle
                            // 02112016 AA: picture_caption
                            // 02192016 AA: added picture_caption_question
							// 03282016Tony: selected > unselected
                            // 05192016 AA: removed
                            // survey_questions.`multiple_dependent_and`,
                            // survey_questions.`m_dependent_question_id_and`,
                            // survey_questions.`m_dependent_answers_and`,
                            // 05192016 AA: added multiple dependent logic (or/and)
                            // survey_questions.`m_dependent_logic`,

        $this->db->from('survey_questions');
        $this->db->where('survey_questions.`parent_id` is NULL');
        if ($id != null)
            $this->db->where('survey_questions.`id`', $id);
        if ($survey_id != null)
            $this->db->where('survey_questions.`survey_id`', $survey_id);
        if ($filter_active)
            $this->db->where('survey_questions.`active`', 1);
        $this->db->where('survey_questions.`deleted`', 0);
        $this->db->order_by('survey_questions.`order`');
        if ($query = $this->db->get()) {
            $questions = $query->result();
            foreach ($questions as &$q) {
                $has_answers = array(
                    "multiple_choice_labels",
                    "multiple_choice_list",
                    "prioritise",
                    "text_page",
                    "rating",
                    "gps",
                    "pic_capture",
                    "sliderq"
                );
                if (in_array($q->type, $has_answers)) {
                    $q->answers = $this->get_answers($q->id);
                    if (($q->type == "multiple_choice_list" || $q->type == "multiple_choice_labels" || $q->type == "text_page") && $q->check_prev_ans != 0) {
                        $q->extras = $this->get_extras($q->id);
                    }
                    if (($q->type == "multiple_choice_list" || $q->type == "multiple_choice_labels") && $q->skip_multiple != 0) {
                        $q->conditions = $this->get_conditions($q->id);
                    }
                } else if ($q->type == "contact_page") {
                    $this->db->select("*");
                    $this->db->from('survey_contact_questions');
                    $this->db->where('survey_contact_questions.`survey_question_id`', $q->id);
                    $query = $this->db->get();
                    if ($query->num_rows() > 0) {
                        $p                          = $query->row();
                        $p->ask_name                = $p->ask_name === '1' ? true : false;
                        $p->ask_email               = $p->ask_email === '1' ? true : false;
                        $p->ask_phone               = $p->ask_phone === '1' ? true : false;
                        $p->ask_address_street      = $p->ask_address_street === '1' ? true : false;
                        $p->ask_address_postal_code = $p->ask_address_postal_code === '1' ? true : false;
                        $p->ask_address_city        = $p->ask_address_city === '1' ? true : false;
                        $p->ask_address_state       = $p->ask_address_state === '1' ? true : false;
                        $p->ask_address_country     = $p->ask_address_country === '1' ? true : false;
                        $p->ask_extra_field         = $p->ask_extra_field === '1' ? true : false;
                        $q                          = (object) array_merge((array) $q, (array) $p);
                    }
                } else if ($q->type == "gender_age") {
                    $this->db->select("survey_questions.`id`,
							survey_questions.`question`,
							survey_questions.`type`,
							survey_questions.`active`,
							survey_questions.`mandatory`
							");
                    $this->db->from('survey_questions');
                    $this->db->where('survey_questions.`parent_id`', $q->id);
                    if ($query = $this->db->get()) {
                        $sub_questions = $query->result();
                        foreach ($sub_questions as $sub_q) {
                            if ($sub_q->type == "age") {
                                $q->age_question  = $sub_q->question;
                                $q->ask_age       = $sub_q->active == 1 ? true : false;
                                $q->mandatory_age = $sub_q->mandatory == 1 ? true : false;
                                $q->age_answers   = $this->get_answers($sub_q->id);
                            } else if ($sub_q->type == "gender") {
                                $q->gender_question  = $sub_q->question;
                                $q->ask_gender       = $sub_q->active == 1 ? true : false;
                                $q->mandatory_gender = $sub_q->mandatory == 1 ? true : false;
                                $q->gender_answers   = $this->get_answers($sub_q->id);
                            }
                        }
                    }
                }
                if ($q->type != "multiple_choice_labels" && $q->type != "multiple_choice_list") {
                    unset($q->allow_multiple);
                } else {
                    $q->allow_multiple = $q->allow_multiple === '1' ? true : false;
                }
                if (($q->type == "open_question" || $q->type == "sliderq" || $q->type == "text_page") && $q->check_prev_ans != 0) {
                    $q->extras = $this->get_extras($q->id);
                }
                if ($q->type != "multiple_choice_labels" && $q->type != "multiple_choice_list" && $q->type != "sliderq") {
                    unset($q->show_prev_ans);
                } else {
                    $q->show_prev_ans = $q->show_prev_ans === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_labels" && $q->type != "multiple_choice_list" && $q->type != "open_question" && $q->type != "sliderq" && $q->type != "text_page") {
                    unset($q->check_prev_ans);
                } else {
                    $q->check_prev_ans = $q->check_prev_ans === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_labels" && $q->type != "multiple_choice_list" && $q->type != "open_question" && $q->type != "sliderq") {
                    unset($q->dependent);
                } else {
                    $q->dependent = $q->dependent === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
                    unset($q->skip_multiple);
                } else {
                    $q->skip_multiple = $q->skip_multiple === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_labels") {
                    unset($q->show_result_SD);
                } else {
                    $q->show_result_SD = $q->show_result_SD === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->enable_listing);
                } else {
                    $q->enable_listing = $q->enable_listing === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->text_confirm);
                } else {
                    $q->text_confirm = $q->text_confirm === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_list") {
                    unset($q->enable_otheropt);
                } else {
                    $q->enable_otheropt = $q->enable_otheropt === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->enable_alternative);
                } else {
                    $q->enable_alternative = $q->enable_alternative === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_list") {
                    unset($q->enable_noneabove);
                } else {
                    $q->enable_noneabove = $q->enable_noneabove === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->enable_dont_know);
                } else {
                    $q->enable_dont_know = $q->enable_dont_know === '1' ? true : false;
                }
                if ($q->type != "open_question") {
                    unset($q->change_input);
                } else {
                    $q->change_input = $q->change_input === '1' ? true : false;
                }
                if ($q->type != "open_question" && $q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
                    unset($q->multiple_dependent);
                } else {
                    $q->multiple_dependent = $q->multiple_dependent === '1' ? true : false;
                }
                // if ($q->type != "open_question" && $q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
                //     unset($q->multiple_dependent_and);
                // } else {
                //     $q->multiple_dependent_and = $q->multiple_dependent_and === '1' ? true : false;
                // }
                if ($q->type != "open_question") {
                    unset($q->input_type);
                }
                if ($q->type != "rating") {
                    unset($q->rating_type);
                }
                if ($q->type != "open_question") {
                    unset($q->back_reference);
                } else {
                    $q->back_reference = $q->back_reference === '1' ? true : false;
                }
                if ($q->type != "open_question" && $q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
                    unset($q->picture_caption_toggle); // 02012016 AA: picture_caption_toggle
                } else {
                    $q->picture_caption_toggle = $q->picture_caption_toggle === '1' ? true : false;
                }
                if ($q->type != "multiple_choice_list") {
                    unset($q->picture_caption_question);
                } else {
                    $q->picture_caption_question = $q->picture_caption_question === '1' ? true : false; // 02192016 AA: added picture_caption_question
                }
				
				//157019_08062015 Tony
				if($q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
					unset($q->selected_answer);
				} else {
					$q->selected_answer	=	$q->selected_answer  === '1' ? true: false;
				}
				
				//157019_08062015 Tony
				if($q->type != "multiple_choice_list" && $q->type != "multiple_choice_labels") {
					unset($q->unselected_answer);
				} else {
					$q->unselected_answer	=	$q->unselected_answer  === '1' ? true: false;
				}

                unset($q->parent_id);
                $no_skip_question = array(
                    "contact_page",
                    "open_question",
                    "gps",
                    "pic_capture",
                    "sliderq"
                );
                // log_message("debug", "questions=".$q->type);
                if (in_array($q->type, $no_skip_question)) {
                    unset($q->skip_question);
                } else {
                    $q->skip_question = $q->skip_question === '1' ? true : false;
                }
            }
            if ($survey_id == null && count($questions) == 1) {
                return $questions[0];
            } else {
                return $questions;
            }
        } else {
            return null;
        }
    }
    function get_answers($question_id) {
        $this->db->select("
						survey_question_answers.`id`,
						survey_question_answers.`order`,
						survey_question_answers.`answer`,
						survey_question_answers.`next_question_id`,
						survey_question_answers.`extra`,
						survey_question_answers.`extra_id`,
                        survey_question_answers.`picture_caption`,
						survey_question_answers.`extra_next_question_id`
				"); // 02032016 AA: picture_caption
        $this->db->from('survey_question_answers');
        $this->db->where('survey_question_answers.`question_id`', $question_id);
        $this->db->where('survey_question_answers.`deleted`', 0);
        $this->db->where('survey_question_answers.`extra`', 0);
        $this->db->where('survey_question_answers.`m_skip_condition`', 0);
        $this->db->order_by('survey_question_answers.`order`');
        $query   = $this->db->get();
        $answers = $query->result();
        return $answers;
    }
    function get_extras($question_id) {
        $this->db->select("
						survey_question_answers.`id`,
						survey_question_answers.`order`,
						survey_question_answers.`answer`,
						survey_question_answers.`next_question_id`,
						survey_question_answers.`extra`,
						survey_question_answers.`extra_id`,
                        survey_question_answers.`picture_caption`,
						survey_question_answers.`extra_next_question_id`
					"); // 02032016 AA: picture_caption
        $this->db->from('survey_question_answers');
        $this->db->where('survey_question_answers.`question_id`', $question_id);
        $this->db->where('survey_question_answers.`deleted`', 0);
        $this->db->where('survey_question_answers.`extra`', 1);
        $this->db->where('survey_question_answers.`m_skip_condition`', 0);
        $this->db->order_by('survey_question_answers.`order`');
        $query   = $this->db->get();
        $answers = $query->result();
        return $answers;
    }
    function get_conditions($question_id) {
        $this->db->select("
						survey_question_answers.`id`,
						survey_question_answers.`order`,
						survey_question_answers.`answer`,
						survey_question_answers.`next_question_id`,
						survey_question_answers.`extra`,
						survey_question_answers.`extra_id`,
						survey_question_answers.`extra_next_question_id`, 
						survey_question_answers.`m_skip_ids`,
                        survey_question_answers.`m_skip_condition`,
                        survey_question_answers.`picture_caption`,
						survey_question_answers.`m_skip_next_question`
					"); // 02032016 AA: picture_caption
        $this->db->from('survey_question_answers');
        $this->db->where('survey_question_answers.`question_id`', $question_id);
        $this->db->where('survey_question_answers.`deleted`', 0);
        $this->db->where('survey_question_answers.`extra`', 0);
        $this->db->where('survey_question_answers.`m_skip_condition` !=', 0);
        $this->db->order_by('survey_question_answers.`order`');
        $query   = $this->db->get();
        $answers = $query->result();
        return $answers;
    }
    function update_answers($data) {
        $now    = date('Y-m-d H:i:s');
        $params = array(
            "deleted" => 1,
            "deleted_date" => $now
        );
        $this->db->where("question_id", $data['id']);
        $this->db->update('survey_question_answers', $params);
        $order = 1;
        foreach ($data['answers'] as $answer) {
            if ($answer["answer_id"] == 0) {
                $params = array(
                    "question_id" => $data['id'],
                    "deleted" => 0,
                    "deleted_date" => "",
                    "created_date" => $now
                );
                if (isset($answer["answer_next_question_id"])) {
                    $params["next_question_id"] = $answer["answer_next_question_id"];
                }
                if (isset($answer["answer_value"])) {
                    $params["answer"] = $answer["answer_value"];
                }
                // log_message("debug", "picture_caption=".$answer["picture_caption"]); // 03162016 AA: fixed log_message bug on picture_caption
                if (isset($answer["picture_caption"])) { // 02042016 AA: picture_caption // 160301_2.07_0011 03032016 AA: delete picture_caption
                    $params["picture_caption"] = basename($answer["picture_caption"]);
                }
                if (isset($answer["answer_extra"])) {
                    $params["extra"] = $answer["answer_extra"];
                    $params["order"] = 0;
                } else {
                    $params["order"] = $order;
                }
                if (isset($answer["answer_extra_id"])) {
                    $params["extra_id"] = $answer["answer_extra_id"];
                }
                if (isset($answer["answer_extra_next_question_id"])) {
                    $params["extra_next_question_id"] = $answer["answer_extra_next_question_id"];
                }
                if (isset($answer["answer_m_skip_ids"])) {
                    $params["m_skip_ids"] = $answer["answer_m_skip_ids"];
                }
                if (isset($answer["answer_m_skip_condition"])) {
                    $params["m_skip_condition"] = $answer["answer_m_skip_condition"];
                    $params["order"]            = 0;
                }
                if (isset($answer["answer_m_skip_next_question"])) {
                    $params["m_skip_next_question"] = $answer["answer_m_skip_next_question"];
                }
                $this->db->insert('survey_question_answers', $params);
            } else {
                $params = array(
                    "question_id" => $data['id'],
                    "order" => $order,
                    "deleted" => 0,
                    "deleted_date" => "",
                    "updated_date" => $now
                );
                $this->db->set('`update_count`', '`update_count`+1', FALSE);
                if (isset($answer["answer_next_question_id"])) {
                    $params["next_question_id"] = $answer["answer_next_question_id"];
                }
                if (isset($answer["answer_value"])) {
                    $params["answer"] = $answer["answer_value"];
                }
                if (isset($answer["picture_caption"])) { // 02042016 AA: picture_caption // 160301_2.07_0011 03032016 AA: delete picture_caption
                    $params["picture_caption"] = basename($answer["picture_caption"]);
                }
                if (isset($answer["answer_extra"])) {
                    $params["extra"] = $answer["answer_extra"];
                    $params["order"] = 0;
                } else {
                    $params["order"] = $order;
                }
                if (isset($answer["answer_extra_id"])) {
                    $params["extra_id"] = $answer["answer_extra_id"];
                }
                if (isset($answer["answer_extra_next_question_id"])) {
                    $params["extra_next_question_id"] = $answer["answer_extra_next_question_id"];
                }
                if (isset($answer["answer_m_skip_ids"])) {
                    $params["m_skip_ids"] = $answer["answer_m_skip_ids"];
                }
                if (isset($answer["answer_m_skip_condition"])) {
                    $params["m_skip_condition"] = $answer["answer_m_skip_condition"];
                    $params["order"]            = 0;
                }
                if (isset($answer["answer_m_skip_next_question"])) {
                    $params["m_skip_next_question"] = $answer["answer_m_skip_next_question"];
                }
                $this->db->where("id", $answer['answer_id']);
                $this->db->update('survey_question_answers', $params);
            }
            $order++;
        }
    }
    function update_survey_updated_date($survey_id) {
        $now                  = date('Y-m-d H:i:s');
        $data["updated_date"] = $now;
        $this->db->set('`update_count`', '`update_count`+1', FALSE);
        $this->db->where("id", $survey_id);
        return $this->db->update('surveys', $data);
    }
    function get_survey_versions($survey_id) {
        $query   = $this->db->query("
				SELECT * FROM (

					SELECT `version`, id, in_live FROM surveys WHERE id = $survey_id AND deleted = 0
					
					UNION 
					
					SELECT `version`, id, in_live FROM surveys WHERE parent_id = $survey_id AND deleted = 0
					
					UNION 
					
					SELECT `version`, id, in_live FROM surveys WHERE id = (SELECT parent_id FROM surveys WHERE id = $survey_id)  AND deleted = 0
					
					UNION 
					
					SELECT `version`, id, in_live FROM surveys WHERE parent_id = (SELECT parent_id FROM surveys WHERE id = $survey_id)  AND deleted = 0
				) a ORDER BY `version` ASC;
				");
        $results = $query->result();
        return $results;
    }
    function publish($survey_id, $user) {
        $this->db->from('surveys');
        $this->db->where('surveys.`id`', $survey_id);
        $this->db->limit(1);
        $query          = $this->db->get();
        $current_survey = $query->row();
        if ($current_survey->in_live == 1) {
            return true;
        }
        $id             = $current_survey->parent_id != null ? $current_survey->parent_id : $survey_id;
        $query          = $this->db->query("SELECT * FROM surveys WHERE in_live = 1 AND (id = " . $id . " OR parent_id = " . $id . ") LIMIT 1 ");
        $in_live_survey = $query->row();
        $this->db->from('user_surveys');
        $this->db->where('user_surveys.`survey_id`', $in_live_survey->id);
        $this->db->where('user_surveys.`deleted`', 0);
        $query                        = $this->db->get();
        $in_live_survey_users         = $query->result();
        $in_live_survey->survey_users = $in_live_survey_users;
        $this->db->trans_start();
        $this->db->where('id', $survey_id);
        $this->db->update('surveys', array(
            "in_live" => 1,
            "title" => $in_live_survey->title,
            "code" => $in_live_survey->code, // 215110011_12142015 AA change code on publish
            "order" => $in_live_survey->order
        ));
        $this->db->where('id', $in_live_survey->id);
        $this->db->update('surveys', array(
            "in_live" => 0,
            "title" => $in_live_survey->title . " (v" . $in_live_survey->version . ")",
            "code" => $in_live_survey->code . " (v" . $in_live_survey->version . ")" // 215110011_12142015 AA change code on publish
        ));
        $user_ids = array();
        foreach ($in_live_survey_users as $survey_user) {
            array_push($user_ids, array(
                "id" => $survey_user->user_id
            ));
        }
        $this->update_user_access($survey_id, $user, json_encode($user_ids));
        $now    = date('Y-m-d H:i:s');
        $params = array(
            "deleted" => 1,
            "deleted_date" => $now
        );
        $this->db->where("survey_id", $in_live_survey->id);
        $this->db->update('user_surveys', $params);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return false;
        } else {
            return true;
        }
    }

    function log_this($log_e) {
        date_default_timezone_set("Asia/Hong_Kong");
        $log  = date("Y-m-d")." ".date("l")." (".date("h:i:sa").") : ".$log_e.PHP_EOL;
        file_put_contents('./application/logs/custom/log-'.date("Y-m-d").'.php', $log, FILE_APPEND);
    }
}
?>