<?php
	
	define('ROOT', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR);
	
	define('CONFIG', ROOT . 'Config/');
	
	define('CONFIG_DEFAULT', CONFIG . 'Config.Default.php');
	define('CONFIG_FILE', CONFIG . 'Config.php');
	
	define('MIGRATIONS', ROOT . 'Application/Migrations/');
	
	define('TEMPLATE_DB_HOST', '__DB_HOST__');
	define('TEMPLATE_DB_USER', '__DB_USER__');
	define('TEMPLATE_DB_PASS', '__DB_PASS__');
	define('TEMPLATE_DB_NAME', '__DB_NAME__');
	
	
	if (file_exists(CONFIG_FILE)) {
		die("Package is already installed.\n");
	}
	
	if (!file_exists(CONFIG_DEFAULT)) {
		die("Default config missing, cannot install.\n");
	}
	
	$config = file_get_contents(CONFIG_DEFAULT);
	$stdIn = fopen('php://stdin', 'r');
	$database = [];
	
	echo "Database connection (PostgreSQL)\n";
	echo "--------------------------------\n";
	
	echo 'Host: ';
	$database['host'] = trim(fgets($stdIn));
	
	echo 'User: ';
	$database['user'] = trim(fgets($stdIn));
	
	echo 'Password: ';
	
	system('stty -echo');
	$database['pass'] = trim(fgets($stdIn));
	system('stty echo');
	
	echo "\n";
	
	echo 'Database: ';
	$database['name'] = trim(fgets($stdIn));
	
	echo "\n";
	
	echo "Trying to connect... ";
	
	try {
		$pdo = new PDO(
			'pgsql:host=' . $database['host'] . ';dbname=' . $database['name'],
			$database['user'],
			$database['pass']
		);
	} catch (PDOException $e) {
	}
	
	if (empty($pdo)) {
		echo "failed.\n";
		
		if (isset($e)) {
			echo $e->getMessage() . "\n";
		}
		
		die();
	} else {
		echo "done.\n";
	}
	
	echo "Writing config file... ";
	
	$config = str_replace([
		TEMPLATE_DB_HOST,
		TEMPLATE_DB_USER,
		TEMPLATE_DB_PASS,
		TEMPLATE_DB_NAME,
	],[
		$database['host'],
		$database['user'],
		$database['pass'],
		$database['name']
	], $config);
	
	if (!file_put_contents(CONFIG_FILE, $config)) {
		echo "failed.\n";
		echo "Cannot write config file, check permissions.\n";
		die();
	}
	
	echo "done.\n";

	
	echo "\n";
	echo "Database superuser\n";
	echo "(tries su postgres if left blank)\n";
	echo "---------------------------------\n";
	
	echo 'User: ';
	$database['user'] = trim(fgets($stdIn));
	
	if (empty($database['user'])) {
		echo "Init database...";
		
		exec(
			'find ' . escapeshellarg(MIGRATIONS) .
			' -name "*.sql" ! -name "*test*" | sort -n | xargs -n 1 sudo -u postgres psql' .
			' --dbname=' .
			escapeshellarg($database['name']) .
			' -f',
			$output,
			$returnCode
		);
	} else {
		echo 'Password: ';
		
		system('stty -echo');
		$database['pass'] = trim(fgets($stdIn));
		system('stty echo');
		
		echo "\n";
		
		echo "Init database...";
		
		exec(
			'PGPASSWORD=' . escapeshellarg($database['pass']) . ' ' .
			'find ' . escapeshellarg(MIGRATIONS) .
			' -name "*.sql" ! -name "*test*" | sort -n | xargs -n 1 psql --host=' .
			escapeshellarg($database['host']) .
			' --username=' .
			escapeshellarg($database['user']) .
			' --dbname=' .
			escapeshellarg($database['name']) .
			' -f',
			$output,
			$returnCode
		);
	}
	
	if (!isset($returnCode) or $returnCode !== 0) {
		echo "failed.\n";
		die();
	}
	
	echo "done.\n";
	
	echo "\n";
	echo "Add first user (admin)\n";
	echo "----------------------\n";
	
	echo 'Username: ';
	$user = trim(fgets($stdIn));
	
	echo 'Password: ';
	
	system('stty -echo');
	$password = trim(fgets($stdIn));
	system('stty echo');
	
	echo "\n";
	
	echo "\n";
	echo "Adding user...";
	
	$stmt = $pdo->prepare('INSERT INTO tbl_user (name, password, role) VALUES (?, ?, ?)');
	$stmt->execute([
		$user,
		password_hash($password, PASSWORD_DEFAULT),
		'admin'
	]);
	
	echo " done.\n";
	
?>