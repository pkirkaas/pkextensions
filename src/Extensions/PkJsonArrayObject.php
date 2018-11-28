<?php
namespace PkExtensions;
/**
 * Description of PkJsonArrayObject
 * Extends ArrayObject with __toString as Json Encode
 *
 * @author pkirkaas
 */
class PkJsonArrayObject extends \ArrayObject {
  public static $jsonopts = JSON_PRETTY_PRINT |
     JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES ;
  public function __toString() {
    return json_encode($this->getArrayCopy(),static::$jsonopts);
  }
}
