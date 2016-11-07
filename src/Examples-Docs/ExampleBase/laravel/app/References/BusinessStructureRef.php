<?php
namespace App\References;
use PkExtensions\PkRefManager;
class BusinessStructureRef extends PkRefManager {
  public static $refArr = [
      null => '(Undeclared)',
      10 => 'Sole Proprietorship',
      20 => 'General Partnership',
      30 => 'S-Corp',
      40 => 'C-Corp',
      50 => 'LLC',
      60 => 'LLP',
      70 => 'Trust',
      80 => 'Non-Profit',
      90 => 'Other',
      ];

}
