<?php


namespace gioppy\YamlContent\Contracts;


use Illuminate\Http\UploadedFile;

interface YamlContentFieldContract extends YamlContentConfiguration {

  /**
   * Get field based on parent path
   *
   * @param string $parent
   * @param array $content
   * @return array
   */
  public function get(string $parent, array $content);

  /**
   * Save field data
   *
   * @param array $data
   * @param array $survey
   * @return array
   */
  public function save(array $data, array $survey);

  /**
   * Update field data
   *
   * @param array $field
   * @param array $data
   * @param array $content
   * @param string $index
   * @param string $type
   * @return array
   */
  public function update(array $field, array $data, array $content, string $index, string $type);

  /**
   * Delete field
   *
   * @param array $field
   * @param array $content
   * @param string $index
   * @param string $type
   * @return array
   */
  public function delete(array $field, array $content, string $index, string $type);

  /**
   * Upload a file and get path of file to attach on field attribute
   * If $name is passed, then previous file will be removed if present
   *
   * @param UploadedFile $file
   * @param string $path
   * @param string $name
   * @return string|boolean
   */
  public function uploadFile(UploadedFile $file, string $path = '', string $name = '');

  /**
   * Delete an uploaded file to field
   *
   * @param string $name
   * @return void
   */
  public function deleteFile(string $name);
}
