<?php
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
 * <p>
 * Implementations build an array keyed by field names, containing the crit and
 * val contents - for example, ['height']=>['crit'=>'>', 'val'=>190]
 * @author Paul Kirkaas
 */
abstract class PkSearchModel extends PkModel {
  use BuildQueryTrait;

  /* An array of keys to be searched, containing the criteria and values */
  public function initializeQuerySets() {
    $this->querySets = $this->buildQuerySets($this->getAttributes());
    return $this->querySets;
  }

  


}
