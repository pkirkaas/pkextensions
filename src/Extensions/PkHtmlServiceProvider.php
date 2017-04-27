<?php
namespace PkExtensions;
use Illuminate\Html\FormBuilder;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

class PkHtmlServiceProvider extends ServiceProvider {
	protected $defer = true;
	public function register() {
		$this->registerPkFormBuilder();
		$this->registerPkHtmlBuilder();
		$this->registerPkHtmlRenderer();
    $this->app->alias('pkform', 'PkExtensions\PkFormBuilder');
    $this->app->alias('pkhtml', 'PkExtensions\PkHtmlBuilder');
    $this->app->alias('pkrenderer', 'PkExtensions\PkHtmlRenderer');
  }
	protected function registerPkHtmlBuilder() {
		$this->app->singleton('pkhtml', function($app) {
			//$html = new PkHtmlBuilder($app['pkhtml'], $app['url']);
			$html = new PkHtmlBuilder($app['url'], $app['view']);
      return $html;
		});
  }
	protected function registerPkHtmlRenderer() {
		$this->app->bind('pkrenderer', function($app) {
			return new PkHtmlRenderer();
		},false);
	}
	protected function registerPkFormBuilder() {
		$this->app->singleton('pkform', function($app) {
			//$form = new PkFormBuilder($app['html'], $app['url'], $app['session.store']->getToken());
      //As of Laravel 5.4, getToken replaced with "token"
			//$form = new PkFormBuilder($app['pkhtml'], $app['url'], $app['view'], $app['session.store']->getToken());
			$form = new PkFormBuilder($app['pkhtml'], $app['url'], $app['view'], $app['session.store']->token());
			return $form->setSessionStore($app['session.store']);
		});
	}
	public function provides() {
		return ['pkform','pkhtml', 'pkrenderer'];
	}
}
