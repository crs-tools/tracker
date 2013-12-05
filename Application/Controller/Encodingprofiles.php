<?php
	
	requires(
		'/Model/EncodingProfile'
	);
	
	class Controller_EncodingProfiles extends Controller_Application {
		
		public $requireAuthorization = true;
		
		public function index() {
			$this->profiles = EncodingProfile::findAllWithVersionCount(array())->orderBy('slug');
			return $this->render('encoding/profiles/index.tpl');
		}
		
		public function view(array $arguments) {
			if (!$this->profile = EncodingProfile::find($arguments['id'])) {
				throw new EntryNotFoundException();
			}
			
			$this->form = $this->form('encodingprofiles', 'compare', $this->profile->toArray());
			$this->versions = $this->profile->Versions->orderBy('revision DESC');
			
			return $this->render('encoding/profiles/view.tpl');
		}
		
		public function compare(array $arguments) {
			if (!$this->profile = EncodingProfile::find($arguments['id'])) {
				throw new EntryNotFoundException();
			}
			
			$values = $this->form()->getValues();
			
			if (!isset($values['version_a']) or !isset($values['version_b'])) {
				return $this->redirect('encodingprofiles', 'view', $this->profile->toArray());
			}
			
			if ($values['version_a'] == $values['version_b']) {
				$this->flash('Cannot compare a version with itself');
				return $this->redirect('encodingprofiles', 'view', $this->profile->toArray());
			}
			
			$versions = EncodingProfileVersion::findAll(array())
				/*->select('xml_template')*/
				->where(array('id' => array($values['version_a'], $values['version_b'])))
				->indexBy('id', 'xml_template')
				->toArray();
			
			$this->Response->setContentType('text/plain');
			$this->Response->setContent(xdiff_string_diff(
				$versions[$values['version_a']],
				$versions[$values['version_b']]
			));
		}
		
		public function create() {
			$this->form = $this->form();
			
			if ($this->form->wasSubmitted() and EncodingProfile::create($this->form->getValues())) {
				$this->flash('Encoding profile created');
				return $this->redirect('encodingprofiles', 'index');
			}
			
			return $this->render('encoding/profiles/edit.tpl');
		}
		
		public function edit(array $arguments) {
			if (!$this->profile = EncodingProfile::find($arguments['id'])) {
				throw new EntryNotFoundException();
			}
			
			$this->form = $this->form();
			
			if ($this->form->wasSubmitted()) {
				
			}
			
			$this->versions = $this->profile->Versions;
			return $this->render('encoding/profiles/edit.tpl');
		}
		
		public function delete(array $arguments) {
			/*
			if (!empty($arguments) and $this->EncodingProfile->delete($arguments['id'], array('project_id' => $this->Project->id))) {
				$this->flash('Encoding profile deleted');
			}
 			
			return $this->View->redirect('projects', 'view', array('project_slug' => $this->Project->slug));
			*/
		}
		
	}
	
?>