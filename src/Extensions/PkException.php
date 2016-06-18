<?php
namespace PkExtensions;
use \Exception;
class PkException extends Exception {
//string $message = "" [, int $code = 0 [, Throwable $previous = NULL ]]]
  public function __construct($message = '', $code = 0, $previous = null) {
    pkstack (10,1);
    parent::__construct($message,$code,$previous);

  }
}
