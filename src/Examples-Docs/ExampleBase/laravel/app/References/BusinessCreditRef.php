<?php
namespace App\References;
use PkExtensions\PkRefManager;
class BusinessCreditRef extends PkRefManager {
  public static $refArr = [
      null => '(Undeclared)',
      0 => 'Not Sure',
      90 => 'Class 1: 580 - 670',
      80 => 'Class 2: 530 - 579',
      70 =>  'Class 3: 481 - 529',
      60 =>  'Class 4: 453 - 480',
      50 =>  'Class 5: 101 - 452',
      ];
}
