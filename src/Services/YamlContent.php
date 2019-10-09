<?php


namespace gioppy\YamlContent\Services;

use gioppy\YamlContent\Contracts\YamlContentContract;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

class YamlContent implements YamlContentContract {

  private $configuration;

  private $disk;

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    $this->disk = Storage::disk($configuration['storage']);

    return $this;
  }

  /**
   * Get storage disk
   *
   * @return string
   */
  public function getStorage() {
    return $this->disk;
  }

  /**
   * Get index file name from configuration
   *
   * @return mixed
   */
  protected function getIndexFileName() {
    return $this->configuration['index']['fileName'];
  }

  /**
   * Get sub forlder name from configuration
   *
   * @return mixed
   */
  protected function getFolder() {
    return $this->configuration['folder'];
  }

  /**
   * Parse generic YAML file, outside of Storage driver
   *
   * @param string $file
   * @return mixed
   */
  public static function parseFile(string $file) {
    return Yaml::parseFile($file);
  }

  /**
   * @inheritDoc
   */
  public function index() {
    try {
      $file = $this->disk->get("{$this->getFolder()}/{$this->getIndexFileName()}.yaml");
      return Yaml::parse($file);
    } catch (FileNotFoundException $exception) {
      return [];
    }
  }

  /**
   * @inheritDoc
   * @throws FileNotFoundException
   */
  public function get(string $fileName) {
    if (!$this->disk->exists("{$this->getFolder()}/$fileName.yaml")) {
      throw new FileNotFoundException();
    }

    $file = $this->disk->get("{$this->getFolder()}/$fileName.yaml");

    return Yaml::parse($file);
  }

  /**
   * @inheritDoc
   * @throws FileNotFoundException
   */
  public function save(string $fileName, array $data) {
    $this->saveToFile($fileName, $data);
    $this->createOrUpdateIndex($data);
  }

  /**
   * @inheritDoc
   * @throws FileNotFoundException
   */
  public function update(string $fileName, array $data) {
    $content = $this->get($fileName);
    $updatedContent = array_merge($content, $data);

    $this->save($fileName, $updatedContent);
    $this->createOrUpdateIndex($updatedContent);
  }

  /**
   * @inheritDoc
   * @throws FileNotFoundException
   */
  public function delete(string $fileName) {
    $data = $this->get($fileName);
    $this->disk->delete("{$this->getFolder()}/$fileName.yaml");
    $this->updateOrDeleteIndex($data);
  }

  /**
   * Create or update index.yaml file
   *
   * @param array $data
   */
  protected function createOrUpdateIndex(array $data) {
    $index = $this->disk->exists("{$this->getFolder()}/{$this->getIndexFileName()}.yaml");

    $key = $this->configuration['index']['key'];
    $value = $this->configuration['index']['value'];

    if ($index) {
      $content = $this->index();
      $content[$data[$key]] = $data[$value];
    } else {
      $content = [
        $data[$key] => $data[$value]
      ];
    }

    $this->saveToFile($this->getIndexFileName(), $content);
  }

  /**
   * Update or delete index
   *
   * @param array $data
   */
  protected function updateOrDeleteIndex(array $data) {
    $content = $this->index();
    unset($content[$data[$this->configuration['index']['key']]]);

    if (empty($content)) {
      // if content is empty, delete the file
      $this->disk->delete("{$this->getFolder()}/{$this->getIndexFileName()}.yaml");
    } else {
      $this->saveToFile($this->getIndexFileName(), $content);
    }
  }

  /**
   * Save data into YAML file
   *
   * @param string $fileName
   * @param array $content
   */
  private function saveToFile(string $fileName, array $content) {
    $yaml = Yaml::dump($content, $this->configuration['yaml']['inline'], $this->configuration['yaml']['indent']);
    $this->disk->put("{$this->getFolder()}/$fileName.yaml", $yaml);
  }
}
