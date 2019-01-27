<?php
/*
 * Assumes a JSON tree structure like:
 * 
 public static $defaultNodes = [
  ['label'=>"Technical Development", 'key'=>'td', "nodes"=>[
      ["label"=>"Web", 'key'=>'web', "nodes"=> [
        ["label"=>"Back End", 'key'=>'be', "nodes" => [
          ["label"=>"Python",'key'=>'py', ],
          ["label" => "JavaScript/Node.js", 'key'=>'js', ],
          ["label" => "PHP", 'key'=>'php', ],
 * // Defaults per node - if idx array, def values are null, else the value
 public static $defaultValues = ["value"=>1,"unset"=>0,"selected"];
 * 
 * Build the default tree with 
 * $defTree = static::defaultTree(); (although you can use other defaults)
 * 
 * Very likely used by descendants of PkJsonArrayObject, but maybe others??
 */

namespace PkExtensions\Traits;

/**
 *
 * @author pkirkaas
 */
trait PkJsonTreeTrait {
  /** Merges trees - but by key "key" - in case the source array ($nodes)
   * changes structure
   * @param array $nodes - has to be indexed array
   * @param array $data - has to be indexed array - from the user/db
   * @param array $skipKeys - has to be indexed array - keys NOT to merge from data
   *  - like "label"
   * @return array merged by node key above. Only works withing a category
   */
  public static function mergeTrees(Array $nodes = [], Array $data=[], Array $skipKeys=[]) {
    $skipKeys[]='nodes';
    if (!ne_array($data)) return $nodes;
    foreach ($nodes as &$node) {
      static::mergeTreeOneDown($node, $data, $skipKeys);
    }
    return $nodes;
  }


  /** Internal utility function for mergeTrees */
  public static function mergeTreeOneDown(&$node, $data, $skipKeys=[]) {
    //pkdebug("In mergTD Node:",$node,'data:',$data);
    if (!$data || !is_array($data)) {
     // pkdebug("Returning because no nodes in Data?", $data);
      return;
    }
    $ckey = $node['key']??null;
    if (!$ckey) return $node;
    $filter = function($row)use ($ckey) { return ($row['key'] ?? null) === $ckey;};
    $sdatarr = array_filter($data,$filter);
    if (!$sdatarr) return $node;
    if (! ne_array($sdatarr)) return;
    $sdata = array_shift($sdatarr);
    if (!is_array($sdata)) return;
    foreach ($sdata as $skey => $sval) {
      if (in_array($skey,$skipKeys,true)) continue;
      $node[$skey]=$sval;
    }
    //pkdebug("Node:",$node,"sdata:",$sdata);
    if (ne_array(keyVal('nodes',$node)) && ne_array(keyVal('nodes',$sdata))) {
      foreach ($node['nodes'] as &$snode) {
        static::mergeTreeOneDown($snode, $sdata['nodes'],$skipKeys);
      }
    }
  }

  /**
   * 
   * @param indexed array $nodes - to set to default, recusively
   * @param mixed idx/assoc array $defaults - to set each node if key doesn't exist
   *   Will convert ['value'=>1,'unset'=>0,'selected'] to ... 'selected'=>null
   * @param boolean $donull - def true - if true & $node.key=>null, replace w. default
   * @return array
   */
  public static function addDefaults(Array $nodes=[], Array $defaults=[],$donull=true) {
    if (!$nodes || !$defaults) return $nodes;
    $defaults = normalizeConfigArray($defaults);
    static::_addDefaults($nodes,$defaults,$donull);
    return $nodes;
  }

  public static function _addDefaults(Array &$nodes = [], Array $defaults=[], $donull=true){
    foreach ($nodes as &$node) {
      foreach ($defaults as $dkey => $dval) {
        if (!array_key_exists($dkey,$node) || (($node[$dkey] === null) && $donull)) {
          $node[$dkey] = $dval;
        }
      }
      if (ne_array('nodes',$node)) {
        static::_addDefaults($node['nodes'],$defaults,$donull);
      }
    }
  }


  /** 
   * Builds the base, virgin node key, absent any intialization or customer
   * values. To construct/reconstruct a model object from the DB, merge with
   * the data array there, remembering the skipKeys array for "label" & such
   * 
   public __construct($data) { //Where $data is array, json string, or nothing
      $array = PkJsonArrayObject::arrayify($data);
   * 
   
   }
   */ 
  /** 
   * Allows a single class to make many kinds of base trees
   * @param array $baseNodes
   * @param array $defaults
   * @param array $data
   * @param array $skipKeys
   * @return type
   */
  public static function makeMergedDataTree(
      Array $baseNodes = [], Array $defaults = [], Array $data=[], Array $skipKeys=[]) {
    $baseTree = static::addDefaults($baseNodes,$defaults);
    $mergedTree = static::mergeTrees($baseTree,$data,$skipKeys);
    return $mergedTree;
  }

  public static function makeDefaultMergedDataTree(Array $data=[]) {
    $skipKeys = static::$skipKeys ?? [];
    return static::makeMergedDataTree(static::defaultTree(),[],$data);
  }
  /**
   *  
   * @param array $customDefaults - opt 
   * @param boolean $replace - replace normal defaults? Will override anyway, but
   *    if true, even keys that exist in defaultValues not in custom will not be added
   * @return array of enhanced nodes
   */
  public static function defaultTree(Array $customDefaults = [], $replace=true) {
    if (ne_array($customDefaults)) {
      $defaults = normalizeConfigArray($customDefaults);
    }
    if (!$replace) {
      $defaultValues = normalizeConfigArray(static::$defaultValues ?? []);
      $defaults = array_merge($defaults, $defaultValues);
    }
    if (!ne_array($defaults)) {
      return static::$defaultNodes;
    }
    return static::addDefaults(static::$defaultNodes,$defaults);
  }

  /**Build tree from $nodes & $defaults. Depends on $nodes each having similar
   * $nodes under the 'nodes' key -- or?
   */
  /*
  public static function merTree(Array $nodes = [], Array $data=[], Array $defaults=[]) {
    if (!$defaults) {
      return $nodes;
    }
    $defaults =  normalizeConfigArray($defaults);
    $dkeys = array_keys($defaults);
    if (is_array_idx($nodes)) {
    }
    foreach ($nodes as $key=>&$node) {
      $
      
    }
  }

  public static function getDefaultTree(Array $customDefault = [], $replace=true) {
    if ($customDefault) {
      $customDefault = normalizeConfigArray($customDefault);
    }
  }
   * *
   */
}
