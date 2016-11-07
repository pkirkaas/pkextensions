<?php
namespace App\References;
use PkExtensions\PkRefManager;
class CollateralRef extends PkRefManager {
  public static $refArr = [
     'Savings/CD',
     'Equipment',
     'Business Assets',
     'Marketable Securities',
     'Real Estate',
     'Unsecured',
  ];
}
