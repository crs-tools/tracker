<?php
	
	class Project extends Model {
		
		public $table = 'tbl_project';
		
		public $validatePresenceOf = array('name' => true, 'slug' => true);
		
		public $hasMany = array(
			'Ticket' => array('key' => 'project_id'),
			'EncodingProfile' => array('key' => 'project_id'),
			'ProjectLanguages' => array('key' => 'project_id', 'fields' => 'language, description')
		);
		
		private $_current;
		
		public function current() {
			if (empty($this->_current)) {
				return false;
			}
			
			$this->clear();
			$this->_data = $this->_current;
			
			return $this;
		}
		
		public function setCurrent($slug) {
			$projects = $this->findAllIndexedBySlug();
			
			if (!isset($projects[$slug])) {
				$this->_current = null;
				return false;
			}
			
			$this->_current = $projects[$slug];
			$this->current();
			
			return $this->_current;
		}
		
		public function findAllIndexedBySlug() {
			if (!$projects = $this->Cache->get('projects')) {
				$projects = array();
				
				$data = $this->findAll(array('ProjectLanguages'), null, array(), 'title ASC');
				
				if (!empty($data)) {
					foreach ($data['project'] as $project) {
						$projects[$project['slug']] = $project;
					
						$projects[$project['slug']]['languages'] = array();
					
						if (!isset($project['projectlanguages'])) {
							continue;
						}
					
						foreach ($project['projectlanguages'] as $language) {
							$projects[$project['slug']]['languages'][$language['language']] = $language['description'];
						}
					
						unset($projects[$project['slug']]['projectlanguages']);
					}
				}
				
				$this->Cache->set('projects', $projects);
			}
			
			return $projects;
		}
		
		public function afterSave() {
			$this->Cache->remove('projects');
			return true;
		}
		
		public function delete($id = null, $conditions = null, array $params = array(), $limit = null, $deleteAssociations = true) {
			if (!parent::delete($id, $conditions, $params, $limit, $deleteAssociations)) {
				return false;
			}
			
			$this->Cache->remove('projects');
			
			return true;
		}
		
	}
	
?>