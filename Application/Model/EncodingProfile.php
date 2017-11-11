<?php
	
	class EncodingProfile extends Model {
		
		public $table = 'tbl_encoding_profile';
		
		public $validatePresenceOf = array('name' => true, 'slug' => true);
		
	}
	
?>