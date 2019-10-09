<?php


namespace gioppy\YamlContent\Contracts;


interface YamlContentContract extends YamlContentConfiguration {

  /**
   * Get parsed file content as array
   *
   * @param string $fileName
   * @return mixed
   */
  public function get(string $fileName);

  /**
   * Get parse index content as array, or return empty array
   *
   * @return array
   */
  public function index();

  /**
   * Save data and create or update index
   *
   * @param string $fileName
   * @param array $data
   */
  public function save(string $fileName, array $data);

  /**
   * Update data
   *
   * @param string $fileName
   * @param array $data
   */
  public function update(string $fileName, array $data);

  /**
   * Delete file
   *
   * @param string $fileName
   */
  public function delete(string $fileName);
}
