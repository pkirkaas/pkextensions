<?php
namespace App\Models;
use PkExtensions\Models\PkModel;

class NewModel extends PkModel {
  public static $table_field_defs = [
      'boolean1' => ['type' => 'boolean', 'methods' => ['nullable', 'default' => 1]],
      'name' => ['type' => 'string', 'methods' => 'nullable', 'type_args'=>2000],
      'purpose_id' => ['type' => 'integer', 'methods' => 'nullable'],
    ];

 #Load Relations - if the controller should update the many side
  public static $load_relations = [
      'items' => 'App\\Models\\Item',
  ];

  #Map the ID to the display value - see PkModel
  #Can also be method names
  public static $display_value_fields = [
      'purpose_id' => 'App\References\PurposeRef',
       #$this->purpose_id_DV returns the Reference/name of the id
      'totals' => 'PkExtensions\Formatters\PkFormatDollar',
       #$this->totals_DV($arg) returns $this->totals($arg) formatted by PkFormatDollar
  ];

#Methods accessable as attribute names - for 
  public static $methodsAsAttributeNames = [
    'amethod',
  ];


  public function items() {
    return $this->hasMany('\App\Models\Item');
  }

  public function owner() {
    return $this->belongsTo('App\Models\Owner');
  }


#If want to return array of ALL real & virtual attributes
#  public function getCustomAttributes($arg = null) {
#    $myAtts = $this->getAttributes();
#    $relationAtts = $this->getRelationshipAttributes();
#    $methodAtts = $this->getMethodAttributes();
#    $dvAtts = $this->getDisplayValueAttributes();
#    return array_merge($dvAtts, $methodAtts, $relationAtts, $myAtts);
#  }

}
