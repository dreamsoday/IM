<?php
namespace Lgy\IM;

use Illuminate\Support\ServiceProvider;

class IMServiceProvider extends ServiceProvider {
    protected $defer = true;

    public function boot() {

        $this->publishes([
            __DIR__.'/../config/im.php' => config_path('im.php'),
        ]);
    }


    public function register() {
        $this->mergeConfigFrom( __DIR__.'/../config/im.php', 'im');

        $this->app->singleton('im', function($app) {
            $config = $app->make('config');

            $client_id = $config->get('im.client_id');
            $client_secret = $config->get('im.client_secret');
            $app_name = $config->get('im.app_name');
            $org_name = $config->get('im.org_name');
            if (!empty ($org_name) && !empty ($app_name)) {
                $url = 'http://a1.easemob.com/' . $org_name . '/' . $app_name . '/';
            }

            return new IMService($client_id, $client_secret, $app_name, $org_name, $url);
        });
    }

    public function provides() {
        return ['im'];
    }
}

