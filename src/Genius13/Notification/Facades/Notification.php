<?php namespace Genius13\Notification\Facades;

use Illuminate\Support\Facades\Facade;

class Notification extends Facade{

	/**
	 * Get the registered name of the componet.
	 * 
	 * @return string
	 */
	 
	 protected static function getFacadeAccessor() {
	 	return 'notification';
	 }

}