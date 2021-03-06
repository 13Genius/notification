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
	 * @param prefix Prefix of list of notifications
	 * @return void
	 */
	public function create($message, $owner_id = 0, $event = 'general', $prefix = null)
	{
		$now = time();
		
		$data = json_encode(array(
			'owner_id' => $owner_id,
			'event' => $event, 
			'value' => $message,
			'time' => $now,
			'seen' => 0,
		));
		
		// Set prefix
		if(!isset($prefix)) {
			$prefix = $this -> prefix;
		}
		
		// Save in new queue
		if (!$this -> redis-> get($prefix . ':new:' . $owner_id)) {
			$this -> redis-> set($prefix . ':new:' . $owner_id, 1);
		} else {
			$this -> redis-> incr($prefix . ':new:' . $owner_id);	
		}
		return $this -> redis-> zadd($prefix . ':' . $owner_id, $now, $data);
	}
	
	/**
	 * Count new notifications
	 *
	 * @param owner_ids Owner user from the notification
	 * @param prefix Prefix of list of notifications
	 * @return integer
	 */
	public function counterNew(Array $owner_ids = array(), $prefix = null)
	{
		return $this -> counter($owner_ids, 'new', $prefix);
	}
	
	/**
	 * Count notifications
	 *
	 * @param list List of queue
	 * @param owner_ids Owner user from the notification
	 * @param prefix Prefix of list of notifications
	 * @return integer
	 */
	private function counter(Array $owner_ids = array(), $list = null, $prefix = null)
	{
		if (empty($owner_ids)) {
			$owner_ids = array(0);
		}
		
		// Set prefix
		if(!isset($prefix)) {
			$prefix = $this -> prefix;
		}
		
		$count = 0;
		
		foreach ($owner_ids as $owner_id) {
			// Pop from new and add to old queue
			if (isset($list) && $list == 'new') {
				$count += $this -> redis-> get($prefix . ':' . $list . ':' . $owner_id);	
			}else{
				$count += $this -> redis-> get($prefix . ':' . $owner_id);
			}
		}
		
		return $count;
	}
	
	/**
	 * Create a new notification
	 *
	 * @param owner_ids Owner user from the notification
	 * @param prefix Prefix of list of notifications
	 * @return void
	 */
	public function clear(Array $owner_ids = array(), $prefix = null)
	{
		if (empty($owner_ids)) {
			$owner_ids = array(0);
		}
		
		$result = 0;
		
		// Set prefix
		if(!isset($prefix)) {
			$prefix = $this -> prefix;
		}
		
		foreach ($owner_ids as $owner_id) {
			$deletion = $this -> redis-> del($prefix . ':' . $owner_id);
			$result = $result || $deletion;
		}
		
		return $result;
	}
	
	/**
	 * Get notifications
	 *
	 * @param owner_ids Owner user from the notification
	 * @param limit Number of results
	 * @param prefix Prefix of list of notifications
	 * @param block Get list without marking as read
	 * @return void
	 */
	public function getByOwner(Array $owner_ids = array(), $limit = -1, $prefix = null, $block = false)
	{
		if (empty($owner_ids)) {
			$owner_ids = array(0);
		}
		
		$result = array();
		
		// Set prefix
		if(!isset($prefix)) {
			$prefix = $this -> prefix;
		}
		
		foreach ($owner_ids as $owner_id) {
			
			$noti = $this -> redis-> zrevrange($prefix . ':' . $owner_id, 0, ($limit <= 0)? -1 : $limit - 1);
			if ($noti) {
								
				foreach ($noti as $value) {

					$data = json_decode($value);
					$data -> prefix = $prefix;
					array_push($result, $data);
					
					if (!$data->seen) {
						if(!$block) {
							$data_aux = json_encode(array(
								'owner_id' => $data->owner_id,
								'event' => $data->event, 
								'value' => $data->value,
								'time' => $data->time,
								'seen' => 1,
							));
							$this -> redis-> zrem($prefix . ':' . $owner_id, $value);
							$this -> redis-> zadd($prefix . ':' . $owner_id, $data->time, $data_aux);	
						}
					}
					
				}	
			}
			
			// New counter to zero
			$this -> redis-> set($prefix . ':new:' . $owner_id, 0);
			
		}
		return $result;
		
	}
}
