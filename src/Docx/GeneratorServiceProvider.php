<?php namespace Docx;

use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider {
	public function register() {
		$this->app->singleton('generator', function() {
			return new Generator;
		});
	}
}