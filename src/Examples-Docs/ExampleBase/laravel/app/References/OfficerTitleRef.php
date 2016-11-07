<?php
namespace App\References;
use PkExtensions\PkRefManager;
class OfficerTitleRef extends PkRefManager {
  public static $refArr = [
      0 => '(Undeclared)',
      10 => 'President',
      20 => 'CTO',
      30 => 'CFO',
      40 => 'Director',
      50 => 'Advisor',
      90 => 'Other',
      ];
}
