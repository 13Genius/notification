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
		$data = json_encode(array(
			'owner_id' => $owner_id,
			'event' => $event, 
			'value' => $message,
			'time' => time(),
		));
		
		// Save in new queue
		return $this -> redis-> sadd($this -> prefix . ':new:' . $owner_id, $data );
	}
	
	/**
	 * Count new notifications
	 *
	 * @param owner_ids Owner user from the notification
	 * @return integer
	 */
	public function counterNew(Array $owner_ids = array())
	{
		return $this -> counter('new', $owner_ids);
	}
	
	/**
	 * Count old notifications
	 *
	 * @param owner_ids Owner user from the notification
	 * @return integer
	 */
	public function counterOld(Array $owner_ids = array())
	{
		return $this -> counter('old', $owner_ids);
	}

	/**
	 * Count notifications
	 *
	 * @param list List of queue
	 * @param owner_ids Owner user from the notification
	 * @return integer
	 */
	private function counter($list, Array $owner_ids = array())
	{
		if (empty($owner_ids)) {
			$owner_ids = array(0);
		}
		
		$count = 0;
		
		foreach ($owner_ids as $owner_id) {
			// Pop from new and add to old queue
			$count += $this -> redis-> scard($this -> prefix . ':' . $list . ':' . $owner_id);
		}
		
		return $count;
	}
	
	/**
	 * Create a new notification
	 *
	 * @param owner_ids Owner user from the notification
	 * @return void
	 */
	public function getByOwner(Array $owner_ids = array())
	{
		if (empty($owner_ids)) {
			$owner_ids = array(0);
		}
		
		$result = array();
		
		foreach ($owner_ids as $owner_id) {
			
			$new = $this -> redis-> smembers($this -> prefix . ':new:' . $owner_id);
			if ($new) {
				$result['new'][$owner_id] = $new;	
			}
			
			$old = $this -> redis-> smembers($this -> prefix . ':old:' . $owner_id);
			if ($old) {
				$result['old'][$owner_id] = $old;	
			}
			
			if ($new) {
				$this -> redis -> sadd($this -> prefix . ':old:' . $owner_id, $new);
				$this -> redis -> srem($this -> prefix . ':new:' . $owner_id, $new);
			}
			
		}
		
		return $result;
		
	}

}