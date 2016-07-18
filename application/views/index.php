<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no"/>
		<meta name="apple-mobile-web-app-capable" content="yes" />
		
		<title>Market Intel</title>
		<!-- CSS  -->
		<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
		<link href='https://fonts.googleapis.com/css?family=Roboto:400,100,300|Roboto+Condensed|Roboto+Mono|Roboto+Slab' rel='stylesheet' type='text/css'>
		<link href="./inc/plugins/startbootstrap-simple-sidebar-1.0.5/css/simple-sidebar.css" type="text/css" rel="stylesheet">
		<link href="./inc/frameworks/materialize/css/materialize.css" type="text/css" rel="stylesheet" media="screen,projection"/>
		<link href="./inc/frameworks/style.css" type="text/css" rel="stylesheet" media="screen,projection"/>
		<!-- sidebar plugin -->
		<!-- <link href="./inc/frameworks/CSS tooltips/reset.css" type="text/css" rel="stylesheet" media="screen,projection"/> -->
		<!-- <link href="./inc/frameworks/CSS tooltips/style.css" type="text/css" rel="stylesheet" media="screen,projection"/> -->
		<!-- FAVICON -->
		<link rel="shortcut icon" href="./inc/favicon.ico">
	</head>
	<body>
		<div id="general_loading" class="loading-bg">
			<div class="preloader-wrapper big active global-centered">
				<div class="spinner-layer spinner-blue">
					<div class="circle-clipper left">
						<div class="circle"></div>
					</div>
					<div class="gap-patch">
						<div class="circle"></div>
					</div>
					<div class="circle-clipper right">
						<div class="circle"></div>
					</div>
				</div>

				<div class="spinner-layer spinner-red">
					<div class="circle-clipper left">
						<div class="circle"></div>
					</div>
					<div class="gap-patch">
						<div class="circle"></div>
					</div>
					<div class="circle-clipper right">
						<div class="circle"></div>
					</div>
				</div>

				<div class="spinner-layer spinner-yellow">
					<div class="circle-clipper left">
						<div class="circle"></div>
					</div>
					<div class="gap-patch">
						<div class="circle"></div>
					</div>
					<div class="circle-clipper right">
						<div class="circle"></div>
					</div>
				</div>

				<div class="spinner-layer spinner-green">
					<div class="circle-clipper left">
						<div class="circle"></div>
					</div>
					<div class="gap-patch">
						<div class="circle"></div>
					</div>
					<div class="circle-clipper right">
						<div class="circle"></div>
					</div>
				</div>
			</div>
		</div>
		

		<div id="clockin-container" class="app-container">
			<div class="section no-pad-bot" id="index-banner">
				<div class="container header-container col s12 m8 l4 offset-l4 offset-m2">
					<h1 class="center header-text amber-text text-accent-2"><span class="amber-text text-darken-3">Market</span> <span class="amber-text text-darken-1">Intel</span></h1>
					<!-- <img class="header-logo center-image" src="./inc/images/MI_logo_v2.6.2.png"> -->
					<div class="landing_direction-holder">
						<p id="landing_direction" class="center grey-text text-darken-2"></p>
					</div>
				</div>
			</div>
			<div class="container">
				<div class="row">
					<div class="col s12 m8 l4 offset-l4 offset-m2">
						<div class="card">
							<div class="card-content">
								<i id="landing-back" class="material-icons small up">chevron_left</i>
								<img class="landing-logo center-image" src="./inc/images/MI_logo_v2.9.1.png">
								<div class="account-label-holder">
									<p id="accountname" class="center grey-text text-darken-4 accountname"></p>
									<p id="accountemail" class="center grey-text accountemail"></p>
								</div>
							</div>
							<div id="landing-loader" class="progress white landing-loader">
								<div class="indeterminate"></div>
							</div>
							<div id="card_action" class="card-action">
								<div class="row">
									<div id="landing_email" class="col s12">
									</div>
									<div id="landing_password" class="col s12">
									</div>
									<div id="landing_clockin" class="col s12">
									</div>
								</div>
							</div>
							<div class="card-reveal">
								<span class="card-title grey-text text-darken-4">DTR entry<i class="material-icons right">close</i></span>
								<div id="dtr_entry" class="dtr_entry">
									<p id="rendered_time" class="center flow-text rendered-time">Lorem Ipsum</p>
									<p id="" class="center clockindate">Rendered Time</p>
									<table class="highlight centered">
										<thead>
											<tr>
												<th data-field="">Date</th>
												<th data-field="">Started</th>
												<th data-field="">Rounded</th>
											</tr>
										</thead>
										<tbody id="clockedinstatus">
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<p id="logout" class="amber-text text-darken-2 logout pull-right"><span id="logout_confirm" class="change_user clickable tooltipped" data-position="right" data-delay="200" data-tooltip="Not you?">change user</span></p>
					</div>
				</div>
			</div>
		</div>
		
		<div id="mainmenu-container" class="app-container">
		</div>
		
		<!-- Scripts -->
		<!-- <script src="./inc/js/libs/jquery-2.1.1.min.js"></script>
		<script src="./inc/frameworks/materialize/js/materialize.min.js"></script> -->
		<script data-main="./inc/js/app" src="./inc/js/libs/require-2.2.0.js"></script>
	</body>
</html>