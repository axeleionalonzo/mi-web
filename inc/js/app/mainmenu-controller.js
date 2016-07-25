/********************* landing script controller *********************/

define(function() {

    var mainmenu = (function() {
    	var template,
            templates = {
                "multiple_choice_labels": "",
                "multiple_choice_list": "",
                "open_question": "",
                "gender_age": "",
                "text_page": "",
                "rating": "",
                "prioritise": "",
                "contact_page": "",
                "gps": "",
                "pic_capture": "",
                "sliderq": ""
            },
    		avail = true;
    		menu_container = "",
    		surveyList_container = "",
			dataUser = [],
			dataSurvey = [],
			surveyQuestions = [],
			current_surveyID = "",
			current_survey = 0,
			current_questions = "",
			questionIndex = 0;

		function isEmpty(p) {
		    return p.length == 0 ? true : false;
		}

		function showLoader(action) {
		    var main_content = $("div#mainmenu-container");
			var loader = main_content.find("div#landing-loader");

			if (action === true) {
				loader.fadeIn("slow");
			} else loader.fadeOut("slow");
		}

		function attachHandlers() {

			$("body").on("click", "#menu-toggle", function(e) {
		        e.preventDefault();
		        $("#wrapper").toggleClass("toggled");
		    });

			$("body").on("click", "#go_clockin", function(e) {
		        e.preventDefault();

		        var main_content = $("body").find("div#mainmenu-container");

	            require(["app/page-controller"], function(slider) {
			        slider.where("clockin");
			        main_content.fadeOut("slow").promise().done(function(){
						main_content.children().fadeOut("slow").remove();
					});
			    });
		    });

			$("body").on("click", "#start_survey", function(e) {
		        e.preventDefault();
		        var survey_id = $(this).attr("survey-id");
		        	// alert(survey_id+ " : " +avail);
		        if (survey_id && avail) {
		        	current_surveyID = survey_id;
		        	start_survey(survey_id);
		        }
		    });
		    
			$("body").on("click", "#prev_question", function(e) {
		        e.preventDefault();
		        if (current_surveyID && avail) {
					makeSurveyFragment(false);
		        }
		    });
		    
			$("body").on("click", "#next_question", function(e) {
		        e.preventDefault();
		        if (current_surveyID && avail) {
					makeSurveyFragment(true);
		        }
		    });

		    // toggle checkbox/radio  on div
			$("body").on("click", ".toggler", function (e) {
				var input = $(this).children().children('input');
				var isActive = input.prop('checked');
				var inputType = input.attr("type");

				if (inputType == "checkbox") { // if checkbox
					if (isActive) {
						input.prop('checked', false);
						input.next('label').removeClass('white-text').addClass('grey-text');
						$(this).removeClass("grey darken-3");
					} else {
						input.prop('checked', true);
						input.next('label').removeClass('grey-text').addClass('white-text');
						$(this).addClass("grey darken-3");
					}
				} else if (inputType == "radio") { // if radio
					if (isActive) {
						input.prop('checked', false);
						input.next('label').removeClass('white-text').addClass('grey-text');
						$(this).removeClass("grey darken-3");
					} else {
						input.prop('checked', true);
						input.next('label').removeClass('grey-text').addClass('white-text');
						$(this).addClass("grey darken-3");
						// restore default styles on unselected radios
						$(this).siblings().removeClass("grey darken-3");
						$(this).siblings().find("label").removeClass('white-text').addClass('grey-text');
					}
				}
			});

			$("body").on("click", "#show_location", function(e) {
		        e.preventDefault();
		        if (avail) {
					geoFindMe();
		        }
		    });
		}

		function removeHandlers() {
			// removing all jquery event handlers
			$('body').off();
			$("#password").off();
			$('#email').off();
		}

		function refreshHandlers() {
			removeHandlers();
			attachHandlers();
		}

		function geoFindMe() {
			avail = false;
			var gps_staticmap = $("body").find("div#staticmap");
			var gps_streepview = $("body").find("div#streepview");
			var geo_stat = $("body").find("p#geo_status");
			var geo_data = $("body").find("p#geo_details");

			if (!navigator.geolocation){
				geo_stat.html("Geolocation is not supported by your browser.");
				return;
			}

			function success(position) {
				var latitude  = position.coords.latitude;
				var longitude = position.coords.longitude;

				geo_data.html('Latitude is ' + latitude + '° Longitude is ' + longitude + '°');

				var staticimg = new Image();
				var streetimg = new Image();
				var geo_address = [];
				staticimg.src = "https://maps.googleapis.com/maps/api/staticmap?center=" + latitude + "," + longitude + "&zoom=14&size=1000x350&markers=color:blue%7Clabel:A%7C" + latitude + "," + longitude + "&sensor=false";
				streetimg.src = "https://maps.googleapis.com/maps/api/streetview?location=" + latitude + "," + longitude + "&size=1000x350&heading=151.78&pitch=-0.76";
				

				$.ajax({
					url: "http://maps.googleapis.com/maps/api/geocode/json?latlng=" + latitude + "," + longitude + "&sensor=true",
					type: 'GET',
	            	dataType: "json",
					success: function(data) {
						if (data.status == "OK") {
							var getAddress = data.results;
							var user_address = getAddress[0].formatted_address;
							geo_stat.html(user_address);
						}
						avail = true;
					},
		            error: function(xhr, status, errorThrown) {
						showLoader(false);
						avail = true;
						console.log("Error: " + errorThrown);
						console.log("Status: " + status);
						console.dir(xhr);
					}
				});

				// console.log(geo_address);
				gps_staticmap.append(staticimg);
				gps_streepview.append(streetimg);
			};

			function error() {
				geo_stat.html("Unable to retrieve your location.");
			};

			geo_stat.html("Locating …");

			navigator.geolocation.getCurrentPosition(success, error);
		}

		function start_survey() {

			avail = false;
			showLoader(true);
			var survey_container = $("body").find("div#survey_container");
			var survey_title_header = $("body").find("span#survey_title_header");
			var pagination = $("body").find("div#question_pagination");
			var survey_box = survey_container.find("li[survey-id='"+current_surveyID+"']");

			$.ajax({
				url: './survey/get_questions/' + current_surveyID,
				type: 'GET',
				success: function(data) {
					var surveyQuestion = data.questions; 

					survey_box.siblings().hide("slow");

					var onGoingSurveyData = $.grep(dataSurvey, function(data) {
					    return data.id === current_surveyID;
					});

					if (onGoingSurveyData[0]) {
						current_survey = onGoingSurveyData;
						current_questions = surveyQuestion;
						makeSurveyFragment(true, true);
					}

					showLoader(false);
					avail = true;
				},
	            error: function(xhr, status, errorThrown) {
					showLoader(false);
					avail = true;
					console.log("Error: " + errorThrown);
					console.log("Status: " + status);
					console.dir(xhr);
				},
	            dataType: "json"
        	});
		}

		function makeSurveyFragment(goNext, init_start = null) {
			avail = false;

			if (!init_start) { // check if it is indeed the first question
				if (goNext) {
					questionIndex++; // checks the index of the question instead of the order
				} else questionIndex--;
			}

			var survey_container = $("body").find("div#survey_container");
			var survey_head = $("body").find("span#survey_title_header");
			var pagination = $("body").find("div#question_pagination");
			var survey_card = survey_container.find("li[survey-id='"+current_surveyID+"']");
			var currentQuestion = current_questions[questionIndex];
			var currentSurvey = current_survey[0];

			// load the html for the particular question type
			loadHtml(currentQuestion.type, function() { // wait for current question to load its template

				// console.log(current_survey[0]);
				// show survey title at nav-bar
				survey_head.html(currentSurvey.title);

				console.log(currentQuestion);
				// fill the survey currentQuestion with survey details
				survey_card.find("div#survey_title").html(currentQuestion.intro + " ( "+currentQuestion.type+" )  <span id='question_counter' class='right'></span>");
				survey_card.find("span#question_counter").html(currentQuestion.order+"/"+current_questions.length);
				survey_card.find("div#survey_title").removeClass("active").addClass("disabled");

				// pagination controls
				pagination.show("fast");
				// prev button disable on start next button disable on last
				questionIndex < 1 ? pagination.find("a#prev_question").addClass("disabled") : pagination.find("a#prev_question").removeClass("disabled");
				questionIndex === (current_questions.length-1) ? pagination.find("a#next_question").addClass("disabled") : pagination.find("a#next_question").removeClass("disabled");


				// create html for the question
				if (goNext) {
					require(["mustache"], function(Mustache) {
						var questionHTML = Mustache.render(templates[currentQuestion.type], currentQuestion);
						var survey_body = survey_card.find("div#survey_body");
						if (init_start) {
							survey_body.html(questionHTML);
						} else {
							survey_body.children().hide(); // hide all the questions
							survey_body.append(questionHTML); //  then add the current
						}

						if (currentQuestion.type == "gps") {
							geoFindMe();
						} else if (currentQuestion.type == "prioritise") {
							var el = document.getElementById('items');  //returns a HTML DOM Object
							var sortable = new Sortable(el, {
								animation: 150,
							    ghostClass: "sortable-ghost",  // Class name for the drop placeholder
							    chosenClass: "sortable-chosen",  // Class name for the chosen item
							    dataIdAttr: "data-id"
							});
						}
					});
				} else { // if prev
					var survey_body = survey_card.find("div#survey_body");

					survey_body.children("div:nth-child("+(questionIndex+2)+")").remove(); // remove current question
					// survey_body.children("div:nth-child("+(questionIndex.order)+")").remove();
					survey_body.children("div:nth-child("+(questionIndex+1)+")").show(); // show prev question
				}

				avail = true;
			});
		}

		function loadMenu() {

			avail = false;
			var main_content = "";

	        loadHtml("main", function() {
				var mainmenu = $("body").find("div#mainmenu-container");
				var clockin = $("body").find("div#clockin-container");

				clockin.hide();

				console.log(dataUser[0]);
				require(["mustache"], function(Mustache) {

					// require.undef("materialize");
					var maintHTML = Mustache.render(menu_container, dataUser[0]);
					main_content = $("body").find("div#mainmenu-container");
					main_content.html(maintHTML);
					avail = true;

		        	mainmenu.fadeIn("slow");

					showLoader(true);
				});
	        });

			// var params = "token="+dataUser[0].token+"&basic="+false+"&time="+"&survey_id="+"&parent_id="+"&check_updates=";

	        loadHtml("surveyList", function() {
	        	$.ajax({
					url: './survey/',
					type: 'GET',
					success: function(data) {
						result = data.result;
						if (result.status) {
							dataSurvey = data.survey;

							require(["mustache"], function(Mustache) {
							
								$.getScript("./inc/js/libs/materialize.amd.js");

								console.log(data);

								var surveyHTML = Mustache.render(surveyList_container, data);
								survey_content = $("body").find("div#page-content-wrapper");
								survey_content.html(surveyHTML);

								var surveyList = survey_content.find("ul#survey_list_handler");
			        			var survey_container = $("body").find("div#survey_container");
								var start = new Date().getTime();

								var listStatus = false;

								var timeElapsed = new Date().getTime() - start;

								console.log('Time taken: ' + timeElapsed+ ' milliseconds');

								// if (listStatus) {
									survey_content.show("fast");
									showLoader(false);
								// }
							});

							refreshHandlers();

								// var maintHTML = Mustache.render(menu_container);
								// var main_content = $("body").find("div#mainmenu-container");
								// main_content.html(maintHTML);
								avail = true;
						} else {
	                        slider.where("clockin");
	                        return;
						}
					},
		            error: function(xhr, status, errorThrown) {
						showLoader(false);
						console.log("Error: " + errorThrown);
						console.log("Status: " + status);
						console.dir(xhr);
					},
		            dataType: "json"
	        	});
	        });

		}

        function loadTemplate(callback, params) {
            var count = 0;
            var countTarget = 1;

	        $.get('inc/templates/survey/main-menu.mustache', function(data) {
	            menu_container = data;
				count++;
	        });

            checkCallback();

			function checkCallback() {
				if (count === countTarget) {
					callback(params);
				} else {
					setTimeout(function() {
					    checkCallback();
					}, 50);
				}
			}
        }

        function loadHtml(content, callback) {

        	if (content === "main" && isEmpty(menu_container)) {
		        $.get('inc/templates/mainmenu/main-menu.mustache', function(data) {
		            menu_container = data;
		            if (callback) callback();
		        });
        	} else if (content === "surveyList" && isEmpty(surveyList_container)) {
		        $.get('inc/templates/mainmenu/surveyList/survey-list.mustache', function(data) {
		            surveyList_container = data;
		            if (callback) callback();
		        });
        	} else if (content in templates && isEmpty(templates[content])) {
        		$.get('inc/templates/mainmenu/surveyQuestions/' + content + '.mustache', function(data) {
                    // console.log("key="+key);
                    templates[content] = data;
                    if (callback) callback();
                });
        	} else {
        		if (callback) callback();
        	}
        }

        return {
            init: function(data) {
            	dataUser = data.user;
            	loadMenu();
            },
            destroy: function() {
                removeHandlers();
            }

        };
		
    })();

    return mainmenu;

});