<?php
namespace App\References;
use PkExtensions\PkRefManager;
class PersonalCreditRef extends PkRefManager {
  public static $refArr = [
      null => '(Undeclared)',
      0 => 'Not Sure',
      90 => 'A+: 720 or Higher',
      80 => 'Great: 680 - 719',
      70 => 'Good: 650 - 679',
      60 => 'Okay: 600 - 649',
      50 => 'Poor: 500 - 599',
      40 => 'Bad: 499 or below',
      ];
}
