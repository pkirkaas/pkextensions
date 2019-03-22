<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions;
use \Exception;
class PkException extends Exception {
//string $message = "" [, int $code = 0 [, Throwable $previous = NULL ]]]
  /** $message could be an array - then pkdebug it... */
  public function __construct($message = '', $code = 0, $previous = null) {
    //$stack =  pkstack_base(10, true);
    $stack =  pkstack_base(3, false);
    if (is_array($message)) {
      $message['stack']=$stack;
      $message=print_r($message,1);
    }  else if (is_stringish($message)) {
      $message.= "\nStack:\n$stack\n";
    } else {
      $message = pkdebug_base("Message:", $message, "\nStack:\n$stack");
    }
          
    //pkstack (10,1);
    parent::__construct($message,$code,$previous);

  }
}
