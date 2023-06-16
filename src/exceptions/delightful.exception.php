<?php

namespace DelightfulDB\Exceptions;

use DelightfulDB\Models\Store as Store;
use DelightfulDB\Models\Index as Index;

/**
 * DelightfulDB Exception Handler
 * The name of this class really wants me to throw exceptions for fun.
 */
class Delightful extends \Exception {
  /** Error Token **/
  protected $_errorToken = '';

  /** Error Store Name **/
  protected $_errorStore = '';

  /** Error Index Name **/
  protected $_errorIndex = '';

  /** Error Class Name **/
  protected $_errorClass = '';

  /** Error Document Id **/
  protected $_errorDocumentId = '';

  /** Error Method Name **/
  protected $_errorMethod = 'λƒ';

  /** Error Line Number **/
  protected $_errorLine = -1;

  /**
   * Returns a structured array of error objects
   * @return Array
   */
  public function getErrors() : Array
  {
    return [
      'token'   => $this->_errorToken,
      'store'   => $this->_errorStore,
      'index'   => $this->_errorIndex,
      'class'   => $this->_errorClass,
      'method'  => $this->_errorMethod,
      'line'    => $this->_errorLine,
      'id'      => $this->_errorDocumentId,
      'message' => $this->getMessage(),
    ];
  }

  /**
   * Class Constructor
   * @param String $error
   * @param Mixed $store
   * @param Mixed $index
   * @param String $id
   * @param Int $code
   * @param Throwable $previous
   * @return Void
   */
  public function __construct(String $error = '', $store = null, $index = null, String $id = '', Int $code = 1, \Throwable $previous = null)
  {
    // For Class and Method Message Output
    $backtrace = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1];

    // Get Exception Class Name
    $exception = get_called_class();

    // Get Class Name (If Applicable)
    $method = '';
    if (!empty($backtrace['class'])) {
      $this->_errorClass = $backtrace['class'];
      $method = $backtrace['class'] . '::';
    }

    // Get Function Name
    if (!empty($backtrace['function'])) {
      $this->_errorMethod = $backtrace['function'];
      $method = $method . $backtrace['function'];
    } else {
      $method = $method . 'λƒ';
    }

    // Get Line Number
    if (!empty($backtrace['line'])) {
      $this->_errorLine = $backtrace['line'];
      $method = $method . ':' . $backtrace['line'];
    }

    // Check For Empty Error
    if (empty($error)) {
      $error = 'UNKNOWN_EXCEPTION';
    }

    // Set Extra Data
    $this->_errorToken = $error;

    // Load Store String
    if (is_a($store, 'DelightfulDB\Models\Store')) {
      $this->removeWriteLock();
      $this->_errorStore = $store->getName();
    } else if (is_string($store)) {
      $this->_errorStore = $store;
    }

    // Load Index String
    if (is_a($store, 'DelightfulDB\Models\Index')) {
      $this->_errorIndex = $index->getName();
    } else if (is_string($index)) {
      $this->_errorIndex = $index;
    }

    // Create Message
    $message = "$exception [$method] $error";

    // Initialze Parent Exception
    parent::__construct($message, $code, $previous);
  }
}
