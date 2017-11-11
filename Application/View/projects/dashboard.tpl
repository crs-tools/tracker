<?php $this->layout(false); ?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $this->title((!empty($project))? 'Dashboard | ' . $project['title'] : 'C3 Ticket Tracker'); ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<base href="<?php echo Uri::getBaseUrl(); ?>" />
		<link rel="shortcut icon" type="image/x-icon" href="<?php echo Uri::getBaseUrl(); ?>favicon.ico" />
		<link rel="stylesheet" href="<?php echo Uri::getBaseUrl(); ?>css/dashboard.css" type="text/css" />
	</head>
	<body>
		<div class="actions row clearfix">
			<div class="column column-33">
				<div class="action">
					<div id="action-count-cutting" class="action-count">–</div>
					<div class="action-description">recording tasks to cut</div>
				</div>
			</div>
			<div class="column column-33">
				<div class="action">
					<div id="action-count-checking" class="action-count">–</div>
					<div class="action-description">encoding tasks to check</div>
				</div>
			</div>
			<div class="column column-33">
				<div class="action">
					<div id="action-count-fixing" class="action-count">–</div>
					<div class="action-description">tickets to fix</div>
				</div>
			</div>
		</div>
		<div class="contributors row clearfix">
			<div class="column column-33 contributor"><span id="contributor-cutting">–</span></div>
			<div class="column column-33 contributor"><span id="contributor-checking">–</span></div>
			<div class="column column-33 contributor"><span id="contributor-fixing">–</span></div>
		</div>	
		<div class="contributors row">
			<div class="contributors-description">Top contributors</div>
			<div id="contributors-description-line-left-up" class="contributors-description-line"></div>
			<div id="contributors-description-line-left" class="contributors-description-line"></div>
			<div id="contributors-description-line-center-up" class="contributors-description-line"></div>
			<div id="contributors-description-line-right" class="contributors-description-line"></div>
			<div id="contributors-description-line-right-up" class="contributors-description-line"></div>
		</div>
		
		<div class="viewer-total row clearfix">
			<div class="column column-33">
				<div class="viewer-total-room">
					<span id="viewer-total-room-1">–</span><!-- class="viewer-total-up" -->
				</div>
			</div>
			<div class="column column-33">
				<div class="viewer-total-room">
					<span id="viewer-total-room-2">–</span><!-- class="viewer-total-down" -->
				</div>
			</div>
			<div class="column column-33">
				<div class="viewer-total-room">
					<span id="viewer-total-room-3">–</span>
				</div>
			</div>
		</div>
		
		<!--
		<div class="viewer row clearfix">
			<div class="column column-33">
				<div class="viewer-room">
					<div class="column column-33">
						<div class="viewer-room-stream viewer-room-windows" id="viewer-room-1-windows">–</div>
					</div>
					<div class="column column-33">
						<div class="viewer-room-stream viewer-room-ffmpeg" id="viewer-room-1-ffmpeg">–</div>
					</div>
					<div class="column column-33">
						<div class="viewer-room-stream viewer-room-slides" id="viewer-room-1-slides">–</div>
					</div>
				</div>
			</div>
			<div class="column column-33">
				<div class="viewer-room">
					<div class="column column-33">
						<div class="viewer-room-stream viewer-room-windows" id="viewer-room-2-windows">–</div>
					</div>
					<div class="column column-33">
						<div class="viewer-room-stream viewer-room-ffmpeg" id="viewer-room-2-ffmpeg">–</div>
					</div>
					<div class="column column-33">
						<div class="viewer-room-stream viewer-room-slides" id="viewer-room-2-slides">–</div>
					</div>
				</div>
			</div>
			<div class="column column-33">
				<div class="viewer-room">
					<div class="column column-33">
						<div class="viewer-room-stream viewer-room-windows" id="viewer-room-3-windows">–</div>
					</div>
					<div class="column column-33">
						<div class="viewer-room-stream viewer-room-ffmpeg" id="viewer-room-3-ffmpeg">–</div>
					</div>
					<div class="column column-33">
						<div class="viewer-room-stream viewer-room-slides" id="viewer-room-3-slides">–</div>
					</div>
				</div>
			</div>
		</div>
		-->
		
		<div class="schedule row clearfix">
			<div class="column column-33" id="schedule-room-1">
				<div class="schedule-entry" data-start="12:45" data-end="13:45"><span>Copyright Enforcement Vs. Freedoms</span></div>
				<!-- <div class="schedule-entry" data-start="14:00" data-end="15:00"><span>Von Zensursula über Censilia hin zum Kindernet</span></div> -->
				<div class="schedule-entry"><span></span></div>
				<div class="schedule-entry" data-start="16:00" data-end="17:00"><span>Whistleblowing</span></div>
			</div>
			<div class="column column-33" id="schedule-room-2">
				<div class="schedule-entry" data-start="12:45" data-end="13:45"><span>Code deobfuscation by optimization</span></div>
				<div class="schedule-entry" data-start="14:00" data-end="15:00"><span>Contemporary Profiling of Web Users</span></div>
				<div class="schedule-entry" data-start="16:00" data-end="17:00"><span>Eins, zwei, drei - alle sind dabei</span></div>
			</div>
			<div class="column column-33" id="schedule-room-3">
				<div class="schedule-entry" data-start="12:45" data-end="13:45"><span>From robot to robot</span></div>
				<div class="schedule-entry" data-start="14:00" data-end="15:00"><span>JTAG/Serial/FLASH/PCB Embedded…and Techniques</span></div>
				<div class="schedule-entry" data-start="16:00" data-end="17:00"><span>Automatic Identification of Cryptographic…in Software</span></div>
			</div>
		</div>
		
		<div class="mentions row">
			<div class="mentions-title">@c3streaming</div>
			<ul id="mentions-stripe">
			</ul>
		</div>
		
		<script src="<?php echo Uri::getBaseUrl(); ?>javascript/jquery-1.7.min.js" type="text/javascript"></script>
		<script src="<?php echo Uri::getBaseUrl(); ?>javascript/dashboard.js" type="text/javascript"></script>
	</body>
</html>