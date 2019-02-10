<?php
/*
 * Manages media files/urls that are embedded as a field in the model/table -
 * simple - the model definition 
 * public static $media_entries [
 *   'avatar' => ['type'=>'image', ... limitations, resize, subtype],
 *   'resume' => ['type'=>'doc', 'method'=>'embed'],
 * ];
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace PkExtensions\Traits;
use PkExtensions\PkFileUploadService;
//use PkExtensions\Traits\PkUploadTrait; #Just the static methods
use Storage;

/**
 *
 * @author pkirkaas
 */
trait PkEmbeddedMediaTrait {
  public static function getMediaEntries() {
    $mediaEntries = static::getArraysMerged('media_entries');
    return $mediaEntries;
  }

  
  /** Keys Appended to media entry names, values map to the method
   * to be called for the extra attribute
   * @var type 
   */
  public static $entry_appends = ['_url'=>'getUrl'];
  public static function getLocalAttributeNames() {
    $ret = [];
    $entries = array_keys(static::getMediaEntries());
    foreach ($entries as $entry) foreach (array_keys(static::$entry_appends) as $eap) {
      $ret[] = $entry.$eap;
    }
//    pkdebug ("Extra attribute names: ", $ret);
    return $ret;
  }

  
  public function __get($key) {
    if (in_array($key,static::getLocalAttributeNames(),1)) {
      $fieldend = strrpos($key,'_');
      $field = substr($key, 0, $fieldend);
      $append = substr($key,$fieldend);
      $method = static::$entry_appends[$append] ?? null;
      $url = $this->$method($field);
      pkdebug("Field: [$field], append: [$append], method: [$method], url: [$url]");
      return $url;
    }
    return parent::__get($key);
  }

  public function __set($key,$val) {
    if (in_array($key,array_keys(static::getMediaEntries()),1)) {
       pkdebug("KEY: [$key], VAL is: ",$val);
      if (!$val) {
        $this->deleteEntry($key);
        parent::__set($key,null);
        $this->save();
        return;
      } else {
        if ($val == 1) {
          $params = [];
        } else if (ne_string($val)) {
          $params=['url'=>$val];
        } else if (is_array($val)){
          pkdebug("val is: ",$val);
          $params = $val;
        } else {
          $params = [];
        }
        if (!keyVal('type', $params)) {
          $params['type']=keyVal('type',static::getMediaEntries()[$key]);
        }
        $result = $this->createEntry($key,$params);
        if (ne_string($result)) {
          parent::__set($key,$result);
          $this->save();
          pkdebug("Leaving set - key: [$key], value of this->key:",$this->$key);
          return;
          /*
          $this->$key = $result;
          $this->save();
          pkdebug("Leaving set - key: [$key], value of this->key:",$this->$key);
           * *
           */
        } else {
          pkdebug("The result was:",$result);
        }
      }
      return;
    }
    return parent::__set($key,$val); 
  }


  #An entry can be a URL to somewhere else, or else a local
  #file reference
  public function getUrl($field,$default=null,$subdir='public') {
    $segment = $this->$field; #URL or relative path to storage
    if (!$segment) {
      return $default;
    }
    if  (isUrl($segment)) {
      return $segment;
    }
    $url = Storage::url($segment);
    //$url = PkFileUploadService::sfile_path($segment,$subdir);
    pkdebug("The generated URL: [$url]");
    return $url ?? $default;
  }

  public function deleteEntry($field, $subdir='public') {
    $segment = $this->$field;
    if (!$segment) return;
    if (!isUrl($segment)) {
      Storage::delete($segment);
    }
    $this->$field = null;
    return;
  }

  /**
   * Takes an array of parameters & either uploads a file or includes the 
   * URL, performs validation if specified, resizing if specified
   * sets the field & returns
   * @param string - field name
   * @param assoc array $params
   *   url: string URL/rel URL or empty
   *   uploadurl: boolean: true - upload from URL, else just enter
   *   attribute: string|empty - the FILES key, or just the first entry
   *   type: string or empty - if uploading, verifies the general type
   *   validators: empty
   * @return string relative path or filename 
   */
  public function createEntry($field,$params=[]) {
    $this->deleteEntry($field);
    $result = PkFileUploadService::intake($params);
    pkdebug("Result is:",$result);
    if (ne_string($result)) {
      $this->$field = $result;
    } else { 
      $this->$field = null;
    }
    return $result;
  }
    

  
  //put your code here
}
