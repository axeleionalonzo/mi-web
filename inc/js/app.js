// filename: app.js

// require.js configuration files
require.config({
	baseUrl: 'inc/js',
	paths: {
		// libraries
		'materialize': 'libs/materialize.amd',
		'jquery': 'libs/jquery-2.1.1.min',
		'jqueryui': 'libs/jquery-ui.min',
		'jquerythde': 'libs/jquery.ba-throttle-debounce.min',
		'moment': 'libs/moment.min',
		'mustache': 'libs/mustache.min',
		'bootstrap_sidenav': '../plugins/startbootstrap-simple-sidebar-1.0.5/js/bootstrap.min',
		'sortable': '../plugins/Sortable-master/sortable'

		// materialize components start
		// 'hammerjs': '../frameworks/materialize/js/components/hammer.min',
		// 'jquery.easing': '../frameworks/materialize/js/components/jquery.easing.1.3',
		// 'velocity': '../frameworks/materialize/js/components/velocity.min',
		// 'picker': '../frameworks/materialize/js/components/date_picker/picker',
		// 'picker.date': '../frameworks/materialize/js/components/date_picker/picker.date',
		// 'waves': '../frameworks/materialize/js/components/waves',
		// 'global': '../frameworks/materialize/js/components/global',
		// 'animation': '../frameworks/materialize/js/components/animation',
		// 'collapsible': '../frameworks/materialize/js/components/collapsible',
		// 'dropdown': '../frameworks/materialize/js/components/dropdown',
		// 'leanModal': '../frameworks/materialize/js/components/leanModal',
		// 'materialbox': '../frameworks/materialize/js/components/materialbox',
		// 'tabs': '../frameworks/materialize/js/components/tabs',
		// 'sideNav': '../frameworks/materialize/js/components/sideNav',
		// 'parallax': '../frameworks/materialize/js/components/parallax',
		// 'scrollspy': '../frameworks/materialize/js/components/scrollspy',
		// 'tooltip': '../frameworks/materialize/js/components/tooltip',
		// 'slider': '../frameworks/materialize/js/components/slider',
		// 'cards': '../frameworks/materialize/js/components/cards',
		// 'buttons': '../frameworks/materialize/js/components/buttons',
		// 'pushpin': '../frameworks/materialize/js/components/pushpin',
		// 'character_counter': '../frameworks/materialize/js/components/character_counter',
		// 'toasts': '../frameworks/materialize/js/components/toasts',
		// 'forms': '../frameworks/materialize/js/components/forms',
		// 'scrollFire': '../frameworks/materialize/js/components/scrollFire',
		// 'transitions': '../frameworks/materialize/js/components/transitions',
		// 'jquery.hammer': '../frameworks/materialize/js/components/jquery.hammer'
		// materialize components end
	},
	shim: {
		materialize: {
			deps: ['jquery']
		},
		jqueryui: {
			deps: ['jquery']
		},
		jquerythde: {
			deps: ['jquery', 'jqueryui']
		},
		bootstrap_sidenav: {
			deps: ['jquery']
		},
		sortable: {
			deps: ['jquery']
		}
	}
});

// initialize module
require(['jquery', 'materialize', 'jqueryui', 'jquerythde', 'bootstrap_sidenav', 'sortable'], function($) {
	$(function() {

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
		$('.collapsible').collapsible();
		$('select').material_select();

		require(['app/page-controller'], function(sliding) {
			sliding.init();
		});
		
		// require(['app/landing-controller'], function(slider) {
		// 	slider.init();
		// });
	});
});