<?php $this->layout(false); ?>
<!DOCTYPE html>
<html>
	<head>
		<title>C3 Ticket Tracker</title>
		<meta charset="utf-8" />
		
		<link rel="stylesheet" href="/css/main.css" type="text/css" />
	</head>
	<body>
		<div id="projects">
			<ul class="horizontal">
				
			</ul>
		</div>
		<div id="header">
			<h1><?= $this->a('/', 'C3 Ticket Tracker'); ?></h1>
		</div>
		<div id="content" class="clearfix">
			<h2>Server error</h2>
			<h3><?php echo get_class($exception); ?></h3>
			<p><?= $exception->getMessage(); ?></p>
		</div>
	</body>
</html>