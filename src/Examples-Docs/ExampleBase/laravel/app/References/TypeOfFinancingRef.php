<?php
namespace App\References;
use PkExtensions\PkRefManager;
class TypeOfFinancingRef extends PkRefManager {
  public static $refArr = [
      0 => '(Unsure)',
      10 => 'Long Term Loan',
      30 => 'Line of Credit',
      40 => 'SBA',
      60 => 'Business Acquisition',
      70 => 'Start-Up',
      ];
}
