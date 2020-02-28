<?php


namespace gioppy\YamlContent\Services;

use gioppy\YamlContent\Contracts\YamlContentFieldContract;
use gioppy\YamlContent\Exceptions\YamlContentFieldDuplicate;
use gioppy\YamlContent\Exceptions\YamlFormFieldIncorrectType;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class YamlContentField implements YamlContentFieldContract {

  private $configuration;

  private $field;

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;

    return $this;
  }

  /**
   * @inheritDoc
   */
  public function get(string $parent, array $content) {
    //$dotPath = preg_replace('/(\d+)([.*\d+])/x', '$1.fields$2', $parent);
    //$path = explode('.', $dotPath);
    $completePath = explode('.', $parent);
    $dotPath = implode('.fields.', $completePath);
    $path = explode('.', $dotPath);

    $select = &$content[$this->configuration['fields_key']];

    foreach ($path as $segment) {
      $select = &$select[$segment];
    }

    $this->field = $select;

    return $select;
  }

  /**
   * @inheritDoc
   * @throws YamlContentFieldDuplicate
   */
  public function save(array $data, array $survey) {
    if ($this->findUniqueName($data['name'], $survey[$this->configuration['fields_key']]) === TRUE) {
      throw new YamlContentFieldDuplicate("The field {$data['name']} already exists");
    }

    if (isset($data['parent'])) {
      $survey = $this->buildParent($data, $survey);
    } else {
      $survey[$this->configuration['fields_key']][] = $data;
    }

    return $survey;
  }

  /**
   * @inheritDoc
   * @throws YamlFormFieldIncorrectType
   */
  public function update(array $field, array $data, array $content, string $index, string $type) {
    if ($field['type'] != $type) {
      throw new YamlFormFieldIncorrectType("Field type {$field['type']} cannot be type {$type}");
    }

    if (!isset($data['rules']) && isset($field['rules'])) {
      unset($field['rules']);
    }

    // merge with new data
    $newField = array_merge($field, $data);
    // prevent change original field name
    $newField['name'] = $field['name'];

    // remove field from old parent
    if (array_key_exists('parent', $data)) {
      $this->removeField($index, $content);

      // add new field on new parent
      $content = $this->buildParent($newField, $content, $index);
    } else {
      $content[$this->configuration['fields_key']][$index] = $newField;
    }

    return $content;
  }

  /**
   * @inheritDoc
   * @throws YamlFormFieldIncorrectType
   */
  public function delete(array $field, array $content, string $index, string $type) {
    if ($field['type'] != $type) {
      throw new YamlFormFieldIncorrectType("Field type {$field['type']} cannot be type {$type}");
    }

    // remove field from old parent
    if (isset($field['parent'])) {
      $this->removeField($index, $content);
    } else {
      unset($content[$this->configuration['fields_key']][$index]);
    }

    return $content;
  }

  /**
   * @inheritDoc
   * @return false|string
   */
  public function uploadFile(UploadedFile $file, string $path = '', string $name = '') {
    $this->deleteFile($name);

    $savedFiles = Storage::disk($this->configuration['public_disk'])->files($path);

    // separate file name and file extension
    $fileName = preg_replace('/\.[^.\s]{3,4}$/', '', $file->getClientOriginalName());
    preg_match('/\.[^.\s]{3,4}$/', $file->getClientOriginalName(), $fileExt);

    // search on previous files if the base name of uploaded file already exists
    $previousFiles = preg_grep("/$fileName/", $savedFiles);
    $previousFilesCount = count($previousFiles);

    // change name of file if previous uploaded file exists
    if ($previousFilesCount > 0) {
      $fileNewName = $fileName . '_0' . $fileExt[0];

      sort($savedFiles);
      $lastInsert = array_pop($previousFiles);
      if (preg_match_all('/\d+/', $lastInsert, $numbers)) {
        $lastNumber = (int) end($numbers[0]);
        $fileNewName = $fileName . '_' . ($lastNumber + 1) . $fileExt[0];
      }

      return Storage::disk($this->configuration['public_disk'])->putFileAs($path, $file, $fileNewName, 'public');
    }

    return Storage::disk($this->configuration['public_disk'])->putFileAs($path, $file, $file->getClientOriginalName(), 'public');
  }

  /**
   * @inheritDoc
   */
  public function deleteFile(string $name) {
    if ($name && isset($this->field[$name])) {
      Storage::disk('public')->delete($this->field[$name]);
    }
  }

  /**
   * Find original field based on name
   *
   * @param string $name
   * @param array $content
   * @return bool|mixed
   */
  public function find(string $name, array $content) {
    $key = array_search($name, array_column($content, 'name'));
    if (array_key_exists($key, $content)) {
      return $content[$key];
    }

    return FALSE;
  }

  /**
   * Check if there is another field with this name
   *
   * @param string $name
   * @param array $content
   * @return bool
   */
  private function findUniqueName(string $name, array $content) {
    $array = Arr::dot($content);
    return in_array($name, $array, TRUE);
  }

  /**
   * Prepare parent tree
   *
   * @param array $data
   * @param array $content
   * @param string|null $pathIndex
   * @return array
   */
  private function buildParent(array $data, array $content, string $pathIndex = NULL) {
    $parent = $data['parent'];

    $select = &$content[$this->configuration['fields_key']];

    if (!is_null($parent)) {
      $path = explode('.', $parent);

      foreach ($path as $segment) {
        $select = &$select[$segment]['fields'];
      }
    }

    if (!is_null($pathIndex)) {
      preg_match('/(\d+)$/', $pathIndex, $index);
      $select[$index[1]] = $data;
    } else {
      $select[] = $data;
    }

    ksort($select, SORT_NUMERIC);

    return $content;
  }

  /**
   * Remove field based on parent path
   *
   * @param string $parent
   * @param array $content
   */
  private function removeField(string $parent, array &$content) {
    $path = preg_replace('/(\d+)([.*\d+])/x', '$1.fields$2', $parent);

    //$element = Arr::get($content['questions'], $path);

    Arr::forget($content[$this->configuration['fields_key']], $path);

    // remove fields index if it is empty
    /*$parentElement = Arr::get($content['questions'], $element['parent']);
    if (isset($parentElement['fields']) && empty($parentElement['fields'])) {
      $children = preg_replace('/([.]\d+$)/', '', $path);
      Arr::forget($content['questions'], $children);
    }*/
  }
}
