<!DOCTYPE html>
<html>
	<head>
		<title><?= $this->title((!empty($project))? $project['title'] : 'C3 Ticket Tracker'); ?></title>
		<meta charset="utf-8" />
		
		<base href="<?= $this->Request->getRootURL(); ?>" />
		<link rel="shortcut icon" type="image/x-icon" href="<?= $this->Request->getRootURL(); ?>favicon.ico" />
		<link rel="stylesheet" href="<?= $this->Request->getRootURL(); ?>css/main.css" type="text/css" />
		<?= $this->content('stylesheets'); ?>
	</head>
	<body>
		<div id="projects">
			<ul class="horizontal">
				<?php if (User::isAllowed('projects', 'index')): ?>
					<li class="link<?= ($arguments['controller'] == 'projects' and $arguments['action'] == 'index')? ' current' : ''; ?>"><?= $this->linkTo('projects', 'index', 'All projects'); ?></li>
				<?php endif; ?>
				
				<?php if (isset($project)): ?>
					<li class="padding">›</li>
					<?php /* ($arguments['controller'] != 'projects' or $arguments['action'] != 'view')? ' current' : ''; */ ?>
					<li class="link current">
						<?php echo $this->linkTo('tickets', 'feed', $project, $project['title']);
						
						if (User::isAllowed('projects', 'edit')) {
							echo ' ' . $this->linkTo('projects', 'view', $project, '(settings)');
						} ?>
					</li>
				<?php endif; ?>
				
			
				<?php if (User::isLoggedIn()): ?>
					<li class="right link"><?= $this->linkTo('user', 'logout', 'Logout'); ?></li>
					<li class="right padding">·</li>
					<li class="right link"><?= $this->linkTo('user', 'settings', 'Settings'); ?></li>
					<li class="right padding">·</li>
					<li class="right text">
						Signed in as <strong><?= User::getCurrent()['name']; ?></strong>
						<?php /*if ($this->User->isSubstitute()) {
							echo '(' . $this->linkTo('user', 'changeback', 'leave') . ')';
						}*/ ?>
					</li>
				<?php else: ?>
					<li class="right link"><?= $this->linkTo('user', 'login', 'Login'); ?></li>
				<?php endif; ?>
			</ul>
			<script type="text/javascript" charset="utf-8">
				var Tracker = {User: {}, Search: {}};
				
				if (document.cookie.indexOf('p=0') != -1) {
					document.getElementsByTagName('body')[0].className = 'full';
					document.getElementById('projects').getElementsByTagName('ul')[0].style.display = 'none';
				}
			</script>
		</div>
		<?php if (User::isLoggedIn()): ?>
			<noscript>
				<div class="noscript-warning">Please enable javascript, some editing forms on this site don't work without.</div>
			</noscript>
		<?php endif; ?>
		<div id="header">
			<h1><?php if (!empty($project['project_slug'])) {
				echo $this->linkTo('tickets', 'feed', $project/* + (($referer = Request::get('ref') and $this->isValidReferer($referer))? array('?t=' . $referer) : array())*/, 'C3 Ticket Tracker');
			} else {
				echo $this->linkTo('projects', 'index', 'C3 Ticket Tracker');
			}?></h1>
			
			<?php if (empty($project['project_slug'])): ?>
				<?php if (($arguments['controller'] == 'projects' and $arguments['action'] == 'index') or $arguments['controller'] == 'encodingprofiles' or $arguments['controller'] == 'workers' or ($arguments['controller'] == 'user' and $arguments['action'] != 'settings' and $arguments['action'] != 'login')): ?>
					<ul id="menu" class="horizontal">
						<li id="menu-background-left"></li>
					
						<?php if (User::isAllowed('projects', 'index')): ?>
							<li class="first menu-projects <?= (($arguments['controller'] == 'projects' and $arguments['action'] == 'index')? ' current' : ''); ?>">
								<?= $this->linkTo('projects', 'index', '<span>Projects</span>', 'Projects'); ?>
							</li>
						<?php endif; ?>
						<?php if (User::isAllowed('encodingprofiles', 'index')): ?>
							<li class="menu-encodingprofiles <?= (($arguments['controller'] == 'encodingprofiles')? ' current' : ''); ?>">
								<?= $this->linkTo('encodingprofiles', 'index', '<span>Encoding profiles</span>', 'Encoding profiles'); ?>
							</li>
						<?php endif; ?>
						<?php if (User::isAllowed('workers', 'index')): ?>
							<li class="last menu-services <?= (($arguments['controller'] == 'workers')? ' current' : ''); ?>">
								<?= $this->linkTo('workers', 'index', '<span>Workers</span>', 'Workers'); ?>
							</li>
						<?php endif; ?>
						<?php if (User::isAllowed('user', 'index')): ?>
							<li class="last menu-users <?= (($arguments['controller'] == 'user')? ' current' : ''); ?>">
								<?= $this->linkTo('user', 'index', '<span>Users</span>', 'Users'); ?>
							</li>
						<?php endif; ?>
						
						<li id="menu-background-right"></li>
					</ul>
				<?php endif; ?>
			<?php else: ?>
				<ul id="menu" class="horizontal">
					<li id="menu-background-left"></li>
					
					<?php if (User::isAllowed('tickets', 'feed')): ?>
						<li class="first menu-feed <?= (($arguments['controller'] == 'tickets' and $arguments['action'] == 'feed')? ' current' : ''); ?>">
							<?= $this->linkTo('tickets', 'feed', $project, '<span>Feed</span>', 'Feed'); ?>
						</li>
					<?php endif; ?>
					<?php if (User::isAllowed('tickets', 'index')): ?>
						<li class="menu-tickets <?= ((($arguments['controller'] == 'tickets' and $arguments['action'] != 'feed') or $arguments['controller'] == 'import' or $arguments['controller'] == 'export')? ' current' : ''); ?>">
							<?= $this->linkTo('tickets', 'index', $project/* + (($referer = Request::get('ref') and $this->isValidReferer($referer))? array('?t=' . $referer) : array())*/, '<span>Tickets</span>', 'Feed'); ?>
						</li>
					<?php endif; ?>
					<?php // TODO: rename to jobs
					/*if (User::isAllowed('services', 'workers')): ?>
						<li class="menu-services <?= (($arguments['controller'] == 'workers' and $arguments['action'] == 'project')? ' current' : ''); ?>">
							<?= $this->linkTo('workers', 'project', $project, '<span>Workers</span>', 'Workers') ?>
						</li>
					<?php endif;*/ ?>
					
					<?php if (User::isAllowed('projects', 'view')): ?>
						<li class="last menu-project <?= (($arguments['controller'] == 'projects')? ' current' : ''); ?>">
							<?= $this->linkTo('projects', 'view', $project, '<span>Settings</span>', 'Settings') ?>
						</li>
					<?php endif; ?>
				
					<li id="menu-background-right"></li>
				</ul>
			<?php endif; ?>
		</div>
		<div id="content" class="clearfix">
			<?php /*if (empty($flash)) {
				if ($flash = Model::getValidationErrors() and !empty($flash)) {
					$flash = array_slice($flash, -1);
					$flash = end($flash);
					$flash = array(array('type' => 2, 'text' => mb_ucfirst(current($flash))));
				}
			}*/
		
			if (!empty($flash)):
				$flash = array_slice($flash, -1); ?>
				<div id="flash">
					<?= $this->h($flash[0]['message']); ?>
				</div>
			<?php endif; ?>
			<?= $this->content(); ?>
		</div>
		
		<script src="<?= $this->Request->getRootURL(); ?>javascript/jquery-2.1.0.min.js" type="text/javascript"></script>
		<script src="<?= $this->Request->getRootURL(); ?>javascript/jquery.cookie.min.js" type="text/javascript"></script>
		<?php if (User::isLoggedIn()): ?>
			<script type="text/javascript">
				Tracker.User.data = <?= json_encode(array('id' => User::getCurrent()['id'], 'name' => User::getCurrent()['name'])); ?>;
			</script>
		<?php endif; ?>
		<?= $this->content('scripts'); ?>
		<script src="<?= $this->Request->getRootURL(); ?>javascript/main.js" type="text/javascript"></script>
	</body>
</html>