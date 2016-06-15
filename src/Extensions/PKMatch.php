<?php
namespace PkExtensions\PkMatch;

/** Way too simple to call it a query - just does a simple match on simple criteria
 * It has two levels of variables - the names and types of fields it should match -
 * but then has values and criteria
*/

class PkMatch {
  public static $criteriaTypes = [
    'numeric' => [
       '0' => "Don't Care"
       '>' => 'More Than',
       '<' => 'Less Than',
       '=' => 'Equal To',
       '!=' => 'Not Equal To',
      ],
    'string' => [
      '0' => "Don't Care",
      'LIKE' => 'Is',
      '%LIKE' => 'Starts With',
      'LIKE%' => 'Ends With',
      '%LIKE%' => 'Contains',
  ],
      'group' => [
      '0' => "Don't Care",
      'IN' => 'In',
      'NOTIN' => 'Not In',
  ],
  'within' =>
      '0' => "Don't Care",
      '1' => 'Within 1 mile',
      '5' => 'Within 5 miles',
      '10' => 'Within 10 miles',
      '20' => 'Within 20 miles',
      '50' => 'Within 50 miles',
  ],
  'betweenQueryCrit' => [
      '0' => "Don't Care",
      'BETWEEN' => 'Between',
      ]
  ];
       
       
       
       ,
        ],
  ];
  public $criteriType;
  public function __construct($argarr[]) {
  }

}
