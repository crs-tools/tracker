<?php
	
	function timeAgo($dateTime) {
		if (!$dateTime instanceOf DateTime) {
			$dateTime = new DateTime($dateTime);
		}
		
		return View::tag(
			'time',
			[
				'aria-label' => $dateTime->format('Y-m-d H:i:s'),
				'data-tooltip' => true,
				'datetime' => $dateTime->format('c')
			],
			timeRelativeDifference($dateTime)
		);
	}
	
	function timeRelativeDifference(DateTime $dateTime, $now = null) {
		if ($now === null) {
			$now = new DateTime();
		}
		
		$seconds = ($now->getTimestamp() - $dateTime->getTimestamp());
		$minutes = round($seconds / 60);
		$hours = round($minutes / 60);
		
		if ($seconds < 10) {
			return 'a second ago';
		} elseif ($seconds < 45) {
			return $seconds . ' seconds ago';
		} elseif ($seconds < 90) {
			return 'a minute ago';
		} elseif ($minutes < 45) {
			return $minutes . ' minutes ago';
		} elseif ($minutes < 90) {
			return 'an hour ago';
		} elseif ($hours < 7) {
			return $hours . ' hours ago';
		}
		
		$days = (($hours + ((int) $dateTime->format('H'))) / 24);
		
		if ($days < 1) {
			return 'today at ' . $dateTime->format('H:i');
		} elseif ($days < 2) {
			return 'yesterday at ' . $dateTime->format('H:i');
		} elseif ($days < 7) {
			return $dateTime->format('l') . ' at ' . $dateTime->format('H:i');
		}
		
		$year = $dateTime->format('Y');
		
		return 'on ' . $dateTime->format('M j') .
			(($now->format('Y') !== $year)? (', ' . $year) : '');
	}
	
	function formatDuration($duration) {
		$seconds = $duration % 60;
		$minutes = ($duration / 60) % 60;
		$hours = floor($duration / 60 / 60);
		
		return (($hours > 0)? $hours . ' hour' . (($hours !== 1)? 's' : '') . ' ' : '') .
			(($minutes > 0)? $minutes . ' minute' . (($minutes !== 1)? 's' : '') . ' ' : '') .
			(($seconds > 0)? $seconds . ' second' . (($seconds !== 1)? 's' : '') . '' : '');
	}
	
?>