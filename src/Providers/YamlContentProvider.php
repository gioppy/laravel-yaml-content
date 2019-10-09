<?php

namespace gioppy\YamlContent\Providers;

use gioppy\YamlContent\Services\YamlContent;
use gioppy\YamlContent\Services\YamlContentField;
use Illuminate\Support\ServiceProvider;

class YamlContentProvider extends ServiceProvider {
  /**
   * Register services.
   *
   * @return void
   */
  public function register() {
    $this->app->singleton('gioppy\YamlContent\Contracts\YamlFormContract', function ($app) {
      return new YamlContent();
    });

    $this->app->singleton('gioppy\YamlContent\Contracts\YamlFormFieldContract', function ($app) {
      return new YamlContentField();
    });
  }

  /**
   * Bootstrap services.
   *
   * @return void
   */
  public function boot() {
    //
  }
}
