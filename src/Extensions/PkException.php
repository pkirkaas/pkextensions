<?php
namespace PkExtensions;
use \Exception;
class PkException extends Exception {
//string $message = "" [, int $code = 0 [, Throwable $previous = NULL ]]]
  /** $message could be an array - then pkdebug it... */
  public function __construct($message = '', $code = 0, $previous = null) {
    if (is_array($message)) $message=print_r($message,1);
    pkstack (10,1);
    parent::__construct($message,$code,$previous);

  }
}
