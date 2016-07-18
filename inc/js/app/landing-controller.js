/********************* landing script controller *********************/

define(function() {

    var landing = (function() {
    // var landingController = (function() {
  //   	window.onbeforeunload = function() {
		//     return 'You have unsaved changes!';
		// }

    	var template,
    		avail = true;
    		email_container = "",
			password_container = "",
			interval = "",
			momentNow = "",
			moment_time = "",
			moment_date = "",
			data = "",
			dataUser = "",
			dtr_in = "",
			dtr_in_date = "",
			dtr_out = "",
			dtr_out_date = "";

		function attachHandlers() {
			// add all jquery event handlers
	    	// plugin: on enter
			$.fn.onEnter = function(func) {
				this.bind('keypress', function(e) {
					if (e.keyCode == 13) func.apply(this, [e]);    
				});               
				return this; 
			};

			$("body").on("click", "#next", function(e) {
				e.preventDefault();
				if (avail) {
					check_email();
				}
			});

			$("body").on("click", "#landing-back", function(e) {
				e.preventDefault();
				if (avail) {
					back_to_mail();
				}
			});

			$("body").on("click", "#signin", function(e) {
				e.preventDefault();
				if (avail) {
					signin();
				}
			});

			// logout handlers start
			$("body").on("click", "#logout_confirm", function(e) {
				e.preventDefault();
				dtr_out = "", dtr_out_date = "";
				$('#logout_clockout_message').html("You need to clock out first. Your end time is <span class='amber-text text-accent-4'>" + moment_time + "</span>. Proceed?");
				dtr_out = momentNow.format('YYYY-MM-DD') + ' ' + momentNow.format('HH:mm:ss.ms');
				dtr_out_date = momentNow.format('YYYY-MM-DD');
				$('#logout_modal').openModal();
			});

			$("body").on("click", "#logout_proceed", function(e) {
				e.preventDefault();
				if (avail) {
					logout();
				}
			});
			// logout handlers end

			$('#email').onEnter(function(e) {
				e.preventDefault();
				if (avail) {
					check_email();
				}
			});

			$("#password").onEnter(function(e) {
				e.preventDefault();
				if (avail) {
					signin();
				}
			});

			$("#password").keyup($.debounce(250, passwordConf));

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

		function passwordConf(e) {
			e.preventDefault();

			// store pressed key
			var code = (e.keyCode ? e.keyCode : e.which);

			// containers
			var input_password = $("#landing_password");

			// labels
			var account_password = input_password.find("input#password").val();

			// buttons
			var signin_button = input_password.find("a#signin");

			if (isEmpty(account_password)) {
				signin_button.addClass("disabled");
			} else if (code==13) { // if enterkey is pressed
				signin_button.addClass("disabled");
			} else signin_button.removeClass("disabled");
		}

		function isEmpty(p) {
		    return p.length == 0 ? true : false;
		}

		function showLoader(action) {
			var card = $(".card");
			var loader = card.find("div#landing-loader");

			if (action === true) {
				loader.fadeIn("slow");
			} else loader.fadeOut("slow");
		}

		function validateEmail(email) {
			var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
			return pattern.test(email);
		};

		function check_email() {
			// containers
			var card = $(".card");
			var card_content = $(".card-content");
			var card_action = $(".card-action");
			var input_email = $("#landing_email");
			var input_password = $("#landing_password");

			// components
			var back = card.find("i#landing-back");

			// labels
			var account_name = card_content.find("p#accountname");
			var account_email = card_content.find("p#accountemail");
			var error_box = input_email.find("div#error");

			// i/o
			var email = input_email.find("input#email").val();
			var error_message = "";
			var params = "";

			// buttons
			var next_button = input_email.find("a#next");

			// local params
			var valid = false;

		  	// validation
		  	if (isEmpty(email)) {
		  		$("#email").addClass("invalid");
		  		error_message += "We need your email here!\n";
		  	} else if (!validateEmail(email)) {
		  		$("#email").addClass("invalid");
		  		error_message += "You entered an invalid email.\n";
		  	} else valid = true;

		  	if (error_message) {
		  		error_box.fadeIn("fast").html(error_message);
		  	}

			if (valid) {

				avail = false;
				// show loading
				showLoader(true);
				// clear error message
		  		error_box.fadeOut("fast").html("");
		  		// disable next
		  		next_button.addClass("disabled");

				params += "email=" + email;

				$.ajax({
					url: './landing/doesEmailExists',
					type: 'POST',
					data: params,
					dataType: 'json',
					success: function(data) {
						if (data.success) {
							data = data.user;

			                loadHtml("password", function() {

				                // hide email container
				                $(card_action).hide("slide", { direction: "left" }, 100).promise().done(function(){
		        					require(["mustache"], function(Mustache) {
										var passwordHTML = Mustache.render(password_container);
										var password_content = $("body").find("div#landing_password");
										// show password container
										password_content.append(passwordHTML).show().promise().done(function(){
											account_name.html(data.username).show("slide", { direction: "right" }, 200);
											account_email.html(data.email).show("slide", { direction: "right" }, 200);
											input_email.children().remove();
											$(card_action).show("slide", { direction: "right" }, 200).promise().done(function(){
												var password = input_password.find("input#password").val();
												var password_focus = input_password.find("input#password");
												var signin_button = input_password.find("a#signin");
												if (!isEmpty(password)) {
													signin_button.removeClass("disabled");
												}
												refreshHandlers();
												avail = true;
												password_focus.focus();
											});
										});
									});
								});

								back.fadeIn("fast");
			                });
						} else {
							input_email.find("input#email").addClass("invalid");
		  					error_message += "We could not find an account for that email address.";
							error_box.fadeIn("fast").html(error_message);
							avail = true;
						}

						showLoader(false);
					  	next_button.removeClass("disabled");
					}, // End of success function of ajax form
					error: function(xhr, status, errorThrown) {
						avail = true;
						$("#submit").fadeIn("slow");
						console.log("Error: " + errorThrown);
						console.log("Status: " + status);
						console.dir(xhr);
					},
				}); // End of ajax call 
			}
		}


		function back_to_mail() {

			// containers
			var card = $(".card");
			var card_content = $(".card-content");
			var card_action = $(".card-action");
			var input_email = $("#landing_email");
			var input_password = $("#landing_password");

			// components
			var back = card.find("i#landing-back");

			// labels
			var account_name = card_content.find("p#accountname");
			var account_email = card_content.find("p#accountemail");

			// clear
			account_name.hide("slide", { direction: "left" }, 200);
			account_email.hide("slide", { direction: "left" }, 200);

			// show loading
			showLoader(true);

			avail = false;
			// change card content
			back.fadeOut("fast");
			$(card_action).hide("slide", { direction: "left" }, 100).promise().done(function(){
				loadEmail();
				input_password.children().remove();

				avail = true;
				showLoader(false);
			});

		}

		function signin() {
			// containers
			var card = $(".card");
			var card_action = $(".card-action");
			var card_content = $(".card-content");
			var input_email = $("#landing_email");
			var input_password = $("#landing_password");
			var input_clockin = $("#landing_clockin");
			var clockin = $("body").find("div#clockin-container");

			// components
			var back = card.find("i#landing-back");

			// labels
			var account_name = card_content.find("p#accountname");
			var account_email = card_content.find("p#accountemail");
			var clockin_time = card.find("#clockintime");
			var clockin_date = card.find("p#clockindate");
			var error_box = input_password.find("div#error");
			var instruction = $("body").find("p#landing_direction");

			// i/o
			var password = input_password.find("input#password").val();
			var email = card_content.find("p#accountemail").html();
			var error_message = "";
			var params = "";

			// buttons
			var next_button = input_email.find("a#next");
			var signin_button = input_password.find("a#signin");
			var logout_button = $("body").find("p#logout");

			// local params
			var valid = false;
			var parameter = "";

		  	// validation
		  	if (isEmpty(password)) {
		  		$("#password").addClass("invalid");
		  		error_message += "We need your password here!\n";
		  	} else valid = true;

		  	if (error_message) {
		  		error_box.fadeIn("fast").html(error_message);
		  	}

			if (valid) {

				avail = false;
				// show loding
				showLoader(true);
				// clear error message
		  		error_box.fadeOut("fast").html("");
		  		// disable signin
		  		signin_button.addClass("disabled");

		  		var device_token = "32as2d13a6w1d3awd135a153153gfdb0";
		  		var device_type = "web";
				params += "email=" + email + "&password=" + password + "&device_token=" + device_token + "&device_type=" + device_type;
				parameter += "identity=" + email + "&password=" + password + "&rememberme=" + true;

				$.ajax({
	                type: "POST",
	                url: "./auth/login",
	                data: parameter,
	                success: function(data) {
						clockin.fadeOut("slow");
	                	if (data.logged_in) {
	                		require(["app/page-controller"], function(slider) {
			                    slider.where("clockin");
			                });
	                	} else {
							input_password.find("input#password").addClass("invalid");
		  					error_message += "The password you entered is wrong!";
							error_box.fadeIn("fast").html(error_message);
							avail = true;
							showLoader(false);
	                	}
	                }, // End of success function of ajax form
					error: function(xhr, status, errorThrown) {
						console.log("Error: " + errorThrown);
						console.log("Status: " + status);
						console.dir(xhr);
					},
	                dataType: "json"
	            });
			}
		}

		function logout() {
			// containers
			var card = $(".card");
			var card_content = $(".card-content");
			var input_email = $("#landing_email");
			var input_password = $("#landing_password");
			var input_clockin = $("#landing_clockin");
			var clockin = $("body").find("div#clockin-container");

			// components
			var back = card.find("i#landing-back");

			// labels
			var account_name = card_content.find("p#accountname");
			var account_email = card_content.find("p#accountemail");
			var error_box = input_email.find("div#error");

			// i/o
			var password = input_password.find("input#password");
			var email = input_email.find("input#email").val();
			var error_message = "";
			var params = "";

			// buttons
			var next_button = input_email.find("a#next");
			var signin_button = input_password.find("a#signin");
			var logout_button = $("body").find("p#logout");

			avail = false;
			showLoader(true);
			clockin.fadeOut("slow");
			$.ajax({
	            type: "POST",
	            url: "./auth/logout",
	            success: function(log) {
	            	if (log.status) {
	            		clearInterval(interval);

						// clear
						account_name.html("").hide("slide", { direction: "left" }, 200);
						account_email.html("").hide("slide", { direction: "left" }, 200);

						$(input_clockin).hide("slide", { direction: "left" }, 100).promise().done(function(){

	            			loadEmail();

							input_clockin.children().remove();
							input_password.children().remove();

							logout_button.fadeOut("fast");
							refreshHandlers();
						});
	            	}
	            avail = true;
	            showLoader(false);
	            }, // End of success function of ajax form
				error: function(xhr, status, errorThrown) {
					console.log("Error: " + errorThrown);
					console.log("Status: " + status);
					console.dir(xhr);
				},
	            dataType: "json"
	        });
		}

        function loadEmail() {

			var card = $(".card");
			var input_clockin = $("#landing_clockin");
			var card_action = $(".card-action");
			var clockin = $("body").find("div#clockin-container");
			var data = {
				"email": "nmnsurvey@nmn.com"
			}

			input_clockin.hide();
			require(["mustache"], function(Mustache) {
				clockin.fadeIn("slow");
				var emailtHTML = Mustache.render(email_container, data);
				var email_content = $("body").find("div#landing_email");
				$(email_content).append(emailtHTML).show().promise().done(function(){
					$(card_action).show("slide", { direction: "right" }, 200).promise().done(function(){
						$("#next").removeClass("disabled").addClass("waves-effect waves-light teal");
						$("body").find("p#landing_direction").hide().html("Sign in to continue using Market Intel").fadeIn('fast');

						var input_email = $("#landing_email");
						var email = input_email.find("input#email");
						email.focus();

						refreshHandlers();
						showLoader(false);
						avail = true;
					});
				});
			});
        }

        function loadHtml(content, callback) {

        	if (content === "email" && isEmpty(email_container)) {
		        $.get('inc/templates/landing/landing-email.mustache', function(data) {
		            email_container = data;
		            if (callback) callback();
		        });
        	} else if (content === "password" && isEmpty(password_container)) {
		        $.get('inc/templates/landing/landing-password.mustache', function(data) {
		            password_container = data;
		            if (callback) callback();
		        });
        	} else {
	        	if (callback) callback();
	        }
        }

        return {
            init: function(userData) {
            	data = userData;
            	// load email form
            	loadHtml("email", function() {
            		loadEmail();
            	});
            },
            destroy: function() {
                removeHandlers();
            }
        };
		
    })();

    return landing;

});