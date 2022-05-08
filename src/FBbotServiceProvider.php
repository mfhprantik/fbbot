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
		$this->publishes([
			__DIR__ . '/views' => base_path('resources/views/vendor/prantik/fbbot'),
		]);
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}
}
