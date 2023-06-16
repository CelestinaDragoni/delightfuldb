<?php

namespace DelightfulDB\Models;
use DelightfulDB\Models\Index as Index;
use DelightfulDB\Services\Helper as Helper;
use DelightfulDB\Exceptions\Model as Exception;

class Store
{
  /** Field Express Limit **/
  private $_exp = '/^[\w]{1,}$/';

  /** Store Name **/
  private $_name = '';

  /** Store Directory **/
  private $_directory = '';

  /** Store Indexes **/
  private $_indexes = [];

  /** Write Lock Timeout **/
  private $_timeout = 5;

  /**
   * Class Constructor
   * @param String $root
   * @param String $store
   * @return Void
   */
  public function __construct(String $root, String $store, Int $timeout)
  {
    $dir = "$root/$store";

    if (!is_dir($dir)) {
      if (!preg_match($this->_exp, $store)) {
        throw new Exception('STORE_KEY_MALFORMED', $store);
      }

      if (!mkdir($dir, 0755, true)) {
        throw new Exception('STORE_FAILED_DIRECTORY_CREATE', $store);
      }
    }

    if (!is_writable($dir)) {
      throw new Exception('STORE_NOT_WRITABLE', $store);
    }

    $this->_name = $store;
    $this->_directory = $dir;

    if ($timeout > 0) {
      $this->_timeout = $timeout;
    }
  }

  /**
   * Check For Write Lock
   * @return bool
   */
  public function hasWriteLock()
  {
    if (file_exists("{$this->_directory}/_lock.ddl")) {
      return true;
    }

    return false;
  }

  /**
   * Set Write Lock
   * @return Void
   */
  public function setWriteLock() : Void
  {
    $this->waitWriteLock();
    $retval = file_put_contents("{$this->_directory}/_lock.ddl", '');
    if ($retval === false) {
      throw new Exception('STORE_LOCK_WRITE_FAILURE', $store);
    }
  }

  /**
   * Remove Write Lock
   * @return Void
   */
  public function removeWriteLock() : Void
  {
    $lockFile = "{$this->_directory}/_lock.ddl";
    if (file_exists($lockFile) && is_writable($lockFile)) {
      @unlink($lockFile);
    }
  }

  /**
   * Wait Write Lock
   * @return Void
   */
  public function waitWriteLock() : Void
  {
    $timeout = time() + $this->_timeout;
    while (true) {
      if (!$this->hasWriteLock()) {
        return;
      }
      if ($timeout < time()) {
        return;
      }
      usleep(500);
    }
  }

  /**
   * Index Generator
   * @return Generator
   */
  public function iterateIndexes()
  {
    foreach ($this->_indexes as $index) {
      yield $index;
    }
  }

  /**
   * Add Index To Store
   * @param String $index
   * @param Array $config
   * @return Void
   */
  public function addIndex(String $index, Array $config) : Void
  {
    $this->_indexes[$index] = new Index($this->_directory, $this->_name, $index, $config);
  }

  /**
   * Get Index From Store
   * @param String $index
   * @return DelightfulDB\Models\Index
   */
  public function getIndex(String $index)
  {
    if (!isset($this->_indexes[$index])) {
      throw new Exception('STORE_INDEX_INVALID_KEY', $store);
    }

    return $this->_indexes[$index];
  }

  /**
   * Create Document Filename
   * @return String
   */
  public function getDocumentFilename(String $id)
  {
    return "{$this->_directory}/$id.ddb";
  }

  /**
   * Get Store Directory
   * @return String
   */
  public function getDirectory() : String
  {
    return $this->_directory;
  }

  /**
   * Get Store Name
   * @return String
   */
  public function getName() : String
  {
    return $this->_name;
  }

  /**
   * Write Document
   * @param String $filename
   * @param Array $document
   * @return String
   */
  public function writeDocument(String $filename, Array $document)
  {
    return file_put_contents(
      $filename,
      json_encode($document, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE)
    );
  }

  /**
   * Load Document
   * @param String $filename
   * @return Array
   */
  public function loadDocument(String $filename)
  {
    return json_decode(
      file_get_contents($filename),
      true,
      512,
      JSON_INVALID_UTF8_SUBSTITUTE
    );
  }
}
