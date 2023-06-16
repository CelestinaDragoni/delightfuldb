<?php

namespace DelightfulDB\Services;

class Helper {
  /**
   * Generate UUIDv4
   * @return String
   */
  static function generateId() : String
  {
    $data     = openssl_random_pseudo_bytes(16);
    $data[6]  = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8]  = chr(ord($data[8]) & 0x3f | 0x80);
    return strtolower(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
  }

  /**
   * Validate UUIDv4 String
   * @param String $id
   * @return String
   */
  public static function validateId(String $id) : Bool
  {
    return preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}/', $id);
  }

  /**
   * Filters forbidden text for fulltext content and queries
   * @param String $value
   * @return String
   */
  public static function filterForbidden(String $value)
  {
    return preg_replace(
      '/[\.\-\_\=\+\[\{\]\}\\\|\;\:\'\"\,\<\>\?\/\0\t\n\r\!\@\#\$\%\^\&\*\(\)\`\~]/',
      '',
      trim(strtolower(strip_tags($value)))
    );
  }

  /**
   * Fetches the ID from an ddb or ddi file context
   * @param Mixed $filename
   * @return Mixed
   */
  public static function getIdFromFilename($filename)
  {;
    if (empty($filename) || !is_string($filename)) {
      return false;
    }

    $id = explode('.', basename($filename))[0];

    if (!Self::validateId($id)) {
      return false;
    }

    return $id;
  }

  /**
   * Fetch limit and offset helper functions for query service.
   * 1 => Fetch
   * 2 => Offset Not Met, Continue Without Fetching
   * 3 => Break From Query Loop
   * @param Int $limit
   * @param Int $offset
   * @param Int $cLimit
   * @param Int $cOffset
   * @return Int
   */
  public static function determineFetchLimits(Int $limit, Int $offset, Int &$cLimit, Int &$cOffset) : Int
  {
    if ($limit > 0 && $offset > 0) {
      if ($cOffset >= $offset) {
        if ($cLimit < $limit) {
          $cLimit++;
          return 1;
        }
        $cOffset++;
        return 2;
      } else {
        $cOffset++;
        return 0;
      }
    }

    if ($limit > 0) {
      if ($cLimit < $limit) {
        $cLimit++;
        return 1;
      }
      return 2;
    }

    return 1;
  }

  /**
   * Helper function that ensure an array is a list and not an associative/dictionary.
   * Note this function
   * @param Mixed $list
   * @return Bool
   */
  public static function checkList($list) : Bool
  {
    if (
      is_array($list) &&
      (
        $list === [] ||
        array_keys($list) === range(0, count($list) - 1)
      )
    ) {
      return true;
    }

    return false;
  }

  /**
   * Write Document To File
   * @param String $filename
   * @param Array $document
   * @return Mixed
   */
  public static function writeDocument(String $filename, Array $document)
  {
    return file_put_contents(
      $filename,
      json_encode($document, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE)
    );
  }

  /**
   * Load Document From File
   * @param String $filename
   * @return Mixed
   */
  public static function loadDocument(String $filename)
  {
    return json_decode(
      file_get_contents($filename),
      true,
      512,
      JSON_INVALID_UTF8_SUBSTITUTE
    );
  }

  /**
   * Hash Value For Index
   * @param Mixed $value
   * @return String
   */
  public static function hashIndex($value)
  {
    return hash('sha256', json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE));
  }

  /**
   * Build Fulltext Query
   * @param String $search
   * @param Bool $opAnd
   * @return String
   */
  public static function buildSearchQuery(String $search, Bool $opAnd) : String
  {
    // Parameterize Search
    $query = [];
    $terms = explode(' ', $search);
    foreach ($terms as $term) {
      $term = Helper::filterForbidden($term);
      if (!empty($term)) {
        $query[] = "/$term/";
      }
    }

    // Build Query w/ Operator
    $operator = $opAnd ? '&&' : '||';

    return implode(" $operator ", $query);
  }
}
