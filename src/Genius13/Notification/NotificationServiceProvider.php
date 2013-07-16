<?php namespace Genius13\Notification;

use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('genius13/notification');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this -> app -> bind('notification', function($app)
		{
			return new Notification($app);
		});
		
		$this -> app -> booting(function() {
			$loader = \Illuminate\Foundation\AliasLoader::getInstance();
			$loader -> alias('Notification' , 'Genius13\Notification\Facades\Notification');
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('notification');
	}

}