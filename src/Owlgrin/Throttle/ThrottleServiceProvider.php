<?php namespace Owlgrin\Throttle;

use Illuminate\Support\ServiceProvider;

class ThrottleServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('sahil/throttle');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerCommands();
		$this->registerRepositories();
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

	protected function registerCommands()
	{
		$this->app->bindShared('command.throttle.table', function($app)
		{
			return new \Owlgrin\Throttle\Commands\ThrottleTableCommand;
		});

		$this->commands('command.throttle.table');
	}

	protected function registerRepositories()
	{
		$this->app->bind('Owlgrin\Throttle\Biller\Biller', 'Owlgrin\Throttle\Biller\PayAsYouGoBiller');
		$this->app->bind('Owlgrin\Throttle\Subscriber\SubscriberRepo', 'Owlgrin\Throttle\Subscriber\DbSubscriberRepo');
		$this->app->bind('Owlgrin\Throttle\Plan\PlanRepo', 'Owlgrin\Throttle\Plan\DbPlanRepo');

		$this->app->singleton('throttle', 'Owlgrin\Throttle\Throttle');
	}

}
