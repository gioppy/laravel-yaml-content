<?php


namespace gioppy\YamlContent\Tests;


use gioppy\YamlContent\Providers\YamlContentProvider;

class TestCase extends \Orchestra\Testbench\TestCase {

  protected function getPackageProviders($app) {
    return [
      YamlContentProvider::class,
    ];
  }
}
