<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions\Models;
use PkExtensions\Traits\BuildQueryTrait;
use Illuminate\Database\Eloquent\Model;
/**
 * Abstract model class to persist and implement searches
 * Implementations will generally have paired field names, based on the fields
 * or calculated values of the searched model. If the searched model has 
 * fields of 'height', 'weight', 'age', the searching model might have fields
 * of 'height_crit', 'height_val', 'weight_crit', 'weight_val', 'age_crit', 'age_val',
 * etc. Where the contents of the *_crit fields are SQL comparison operators as
 * below, and the content of the *_val fields are compared against.
 *
 * Uses BuilQueryTrait - SO CAN BUILD HTML CONTROLS/Inputs for SEARCH FORMS!!
 * <p>
 * Implementations build an array keyed by field names, containing the crit and
 * val contents - for example, ['height']=>['crit'=>'>', 'val'=>190]
 * @author Paul Kirkaas
 */
abstract class PkSearchModel extends PkModel {
  use BuildQueryTrait;

  public $cleanAllText = false; #Default - runs hpure/escapes every text field
  /* An array of keys to be searched, containing the criteria and values */
  public function initializeQuerySets() {
    $this->querySets = $this->buildQuerySets($this->getAttributes());
    return $this->querySets;
  }

  public function searchCtls() {
    return static::buildSearchControlArray($this);
  }

  


}
