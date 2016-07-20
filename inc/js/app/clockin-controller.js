/********************* landing script controller *********************/

define(function() {

    var clockin = (function() {

    	var template,
    		avail = true;
    		email_container = "",
			password_container = "",
			clockin_container = "",
			clockout_container = "",
			interval = "",
			momentNow = "",
			moment_time = "",
			moment_date = "",
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
					clearInterval(interval);
					logout();
				}
			});
			// logout handlers end

			// clockin handlers start
			$("body").on("click", "#clockin_confirm", function(e) {
				e.preventDefault();
				dtr_in = "", dtr_in_date = "";
				$('#clockin_message').html("Your start time is <span class='amber-text text-accent-4'>" + moment_time + "</span>. Proceed?");
				dtr_in = momentNow.format('YYYY-MM-DD') + ' ' + momentNow.format('HH:mm:ss.ms');
				dtr_in_date = momentNow.format('YYYY-MM-DD');
				$('#clockin_modal').openModal();
			});

			$("body").on("click", "#clockin_proceed", function(e) {
				e.preventDefault();
				if (avail) {
					clockin(true, dtr_in, dtr_in_date);
				}
			});
			// clockin handlers end

			// clockout handlers start
			$("body").on("click", "#clockout_confirm", function(e) {
				e.preventDefault();
				dtr_out = "", dtr_out_date = "";
				$('#clockout_message').html("Your end time is <span class='amber-text text-accent-4'>" + moment_time + "</span>. Proceed?");
				dtr_out = momentNow.format('YYYY-MM-DD') + ' ' + momentNow.format('HH:mm:ss.ms');
				dtr_out_date = momentNow.format('YYYY-MM-DD');
				$('#clockout_modal').openModal();
			});

			$("body").on("click", "#clockout_proceed", function(e) {
				e.preventDefault();
				if (avail) {
					clearInterval(interval);
					console.log(dtr_out);
					clockin(false, dtr_out, dtr_out_date);
				}
			});
			// clockout handlers end

			$("body").on("click", "#start", function(e) {
				e.preventDefault();
				if (avail) {
					clearInterval(interval);

			        var clockin = $("body").find("div#clockin-container");
			        clockin.hide();
			        
	                require(["app/page-controller"], function(slider) {
			            slider.where("mainmenu");
			        });
					// require(['jquery', 'materialize', 'materializeinit', 'jqueryui', 'jquerythde'], function($) {
					// 	$(function() {
					// 		require(['app/mainmenu-controller'], function(mainmenu) {
					// 			mainmenu.start();
					// 		});
					// 	});
					// });
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

		function logout() {

			// containers
			var card = $(".card");
			var card_content = $(".card-content");
			var card_action = $(".card-action");
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

			$.ajax({
	            type: "POST",
	            url: "./auth/logout",
	            success: function(log) {

					clockin.fadeOut("slow");
	            	if (log.status) {
	            		clearInterval(interval);

						// clear
						account_name.html("").hide("slide", { direction: "left" }, 200);
						account_email.html("").hide("slide", { direction: "left" }, 200);
						
						$(card_action).hide("slide", { direction: "left" }, 100).promise().done(function(){

	                		require(["app/page-controller"], function(slider) {
			                    slider.where("landing");
			                });

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

		function clockin(clockin, dtrData, dtrDate) {

			var input_clockin = $("#landing_clockin");
			var params = "";

			if (clockin) {
				var out = "0000-00-00 00:00:00.000000";
				params += "clock_in=" +dtrData+ "&clock_out=" +out+ "&entry_date=" +dtrDate+ "&isClockin=" +clockin;
			} else {
				params += "clock_in=" +dataUser[0].clock_in+ "&clock_out=" +dtrData+ "&entry_date=" +dataUser[0].entry_date+ "&isClockin=" +clockin;
			}
			avail = false;
			showLoader(true);
			console.log(params);
			$.ajax({
				type: "POST",
				url: "./clockin/appendDTR",
	            data: params,
	            success: function(data) {
	            	if (data.isClockin) {
	            		dataUser = data.user;
		            	loadLanding();
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

        function loadLanding() {

		    showLoader(true);
			var card = $(".card");
			var card_action = $(".card-action");
			var card_content = $(".card-content");
			var input_email = $("#landing_email");
			var input_password = $("#landing_password");
			var input_clockin = $("#landing_clockin");
			var main_content = $("body").find("div#mainmenu-container");
            var clockin = $("#clockin-container");

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
			var error_message = "";
			var params = "";

			// buttons
			var logout_button = $("body").find("p#logout");

			// local params
			var valid = false;
			var parameter = "";
			avail = false;

			main_content.children().remove();

			// change instruction
			instruction.hide().html("Clock in and start working Market Intel").fadeIn('fast');

				require(["moment"], function (moment) {

                	clockin.fadeIn("slow");
					var clockinedToday = moment(dataUser[0].clock_in).isSame(new Date(), "day");
					console.log(dataUser[0]);
					if (dataUser.length >= 1 && dataUser[0].clock_out == "0000-00-00 00:00:00") {
						// is there's a user (today/yesterday)
						// is the recent user clockedin
						// is the recent user not clockedout yet

						// hide password container
						$(card_action).hide("slide", { direction: "left" }, 100).promise().done(function(){
							loadHtml("clockout", function() {
								input_password.children().remove();
								input_clockin.children().remove();
								loadClockout();
							});
						});

					} else if (dataUser.length == 0 || dataUser[0].clock_out != "0000-00-00 00:00:00") {

						loadHtml("clockin", function() {

				    		require(["mustache"], function(Mustache) {
								account_name.html(dataUser[0].username).show("slide", { direction: "right" }, 200);
								account_email.html(dataUser[0].email).show("slide", { direction: "right" }, 200);

								// hide password container
								$(card_action).hide("slide", { direction: "left" }, 100).promise().done(function(){
									// show clockin container
									input_password.children().remove();
									input_clockin.children().remove();

									var clockinHTML = Mustache.render(clockin_container);
									var clockin_content = $("body").find("div#landing_clockin");
									clockin_content.append(clockinHTML).show().promise().done(function(){
										$(card_action).show("slide", { direction: "right" }, 200);
										// dtr status
										real_time = clockin_content.find("#clockintime");
										real_date = card.find("p#clockindate");
										require(["moment"], function (moment) { // live time
											interval = setInterval(function() {
												momentNow = moment();
												moment_time = momentNow.format('hh:mm:ss A');
												moment_date = momentNow.format('MMMM DD, YYYY') + ' ' 
													+ momentNow.format('dddd')
													.substring(0,3).toUpperCase();
												real_time.html(moment_time);
												real_date.html(moment_date);
											}, 100);
										});

										logout_button.fadeIn("fast");
										refreshHandlers();
										avail = true;
									});
								});
							});
						});
					}
				});

			back.fadeOut("fast");
			showLoader(false);
        }

        function loadClockout() {
		    showLoader(true);
			var card = $(".card");
			var card_content = $(".card-content");
			var input_email = $("#landing_email");
			var input_password = $("#landing_password");
			var input_clockin = $("#landing_clockin");

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
			var email = input_email.find("input#email").val();
			var error_message = "";
			var params = "";

			// buttons
			var next_button = input_email.find("a#next");
			var signin_button = input_password.find("a#signin");
			var logout_button = $("body").find("p#logout");

			// local params
			var valid = false;
			var parameter = "";

			avail = false;

			input_clockin.children().remove();

        	require(["mustache"], function(Mustache) {
				var clockinHTML = Mustache.render(clockout_container);
				var clockin_content = $("body").find("div#landing_clockin");
				account_name.html(dataUser[0].username).show("slide", { direction: "right" }, 200);
				account_email.html(dataUser[0].email).show("slide", { direction: "right" }, 200);

				// show clockin container
				clockin_content.append(clockinHTML).show().promise().done(function(){
					$(card_action).show("slide", { direction: "right" }, 200);

					var clockout_button = clockin_content.find("a#clockout_confirm");
					var start_button = clockin_content.find("a#start");
					var start_text = card.find("span#start_text");
					// dtr status
					var clockin_status = card.find("tbody#clockedinstatus");
					var rendered_time = card.find("p#rendered_time");
					// live time
					real_time = clockin_content.find("#clockintime");
					real_date = card.find("#clockindate");

					clockin_status.html("");
					require(["moment"], function (moment) {
						var clockined_time = moment(dataUser[0].clock_in).format('YYYY-MM-DD hh:mm:ss A');
						interval = setInterval(function() {
							momentNow = moment();
							moment_time = momentNow.format('hh:mm:ss A');
							moment_date = momentNow.format('MMMM DD, YYYY') + ' ' 
								+ momentNow.format('dddd')
								.substring(0,3).toUpperCase();
							real_time.html(moment_time);
							real_date.html(moment_date);
							moment_dateDefault = momentNow.format('YYYY-MM-DD');

							// rendered time
							startTime = moment(clockined_time, "YYYY-MM-DD hh:mm:ss A");
							endTime = moment(moment_dateDefault + " " + moment_time, "YYYY-MM-DD hh:mm:ss A");
							duration = moment.duration(endTime.diff(startTime));
							hours = parseInt(duration.asHours());
							minutes = parseInt(duration.asMinutes())-hours*60;
							seconds = parseInt(duration.asSeconds())-(minutes*60)-(hours*3600);
							rendered_time.html(hours+"h "+minutes+"m ("+seconds+")");

							clockout_button.removeClass("disabled"); // can now clockout
							start_button.removeClass("disabled"); // can now clockout
						}, 1000);


						// dtr status
				        $.each(dataUser, function(index, user_data) {
							console.log(index +".) "+user_data.clock_in+" "+user_data.clock_out);

							var clockintime_value = moment(user_data.clock_in).format('hh:mm A');
							var clockindate_value = moment(user_data.entry_date).format('MMMM DD') + " (" + moment(user_data.entry_date).format('dddd').substring(0,3) + ")";

							if (user_data.clock_out != "0000-00-00 00:00:00") {
								// rendered time
								dtr_time_in = moment(user_data.clock_in).format('YYYY-MM-DD hh:mm:ss A');
								dtr_time_out = moment(user_data.clock_out).format('YYYY-MM-DD hh:mm:ss A');
								dtr_startTime = moment(dtr_time_in, "YYYY-MM-DD hh:mm:ss A");
								dtr_endTime = moment(dtr_time_out, "YYYY-MM-DD hh:mm:ss A");
								dtr_duration = moment.duration(dtr_endTime.diff(dtr_startTime));
								dtr_hours = parseInt(dtr_duration.asHours());
								dtr_minutes = parseInt(dtr_duration.asMinutes())-dtr_hours*60;

								clockin_status.append("<tr class='short'><td>"+clockindate_value+"</td><td>"+clockintime_value+"</td><td>"+dtr_hours+"."+dtr_minutes+"h</td></tr>");
							} else if (index === 0) {
								start_text.html("Continue");
								clockin_status.append("<tr class='short'><td>"+clockindate_value+"</td><td>"+clockintime_value+"</td><td>working</td></tr>");
							} else {
								clockin_status.append("<tr class='short'><td>"+clockindate_value+"</td><td>"+clockintime_value+"</td><td class='align-icon'>N/A <i class='material-icons tiny clickable tooltipped' data-position='right' data-delay='200' data-tooltip='You did not clockout for this day. Please contact administrator.'>error_outline</i></td></tr>");
							}
				        });
			        });

					logout_button.fadeIn("fast");
					back.fadeOut("fast");
					avail = true;
					showLoader(false);
					refreshHandlers();
				});
			});

        }

        function loadHtml(content, callback) {

        	if (content === "clockin" && isEmpty(clockin_container)) {
		        $.get('inc/templates/landing/landing-clockin.mustache', function(data) {
		            clockin_container = data;
		            if (callback) callback();
		        });
        	} else if (content === "clockout" && isEmpty(clockout_container)) {
		        $.get('inc/templates/landing/landing-clockout.mustache', function(data) {
		            clockout_container = data;
		            if (callback) callback();
		        });
        	} else {
	        	if (callback) callback();
	        }
        }

        return {
            init: function(data) {
            	dataUser = data.user;
            	loadLanding();
            },
            destroy: function() {
                removeHandlers();
            }
        };
		
    })();

    return clockin;

});