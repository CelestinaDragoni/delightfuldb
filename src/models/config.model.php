<?php

namespace DelightfulDB\Models;
use DelightfulDB\Models\Store as Store;
use DelightfulDB\Services\Helper as Helper;
use DelightfulDB\Exceptions\Model as Exception;

class Config
{
  /** Field Express Limit **/
  private $_exp = '/^[\w]{1,}$/';

  /** Root Filesystem Directory **/
  private $_root = '';

  /** Stores and Indexes Configuration **/
  private $_stores = [];

  /**
   * Class Constructor
   * @param String $root
   * @param Array $stores
   * @return void
   */
  public function __construct(String $root, Array $stores, Int $timeout)
  {
    // Set Root Filesystem
    $this->_initFilesystem($root);

    // Set Stores
    $this->_initStores($this->_root, $stores, $timeout);
  }

  /**
   * Get Store
   * @param String $store
   * @return DelightfulDB\Models\Store
   */
  public function getStore(String $store) : Store
  {
    if (!isset($this->_stores[$store])) {
      throw new Exception('CONFIG_INVALID_STORE', $store);
    }

    return $this->_stores[$store];
  }

  ////////////////////////////////////////////////////
  /// Construction Validation Functions
  ////////////////////////////////////////////////////

  /**
   * Initialize Database Root Directory
   * @param String $root
   * @return Void
   */
  private function _initFilesystem(String $root) : Void
  {
    // Attempt to make directory if it does not exist.
    if (!is_dir($root)) {
      if (!mkdir($root)) {
        throw new Exception('CONFIG_ROOT_FAILED_DIRECTORY_CREATE');
      }
    }

    // Check if we can write to folder.
    if (!is_writable($root)) {
      throw new Exception('CONFIG_ROOT_NOT_WRITABLE');
    }

    $this->_root = $root;
  }

  /**
   * Initialize Stores and Indexes
   * @param String $root
   * @param String $stores
   * @param Int $timeout
   * @return Void
   */
  private function _initStores(String $root, Array $stores, Int $timeout) : Void
  {
    foreach ($stores as $store => $indexes) {
      // Validate Filesystem
      $this->_stores[$store] = new Store($root, $store, $timeout);

      // Validate Indexes
      foreach ($indexes as $index => $config) {
        $this->_stores[$store]->addIndex($index, $config);
      }
    }
  }
}
