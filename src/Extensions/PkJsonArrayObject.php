<?php
namespace PkExtensions;
/**
 * Description of PkJsonArrayObject
 * Extends ArrayObject with __toString as Json Encode
 * BUT - Can be overridden by custom JSON implementations to customize read/
 * write - specified in the model currently:
 * 
  public static $jsonfields=['skills_m','tskills_m'];
 *BUT if 
  public static $jsonfields=['skills_m','tskills_m'=>'\App\JsonManagers\SkillTree'];
 * Will use that class to read/write/create to/from DB
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
