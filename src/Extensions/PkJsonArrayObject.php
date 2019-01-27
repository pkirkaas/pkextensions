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

  /** Just for descendants to override */
  public function __toArray() {
    return $this->getArrayCopy();
  }

  /** Just try to make $data into an array for building. Could be empty, could
   * be a jsonable string, could already be an array?
   * @param mixed $data
   * @return array
   */
  public static function arrayify($data) {
    if (!$data) return [];
    if (is_array($data)) return $data;
    if ($data instanceOf self) {
      return $data->__toArray();
    }
    if (is_string($data)) { #Is it json?
      $tst = json_decode($data,1);
      if (!json_last_error()) {
        return $tst;
      }
    } #What to do?
    pkdebug("Couldn't convert data to array::",$data);
    return [];
  }
}
