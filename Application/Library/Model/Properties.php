<?php
	
	class Model_Properties extends Model {
		
		public $primaryKey = false;
		
		public $objectField;
		
		public $nameField = 'name';
		public $valueField = 'value';
		
		protected $_properties;
		
		public function __construct() {
			parent::__construct();
			
			$this->_properties = array();
		}
		
		public function findByObject($object, $conditions = null, array $params = array(), $fields = null) {
			$query = new Database_Query($this->table);
			$query->where(array($this->objectField => $object));
			
			if (!$properties = Model::indexByField($this->_find($query, array(), $conditions, $params, $this->nameField, null, $fields), $this->nameField, $this->valueField)) {
				return false;
			}
			
			if (!isset($this->_properties[$object])) {
				$this->_properties[$object] = array();
			}
			
			$this->_properties[$object] = array_merge($this->_properties[$object], $properties);
			
			return $properties;
		}
		
		public function findByObjectWithRoot($object) {
			$properties = Database_query::selectFrom($this->table, $this->nameField . ', ' . $this->valueField . ', SUBPATH(' . $this->nameField . ', 0, 1) AS root')
				->where(array($this->objectField => $object))
				->orderBy($this->nameField)
				->fetch();
			
			return $properties;
		}
		
		public function update($object, $keys, $values, $changed = array()) {
			if (empty($object)) {
				return false;
			}
			
			$this->set($this->objectField, $object);
			
			$properties = array();
			
			if (is_array($keys) and is_array($values) and count($keys) == count($values)) {
				$properties = array_combine($keys, $values);
			}
			
			// handle empty submissions
			if (isset($properties[''])) {
				unset($properties['']);
			}
			
			if (is_array($changed)) {
				foreach ($changed as $key => $value) {
					if (!empty($key)) {
						if ($value == '') {
							$properties[$key] = null;
						} else {
							$properties[$key] = $value;
						}
					}
				}
			}
			
			$staleProperties = $this->findByObject($this->_data[$this->objectField]);
			
			if (!empty($staleProperties)) {
				foreach ($staleProperties as $key => $value) {
					if (!isset($properties[$key])) {
						$properties[$key] = null;
					}
				}
			}
			
			if (empty($properties)) {
				return true;
			}
			
			return $this->save($properties);
		}
		
		public function save($data = null, $conditions = null, array $params = array()) {
			if ($data !== null) {
				$this->set($data);
			}
			
			if (empty($this->_data[$this->objectField])) {
				return false;
			}
			
			$object = $this->_data[$this->objectField];
			$fields = $this->getFields();
			$changed = $this->changed();
			
			$properties = array_intersect_key(array_diff_key($this->_data, $fields), $changed);
			// var_dump($properties);
			if (empty($properties)) {
				// TODO: check if save returns false on beforeSave or somewhere else
				// (in the later case we should return false)
				parent::save();
				return true;
			}
			
			foreach ($properties as $key => $value) {
				if (!isset($this->_properties[$object]) or !array_key_exists($key, $this->_properties[$object]) or $this->_properties[$object][$key] !== $value) {					
					if (!isset($this->_properties[$object])) {
						$this->_properties[$object] = array();
					}
					
					$this->_properties[$object][$key] = $value;
					
					parent::save(array($this->nameField => $key, $this->valueField => $value), $conditions, $params);
				}
			}
			
			return true;
		}
		
		protected function beforeSave($data) {
			if (empty($data[$this->objectField]) or empty($data[$this->nameField])) {
				// TODO: what should really happen here?
				return $data;
			}
			
			$conditions = array(
				$this->objectField => $data[$this->objectField],
				$this->nameField => $data[$this->nameField]
			);
			
			$query = new Database_Query($this->table);
			$query->select($this->objectField)->where($conditions);
			$this->Database->query($query);
			
			if (!$this->Database->rows()) {
				if ($data[$this->valueField] === null) {
					return false;
				}
				
				$this->Database->query(Database_Query::insertInto($this->table, $data));
				
				if (!$this->afterCreate()) {
					return false;
				}
			} else {
				unset($data[$this->objectField]);
				unset($data[$this->nameField]);
				
				if ($data[$this->valueField] === null) {
				        $this->Database->query(Database_Query::deleteFrom($this->table, $conditions));
				} else {
				        $this->Database->query(Database_Query::updateTable($this->table, $data, $conditions));
				}
				
				if (!$this->afterUpdate()) {
					return false;
				}
			}
			
			return false;
		}
		
	}
	
?>