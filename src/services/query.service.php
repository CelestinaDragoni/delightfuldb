<?php

namespace DelightfulDB\Services;

use DelightfulDB\Exceptions\Service as Exception;
use DelightfulDB\Exceptions\Delightful as DelightfulException;
use DelightfulDB\Models\Store as Store;
use DelightfulDB\Models\Index as Index;
use DelightfulDB\Services\Helper as Helper;
use DelightfulDB\Services\Indexer as Indexer;

class Query {
  /**
   * Singleton Service Constructor
   */
  public static function getInstance() : Query
  {
    static $instance = false;
    return ($instance) ? $instance : $instance = new Query();
  }

  /**
   * Write Document
   * @param DelightfulDB\Models\Store $store
   * @param Array $document
   * @return String
   */
  public function createDocument(Store &$store, Array &$document) : String
  {
    // Check For Write Lock
    $store->setWriteLock();

    // Generate Id
    $id = Helper::generateId();

    // Document Path
    $file = $store->getDocumentFilename($id);

    // Should Never Happen
    if (file_exists($file)) {
      throw new Exception('QUERY_ID_COLLISION', $store, null, $id);
    }

    // Write Document
    $retval = Helper::writeDocument($file, $document);

    // Write Error
    if (!$retval) {
      throw new Exception('QUERY_WRITE_ERROR', $store, null, $id);
    }

    // Write Indexes
    try {
      Indexer::getInstance()->writeIndexes($store, $document, $id);
    } catch (DelightfulException $e) {

      // Rollback Transaction
      $this->deleteDocumentById($store, $id);

      // Throw Original Exception
      throw $e;
    }

    // Remove Write Lock
    $store->removeWriteLock();

    // Return Document Id
    return $id;
  }

  /**
   * Update Document by Id
   * @param DelightfulDB\Models\Store $store
   * @param Array $document
   * @param String $id
   * @return String
   */
  public function updateDocumentById(Store &$store, Array &$document, String &$id) : String
  {
    // Check For Write Lock
    $store->setWriteLock();

    // Prevent Invalid IDs and Directory Taversing Exploits
    if (!Helper::validateId($id)) {
       throw new Exception('QUERY_ID_MALFORMED', $store, null, $id);
    }

    // Document Path
    $file = $store->getDocumentFilename($id);

    // Check For Existing File
    if (!file_exists($file)) {
      throw new Exception('QUERY_FILE_MISSING', $store, null, $id);
    }

    // Check For Write
    if (!is_writable($file)) {
      throw new Exception('QUERY_NOT_WRITABLE', $store, null, $id);
    }

    // Write Document
    $retval = Helper::writeDocument($file, $document);

    // Write Error
    if (!$retval) {
      throw new Exception('QUERY_WRITE_ERROR', $store, null, $id);
    }

    // Write Indexes
    try {
      Indexer::getInstance()->writeIndexes($store, $document, $id);
    } catch (DelightfulException $e) {

       // Rollback Transaction
      $this->deleteDocumentById($store, $id);

      // Throw Original Exception
      throw $e;
    }

    // Remove Write Lock
    $store->removeWriteLock();

    // Return Document Id
    return $id;
  }

  /**
   * Delete Document by Id
   * @param DelightfulDB\Models\Store $store
   * @param String $id
   * @return String
   */
  public function deleteDocumentById(Store &$store, String &$id) : Void
  {
    // Check For Write Lock
    $store->setWriteLock();

    // Prevent Invalid IDs and Directory Taversing Exploits
    if (!Helper::validateId($id)) {
      throw new Exception('QUERY_ID_MALFORMED', $store, null, $id);
    }

    // Document Path
    $file = $store->getDocumentFilename($id);

    // Check For Existing File
    if (!file_exists($file)) {
      throw new Exception('QUERY_FILE_MISSING', $store, null, $id);
    }

    // Check For Write
    if (!is_writable($file)) {
      throw new Exception('QUERY_NOT_WRITABLE', $store, null, $id);
    }

    // Remove File
    if(!unlink($file)) {
      throw new Exception('QUERY_DELETE_ERROR', $store, null, $id);
    }

    // Delete Indexes
    Indexer::getInstance()->deleteIndexes($store, $id);

    // Remove Write Lock
    $store->removeWriteLock();
  }

  /**
   * Get Document by Id
   * @param DelightfulDB\Models\Store $store
   * @param String $id
   * @return String
   */
  public function getDocumentById(Store &$store, String &$id) : Array
  {
    // Prevent Invalid IDs and Directory Taversing Exploits
    if (!Helper::validateId($id)) {
       throw new Exception('QUERY_ID_MALFORMED', $store, null, $id);
    }

    // Document Path
    $file = $store->getDocumentFilename($id);

    // Check For Existing File
    if (!file_exists($file)) {
      throw new Exception('QUERY_FILE_MISSING', $store, null, $id);
    }

    // Check For Existing File
    if (!is_readable($file)) {
      throw new Exception('QUERY_NOT_READABLE', $store, null, $id);
    }

    // Fetch Document
    $document = Helper::loadDocument($file);

    // Validate Document
    if (!is_array($document) || empty($document)) {
      throw new Exception('QUERY_READ_ERROR', $store, null, $id);
    }

    // Get File Stats
    $stats = stat($file);

    // Return Document
    return [
      'id'            => $id,
      'dateCreated'   => $stats['ctime'],
      'dateModified'  => $stats['mtime'],
      'document'      => $document,
    ];
  }

  /**
   * Get documents by fulltext search.
   * @param DelightfulDB\Models\Store $store
   * @param DelightfulDB\Models\Index $index
   * @param String $search
   * @param Bool $opAnd
   * @param Int $limit
   * @param Int $offset
   * @return Array
   */
  public function getDocumentsByFulltextSearch(Store &$store, Index &$index, String &$search, Bool $opAnd = true, Int $limit = 0, Int $offset = 0) : Array
  {
    // Wait For Write Lock (If One)
    $store->waitWriteLock();

    // Get Index Directory
    $dir = $index->getDirectory();

    // Build Search Query
    $query = Helper::buildSearchQuery($search, $opAnd);

    // Offset and Limit Counters
    $cOffset = 0;
    $cLimit = 0;

    // Get Documents
    $docs = [];
    foreach (glob("$dir/*._fulltext.ddi", GLOB_NOSORT) as $file) {
      // Do Query
      if (empty(exec("cat $file | awk '$query'"))) {
        continue;
      }

      // Get Document ID From Filename
      $id = Helper::getIdFromFilename($file);

      // Invalid ID, Skip
      if (!$id) {
        continue;
      }

      // Determine Fetch Based Upon Offset And Limits
      $fetch = Helper::determineFetchLimits($limit, $offset, $cLimit, $cOffset);

      // Fetch Document
      if ($fetch === 1) {
        $docs[] = $this->getDocumentById($store, $id);
      }

      // Stop Fetching (Limit Reached)
      if ($fetch === 2) {
        break;
      }
    }

    // Return List of Documents
    return $docs;
  }

  /**
   * Get documents by fulltext search using a generator.
   * @param DelightfulDB\Models\Store $store
   * @param DelightfulDB\Models\Index $index
   * @param String $search
   * @param Bool $opAnd
   * @param Int $limit
   * @param Int $offset
   * @return \Generator
   */
  public function getDocumentsByFulltextSearchGenerator(Store &$store, Index &$index, String &$search, Bool $opAnd = true, Int $limit = 0, Int $offset = 0) : \Generator
  {
    // Wait For Write Lock (If One)
    $store->waitWriteLock();

    // Get Index Directory
    $dir = $index->getDirectory();

    // Build Search Query
    $query = Helper::buildSearchQuery($search, $opAnd);

    // Offset and Limit Counters
    $cOffset = 0;
    $cLimit = 0;

    // Get Documents
    foreach (glob("$dir/*._fulltext.ddi", GLOB_NOSORT) as $file) {
      // Do Query
      if (empty(exec("cat $file | awk '$query'"))) {
        continue;
      }

      // Get Document ID From Filename
      $id = Helper::getIdFromFilename($file);

      // Invalid ID, Skip
      if (!$id) {
        continue;
      }

      // Determine Fetch Based Upon Offset And Limits
      $fetch = Helper::determineFetchLimits($limit, $offset, $cLimit, $cOffset);

      // Fetch Document
      if ($fetch === 1) {
        yield $this->getDocumentById($store, $id);
      }

      // Stop Fetching (Limit Reached)
      if ($fetch === 2) {
        break;
      }
    }

    // Return List of Documents
    return $docs;
  }

  /**
   * Get documents by index value.
   * @param DelightfulDB\Models\Store $store
   * @param DelightfulDB\Models\Index $index
   * @param String $search
   * @param Bool $opAnd
   * @return Array
   */
  public function getDocumentsByIndex(Store &$store, Index &$index, &$value, Int $limit = 0, Int $offset = 0) : Array
  {
    // Wait For Write Lock (If One)
    $store->waitWriteLock();

    // Get Index Directory
    $dir = $index->getDirectory();

    // Get Index Value
    $hash = Helper::hashIndex($value);

    // Offset and Limit Counters
    $cOffset = 0;
    $cLimit = 0;

    // Get Documents By Value Hash
    $docs = [];
    foreach (glob("$dir/*.$hash.ddi", GLOB_NOSORT) as $file) {
      // Get Document ID From Filename
      $id = Helper::getIdFromFilename($file);

      // Invalid ID, Skip
      if (!$id) {
        continue;
      }

      // Determine Fetch Based Upon Offset And Limits
      $fetch = Helper::determineFetchLimits($limit, $offset, $cLimit, $cOffset);

      // Fetch Document
      if ($fetch === 1) {
        $docs[] = $this->getDocumentById($store, $id);
      }

      // Stop Fetching (Limit Reached)
      if ($fetch === 2) {
        break;
      }
    }

    return $docs;
  }

  /**
   * Get documents by index value.
   * @param DelightfulDB\Models\Store $store
   * @param DelightfulDB\Models\Index $index
   * @param String $search
   * @param Bool $opAnd
   * @return \Generator
   */
  public function getDocumentsByIndexGenerator(Store &$store, Index &$index, &$value, Int $limit = 0, Int $offset = 0) : \Generator
  {
    // Wait For Write Lock (If One)
    $store->waitWriteLock();

    // Get Index Directory
    $dir = $index->getDirectory();

    // Get Index Value
    $hash = Helper::hashIndex($value);

    // Offset and Limit Counters
    $cOffset = 0;
    $cLimit = 0;

    // Get Documents By Value Hash
    foreach (glob("$dir/*.$hash.ddi", GLOB_NOSORT) as $file) {
      // Get Document ID From Filename
      $id = Helper::getIdFromFilename($file);

      // Invalid ID, Skip
      if (!$id) {
        continue;
      }

      // Determine Fetch Based Upon Offset And Limits
      $fetch = Helper::determineFetchLimits($limit, $offset, $cLimit, $cOffset);

      // Fetch Document
      if ($fetch === 1) {
        yield $this->getDocumentById($store, $id);
      }

      // Stop Fetching (Limit Reached)
      if ($fetch === 2) {
        break;
      }
    }

    return $docs;
  }

  /**
   * Count instances of value in index.
   * @param DelightfulDB\Models\Store $store
   * @param DelightfulDB\Models\Index $index
   * @param Mixed $value
   * @param String $id (Exclude ID From Count)
   * @return Int
   */
  public function countValueByIndex(Store &$store, Index &$index, $value, String $id = '') : Int
  {
    // Wait For Write Lock (If One)
    $store->waitWriteLock();

    // Get Index Directory
    $dir = $index->getDirectory();

    // Get Index Value
    $hash = Helper::hashIndex($value);

    // Count Existing Values
    $count = 0;
    foreach (glob("$dir/*.$hash.ddi", GLOB_NOSORT) as $file) {
      if (!empty($id)) {
        if ($id != Helper::getIdFromFilename($file)) {
          $count += 1;
        }
      } else {
        $count += 1;
      }
    }

    return $count;
  }

  /**
   * Get distinct values and counts by index. (If distinct is defined for field)
   * @param DelightfulDB\Models\Store $store
   * @param DelightfulDB\Models\Index $index
   * @return Array
   */
  public function getDistinctByIndex(Store &$store, Index &$index) : Array
  {
    // Wait For Write Lock (If One)
    $store->waitWriteLock();

    // Get Index Directory
    $dir = $index->getDirectory();

    // Distinct File
    $file = $index->getDistinctFilename();

    // Check For Distinct File
    if (!file_exists($file)) {
      throw new Exception('QUERY_FILE_MISSING', $store, $index, '_distinct');
    }

    // Check If File Is Readable
    if (!is_readable($file)) {
      throw new Exception('QUERY_NOT_READABLE', $store, $index, '_distinct');
    }

    // Fetch Contents and Parse
    $data = Helper::loadDocument($file);

    // Malformed Json Object
    if (!is_array($data)) {
      throw new Exception('QUERY_READ_ERROR', $store, $index, '_distinct');
    }

    return $data;
  }

  /**
   * Reindexes documents in store.
   * Note: Should likely be done through CLI or as an async process as this is effectively a table scan.
   * When: In the event you change the index schema you might want to do this. Typically during a deploy or CI/CD process.
   * @param DelightfulDB\Models\Store $store
   * @return Void
   */
  public function forceReindex(Store &$store) : Void
  {
    // Wait For Write Lock (If One)
    $store->waitWriteLock();

    // Get Index Directory
    $dir = $store->getDirectory($store);

    // Get Documents
    foreach (glob("$dir/*.ddb", GLOB_NOSORT) as $file) {
      // Get Document ID From Filename
      $id = Helper::getIdFromFilename($file);

      // Invalid ID, Skip
      if (!$id) {
        continue;
      }

      // Get Document
      $doc = $this->getDocumentById($store, $id);

      // Write Indexes
      Indexer::getInstance()->writeIndexes($store, $doc['document'], $id);
    }
  }
}
