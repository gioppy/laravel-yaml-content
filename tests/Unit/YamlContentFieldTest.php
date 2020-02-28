<?php

namespace gioppy\YamlContent\Tests;

use gioppy\YamlContent\Exceptions\YamlContentFieldDuplicate;
use gioppy\YamlContent\Services\YamlContent;
use gioppy\YamlContent\Services\YamlContentField;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;

class YamlContentFieldTest extends TestCase {

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
      'fields_key' => 'form',
      'public_disk' => 'public',
    ];
  }

  public function testFieldWillBeAddedOnForm() {
    $uuid = $this->faker->uuid;

    $form = $this->setContent($uuid);
    $content = $form->get($uuid);

    $field = $this->setField();

    $data = $field->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'text'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $this->assertCount(1, $content['form']);
    $this->assertEquals('test', $content['form'][0]['name']);
  }

  public function testFieldExists() {
    $uuid = $this->faker->uuid;

    $form = $this->setContent($uuid);
    $content = $form->get($uuid);

    $fieldYaml = $this->setField();

    $data = $fieldYaml->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'text'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $field = $fieldYaml->get('0', $content);

    $this->assertNotNull($field);
    $this->assertEquals('test', $field['name']);
    $this->assertEquals('test', $field['label']);
    $this->assertEquals('text', $field['type']);
  }

  public function testFieldWillBeUpdated() {
    $uuid = $this->faker->uuid;

    $form = $this->setContent($uuid);
    $content = $form->get($uuid);

    $fieldYaml = $this->setField();

    $data = $fieldYaml->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'text'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $field = $fieldYaml->get('0', $content);
    $updatedData = $fieldYaml->update($field, [
      'name' => 'test',
      'label' => 'updated test'
    ], $content, 0, 'text');

    $content = $this->saveData($form, $uuid, $updatedData);

    $this->assertCount(1, $content['form']);
    $this->assertEquals('updated test', $content['form'][0]['label']);
  }

  public function testFieldWillBeDeleted() {
    $uuid = $this->faker->uuid;

    $form = $this->setContent($uuid);
    $content = $form->get($uuid);

    $fieldYaml = $this->setField();

    $data = $fieldYaml->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'text'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $field = $fieldYaml->get('0', $content);

    $updatedData = $fieldYaml->delete($field, $content, 0, 'text');

    $content = $this->saveData($form, $uuid, $updatedData);

    $this->assertCount(0, $content['form']);
  }

  public function testFieldNameNotDuplicate() {
    $this->expectException(YamlContentFieldDuplicate::class);

    $uuid = $this->faker->uuid;

    $form = $this->setContent($uuid);
    $content = $form->get($uuid);

    $field = $this->setField();

    $data = $field->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'text'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $field->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'text'
    ], $content);
  }

  public function testFieldHasChild() {
    $uuid = $this->faker->uuid;

    $form = $this->setContent($uuid);
    $content = $form->get($uuid);

    $field = $this->setField();

    $data = $field->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'fieldset',
      'is_container' => TRUE
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $data = $field->save([
      'name' => 'test_1',
      'label' => 'test',
      'type' => 'text',
      'parent' => '0'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $this->assertCount(1, $content['form']);
    $this->assertCount(1, $content['form'][0]['fields']);
  }

  public function testFieldHasNChild() {
    $uuid = $this->faker->uuid;

    $form = $this->setContent($uuid);
    $content = $form->get($uuid);

    $field = $this->setField();

    $data = $field->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'fieldset',
      'is_container' => TRUE
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $data = $field->save([
      'name' => 'test_1',
      'label' => 'test',
      'type' => 'fieldset',
      'is_container' => TRUE,
      'parent' => '0'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $data = $field->save([
      'name' => 'test_2',
      'label' => 'test',
      'type' => 'fieldset',
      'parent' => '0.0'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $this->assertCount(1, $content['form']);
    $this->assertCount(1, $content['form'][0]['fields']);
    $this->assertCount(1, $content['form'][0]['fields'][0]['fields']);
  }

  public function testFieldChildWillBeUpdated() {
    $uuid = $this->faker->uuid;

    $form = $this->setContent($uuid);
    $content = $form->get($uuid);

    $fieldYaml = $this->setField();

    $data = $fieldYaml->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'fieldset',
      'is_container' => TRUE
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $data = $fieldYaml->save([
      'name' => 'test_1',
      'label' => 'test',
      'type' => 'text',
      'parent' => '0'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $field = $fieldYaml->get("0.0", $content);
    $data = $fieldYaml->update($field, [
      'name' => 'test_1',
      'label' => 'updated test',
      'type' => 'text',
      'parent' => '0'
    ], $content, '0.0', 'text');

    $content = $this->saveData($form, $uuid, $data);

    $this->assertEquals('updated test', $content['form'][0]['fields'][0]['label']);
  }

  public function testFieldChildWillBeDeleted() {
    $uuid = $this->faker->uuid;

    $form = $this->setContent($uuid);
    $content = $form->get($uuid);

    $fieldYaml = $this->setField();

    $data = $fieldYaml->save([
      'name' => 'test',
      'label' => 'test',
      'type' => 'fieldset',
      'is_container' => TRUE
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $data = $fieldYaml->save([
      'name' => 'test_1',
      'label' => 'test',
      'type' => 'text',
      'parent' => '0'
    ], $content);

    $content = $this->saveData($form, $uuid, $data);

    $field = $fieldYaml->get("0.0", $content);
    $data = $fieldYaml->delete($field, $content, '0.0', 'text');

    $content = $this->saveData($form, $uuid, $data);

    $this->assertCount(0, $content['form'][0]['fields']);
  }

  private function setContent($uuid) {
    Storage::fake($this->config['storage']);

    $yamlContent = new YamlContent();
    $yamlContent->setConfiguration($this->config);

    $yamlContent->save($uuid, [
      'uuid' => $uuid,
      'title' => 'Test',
      'description' => 'Description',
      'form' => []
    ]);

    return $yamlContent;
  }

  private function setField() {
    $field = new YamlContentField();
    $field->setConfiguration($this->config);

    return $field;
  }

  private function saveData(YamlContent $content, $path, $data) {
    $content->save($path, $data);
    return $content->get($path);
  }
}
