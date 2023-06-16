<?php

namespace DelightfulDB\Models;
use DelightfulDB\Models\Index as Index;
use DelightfulDB\Services\Helper as Helper;
use DelightfulDB\Exceptions\Model as Exception;

class Index
{
  /** Field Express Limit **/
  private $_exp = '/^[\w]{1,}$/';

  /** Index Name **/
  private $_name = '';

  /** Index Directory **/
  private $_directory = '';

  /** Index Config **/
  private $_config = [];

  /**
   * Class Constructor
   * @param String $root
   * @param String $store
   * @param String $index
   * @param Array $config
   * @return Void
   */
  public function __construct(String $root, String $store, String $index, Array $config)
  {
    if (!preg_match($this->_exp, $index)) {
      throw new Exception('INDEX_KEY_MALFORMED', $store, $index);
    }

    if ($index == '_fulltext') {
      if (isset($config['distinct']) || $config['distinct']) {
        throw new Exception('INDEX_FULLTEXT_NO_DISTINCT', $store, $index);
      }

      if (!isset($config['fields']) || !Helper::checkList($config['fields'])) {
        throw new Exception('INDEX_FULLTEXT_INVALID_FIELDS', $store, $index);
      }

      if (empty($config['fields'])) {
        throw new Exception('INDEX_FULLTEXT_EMPTY_FIELDS', $store, $index);
      }

      foreach($config['fields'] as $field) {
        if (!is_string($field)) {
          throw new Exception('INDEX_FULLTEXT_INVALID_FIELDS_STRING', $store, $index);
        }
      }
    } else {
      if (substr($index, 0, 1) == '_') {
        throw new Exception('INDEX_SYSTEM_RESERVED', $store, $index);
      }

      if (isset($config['distinct']) && !is_bool($config['distinct'])){
        throw new Exception('INDEX_INVALID_DISTINCT_VALUE', $store, $index);
      }
    }

    $dir = "$root/indexes/$index";

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
      throw new Exception('INDEX_FAILED_DIRECTORY_CREATE', $store, $index);
    }

    if (!is_writable($dir)) {
      throw new Exception('INDEX_NOT_WRITABLE', $store, $index);
    }

    $this->_name = $index;
    $this->_directory = $dir;
    $this->_config = $config;
  }

  /**
   * Is Index Distinct
   * @return Bool
   */
  public function isDistinct() : Bool
  {
    return !empty($this->_config['distinct']);
  }

  /**
   * Get Name
   * @return String
   */
  public function getName() : String
  {
    return $this->_name;
  }

  /**
   * Get Configuration Array for Index
   * @return Array
   */
  public function getConfig() : Array
  {
    return $this->_config;
  }

  /**
   * Create Document Filename
   * @return String
   */
  public function getFulltextFilename(String $id)
  {
    return "{$this->_directory}/$id._fulltext.ddi";
  }

  /**
   * Create Document Filename
   * @return String
   */
  public function getDistinctFilename()
  {
    return "{$this->_directory}/_distinct.ddt";
  }

  /**
   * Create Document Filename
   * @return String
   */
  public function getIndexFilename(String $id, $value)
  {
    $hash = Helper::hashIndex($value);
    return "{$this->_directory}/$id.$hash.ddi";
  }

  /**
   * Get Store Directory
   * @return String
   */
  public function getDirectory() : String
  {
    return $this->_directory;
  }
}
