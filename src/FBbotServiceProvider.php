<?php

namespace Prantik\FBbot;

use Illuminate\Support\ServiceProvider;

class FBbotServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->loadViewsFrom(__DIR__ . '/views', 'fbbot');

		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__ . '/views' => base_path('resources/views/vendor/prantik/fbbot'),
			], 'views');

			$this->publishes([
				__DIR__ . '/config.php' => config_path('fbbot.php'),
			], 'config');
		}
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/config.php', 'fbbot');
	}
}
