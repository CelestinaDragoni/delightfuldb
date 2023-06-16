<?php

/**
 * DelightfulDB
 * A PHP and *NIX document store filesystem database.
 * Written by Celestina Dragoni
 */
namespace DelightfulDB;
use DelightfulDB\Models\Config as Config;
use DelightfulDB\Exceptions\InitException as Exception;
use DelightfulDB\Services\Query as Query;

class Driver
{
  /** Configuration Object **/
  private $_config = null;

  /** Driver Files **/
  private $_autoload = [
    'exceptions/delightful.exception.php',
    'exceptions/model.exception.php',
    'exceptions/service.exception.php',
    'models/config.model.php',
    'models/index.model.php',
    'models/store.model.php',
    'services/helper.service.php',
    'services/query.service.php',
    'services/indexer.service.php',
  ];

  /**
   * Class Constructor
   * @param String $root Database Storage Root
   * @param Array $stores Stores Configuration
   * @param Int $timeout Database Write Lock Timeout
   * @return void
   */
  public function __construct(String $root, Array $stores, Int $timeout = 5)
  {
    // Load System
    foreach ($this->_autoload as $filename) {
      require_once(dirname(__FILE__) . '/' . $filename);
    }

    // Load Configuration Object
    $this->_config = new Config($root, $stores, $timeout);
  }

  /**
   * Write document to store
   * @param String $store
   * @param Array $document
   * @return String
   */
  public function createDocument(String $store, Array $document) : String
  {
    return Query::getInstance()->createDocument(
      $this->_config->getStore($store),
      $document,
    );
  }

  /**
   * Update document by id
   * @param String $store
   * @param Array $document
   * @param String $id
   * @return String
   */
  public function updateDocumentById(String $store, Array $document, String $id) : String
  {
    return Query::getInstance()->updateDocumentById(
      $this->_config->getStore($store),
      $document,
      $id,
    );
  }

  /**
   * Delete document by id
   * @param String $store
   * @param String $id
   * @return Void
   */
  public function deleteDocumentById(String $store, String $id) : Void
  {
    Query::getInstance()->deleteDocumentById(
      $this->_config->getStore($store),
      $id,
    );
  }

  /**
   * Get Document by Id
   * @param String $store
   * @param String $id
   * @return Array
   */
  public function getDocumentById(String $store, String $id) : Array
  {
    return Query::getInstance()->getDocumentById(
      $this->_config->getStore($store),
      $id,
    );
  }

  /**
   * Get Document By Fulltext Search
   * @param String $store
   * @param String $search
   * @param Bool $optAnd
   * @param Int $limit
   * @param Int $offset
   * @return Array
   */
  public function getDocumentsByFulltextSearch(String $store, String $search, Bool $optAnd = true, Int $limit = 0, Int $offset = 0) : Array
  {
    return Query::getInstance()->getDocumentsByFulltextSearch(
      $this->_config->getStore($store),
      $this->_config->getStore($store)->getIndex('_fulltext'),
      $search,
      $optAnd,
      $limit,
      $offset,
    );
  }

  /**
   * Get Document By Fulltext Search Using PHP Generator
   * @param String $store
   * @param String $search
   * @param Bool $optAnd
   * @param Int $limit
   * @param Int $offset
   * @return \Generator
   */
  public function getDocumentsByFulltextSearchGenerator(String $store, String $search, Bool $optAnd = true, Int $limit = 0, Int $offset = 0) : \Generator
  {
    return Query::getInstance()->getDocumentsByFulltextSearchGenerator(
      $this->_config->getStore($store),
      $this->_config->getStore($store)->getIndex('_fulltext'),
      $search,
      $optAnd,
      $limit,
      $offset,
    );
  }

  /**
   * Get documents by index value.
   * @param String $store
   * @param String $index
   * @param Mixed $value
   * @param Int $limit
   * @param Int $offset
   * @return Array
   */
  public function getDocumentsByIndex(String $store, String $index, $value, Int $limit = 0, Int $offset = 0) : Array
  {
    return Query::getInstance()->getDocumentsByIndex(
      $this->_config->getStore($store),
      $this->_config->getStore($store)->getIndex($index),
      $value,
      $limit,
      $offset,
    );
  }

  /**
   * Get documents by index value using generator.
   * @param String $store
   * @param String $index
   * @param Mixed $value
   * @param Int $limit
   * @param Int $offset
   * @return \Generator
   */
  public function getDocumentsByIndexGenerator(String $store, String $index, $value, Int $limit = 0, Int $offset = 0) : \Generator
  {
    return Query::getInstance()->getDocumentsByIndexGenerator(
      $this->_config->getStore($store),
      $this->_config->getStore($store)->getIndex($index),
      $value,
      $limit,
      $offset,
    );
  }

  /**
   * Count instances of value in index.
   * @param String $store
   * @param String $index
   * @param Mixed $value
   * @param String $id (Exclude ID From Count)
   * @return Int
   */
  public function countValueByIndex(String $store, String $index, $value, String $id = '') : Int
  {
    return Query::getInstance()->countValueByIndex(
      $this->_config->getStore($store),
      $this->_config->getStore($store)->getIndex($index),
      $value,
      $id,
    );
  }

  /**
   * Get distinct values by index.
   * @param String $store
   * @param String $index
   * @return Array
   */
  public function getDistinctByIndex(String $store, String $index) : Array
  {
    return Query::getInstance()->getDistinctByIndex(
      $this->_config->getStore($store),
      $this->_config->getStore($store)->getIndex($index),
    );
  }

  /**
   * Force Reindex Of Documents In Store
   * @param String $store
   * @return Void
   */
  public function forceReindex(String $store) : Void
  {
    Query::getInstance()->forceReindex(
      $this->_config->getStore($store),
    );
  }
}
