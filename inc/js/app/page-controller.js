/*global require:true, define:true */
define(function() {

    /*
        slidingNavigation class
    */

    var slidingNavigation = (function() {

        // set the index to login
        var index = "landing";

        // check if it is logged in
        var isLogin = true;
        var isClockin = false;

        // get the user date
        var userData;
        var current_user;

        // swipe pages
        var pages = {
            "landing": '0',
            "clockin": '1',
            "mainmenu": '2'
        };

        function showLoader(action) {
            var loader = $("#general_loading");
            var container = $(".app-container");

            container.fadeOut("slow");
            if (action === true) {
                loader.fadeIn("slow");
            } else {
                loader.fadeOut("slow");
            }
        }

        // method that calls logout hash
        function logout() {
            $.ajax({
                type: "POST",
                url: "./auth/logout",
                // async: false,
                success: function(data, textStatus, jqXHR) {
                    // check if status response true
                    if (data.status == true) {
                        // destroy all current handlers that interacts for every page
                        destroyCurrentHandlers();

                        // set logged in false
                        slidingNavigation.loggedIn = false;

                        // set the header opacity to 0
                        //$("header").css("opacity", 0); // AA: 01182016

                        // make overflow visible
                        //$("html").css("overflow", "visible");

                        // remove all attributes from body
                        $("body").attr("style", "");

                        // slide to login
                        slidingNavigation.where("landing");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("The following error occured: " + textStatus, errorThrown);
                    return false;
                },
                dataType: "json"
            });
        }

        // method that loads user control for every page
        function loadUser(callback) {
            // require mustache class
            require(["mustache"], function(Mustache) {
                $.ajax({
                    type: "GET",
                    url: "./landing/getUserDetails/",
                    // async: false,
                    success: function(data, textStatus, jqXHR) {
                        // get user date for controls
                        userData = data;
                        current_user = userData.user;

                        if (current_user[0].clock_in == null || current_user[0].clock_in == "0000-00-00 00:00:00") {
                            isClockin = false;
                        } else isClockin = true;
                        
                        if (callback != undefined) {
                            callback();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("The following error occured: " + textStatus, errorThrown);
                        return false;
                    },
                    dataType: "json"
                });
            });
        }

        // method that loads templates
        function loadTemplate(callback) {
            // get request for user controls templates
            $.get('inc/js/templates/controls.mustache', function(data) {
                // here we go the data, this is what we need
                template = data;
            });

            // get request for footer template
            $.get('inc/js/templates/footer.mustache', function(data) {
                // here we go the data, this is what we need
                footerTemplate = data;
            });

            // callback after all
            callback();
        }

        // method that handle all events within this class
        function attachHandlers() {
            // event that check if the hash is changed
            $(window).on("hashchange", function() {
                isSurveyBusy(function(busy) {
                    // console.log(busy);
                    if (!busy) {
                        // get the hash without #
                        var hash = window.location.hash.substring(1);

                        // call the method that swipe through pages
                        slidingNavigation.loadSlide(hash);
                    } else {
                        window.location.hash = index;
                    }
                });
            });

        }

        // method that loads all the html files for all swipe pages and also do the animation stuff
        function loadFile(file) {
            // check if file is not set then do nothing
            if (!file) {
                return;
            }

            // get the current hash
            var currentIndex = index.split("-")[0];

            // page
            var page;

            // current page
            var current;

            // slide type for animations (left or right)
            var slidetype;

        }

        // method that changes the url with HTML5 feature history pushState
        function changeurl() {
            // if it's okay
            if (window.history.pushState) {
                // change the hash
                window.history.pushState(index, index, "#" + index);
            }
        }

        // method that loads the files
        function updateNavigation() {

            // get the file name
            var file = index.split("-")[0];

            if (file == "logout") {
                return;
            }

            // load the current file and do the magic with animations
            loadFile(file);
        }

        // attach all current handlers
        function attachNewHandlers() {
            // get the current hash
            var module = index.split("-");

            // switch through pages
            switch (module[0]) {
                case "landing":
                require(["app/landing-controller"], function(landingCtrl) {
                        showLoader(false);
                        landingCtrl.init(userData);
                    });
                    break;
                case "clockin":
                require(["app/clockin-controller"], function(clockinCtrl) {
                        showLoader(false);
                        clockinCtrl.init(userData);
                    });
                    break;
                case "mainmenu":
                require(["app/mainmenu-controller"], function(mainmenuCtrl) {
                        showLoader(false);
                        mainmenuCtrl.init(userData);
                    });
                    break;
            }
        }

        // destroy all the current handlers
        function destroyCurrentHandlers() {
            // get the current hash
            var module = index.split("-");

            // in case of logout destroy all modules.

            // switch through pages         
            switch (module[0]) {
                case "landing":
                    require(["app/landing-controller"], function(landingCtrl) {
                        landingCtrl.destroy();
                    });
                    break;
                case "clockin":
                    require(["app/clockin-controller"], function(clockinCtrl) {
                        clockinCtrl.destroy();
                    });
                    break;
                case "mainmenu":
                    require(["app/mainmenu-controller"], function(mainmenuCtrl) {
                        mainmenuCtrl.destroy();
                    });
                    break;
            }

            // $("body").off('click', "#contact"); //EMC:10142014
            // $("body").off('click', "#submit_contact");
        }

        // method that checks if the user is logged in or not
        function isLoggedIn(callback, params) {
            // request php, check if the user is logged in.
            $.ajax({
                type: "POST",
                url: "./auth/",
                // async: false,
                success: function(data, textStatus, jqXHR) {
                    // allow these params without being logged in
                    if (params == "password" || params == "forgot") {
                        callback(params, true);
                    } else {
                        loadUser(function() {
                            callback(params, data.logged_in);
                        })
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("The following error occured: " + textStatus, errorThrown);
                    return false;
                },
                dataType: "json"
            });
        }

        // method that returns if a string has numbers
        function hasNumber(s) {
            return /\d/.test(s);
        }

        // method for checking if the hash exists
        function controlHash(hash) {
            // available pages/existing pages
            var pages = ['landing', 'clockin', 'mainmenu'];

            // includes dash
            var dash = hash.indexOf("-") != -1;

            // get the splitted hash
            var splitted_hash = hash.split("-");

            // get the rest
            var the_id = splitted_hash[1];

            // check if only contains number
            var isNumber = /^\d+$/.test(the_id);

            // includes numbers
            var number = hasNumber(hash);

            // looping through pages
            for (var i = 0; i < pages.length; i++) {
                if (hash.indexOf(pages[i]) != -1) {
                    return true;
                }
            }

            // return
            return false;
        }

        // method tha returns alpha
        function isAlphanumeric(str) {
            return /^[0-9a-zA-Z]+$/.test(str);
        }

        function loadSlideCallback(hash, logged_in) {
            // set indicator to true at first
            // indicator(true, hash);

            // check if the hash is undefined then send it to login
            if (typeof hash == 'undefined') {
                hash = 'landing';
            }

            // check if the user is not logged in then send it to login agan, overwise send it to surveys
            if (!logged_in) {
                hash = "landing";
            } else if (logged_in && hash == "landing" || !controlHash(hash)) { // || !controlHash(hash)

                if (isClockin) {
                    hash = "mainmenu";
                } else {
                    hash = "clockin";
                }
            }

            // we call this index variable in some methods, this is actually the hash
            index = hash;

            // call the method that destroy the handlers
            destroyCurrentHandlers();

            // call the method that attach again the handlers
            attachNewHandlers();

            // call the method that changes the url with push state
            changeurl();


        }

        return {
            // property that controls if the user is logged in or not
            loggedIn: true,

            // method that initialize all the stuff behind the scene
            init: function() {

                showLoader(true);

                // attach all the current handlers
                attachHandlers();

                // the hash
                var hash;

                // check if it's okay with the hash
                if (window.location.hash) {
                    // get the hash
                    hash = window.location.hash.substring(1);
                }

                // swipe to current page
                slidingNavigation.loadSlide(hash);

            },

            // method that loads the slides
            loadSlide: function(hash) {

                showLoader(true);
                isLoggedIn(loadSlideCallback, hash);
            },

            // method that swipes through pages
            where: function(id) {

                showLoader(true);
                isLoggedIn(loadSlideCallback, id);
            }

        };



        // helpers
        function isSurveyBusy(callback) {

            var currentIndex = index.split("-")[0];
            if (currentIndex == "survey") {
                require(["app/survey"], function(survey) {
                    callback(survey.changesState());
                });
            } else {
                callback(false);
            }
        }

    })();

    return slidingNavigation;

})