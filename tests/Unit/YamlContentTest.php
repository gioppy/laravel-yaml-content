<?php

namespace gioppy\YamlContent\Tests;

use gioppy\YamlContent\Services\YamlContent;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;

class YamlContentTest extends TestCase {

  use WithFaker;

  private $config;

  public function setUp(): void {
    parent::setUp();

    $this->config = [
      // storage name
      'storage' => 'public',
      // default subdirectory
      'folder' => '/common',
      // yaml settings
      'yaml' => [
        'inline' => 15,
        'indent' => 2
      ],
      // key and value for index.yaml content
      'index' => [
        'fileName' => 'index',
        'key' => 'uuid',
        'value' => 'title'
      ],
      // array key name where stored fields
      'fields_key' => 'form'
    ];
  }

  public function testYamlContentWillBeInitialized() {
    $yamlContent = new YamlContent();
    $yamlContent->setConfiguration($this->config);

    $storage = $yamlContent->getStorage();

    $this->assertIsObject($yamlContent);
    $this->assertIsObject($storage);
  }

  public function testYamlContentSaveFile() {
    $disk = Storage::fake($this->config['storage']);

    $yamlContent = new YamlContent();
    $yamlContent->setConfiguration($this->config);
    $uuid = $this->faker->uuid;

    $yamlContent->save($uuid, [
      'uuid' => $uuid,
      'title' => 'Test',
      'description' => 'Description'
    ]);

    $disk->assertExists("common/$uuid.yaml");

    $savedFile = $yamlContent->get($uuid);

    $this->assertEquals($uuid, $savedFile['uuid']);
    $this->assertEquals('Test', $savedFile['title']);
    $this->assertEquals('Description', $savedFile['description']);
  }

  public function testYamlContentWillBeUpdated() {
    Storage::fake($this->config['storage']);

    $yamlContent = new YamlContent();
    $yamlContent->setConfiguration($this->config);
    $uuid = $this->faker->uuid;

    $yamlContent->save($uuid, [
      'uuid' => $uuid,
      'title' => 'Test',
      'description' => 'Description'
    ]);

    $yamlContent->update($uuid, [
      'title' => 'New test'
    ]);

    $savedFile = $yamlContent->get($uuid);

    $this->assertEquals('New test', $savedFile['title']);
  }

  public function testYamlContentWillBeDeleted() {
    $disk = Storage::fake($this->config['storage']);

    $yamlContent = new YamlContent();
    $yamlContent->setConfiguration($this->config);
    $uuid = $this->faker->uuid;

    $yamlContent->save($uuid, [
      'uuid' => $uuid,
      'title' => 'Test',
      'description' => 'Description'
    ]);

    $yamlContent->delete($uuid);

    $disk->assertMissing("common/$uuid.yaml");
  }

  public function testYamlContentIndexIsCreated() {
    $disk = Storage::fake($this->config['storage']);

    $yamlContent = new YamlContent();
    $yamlContent->setConfiguration($this->config);
    $uuid1 = $this->faker->uuid;

    $yamlContent->save($uuid1, [
      'uuid' => $uuid1,
      'title' => 'Test',
      'description' => 'Description'
    ]);

    $disk->assertExists("common/index.yaml");

    $index = $yamlContent->index();

    $this->assertCount(1, $index);
    $this->assertArrayHasKey($uuid1, $index);

    $uuid2 = $this->faker->uuid;

    $yamlContent->save($uuid2, [
      'uuid' => $uuid2,
      'title' => 'Test 2',
      'description' => 'Description'
    ]);

    $index = $yamlContent->index();
    $this->assertCount(2, $index);
    $this->assertArrayHasKey($uuid2, $index);
  }

  public function testYamlContentIndexWillBeUpdated() {
    Storage::fake($this->config['storage']);

    $yamlContent = new YamlContent();
    $yamlContent->setConfiguration($this->config);

    $uuid1 = $this->faker->uuid;
    $uuid2 = $this->faker->uuid;

    $yamlContent->save($uuid1, [
      'uuid' => $uuid1,
      'title' => 'Test',
      'description' => 'Description'
    ]);

    $yamlContent->save($uuid2, [
      'uuid' => $uuid2,
      'title' => 'Test',
      'description' => 'Description'
    ]);

    $yamlContent->delete($uuid2);

    $index = $yamlContent->index();

    $this->assertCount(1, $index);
  }

  public function testYamlContentIndexWillBeDeleted() {
    $disk = Storage::fake($this->config['storage']);

    $yamlContent = new YamlContent();
    $yamlContent->setConfiguration($this->config);
    $uuid = $this->faker->uuid;

    $yamlContent->save($uuid, [
      'uuid' => $uuid,
      'title' => 'Test',
      'description' => 'Description'
    ]);

    $yamlContent->delete($uuid);

    $disk->assertMissing("common/index");
  }

  public function testYamlContentWillBeUpdatedWithIndex() {
    Storage::fake($this->config['storage']);

    $yamlContent = new YamlContent();
    $yamlContent->setConfiguration($this->config);
    $uuid = $this->faker->uuid;

    $yamlContent->save($uuid, [
      'uuid' => $uuid,
      'title' => 'Test',
      'description' => 'Description'
    ]);

    $yamlContent->update($uuid, [
      'title' => 'New test'
    ]);

    $index = $yamlContent->index();

    $this->assertEquals('New test', $index[$uuid]);
  }
}
