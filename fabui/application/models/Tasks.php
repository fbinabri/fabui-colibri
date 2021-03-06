<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 
 class Tasks extends FAB_Model {
 	
	private $tableName = 'sys_tasks';
	private $completedStatus = array('completed', 'aborted', 'deleted');
	private $date_columns = array('start_date', 'finish_date');
	
	const STATUS_RUNNING = 'running';
 	
	//init class
	public function __construct()
	{
		parent::__construct($this->tableName);
	}
	
	/**
	 * get running task
	 * @return row or false
	 */
	public function getRunning($controller = '')
	{
		if($controller != ''){
			$this->db->like('controller', $controller);
		}
		$this->db->where('status', self::STATUS_RUNNING);
		$this->db->order_by('start_date', 'DESC');
		$query = $this->db->get($this->tableName,1,0);
		if($query->num_rows() > 0){
			return $query->row_array();
		}else{
			return false;
		}
	}
	
	/**
	 * @param $type (print, mill, '') 
	 */
	function getLastCreations($type = '', $limit_end = 10, $limit_start = 0){
		$this->db->select('tf.orig_name, tf.client_name, to.name, tf.id as id_file, to.id as id_object, to.description');
		$this->db->join('sys_files as tf', 'tf.id = tt.id_file', 'left');
		$this->db->join('sys_objects as to', 'to.id = tt.id_object', 'left');
		if($type != '') $this->db->where('tt.type', $type);
		$this->db->where('tt.user', $this->session->user['id']);
		$this->db->where_in('tt.status', $this->completedStatus);
		$this->db->where('tt.finish_date = (select MAX(sys_tasks.finish_date) from sys_tasks where sys_tasks.id_object = tt.id_object and sys_tasks.id_file = tt.id_file)');
		$this->db->group_by('tt.id_file');
		$this->db->order_by('tt.finish_date', 'DESC');
		$query = $this->db->get($this->tableName.' as tt', $limit_end, $limit_start);
		return $query->result_array();
	}
	
	function getMinDate($controller = '')
	{
		$this->db->select('MIN(finish_date) as min', false);
		
		if($controller != ''){
			$this->db->like('controller', $controller);
		}
		$query = $this->db->get($this->tableName);
		
		if ($query->num_rows() > 0){
			
			$row = $query->row_array(); 
			return $row['min'];
		}
		return NULL;
	}
	
	function getMakeTasks($filters = array())
	{
		$this->db->select('sys_tasks.id as id, sys_tasks.user as user, sys_tasks.controller as controller, sys_tasks.type as type, sys_tasks.status as status, sys_tasks.id_object as id_object, 
						 sys_tasks.id_file as id_file, sys_tasks.start_date as start_date, sys_tasks.finish_date as finish_date, sys_objects.name as object_name, sys_files.file_name as file_name,
						 sys_tasks.attributes as task_attributes, sys_files.client_name as client_name, sys_files.deleted as file_deleted,
						 time(cast(( strftime(\'%s\', sys_tasks.finish_date)-strftime(\'%s\', sys_tasks.start_date)) AS real ), \'unixepoch\') as duration,', false)
				->where('sys_tasks.user', $_SESSION['user']['id'])
				->join('sys_objects', 'sys_objects.id = sys_tasks.id_object', 'left')
				->join('sys_files', 'sys_files.id = sys_tasks.id_file', 'left')
				->order_by('finish_date', 'DESC');
		
		if(is_array($filters)){ // if filters are setted
			if(isset($filters['start_date']) && $filters['start_date'] != ''){
				$this->db->where("start_date >=", DateTime::createFromFormat('d/m/Y',$filters['start_date'])->format('Y-m-d')." 00:00:00");
			}
			if(isset($filters['end_date']) && $filters['end_date'] != ''){	
				$this->db->where("finish_date <=", DateTime::createFromFormat('d/m/Y',$filters['end_date'])->format('Y-m-d')." 23:59:59");
			}
			if(isset($filters['type']) && $filters['type'] != ''){
				$this->db->where('type', $filters['type']);
			}else{
			    $this->db->where_in('type', array('print', 'mill', 'scan', 'laser', 'prism'));
			}
			if(isset($filters['status']) && $filters['status'] != ''){
				$this->db->where('status', $filters['status']);
			}
		}
		
		/*
		$ret = $this->db->get($this->tableName)->result_array();
		echo $this->db->last_query(); exit();
		*/
		return $this->db->get($this->tableName)->result_array();
	}
	
	/**
	 *  get totalt time (hh:mm:ss) for specific controller, type, status
	 */
	function getTotalTime($controller, $type, $status, $from_date, $to_date)
	{
		//$this->db->select('SEC_TO_TIME(SUM(TIME_TO_SEC((TIMEDIFF(finish_date, start_date))))) as total_time', false)
		$this->db->select('time(SUM (cast(( strftime(\'%s\', finish_date)-strftime(\'%s\', start_date)) AS real )), \'unixepoch\') total_time', false)
							->like('controller', $controller)
							->where('type', $type)
							->where('sys_tasks.user', $_SESSION['user']['id']);
		
		if($status != ''){ // add status filter condition
			$this->db->where('status', $status);
		}
		if($from_date != ''){ //from date filter condition
			$this->db->where("finish_date >=", DateTime::createFromFormat('d/m/Y',$from_date)->format('Y-m-d')." 00:00:00");
		}
		if($to_date != ''){ // to date filter condition
			$this->db->where("finish_date <=", DateTime::createFromFormat('d/m/Y',$to_date)->format('Y-m-d')." 23:59:59");
		}				   		   
		$result = $this->db->get($this->tableName)->result_array();
		return isset($result[0]['total_time']) ?  $result[0]['total_time'] : 0;
		
	}

	function getTotalTasks($controller, $type, $status, $from_date, $to_date)
	{	
		$this->db->select('count(*) as total', false)
							->like('controller', $controller)
							->where('type', $type)
							->where('status', $status)
							->where('sys_tasks.user', $_SESSION['user']['id']);
						   
		if($from_date != ''){
			$this->db->where("finish_date >=", DateTime::createFromFormat('d/m/Y',$from_date)->format('Y-m-d')." 00:00:00");
		}
		if($to_date != ''){
			$this->db->where("finish_date <=", DateTime::createFromFormat('d/m/Y',$to_date)->format('Y-m-d')." 23:59:59");
		}
		
		$result = $this->db->get($this->tableName)->result_array();
		
		return isset($result[0]['total']) ? $result[0]['total'] : 0;
		
	}
	
	
	function getFileStats($id_file, $from_date, $to_date)
	{		
		$this->db->select('count(*) as total, status,  DATE(finish_date) as date, time(SUM (cast(( strftime(\'%s\', finish_date)-strftime(\'%s\', start_date)) AS real )), \'unixepoch\') as  total_time', false)->where('id_file', $id_file);
		
		if($from_date != ''){
			$this->db->where("finish_date >=", DateTime::createFromFormat('d/m/Y',$from_date)->format('Y-m-d')." 00:00:00");
		}
		if($to_date != ''){
			$this->db->where("finish_date <=", DateTime::createFromFormat('d/m/Y',$to_date)->format('Y-m-d')." 23:59:59");
		}
		
		$this->db->group_by('status');
		$this->db->group_by('DATE(finish_date)');
		$this->db->order_by('DATE(finish_date)', 'ASC');
			
		$result =  $this->db->get($this->tableName)->result_array();
		return $result;
	}
	
	function getLatestCreations($type, $limit_end = 10, $limit_start = 0){
		
		$this->db->select('st.id_file as id, st.id_object as id_object, st.finish_date as finish_date, sys_files.client_name, sys_files.note as note, st.status, (julianday(st.finish_date) - julianday(st.start_date)) as duration, sys_files.attributes as attributes, sys_files.file_type, sys_objects.name as object_name', false)
				  ->from('sys_tasks as st')
				  ->where_in('st.status', $this->_COMPLETED_STATUS)
				  ->where('st.type', $type)
				  ->where('st.user', $_SESSION['user']['id'])
				  ->where('st.finish_date = (select MAX(sys_tasks.finish_date) from sys_tasks where sys_tasks.id_object = st.id_object and sys_tasks.id_file = st.id_file)') 
				  ->join('sys_objects', 'sys_objects.id = st.id_object', 'left')
				  ->join('sys_files', 'sys_files.id = st.id_file', 'left')
				  ->group_by('st.id_file')
				  ->order_by('st.finish_date', 'DESC');
						
		$result = $this->db->get($this->tableName, $limit_end, $limit_start)->result_array();

		return $result;
		
	}
	/**
	 * 
	 */
	function getFileTasks($file_id, $filters)
	{	
		$this->db->select('*, time(cast(( strftime(\'%s\', sys_tasks.finish_date)-strftime(\'%s\', sys_tasks.start_date)) AS real ), \'unixepoch\') as duration', false);
		$this->db->where('id_file', $file_id);
		
		if(is_array($filters)){	
			if(isset($filters['start_date']) && $filters['start_date'] != ''){
				$this->db->where("finish_date >=", DateTime::createFromFormat('d/m/Y',$filters['start_date'])->format('Y-m-d')." 00:00:00");
			}
			if(isset($filters['end_date']) && $filters['end_date'] != ''){
				$this->db->where("finish_date <=", DateTime::createFromFormat('d/m/Y',$filters['end_date'])->format('Y-m-d')." 23:59:59");
			}	
		}
		$this->db->order_by('finish_date', 'DESC');
		$result = $this->db->get($this->tableName)->result_array();
		return $result;
	}
 }
 
?>
