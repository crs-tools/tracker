<?php
	
	class LogEntry extends Model {
		
		public $table = 'tbl_log';
		
		public $belongsTo = array(
			'LogMessage' => array('key' => 'event', 'join' => true, 'fields' => 'message, feed_message, rpc, feed_include_log' ),
			'Ticket' => array(),
			'User' => array('join' => true, 'fields' => 'name AS user_name'),
			'Comment' => array('join' => true, 'fields' => 'comment AS comment_comment'));
		
		public function findByTicketId($id, $fetch = null, $order = 'created DESC') {
			$query = new Database_Query($this->table);
			
			$query->join('tbl_state', 'name AS from_state_name', 'tbl_log.from_state_id = id', array(), 'LEFT');
			$query->join('tbl_state', 'name AS to_state_name', 'tbl_log.to_state_id = id', array(), 'LEFT');
			
			$query->where(array('ticket_id' => $id));
			$query->orderBy($order);
			
			return $this->findBySQL($query, array(), $fetch);
		}
		
		public function findByProjectId($id, $fetch = null, $conditions = null, array $params = array(), $order = 'created DESC , id DESC', $limit = 100) {
			$query = new Database_Query($this->table);
			
			$query->join('tbl_state', 'name AS from_state_name', 'tbl_log.from_state_id = id', array(), 'LEFT');
			$query->join('tbl_state', 'name AS to_state_name', 'tbl_log.to_state_id = id', array(), 'LEFT');
			
			$query->join('tbl_ticket', 'title AS ticket_title, fahrplan_id AS ticket_fahrplan_id', 'tbl_log.ticket_id = tbl_ticket.id');
			
			$query->where(array('tbl_ticket.project_id' => $id));
			
			if ($conditions !== null) {
				$query->where($conditions, $params);
			}
			
			$query->orderBy($order);
			$query->limit($limit);
			
			return $this->findBySQL($query, array(), $fetch);
		}
		
		public function getTopContributor($event) {
			// TODO: move query to Database_Query	
			$this->Database->query('SELECT tbl_user.name AS user_name FROM tbl_log
					LEFT JOIN tbl_user ON tbl_user.id = tbl_log.user_id
					INNER JOIN tbl_ticket ON tbl_ticket.id = tbl_log.ticket_id
					WHERE tbl_ticket.project_id = ? AND tbl_log.event = ? AND (NOW() - tbl_log.created) < INTERVAL \'3 hour\'
					GROUP BY user_name
					ORDER BY COUNT(tbl_log.ticket_id) DESC
				', array($this->Project->id, $event));
			
			$result = $this->Database->fetchRow();
			return $result['user_name'];
		}
		
		public function create(array $data = array(), $save = true, $clear = true) {
			if (empty($data['user_id'])) {
				$data['user_id'] = $this->User->get('id');
			}
			if (empty($data['from_state_id'])) {
				$data['from_state_id'] = $this->Ticket->state_id;
			}
			if (empty($data['to_state_id'])) {
				$data['to_state_id'] = $this->Ticket->state_id;
			}
			
			return parent::create($data, $save, $clear);
		}
		
	}
	
?>