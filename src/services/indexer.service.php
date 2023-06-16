<?php

namespace DelightfulDB\Services;

use DelightfulDB\Exceptions\Service as Exception;
use DelightfulDB\Models\Store as Store;
use DelightfulDB\Models\Index as Index;
use DelightfulDB\Services\Helper as Helper;

class Indexer {
  /**
   * Singleton Service Constructor
   * @return DelightfulDB\Services\Indexer
   */
  public static function getInstance() : Indexer
  {
    static $instance = false;
    return ($instance) ? $instance : $instance = new Indexer();
  }

  /**
   * Write Indexes For Document
   * @param DelightfulDB\Models\Store $store
   * @param Array $document
   * @param String $id
   * @return Void
   */
  public function writeIndexes(Store &$store, Array &$document, String &$id) : Void
  {
    // Clean Our Indexes
    $this->_cleanIndexes($store, $id);

    // Loop Through Index Collection
    foreach ($store->iterateIndexes() as $index) {
      $name = $index->getName();
      if ($name == '_fulltext') {
        $this->_writeFulltext($store, $index, $document, $id);
      } else if (isset($document[$name]))  {
        $this->_writeIndex($store, $index, $id, $document[$name]);
      }
    }
  }

  /**
   * Delete Document Indexes
   * @param DelightfulDB\Models\Store $store
   * @param String $id
   * @return Void
   */
  public function deleteIndexes(Store &$store, String &$id)
  {
    // Clean Our Indexes
    $this->_cleanIndexes($store, $id);

    // Checked Indexes for Distinct
    foreach ($store->iterateIndexes() as $index) {
      if ($index->getName() !== '_fulltext' && $index->isDistinct()) {
        $this->_recalculateDistinct($store, $index);
      }
    }
  }

  /**
   * Cleans Indexes Before Writing
   * @param DelightfulDB\Models\Store $store
   * @param String $id
   * @return Void
   */
  private function _cleanIndexes(Store &$store, String &$id) : Void
  {
    // Glob Iteration
    foreach (glob("{$store->getDirectory()}/indexes/*/$id.*.ddi") as $file) {
      if (!is_writable($file) || !unlink($file)) {
        throw new Exception('INDEXER_CLEAN_ERROR', $store, null, $id);
      }
    }
  }

  /**
   * Write Fulltext Index To Filesystem
   * @param DelightfulDB\Models\Store $store
   * @param DelightfulDB\Models\Index $index
   * @param Array $document
   * @param String $id
   * @return void
   */
  private function _writeFulltext(Store &$store, Index &$index, Array &$document, String &$id) : Void
  {
    // Buffer
    $buffer = [];

    // Build Fulltext
    foreach ($index->getConfig()['fields'] as $field) {
      if (isset($document[$field])) {
        $value = $document[$field];

        // Normalize Values
        if (Helper::checkList($value)) {
          $value = implode(' ', $value);
        } else if (is_numeric($value)) {
          $value = strval($value);
        } else if (is_string($value)) {
          $value = $value;
        } else {
          $value = '';
        }

        // Add To Buffer
        $buffer[] = Helper::filterForbidden($value);
      }
    }

    // Assemble Clean Fulltext
    $buffer = implode(' ', $buffer);

    // Write Fulltext
    $retval = file_put_contents($index->getFulltextFilename($id), $buffer);

    // Error Detection
    if (!$retval) {
      throw new Exception("INDEXER_FULLTEXT_WRITE_ERROR", $store, $index, $id);
    }
  }

  /**
   * Write Custom Indexes for Filesystem
   * @param DelightfulDB\Models\Store $store
   * @param DelightfulDB\Models\Index $index
   * @param String $id
   * @param Mixed $values
   * @return void
   */
  private function _writeIndex(Store &$store, Index &$index, String &$id, &$values) : Void
  {
    // Normalize Input
    if (!is_array($values)) {
      $values = [$values];
    }

    foreach ($values as $value) {
      // Write Index
      $retval = file_put_contents($index->getIndexFilename($id, $value), $value);

      // Error Detection
      if (!$retval) {
        throw new Exception('INDEXER_INDEX_WRITE_ERROR', $store, $index, $id);
      }
    }

    if ($index->isDistinct()) {
      $this->_recalculateDistinct($store, $index);
    }
  }

  /**
   * Caches Distinct Calculation For Later
   * @param String $store
   * @param String $index
   * @param String $idirectory
   * @return void
   */
  private function _recalculateDistinct(Store &$store, Index &$index) : Void
  {
    // Calculation Data
    $data = [];

    // Where Our Distinct Index Will Exist
    $file = $index->getDistinctFilename();

    // Check For Existing File
    if (file_exists($file)) {
      if (!is_writable($file) || !unlink($file)) {
        throw new Exception('INDEXER_DISTINCT_DELETE_ERROR', $store, $index);
      }
    }

    // Get Distinct Calculation
    foreach (glob("{$index->getDirectory()}/*.*.ddi") as $filename) {
      $key = file_get_contents($filename);

      if (isset($data[$key])) {
        $data[$key] += 1;
      } else {
        $data[$key] = 1;
      }
    }

    // Write Index
    $retval = Helper::writeDocument($file, $data);
    if (!$retval) {
      throw new Exception('INDEXER_DISTINCT_WRITE_ERROR', $store, $index);
    }
  }
}
