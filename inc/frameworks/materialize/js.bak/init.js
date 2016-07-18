(function($){
  $(function(){

	$('.tooltipped').tooltip();
	$('.dropdown-button').dropdown();
	$('.materialboxed').materialbox();
	$('.slider').slider();
	$('.carousel').carousel();
	$('.modal-trigger').leanModal();
	$('.parallax').parallax();
	$('.tabs-wrapper .row').pushpin();
	$('ul.tabs').tabs();
	$(".button-collapse").sideNav();
	$('.collapsible').collapsible({
    	accordion : false // A setting that changes the collapsible behavior to expandable instead of the default accordion style
    });
	
  }); // end of document ready
})(jQuery); // end of jQuery name space