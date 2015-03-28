<?php 
namespace Rusdteam\Dom;

use Illuminate\Support\ServiceProvider;

class DomServiceProvider extends ServiceProvider {

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
		$this->package('rusdteam/dom');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('DomCacheInterface', function(){
			return new Rusdteam\Dom\Helpers\JSCache();
		});

		$this->app->bind('DomJSGeneratorInterface', function(){
			return new Rusdteam\Dom\Helpers\JSGenerator(); 
		});
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

}
