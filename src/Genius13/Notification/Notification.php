<?php namespace Genius13\Notification;

use Illuminate\Redis\Database as Redis;
use Config;


class Notification {
	
	// The redis database connection
	protected $redis;
	
	// Prefix to preprended to keys
	protected $prefix = 'notification';
	
	
	
	
	public $notification;
	
	/**
	 * Create a new Notification object.
	 *
	 * @param  Genius13\Notification\Models\Notification  $notification
	 * @return void
	 */
	public function __construct()
	{
		$this -> redis = new Redis(Config::get('database.redis'));
		$this -> redis -> connection();
		
		$this->notification = app();
	}
	
	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public static function notification()
	{
		return $this -> notification;
	}
	
	/**
	 * Create a new notification
	 *
	 * @param owner_id Owner user from the notification
	 * @param message Message of the notification
	 * @param event Event type of the action 
	 * @return void
	 */
	public function create($message, $owner_id = 0, $event = 'general')
	{
		$now = time();
		
		$data = json_encode(array(
			'owner_id' => $owner_id,
			'event' => $event, 
			'value' => $message,
			'time' => $now,
			'seen' => 0,
		));
		
		// Save in new queue
		if (!$this -> redis-> get($this -> prefix . ':new:' . $owner_id)) {
			$this -> redis-> set($this -> prefix . ':new:' . $owner_id, 1);
		} else {
			$this -> redis-> incr($this -> prefix . ':new:' . $owner_id);	
		}
		return $this -> redis-> zadd($this -> prefix . ':' . $owner_id, $now, $data);
	}
	
	/**
	 * Count new notifications
	 *
	 * @param owner_ids Owner user from the notification
	 * @return integer
	 */
	public function counterNew(Array $owner_ids = array())
	{
		return $this -> counter($owner_ids, 'new');
	}
	
	/**
	 * Count notifications
	 *
	 * @param list List of queue
	 * @param owner_ids Owner user from the notification
	 * @return integer
	 */
	private function counter(Array $owner_ids = array(), $list = null)
	{
		if (empty($owner_ids)) {
			$owner_ids = array(0);
		}
		
		$count = 0;
		
		foreach ($owner_ids as $owner_id) {
			// Pop from new and add to old queue
			if (isset($list) && $list == 'new') {
				$count += $this -> redis-> get($this -> prefix . ':' . $list . ':' . $owner_id);	
			}else{
				$count += $this -> redis-> get($this -> prefix . ':' . $owner_id);
			}
		}
		
		return $count;
	}
	
	/**
	 * Create a new notification
	 *
	 * @param owner_ids Owner user from the notification
	 * @return void
	 */
	public function clear(Array $owner_ids = array())
	{
		if (empty($owner_ids)) {
			$owner_ids = array(0);
		}
		
		$result = 0;
		
		foreach ($owner_ids as $owner_id) {
			$deletion = $this -> redis-> del($this -> prefix . ':' . $owner_id);
			$result = $result || $deletion;
		}
		
		return $result;
	}
	
	/**
	 * Get notifications
	 *
	 * @param owner_ids Owner user from the notification
	 * @return void
	 */
	public function getByOwner(Array $owner_ids = array(), $limit = -1)
	{
		if (empty($owner_ids)) {
			$owner_ids = array(0);
		}
		
		$result = array();
		
		foreach ($owner_ids as $owner_id) {
			
			$noti = $this -> redis-> zrevrange($this -> prefix . ':' . $owner_id, 0, ($limit <= 0)? -1 : $limit - 1);
			if ($noti) {
								
				foreach ($noti as $value) {

					$data = json_decode($value);
					array_push($result, $data);
					
					if (!$data->seen) {
						$data_aux = json_encode(array(
							'owner_id' => $data->owner_id,
							'event' => $data->event, 
							'value' => $data->value,
							'time' => $data->time,
							'seen' => 1,
						));
						$this -> redis-> zrem($this -> prefix . ':' . $owner_id, $value);
						$this -> redis-> zadd($this -> prefix . ':' . $owner_id, $data->time, $data_aux);
					}
					
				}	
			}
			
			// New counter to zero
			$this -> redis-> set($this -> prefix . ':new:' . $owner_id, 0);
			
		}
		return $result;
		
	}
	
	

}