<?php
	
	function delayToMilliseconds($delay) {
		return ((float) $delay) * 1000;
	}

	function millisecondsToDelay($milliseconds) {
		return ((int) $milliseconds) / 1000;
	}
	
	function actionExpandOptions() {
		return [
			'' => '',
			'300' => '5 minutes',
			'600' => '10 minutes',
			'1200' => '20 minutes',
			'1800' => '30 minutes',
			'3600' => '60 minutes',
			'5400' => '90 minutes'
		];
	}
	
?>