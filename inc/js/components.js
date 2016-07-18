// require.js configuration files
require.config({
	baseUrl: 'inc/js',
	paths: {
		// libraries
		'jquery': 'libs/jquery-2.1.1.min',
		'jqueryui': 'libs/jquery-ui.min',
		'hammerjs': '../frameworks/materialize/js/components/hammer.min',
        'jquery.easing': '../frameworks/materialize/js/components/jquery.easing.1.3',
        'velocity': '../frameworks/materialize/js/components/velocity.min',
        'picker': '../frameworks/materialize/js/components/date_picker/picker',
        'picker.date': '../frameworks/materialize/js/components/date_picker/picker.date',
        'waves': '../frameworks/materialize/js/components/waves',
        'global': '../frameworks/materialize/js/components/global',
        'animation': '../frameworks/materialize/js/components/animation',
        'collapsible': '../frameworks/materialize/js/components/collapsible',
        'dropdown': '../frameworks/materialize/js/components/dropdown',
        'leanModal': '../frameworks/materialize/js/components/leanModal',
        'materialbox': '../frameworks/materialize/js/components/materialbox',
        'tabs': '../frameworks/materialize/js/components/tabs',
        'sideNav': '../frameworks/materialize/js/components/sideNav',
        'parallax': '../frameworks/materialize/js/components/parallax',
        'scrollspy': '../frameworks/materialize/js/components/scrollspy',
        'tooltip': '../frameworks/materialize/js/components/tooltip',
        'slider': '../frameworks/materialize/js/components/slider',
        'cards': '../frameworks/materialize/js/components/cards',
        'buttons': '../frameworks/materialize/js/components/buttons',
        'pushpin': '../frameworks/materialize/js/components/pushpin',
        'character_counter': '../frameworks/materialize/js/components/character_counter',
        'toasts': '../frameworks/materialize/js/components/toasts',
        'forms': '../frameworks/materialize/js/components/forms',
        'scrollFire': '../frameworks/materialize/js/components/scrollFire',
        'transitions': '../frameworks/materialize/js/components/transitions',
        'jquery.hammer': '../frameworks/materialize/js/components/jquery.hammer',
		'materializeinit': '../frameworks/materialize/js/init'
	},
	shim: {

		'jquery.easing': {
            deps: ['jquery']
        },
        'animation': {
            deps: ['jquery']
        },
        'jquery.hammer': {
            deps: ['jquery', 'hammerjs', 'waves']
        },
        'global': {
            deps: ['jquery']
        },
        'toasts': {
            deps: ['global']
        },
        'collapsible': {
            deps: ['jquery']
        },
        'dropdown': {
            deps: ['jquery']
        },
        'leanModal': {
            deps: ['jquery']
        },
        'materialbox': {
            deps: ['jquery']
        },
        'parallax': {
            deps: ['jquery']
        },
        'tabs': {
            deps: ['jquery']
        },
        'tooltip': {
            deps: ['jquery']
        },
        'sideNav': {
            deps: ['jquery']
        },
        'scrollspy': {
            deps: ['jquery']
        },
        'forms': {
            deps: ['jquery', 'global']
        },
        'slider': {
            deps: ['jquery']
        },
        'cards': {
            deps: ['jquery']
        },
        'pushpin': {
            deps: ['jquery']
        },
        'buttons': {
            deps: ['jquery']
        },
        'transitions': {
            deps: ['jquery','scrollFire']
        },
        'scrollFire': {
            deps: ['jquery', 'global']
        },
        'waves': {
            exports: 'Waves'
        },
        'character_counter': {
            deps: ['jquery']
        }
	},

	deps: ['jquery']
});

// initialize module
require([
	'jquery.easing',
    'animation',
    'velocity',
    'hammerjs',
    'jquery.hammer',
    'global', // very important do not remove!
    'collapsible',
    'dropdown',
    'leanModal',
    'materialbox',
    'parallax',
    'tabs',
    'tooltip',
    'waves',
    'toasts',
    'sideNav',
    'scrollspy',
    'forms',
    'slider',
    'cards',
    'pushpin',
    'buttons',
    'scrollFire',
    'transitions',
    'picker',
    'picker.date',
    'character_counter'
    ], function() {
		$(function() {
			require(['app/landing-controller'], function(landing) {
				landing.start();
			});
		});
});