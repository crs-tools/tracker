<?php
	
	function delayToMilliseconds($delay) {
		return ((float)$delay) * 1000;
	}

	function millisecondsToDelay($milliseconds) {
		return $milliseconds / 1000;
	}
	
	function actionExpandOptions() {
		return [
			'' => '',
			'5' => '5 minutes',
			'10' => '10 minutes',
			'20' => '20 minutes',
			'30' => '30 minutes',
			'60' => '60 minutes',
			'90' => '90 minutes'
		];
	}
	
?>