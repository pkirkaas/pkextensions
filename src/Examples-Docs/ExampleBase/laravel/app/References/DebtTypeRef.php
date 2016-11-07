<?php
namespace App\References;
use PkExtensions\PkRefManager;
class  DebtTypeRef extends PkRefManager {
  public static $refArr = [
     10 => 'Secured Loan',
     20 => 'Unsecured Loan',
     30 => 'Line Commitment',
     40 => 'Other',
  ];
}
