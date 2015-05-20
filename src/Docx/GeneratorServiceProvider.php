<?php namespace Docx;

use Illuminate\Support\ServiceProvider;

class GeneratorServiceProvider extends ServiceProvider {
	public function register() {
		$this->app->singleton('generator', function() {
			return new Generator;
		});
	}
}