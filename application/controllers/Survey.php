<?php
defined('BASEPATH') OR exit('No direct script access allowed');
@set_time_limit(300); // AA:10212015
class Survey extends MY_Controller {
	var $items_per_page = 50;
	var $page_no = 0;

	function __construct() {
		parent::__construct();
	}
	function index() {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('survey_model', 'survey');
				$this->load->model('user_model', 'user');
				// echo json_encode($this->survey->surveyor_listAll($user_id)+$this->user->get_user_orgs());

				if ($survey = $this->survey->surveyor_listAll($user_id)) {
				    $output['survey'] = $survey;
				    if ($orgs = $this->user->get_user_orgs()) {
				    	$output['orgs'] = $orgs;
				    }
				}

		        $all_status = $this->survey->get_status();
		        if ($all_status['status'] == true) {
		            $output['result'] = $all_status;
		        }
		        if (isset($deleted_status) && $deleted_status['status'] == true) {
		            $output['result'] = $deleted_status;
		        } else {
		            $output['result'] = $all_status;
		        }
		        
		        $output['response_time'] = date('Y-m-d H:i:s');
		        echo json_encode($output);

			}
		}
	}
	function get_questions($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				if (isset($survey_id)) {
					// $items_per_page = $this->items_per_page;
					$this->load->model('survey_model', 'survey');
					if ($questions = $this->survey->get_questions(null, $survey_id)) {
						echo json_encode(array(
							"questions" => $questions,
							"status_message" => "Survey has been fetched",
							"status" => true,
							"status_code" => 2
						));
					} else {
						echo json_encode(array(
							"status_message" => "Survey doesn't exists",
							"status" => false,
							"status_code" => -2
						));
					}
				} else {
					echo json_encode(array(
						"status_message" => "Survey doesn't exists",
						"status" => false,
						"status_code" => -2
					));
				}
			}
		}
	}
	public function upload_picture() { // 02102016 AA: picture_caption

		$config['upload_path'] = './data/picture_caption/';
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		$config['overwrite'] = TRUE;

		$this->load->library('upload', $config);
		$this->upload->initialize($config);

		if ($this->check_authorization()) {
			if ($this->ion_auth->get_user_id()) {
				// log_message("debug", $this->upload->do_upload('file'));
				if ($this->upload->do_upload('file')) { // 03012016 AA: changed upload image from native PHP to codeigniter
					$data = $this->upload->data('file');
					// print_r($data);
					// log_message("debug", $data["file_name"]);
					echo json_encode(array(
						"success" => true,
						"image_name" => $data["file_name"]
					));
				} else {
					echo json_encode(array(
						"success" => false,
						"error_message" => $this->upload->display_errors()
					));
				}
			}
		}
	}
	function compress_image($source_url, $destination_url, $quality) { // 02162016 AA: compress image quality for smooth upload
	   	// log_message("debug", "compress_image");
	    $info = getimagesize($source_url);
	    // log_message("debug", "mime=".$info['0']);
	    if ($info['mime'] == 'image/jpeg')
	        $image = imagecreatefromjpeg($source_url);
	    elseif ($info['mime'] == 'image/gif')
	        $image = imagecreatefromgif($source_url);
	    elseif ($info['mime'] == 'image/png')
	        $image = imagecreatefrompng($source_url);
	    if ($image) {
	    	// log_message("debug", (filesize($source_url)/1024));
	    	if ((filesize($source_url)/1024) >= 200) {
	    		imagejpeg($image, $destination_url, $quality);
	    	} else {
	    		imagejpeg($image, $destination_url);
	    	}
	    	return true;
	    } else {
	    	return false;
	    }
	    // return $destination_url;
	}
	function count_survey_questions() {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('survey_model', 'survey');
				if ($count = $this->survey->count_survey_questions()) {
					echo json_encode(array(
						"count" => $count,
						"success" => true
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function reorderQuestion($survey_id) { // 04062016 AA: added updateQuestionOrder function to update order when loading survey
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				if ($this->input->post('questionNumber') && $this->input->post('order') && $this->input->post('questionId')) {
					$questionNumber   = $this->input->post('questionNumber');
					$order   = $this->input->post('order');
					$questionId   = $this->input->post('questionId');
					$this->load->model('survey_model', 'survey');
					if ($answer = $this->survey->reorderQuestion($questionNumber, $order, $questionId, $survey_id)) { // 04062016 AA: added updateQuestionOrder function to update order when loading survey
						echo json_encode(array(
							"answer" => $answer,
							"success" => true
						));
					} else {
						echo json_encode(array(
							"success" => false,
							"error" => "unable to reorder question"
						));
					}
				} else {
					echo json_encode(array(
						"success" => false,
						"error" => "reorderQuestion: parameters not passed correctly"
					));
				}
			}
		}
	}
	function get_question() { // AA:10222015
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				if ($this->input->post('id') && $this->input->post('s_id')) {
					$question_id   = $this->input->post('id');
					$survey_id   = $this->input->post('s_id');
					$this->load->model('survey_model', 'survey');
					if ($question = $this->survey->get_question($question_id, $survey_id)) {
						echo json_encode(array(
							"question" => $question,
							"success" => true
						));
					} else {
						echo json_encode(array(
							"success" => false
						));
					}
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function create() {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->library('form_validation');
				$title = $this->input->post("title", true);
				$org_id = $this->input->post("org_id", true);
				if (isset($title) && trim($title) != "") {
					$this->load->model('user_model', 'user');
					$user = $this->user->get($user_id);
					$this->load->model('survey_model', 'survey');

					$this->form_validation->set_rules('title', 'Title', 'is_unique_title[surveys.title]'); // 215110015_12102015 AA: added unique validation

					if ($this->form_validation->run() == FALSE) { // 215110015_12102015 AA: added unique validation
						echo json_encode(array(
							"success" => false,
							"error" => '"'.$title.'" is already being used.'
						));
					} else {
						// log_message("debug", "title=".$title." org_id=".$org_id);
						if ($id = $this->survey->create($title, $org_id, $user)) {
							echo json_encode(array(
								"survey_id" => $id,
								"success" => true
							));
						} else {
							echo json_encode(array(
								"success" => false
							));
						}
					}
				}
			}
		}
	}
	function duplicate($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('user_model', 'user');
				$user = $this->user->get($user_id);
				$this->load->model('survey_model', 'survey');
				if ($id = $this->survey->duplicate($survey_id, $user)) {
					echo json_encode(array(
						"survey_id" => $id,
						"success" => true
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function re_order_survey($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('user_model', 'user');
				$user      = $this->user->get($user_id);
				$new_order = $this->input->post("passing_survey_id", true);
				$this->load->model('survey_model', 'survey');
				if ($id = $this->survey->re_order_survey($survey_id, $user, $new_order)) {
					echo json_encode(array(
						"success" => true
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function activate($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('user_model', 'user');
				$user     = $this->user->get($user_id);
				$activate = $this->input->post("activate", true);
				$this->load->model('survey_model', 'survey');
				if ($date = $this->survey->activate($survey_id, $activate, $user)) {
					echo json_encode(array(
						"success" => true,
						"activated_date" => $date
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}

    // 215110014_11192015: AA added backcheck functionalities
	function activateBackCheck($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('user_model', 'user');
				$user     = $this->user->get($user_id);
				$activate = $this->input->post("activate", true);
				$this->load->model('survey_model', 'survey');
				if ($date = $this->survey->activateBackCheck($survey_id, $activate, $user)) {
					echo json_encode(array(
						"success" => true,
						"activated_date" => $date
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function update_image($image_field) {
		if ($this->check_authorization()) {
			if ($_POST) {
				if ($_FILES['survey_' . $image_field]) {
					$survey_id    = $this->input->post("survey_id", true);
					$org_folder   = "./inc/images/" . $survey_id;
					$image_folder = "./inc/images/" . $survey_id . "/" . $image_field . "/";
					if (is_dir($org_folder)) {
						if (is_dir($image_folder)) {
						} else {
							if (mkdir($image_folder)) {
							} else {
								echo json_encode(array(
									"success" => false,
									"message" => "Could not create directory"
								));
							}
						}
					} else {
						if (mkdir($org_folder)) {
							if (mkdir($image_folder)) {
							} else {
								echo json_encode(array(
									"success" => false,
									"message" => "Could not create directory"
								));
							}
						} else {
							echo json_encode(array(
								"success" => false,
								"message" => "Could not create directory"
							));
						}
					}
					$this->load->library('upload');
					$file_format = @end(explode(".", $_FILES['survey_' . $image_field]['name']));
					$image_name  = $image_field . "_" . $this->_rand_string() . "." . $file_format;
					$this->_initialize($image_folder, $image_name);
					if (!$this->upload->do_upload('survey_' . $image_field)) {
						$this->upload->display_errors();
						echo json_encode(array(
							"success" => false,
							"error_mesage" => "Logo could not be saved."
						));
						exit();
					}
					$data = array(
						$image_field => $image_name
					);
					$this->load->model('survey_model', 'survey');
					if ($update = $this->survey->update($survey_id, $data)) {
						echo json_encode(array(
							"success" => true,
							"new_html" => '<img src="' . $image_folder . $image_name . '" alt="Survey ' . strtoupper($image_field) . '" class="surveyimage" id="' . $image_field . '_holder">'
						));
					} else {
						echo json_encode(array(
							"success" => false,
							"error_mesage" => "Logo could not be saved."
						));
					}
				} else {
					echo json_encode(array(
						"success" => false,
						"error_mesage" => "Logo could not be saved."
					));
				}
			} else {
				echo json_encode(array(
					"success" => false,
					"error_mesage" => "Logo could not be saved."
				));
			}
		} else {
			echo json_encode(array(
				"success" => false,
				"error_mesage" => "Logo could not be saved."
			));
		}
	}
    // 215110014_11192015: AA added backcheck functionalities
	function update_general_settings($survey_id) { // AA:11182015
		if ($this->check_authorization()) {
			$this->load->library('form_validation');
			$title      		= $this->input->post("title", true);
			$language   		= $this->input->post("language", true);
			$backcheckfile 		= $this->input->post("backcheckfile", true);
			$backcheckreference = $this->input->post("backcheckref", true); // 12092015 AA: added backcheck reference
			$code       		= $this->input->post("code", true);
			$date_range 		= $this->input->post("date_range", true);
			$date_from  		= $this->input->post("date_from", true);
			$survey_image  		= $this->input->post("survey_image", true);
			// $version    = $this->input->post("version", true);

			$this->form_validation->set_rules('title', 'Title', 'edit_unique_title[surveys.title.'.$survey_id.']'); // 215110015_12102015 AA: added edit_unique_title validation
			$this->form_validation->set_rules('code', 'Code', 'edit_unique_code[surveys.code.'.$survey_id.']'); // 215110011_12142015 AA: added uniqueness in code
			
			$this->form_validation->set_message('edit_unique_title', '"'.$title.'" is already being used.'); // 215110011_12142015 AA: separate error messages for title and code

			if ($code == "" || $code == "N/A") {
				$this->form_validation->set_message('edit_unique_code', 'Please create a unique code for this project'); // 215110011_12142015 AA: separate error messages for title and code
			} else {
				$this->form_validation->set_message('edit_unique_code', '"'.$code.'" is already being used.'); // 215110011_12142015 AA: separate error messages for title and code
			}

			if ($this->form_validation->run() == FALSE) { // 215110015_12102015 AA: added unique validation
				echo json_encode(array(
					"success" => false,
					"error_title" => form_error('title'), // 215110011_12142015 AA: separate error messages for title and code
					"error_code" => form_error('code') // 215110011_12142015 AA: separate error messages for title and code
				));
			} else {
				$data       = array(
					"title" => $title,
					"language" => $language,
					"backcheckfile" => $backcheckfile,
					"backcheckreference" => $backcheckreference,
					"code" => $code,
					"date_range" => ($date_range == "true" ? 1 : 0),
					"date_from" => $date_from,
					"survey_image" => $survey_image
					//,"version" => $version
				);

				// log_message("debug", "backcheckref=".$backcheckref);
				$this->load->model('survey_model', 'survey');
				if ($update = $this->survey->update($survey_id, $data)) {
					echo json_encode(array(
						"success" => true
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}

		}
	}

	public function upload_surveyImage() {

		$config['upload_path'] = './data/survey_image/';
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		$config['overwrite'] = TRUE;

		$this->load->library('upload', $config);
		$this->upload->initialize($config);
		
		if ($this->check_authorization()) {
			if ($this->ion_auth->get_user_id()) {
				if ($this->upload->do_upload('file')) {
					$data = $this->upload->data('file');
					echo json_encode(array(
						"success" => true,
						"image_name" => $data["file_name"]
					));
				} else {
					echo json_encode(array(
						"success" => false,
						"error_message" => $this->upload->display_errors()
					));
				}
			}
		}
	}
	function get_user_accounts($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('user_model', 'user');
				$user = $this->user->get($user_id);
				$this->load->model('survey_model', 'survey');
				if ($accounts = $this->survey->get_user_accounts($survey_id, $user)) {
					echo json_encode($accounts);
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function update_user_access($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$users = $this->input->post("users");
				$this->load->model('user_model', 'user');
				$user = $this->user->get($user_id);
				$this->load->model('survey_model', 'survey');
				$count = $this->survey->update_user_access($survey_id, $user, $users);
				if ($count === 0 || $count) {
					echo json_encode(array(
						"success" => true,
						"accountsLinked" => $count
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function create_question($survey_id) {
		if ($this->check_authorization()) {
			$type  = $this->input->post("type", true);
			$order = $this->input->post("order", true);
			$data  = array(
				"type" => $type,
				"order" => $order
			);
			$this->load->model('survey_model', 'survey');
			if ($question = $this->survey->create_question($survey_id, $data)) {
				echo json_encode(array(
					"question" => $question,
					"success" => true
				));
			} else {
				echo json_encode(array(
					"success" => false
				));
			}
		}
	}
	function generate_question($survey_id) { // 03092016 AA: integrating generation of questions
		if ($this->check_authorization()) {
			$type                       = $this->input->post("type", true);
			$intro                      = $this->input->post("intro", true);
			$question                   = $this->input->post("question", true);
			$order 						= $this->input->post("order", true);
			$id                         = $this->input->post("id", true);
			$allow_multiple             = $this->input->post("allow_multiple", true);
			$skip_question              = $this->input->post("skip_question", true);
			$show_prev_ans              = $this->input->post("show_prev_ans", true);
			$prev_ques_id               = $this->input->post("prev_ques_id", true);
			$check_prev_ans             = $this->input->post("check_prev_ans", true);
			$check_prev_ans_id          = $this->input->post("check_prev_ans_id", true);
			$dependent                  = $this->input->post("dependent", true);
			$dependent_question_id      = $this->input->post("dependent_question_id", true);
			$dependent_answer_id        = $this->input->post("dependent_answer_id", true);
			$multiple_dependent         = $this->input->post("multiple_dependent", true);
			$back_reference         	= $this->input->post("back_reference", true);
			$picture_caption_toggle     = $this->input->post("picture_caption_toggle", true); // 02012016 AA: picture_caption_toggle
			$picture_caption_question   = $this->input->post("picture_caption_question", true); // 02192016 AA: added picture_caption_question
			$picture_caption     		= $this->input->post("picture_caption", true); // 02112016 AA: picture_caption
			$m_dependent_question_id    = $this->input->post("m_dependent_question_id", true);
			$md_logic_handler    		= $this->input->post("md_logic_handler", true); // 05252016 AA: add logic handler
			$m_dependent_answers        = $this->input->post("m_dependent_answers", true);
			$skip_multiple              = $this->input->post("skip_multiple", true);
			$rating_type                = $this->input->post("rating_type", true);
			$rating_amount              = $this->input->post("rating_amount", true);
			$show_result_SD             = $this->input->post("show_result_SD", true);
			$dbname_SD                  = $this->input->post("dbname_SD", true);
			$dbfilter_SD                = $this->input->post("dbfilter_SD", true);
			$enable_listing             = $this->input->post("enable_listing", true);
			$text_confirm               = $this->input->post("text_confirm", true);
			$enable_otheropt            = $this->input->post("enable_otheropt", true);
			$enable_alternative         = $this->input->post("enable_alternative", true);
			$enable_noneabove           = $this->input->post("enable_noneabove", true);
			$noneabove_text             = $this->input->post("noneabove_text", true);
			$enable_dont_know           = $this->input->post("enable_dont_know", true);
			$dontknow_text              = $this->input->post("dontknow_text", true);
			$none_next_question_id      = $this->input->post("none_next_question_id", true);
			$dont_know_next_question_id = $this->input->post("dont_know_next_question_id", true);
			$change_input               = $this->input->post("change_input", true);
			$input_type                 = $this->input->post("input_type", true);
			$text_limit                 = $this->input->post("text_limit", true);
			$slider_min                 = $this->input->post("slider_min", true);
			$slider_interval            = $this->input->post("slider_interval", true);
			$slider_max                 = $this->input->post("slider_max", true);
			//157019_08062015 Tony
			$selected_answer			= $this->input->post("selected_answer", true);
			$selected_answer_id			= $this->input->post("selected_answer_id", true);
			$unselected_answer			= $this->input->post("unselected_answer", true);
			$unselected_answer_id		= $this->input->post("unselected_answer_id", true);
			// 05192016 AA: added multiple dependent logic (or/and)
			$m_dependent_logic     		= $this->input->post("m_dependent_logic", true);
			// 05022016 AA: added multiple_dependent_AND
			// $multiple_dependent_and      = $this->input->post("multiple_dependent_and", true);
			// $m_dependent_question_id_and = $this->input->post("m_dependent_question_id_and", true);
			// $m_dependent_answers_and     = $this->input->post("m_dependent_answers_and", true);
			$contact_params             = array();
			if ($type == "contact_page") {
				$ask_name                = $this->input->post("ask_name", true);
				$ask_email               = $this->input->post("ask_email", true);
				$ask_phone               = $this->input->post("ask_phone", true);
				$ask_address_street      = $this->input->post("ask_address_street", true);
				$ask_address_postal_code = $this->input->post("ask_address_postal_code", true);
				$ask_address_city        = $this->input->post("ask_address_city", true);
				$ask_address_state       = $this->input->post("ask_address_state", true);
				$ask_address_country     = $this->input->post("ask_address_country", true);
				$ask_extra_field         = $this->input->post("ask_extra_field", true);
				$extra_field_label       = $this->input->post("extra_field_label", true);
				$contact_params          = array(
					"ask_name" => $ask_name,
					"ask_email" => $ask_email,
					"ask_phone" => $ask_phone,
					"ask_address_street" => $ask_address_street,
					"ask_address_postal_code" => $ask_address_postal_code,
					"ask_address_city" => $ask_address_city,
					"ask_address_state" => $ask_address_state,
					"ask_address_country" => $ask_address_country,
					"ask_extra_field" => $ask_extra_field,
					"extra_field_label" => $extra_field_label
				);
			}
			$gender_age_params = array();
			if ($type == "gender_age") {
				$age    = $this->input->post("age", true);
				$gender = $this->input->post("gender", true);
				if (isset($age)) {
					$age = json_decode($age, true);
				}
				if (isset($gender)) {
					$gender = json_decode($gender, true);
				}
				$gender_age_params = array(
					"age" => $age,
					"gender" => $gender
				);
			}
			$answers    = array();
			$extra      = array();
			$conditions = array();
			$i          = 0;
			while ($answer = $this->input->post("answer" . $i, true)) {
				$answers[] = json_decode($answer, true);
				$i++;
			}
			$j = 0;
			while ($extra = $this->input->post("extra" . $j, true)) {
				$answers[] = json_decode($extra, true);
				$j++;
			}
			$k = 1;
			while ($conditions = $this->input->post("condition" . $k, true)) {
				$answers[] = json_decode($conditions, true);
				$k++;
			}
			$data = array(
				"id" => $id,
				"type" => $type,
				"intro" => $intro,
				"question" => $question,
				"order" => $order,
				"allow_multiple" => $allow_multiple,
				"skip_question" => $skip_question,
				"show_prev_ans" => $show_prev_ans,
				"prev_ques_id" => $prev_ques_id,
				"check_prev_ans" => $check_prev_ans,
				"check_prev_ans_id" => $check_prev_ans_id,
				"dependent" => $dependent,
				"dependent_question_id" => $dependent_question_id,
				"dependent_answer_id" => $dependent_answer_id,
				"multiple_dependent" => $multiple_dependent,
				"back_reference" => $back_reference,
				"picture_caption_toggle" => $picture_caption_toggle, // 02012016 AA: picture_caption_toggle
				"picture_caption_question" => $picture_caption_question, // 02192016 AA: added picture_caption_question
				"picture_caption" => $picture_caption, // 02112016 AA: picture_caption
				"m_dependent_question_id" => $m_dependent_question_id,
				"md_logic_handler" => $md_logic_handler, // 05252016 AA: add logic handler
				"m_dependent_answers" => $m_dependent_answers,
				"skip_multiple" => $skip_multiple,
				"answers" => $answers,
				"rating_type" => $rating_type,
				"rating_amount" => $rating_amount,
				"gender_age_params" => $gender_age_params,
				"contact_params" => $contact_params,
				"show_result_SD" => $show_result_SD,
				"dbname_SD" => $dbname_SD,
				"dbfilter_SD" => $dbfilter_SD,
				"enable_listing" => $enable_listing,
				"text_confirm" => $text_confirm,
				"enable_otheropt" => $enable_otheropt,
				"enable_alternative" => $enable_alternative,
				"enable_noneabove" => $enable_noneabove,
				"noneabove_text" => $noneabove_text,
				"enable_dont_know" => $enable_dont_know,
				"dontknow_text" => $dontknow_text,
				"none_next_question_id" => $none_next_question_id,
				"dont_know_next_question_id" => $dont_know_next_question_id,
				"change_input" => $change_input,
				"input_type" => $input_type,
				"text_limit" => $text_limit,
				"slider_min" => $slider_min,
				"slider_interval" => $slider_interval,
				"slider_max" => $slider_max,
				// 05192016 AA: added multiple dependent logic (or/and)
				"m_dependent_logic" => $m_dependent_logic, 
				// 05022016 AA: added multiple_dependent_AND
				// "multiple_dependent_and" => $multiple_dependent_and, 
				// "m_dependent_question_id_and" => $m_dependent_question_id_and, 
				// "m_dependent_answers_and" => $m_dependent_answers_and, 

				"selected_answer"				=> $selected_answer,//157019_08062015 Tony
				"selected_answer_id"			=> $selected_answer_id, 
				"unselected_answer"				=> $unselected_answer,
				"unselected_answer_id"			=> $unselected_answer_id
			);
			$this->load->model('survey_model', 'survey');
			if ($question = $this->survey->generate_question($survey_id, $data)) {
				echo json_encode(array(
					"question" => $question,
					"success" => true
				));
			} else {
				echo json_encode(array(
					"success" => false
				));
			}
		}
	}
	function check_validation($survey_id) {
		if ($this->check_authorization()) {
			$this->load->model('survey_model', 'survey');
			$intro  = $this->input->post("check_intro", true);
			if ($valid = $this->survey->check_validation($survey_id, $intro)) {
				// log_message("debug", "validity_controller=".$valid);
				echo json_encode(array(
					"valid" => $valid,
					"success" => true
				));
			} else {
				echo json_encode(array(
					"success" => false
				));
			}
		}
	}
	function edit_question($survey_id) {
		$this->load->library('firephp');
		$this->load->library('form_validation');
		$id 	= $this->input->post("id", true);
		$intro 	= $this->input->post("intro", true);


		// $config['upload_path'] = './data/picture_caption/';
		// $config['allowed_types'] = 'gif|jpg|png|jpeg';
		// $config['overwrite'] = TRUE;

		// $this->load->library('upload', $config);
		// $this->upload->initialize($config);

		$this->form_validation->set_rules('intro', 'Introduction', 'edit_unique[survey_questions.intro.'.$id.'.'.$survey_id.']'); // 215110010_11252015 AA: Added validation rules

		if ($this->form_validation->run() == FALSE) { // 215110010_11252015 AA: Added validation rules
			echo json_encode(array(
				"success" => false,
				"error" => "'".$intro."' as introduction is already being used in this Project." // 215110036_12012015 AA: Added error message
				// 215110037_12182015 AA: changed error message
			));
		}
		else {
			// if($this->upload->do_upload()){
			// 	$data = $this->upload->data();
			// 	print_r($data);
			// 	echo "Hello world\n";
			// }
			if ($this->check_authorization()) {
				$type                       = $this->input->post("type", true);
				$intro                      = $this->input->post("intro", true);
				$order                      = $this->input->post("order", true);
				$question                   = $this->input->post("question", true);
				$id                         = $this->input->post("id", true);
				$allow_multiple             = $this->input->post("allow_multiple", true);
				$skip_question              = $this->input->post("skip_question", true);
				$show_prev_ans              = $this->input->post("show_prev_ans", true);
				$prev_ques_id               = $this->input->post("prev_ques_id", true);
				$check_prev_ans             = $this->input->post("check_prev_ans", true);
				$check_prev_ans_id          = $this->input->post("check_prev_ans_id", true);
				$dependent                  = $this->input->post("dependent", true);
				$dependent_question_id      = $this->input->post("dependent_question_id", true);
				$dependent_answer_id        = $this->input->post("dependent_answer_id", true);
				$multiple_dependent         = $this->input->post("multiple_dependent", true);
				$back_reference         	= $this->input->post("back_reference", true); 
				$picture_caption_toggle     = $this->input->post("picture_caption_toggle", true); // 02012016 AA: picture_caption_toggle
				$picture_caption_question   = $this->input->post("picture_caption_question", true); // 02192016 AA: added picture_caption_question
				$picture_caption     		= $this->input->post("picture_caption", true); // 02112016 AA: picture_caption
				$m_dependent_question_id    = $this->input->post("m_dependent_question_id", true);
				$md_logic_handler    		= $this->input->post("md_logic_handler", true); // 05252016 AA: add logic handler
				$m_dependent_answers        = $this->input->post("m_dependent_answers", true);
				$skip_multiple              = $this->input->post("skip_multiple", true);
				$rating_type                = $this->input->post("rating_type", true);
				$rating_amount              = $this->input->post("rating_amount", true);
				$show_result_SD             = $this->input->post("show_result_SD", true);
				$dbname_SD                  = $this->input->post("dbname_SD", true);
				$dbfilter_SD                = $this->input->post("dbfilter_SD", true);
				$enable_listing             = $this->input->post("enable_listing", true);
				$text_confirm               = $this->input->post("text_confirm", true);
				$enable_otheropt            = $this->input->post("enable_otheropt", true);
				$enable_alternative         = $this->input->post("enable_alternative", true);
				$enable_noneabove           = $this->input->post("enable_noneabove", true);
				$noneabove_text             = $this->input->post("noneabove_text", true);
				$enable_dont_know           = $this->input->post("enable_dont_know", true);
				$dontknow_text              = $this->input->post("dontknow_text", true);
				$none_next_question_id      = $this->input->post("none_next_question_id", true);
				$dont_know_next_question_id = $this->input->post("dont_know_next_question_id", true);
				$change_input               = $this->input->post("change_input", true);
				$input_type                 = $this->input->post("input_type", true);
				$text_limit                 = $this->input->post("text_limit", true);
				$slider_min                 = $this->input->post("slider_min", true);
				$slider_interval            = $this->input->post("slider_interval", true);
				$slider_max                 = $this->input->post("slider_max", true);
				// 05192016 AA: added multiple dependent logic (or/and)
				$m_dependent_logic     		= $this->input->post("m_dependent_logic", true);
				// 05022016 AA: added multiple_dependent_AND
				// $multiple_dependent_and      = $this->input->post("multiple_dependent_and", true);
				// $m_dependent_question_id_and = $this->input->post("m_dependent_question_id_and", true);
				// $m_dependent_answers_and     = $this->input->post("m_dependent_answers_and", true);
				//157019_08062015 Tony
				$selected_answer			= $this->input->post("selected_answer", true);
				$selected_answer_id			= $this->input->post("selected_answer_id", true);
				$unselected_answer			= $this->input->post("unselected_answer", true);
				$unselected_answer_id		= $this->input->post("unselected_answer_id", true);
				$contact_params             = array();
				if ($type == "contact_page") {
					$ask_name                = $this->input->post("ask_name", true);
					$ask_email               = $this->input->post("ask_email", true);
					$ask_phone               = $this->input->post("ask_phone", true);
					$ask_address_street      = $this->input->post("ask_address_street", true);
					$ask_address_postal_code = $this->input->post("ask_address_postal_code", true);
					$ask_address_city        = $this->input->post("ask_address_city", true);
					$ask_address_state       = $this->input->post("ask_address_state", true);
					$ask_address_country     = $this->input->post("ask_address_country", true);
					$ask_extra_field         = $this->input->post("ask_extra_field", true);
					$extra_field_label       = $this->input->post("extra_field_label", true);
					$contact_params          = array(
						"ask_name" => $ask_name,
						"ask_email" => $ask_email,
						"ask_phone" => $ask_phone,
						"ask_address_street" => $ask_address_street,
						"ask_address_postal_code" => $ask_address_postal_code,
						"ask_address_city" => $ask_address_city,
						"ask_address_state" => $ask_address_state,
						"ask_address_country" => $ask_address_country,
						"ask_extra_field" => $ask_extra_field,
						"extra_field_label" => $extra_field_label
					);
				}
				$gender_age_params = array();
				if ($type == "gender_age") {
					$age    = $this->input->post("age", true);
					$gender = $this->input->post("gender", true);
					if (isset($age)) {
						$age = json_decode($age, true);
					}
					if (isset($gender)) {
						$gender = json_decode($gender, true);
					}
					$gender_age_params = array(
						"age" => $age,
						"gender" => $gender
					);
				}
				$answers    = array();
				$extra      = array();
				$conditions = array();
				$i          = 0;
				while ($answer = $this->input->post("answer" . $i, true)) {
					$answers[] = json_decode($answer, true);
					$i++;
				}
				$j = 0;
				while ($extra = $this->input->post("extra" . $j, true)) {
					$answers[] = json_decode($extra, true);
					$j++;
				}
				$k = 1;
				while ($conditions = $this->input->post("condition" . $k, true)) {
					$answers[] = json_decode($conditions, true);
					$k++;
				}
				$data = array(
					"id" => $id,
					"type" => $type,
					"intro" => $intro,
					"order" => $order,
					"question" => $question,
					"allow_multiple" => $allow_multiple,
					"skip_question" => $skip_question,
					"show_prev_ans" => $show_prev_ans,
					"prev_ques_id" => $prev_ques_id,
					"check_prev_ans" => $check_prev_ans,
					"check_prev_ans_id" => $check_prev_ans_id,
					"dependent" => $dependent,
					"dependent_question_id" => $dependent_question_id,
					"dependent_answer_id" => $dependent_answer_id,
					"multiple_dependent" => $multiple_dependent,
					"back_reference" => $back_reference,
					"picture_caption_toggle" => $picture_caption_toggle, // 02012016 AA: picture_caption_toggle
					"picture_caption_question" => $picture_caption_question, // 02192016 AA: added picture_caption_question
					"picture_caption" => $picture_caption, // 02112016 AA: picture_caption
					"m_dependent_question_id" => $m_dependent_question_id,
					"md_logic_handler" => $md_logic_handler,
					"m_dependent_answers" => $m_dependent_answers,
					"skip_multiple" => $skip_multiple,
					"answers" => $answers,
					"rating_type" => $rating_type,
					"rating_amount" => $rating_amount,
					"gender_age_params" => $gender_age_params,
					"contact_params" => $contact_params,
					"show_result_SD" => $show_result_SD,
					"dbname_SD" => $dbname_SD,
					"dbfilter_SD" => $dbfilter_SD,
					"enable_listing" => $enable_listing,
					"text_confirm" => $text_confirm,
					"enable_otheropt" => $enable_otheropt,
					"enable_alternative" => $enable_alternative,
					"enable_noneabove" => $enable_noneabove,
					"noneabove_text" => $noneabove_text,
					"enable_dont_know" => $enable_dont_know,
					"dontknow_text" => $dontknow_text,
					"none_next_question_id" => $none_next_question_id,
					"dont_know_next_question_id" => $dont_know_next_question_id,
					"change_input" => $change_input,
					"input_type" => $input_type,
					"text_limit" => $text_limit,
					"slider_min" => $slider_min,
					"slider_interval" => $slider_interval,
					"slider_max" => $slider_max,
					// 05192016 AA: added multiple dependent logic (or/and)
					"m_dependent_logic" => $m_dependent_logic, 
					// 05022016 AA: added multiple_dependent_AND
					// "multiple_dependent_and" => $multiple_dependent_and, 
					// "m_dependent_question_id_and" => $m_dependent_question_id_and, 
					// "m_dependent_answers_and" => $m_dependent_answers_and, 
					"selected_answer"				=> $selected_answer,//157019_08062015 Tony
					"selected_answer_id"			=> $selected_answer_id, 
					"unselected_answer"				=> $unselected_answer,
					"unselected_answer_id"			=> $unselected_answer_id
				);
				// log_message("debug", "back_ref=".$back_reference);
				$items_per_page = $this->items_per_page;
				$this->load->model('survey_model', 'survey');
				if ($result = $this->survey->edit_question($survey_id, $data, $items_per_page)) {
					$total_page = $this->survey->get_total_page($items_per_page, $survey_id);
					// log_message("debug", "total_page=".$total_page);
					if ($type == "closing") {
						echo json_encode(array(
							"success" => true,
							"intro" => $result,
							"total_page" => $total_page
						));
					} else {
						echo json_encode(array(
							"success" => true,
							// "error" => "The question could not be saved",
							"question" => $result,
							"total_page" => $total_page
						));
					}
				} else {
					echo json_encode(array(
						"success" => false,
						"error" => "The question could not be saved"
					));
				}
			}
		}
	}
	function re_order_question($question_id) {
		if ($this->check_authorization()) {
			$new_order = $this->input->post("new_order", true);
			$this->load->model('survey_model', 'survey');
			if ($id = $this->survey->re_order_question($question_id, $new_order)) {
				echo json_encode(array(
					"success" => true
				));
			} else {
				echo json_encode(array(
					"success" => false
				));
			}
		}
	}
	function delete_question($question_id) {
		if ($this->check_authorization()) {
			$this->load->model('survey_model', 'survey');
			if ($id = $this->survey->delete_question($question_id)) {
				echo json_encode(array(
					"success" => true
				));
			} else {
				echo json_encode(array(
					"success" => false
				));
			}
		}
	}
	function delete_entries($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('survey_model', 'survey');
				if ($success = $this->survey->delete_entries($survey_id, $user_id)) {
					echo json_encode(array(
						"success" => true
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function delete_entry($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('survey_model', 'survey');
				$entries = $this->input->post("entries", true);
				if (isset($entries)) {
					$entries = json_decode($entries, true);
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
				if ($success = $this->survey->delete_entry($survey_id, $entries, $user_id)) {
					echo json_encode(array(
						"success" => true,
						"unaffected_rows" => $success
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function delete_survey($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('user_model', 'user');
				$user = $this->user->get($user_id);
				$this->load->model('survey_model', 'survey');
				if ($success = $this->survey->delete_survey($survey_id, $user)) {
					echo json_encode(array(
						"success" => true
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	function _initialize($folder, $name) {
		$config['upload_path']   = $folder;
		$config['allowed_types'] = 'gif|jpg|jpeg|jpe|png|tiff|tif|bmp';
		$config['remove_spaces'] = true;
		$config['file_name']     = $name;
		return $this->upload->initialize($config);
	}
	function _rand_string($length = 10) {
		$characters   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	}
	
	function get_survey_versions($survey_id) {
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('survey_model', 'survey');
				if ($versions = $this->survey->get_survey_versions($survey_id)) {
					echo json_encode(array(
							"versions" => $versions,
							"success" => true
					));
				} else {
					echo json_encode(array(
							"success" => false
					));
				}
			}
		}
	}
	
	function create_new_version($survey_id){
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('user_model', 'user');
				$user = $this->user->get($user_id);
				$this->load->model('survey_model', 'survey');
				if ($id = $this->survey->duplicate($survey_id, $user, true)) {
					echo json_encode(array(
						"survey_id" => $id,
						"success" => true
					));
				} else {
					echo json_encode(array(
						"success" => false
					));
				}
			}
		}
	}
	
	function publish($survey_id){
		if ($this->check_authorization()) {
			if ($user_id = $this->ion_auth->get_user_id()) {
				$this->load->model('user_model', 'user');
				$user = $this->user->get($user_id);
				$this->load->model('survey_model', 'survey');
				echo json_encode(array(
						"success" => $this->survey->publish($survey_id, $user)
				));
			}
		}
	}
}
?>