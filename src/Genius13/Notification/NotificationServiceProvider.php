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
		
		//include __DIR__.'/../../routes.php';
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this -> app['notification'] = $this -> app -> share(function($app)
		{
			return new Notification;
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