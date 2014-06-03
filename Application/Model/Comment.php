<?php
	
	class Comment extends Model {
		
		const TABLE = 'tbl_comment';
		
		public $belongsTo = [
            'Handle' => [
                'foreign_key' => ['handle_id'],
                'select' => 'name AS handle_name'
            ],
            'Ticket' => [
				'foreign_key' => ['ticket_id']
			],
			'User' => [
				'foreign_key' => ['handle_id'],
				'select' => 'name AS user_name'
			]
		];
		
	}
	
?>