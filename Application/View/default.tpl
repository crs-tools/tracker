<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $this->title((!empty($project))? $project['title'] : 'C3 Ticket Tracker'); ?></title>
		<meta charset="utf-8" />
		
		<base href="<?php echo $this->Request->getRootURL(); ?>" />
		<link rel="shortcut icon" type="image/x-icon" href="<?php echo $this->Request->getRootURL(); ?>favicon.ico" />
		<link rel="stylesheet" href="<?= $this->Request->getRootURL(); ?>css/main.css" type="text/css" />
		<?php echo $this->content('stylesheets'); ?>
	</head>
	<body>
		<div id="projects">
			<ul class="horizontal">
				<?php if (!empty($projects) and User::isAllowed('projects', 'index')): ?>
					<li class="link<?php echo ($arguments['controller'] == 'projects' and $arguments['action'] == 'index')? ' current' : ''; ?>"><?php echo $this->linkTo('projects', 'index', 'All projects'); ?></li>
					
					<?php if (isset($project)): ?>
						<li class="padding">›</li>
						<li class="link current">
							<?php echo $this->linkTo('tickets', 'feed', $project, $project['title']);
							
							if (User::isAllowed('projects', 'edit')) {
								echo ' ' . $this->linkTo('projects', 'edit', $project, '(settings)');
							} ?>
						</li>
					<?php endif; ?>
				<?php endif; ?>
			
				<?php if (User::isLoggedIn()): ?>
					<li class="right link"><?php echo $this->linkTo('user', 'logout', 'Logout'); ?></li>
					<li class="right padding">·</li>
					<li class="right link"><?php echo $this->linkTo('user', 'settings', 'Settings'); ?></li>
					<li class="right padding">·</li>
					<li class="right text">
						Signed in as <strong><?php echo User::getCurrent()['name']; ?></strong>
						<?php /*if ($this->User->isSubstitute()) {
							echo '(' . $this->linkTo('user', 'changeback', 'leave') . ')';
						}*/ ?>
					</li>
					<?php if (User::isAllowed('user', 'index')): ?>
						<li class="right padding">·</li>
						<li class="right link"><?php echo $this->linkTo('user', 'index', 'Manage users'); ?></li>
					<?php endif; ?>
				<?php else: ?>
					<li class="right link"><?php echo $this->linkTo('user', 'login', 'Login'); ?></li>
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
		<div id="header">
			<h1><?php if (!empty($project['project_slug'])) {
				echo $this->linkTo('tickets', 'feed', $project/* + (($referer = Request::get('ref') and $this->isValidReferer($referer))? array('?t=' . $referer) : array())*/, 'C3 Ticket Tracker');
			} else {
				echo $this->linkTo('projects', 'index', 'C3 Ticket Tracker');
			}?></h1>
			
			<?php if (empty($project['project_slug'])): ?>
				<?php if (($arguments['controller'] == 'projects' and $arguments['action'] == 'index') or $arguments['controller'] == 'encodingprofiles' or $arguments['controller'] == 'workers'): ?>
					<ul id="menu" class="horizontal">
						<li id="menu-background-left"></li>
					
						<?php if (User::isAllowed('projects', 'index')): ?>
							<li class="first menu-projects <?php echo (($arguments['controller'] == 'projects' and $arguments['action'] == 'index')? ' current' : ''); ?>">
								<?php echo $this->linkTo('projects', 'index', '<span>Projects</span>', 'Projects'); ?>
							</li>
						<?php endif; ?>
						<?php if (User::isAllowed('encodingsprofiles', 'index')): ?>
							<li class="menu-encodingprofiles <?php echo (($arguments['controller'] == 'encodingprofiles')? ' current' : ''); ?>">
								<?php echo $this->linkTo('encodingprofiles', 'index', '<span>Encoding profiles</span>', 'Encoding profiles'); ?>
							</li>
						<?php endif; ?>
						<?php if (User::isAllowed('workers', 'index')): ?>
							<li class="last menu-services <?php echo (($arguments['controller'] == 'workers')? ' current' : ''); ?>">
								<?php echo $this->linkTo('workers', 'index', '<span>Workers</span>', 'Workers'); ?>
							</li>
						<?php endif; ?>
						
						<li id="menu-background-right"></li>
					</ul>
				<?php endif; ?>
			<?php else: ?>
				<ul id="menu" class="horizontal">
					<li id="menu-background-left"></li>
					
					<?php if (User::isAllowed('tickets', 'feed')): ?>
						<li class="first menu-feed <?php echo (($arguments['controller'] == 'tickets' and $arguments['action'] == 'feed')? ' current' : ''); ?>">
							<?php echo $this->linkTo('tickets', 'feed', $project, '<span>Feed</span>', 'Feed'); ?>
						</li>
					<?php endif; ?>
					<?php if (User::isAllowed('tickets', 'index')): ?>
						<li class="menu-tickets <?php echo ((($arguments['controller'] == 'tickets' and $arguments['action'] != 'feed') or $arguments['action'] == 'import' or $arguments['action'] == 'export')? ' current' : ''); ?>">
							<?php echo $this->linkTo('tickets', 'index', $project/* + (($referer = Request::get('ref') and $this->isValidReferer($referer))? array('?t=' . $referer) : array())*/, '<span>Tickets</span>', 'Feed'); ?>
						</li>
					<?php endif; ?>
					<?php // TODO: rename to jobs
					/*if (User::isAllowed('services', 'workers')): ?>
						<li class="menu-services <?php echo (($arguments['controller'] == 'workers' and $arguments['action'] == 'project')? ' current' : ''); ?>">
							<?php echo $this->linkTo('workers', 'project', $project, '<span>Workers</span>', 'Workers') ?>
						</li>
					<?php endif;*/ ?>
					
					<?php if (User::isAllowed('project', 'view')): ?>
						<li class="last menu-project <?php echo (($arguments['controller'] == 'projects')? ' current' : ''); ?>">
							<?php echo $this->linkTo('projects', 'view', $project, '<span>Settings</span>', 'Settings') ?>
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
					<?php echo $this->h($flash[0]['message']); ?>
				</div>
			<?php endif; ?>
			<?php echo $this->content(); ?>
		</div>
		
		<script src="<?php echo $this->Request->getRootURL(); ?>javascript/jquery-1.7.min.js" type="text/javascript"></script>
		<script src="<?php echo $this->Request->getRootURL(); ?>javascript/jquery.cookie.min.js" type="text/javascript"></script>
		<?php if (User::isLoggedIn()): ?>
			<script type="text/javascript">
				Tracker.User.data = <?php echo json_encode(array('id' => User::getCurrent()['id'], 'name' => User::getCurrent()['name'])); ?>;
			</script>
		<?php endif; ?>
		<?php echo $this->content('scripts'); ?>
		<script src="<?php echo $this->Request->getRootURL(); ?>javascript/main.js" type="text/javascript"></script>
	</body>
</html>