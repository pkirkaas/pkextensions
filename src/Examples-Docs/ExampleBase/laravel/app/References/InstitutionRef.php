<?php
namespace App\References;
use PkExtensions\PkRefManager;
/**
 * Description of IndustryRef
 * @author Paul Kirkaas
 */
class InstitutionRef extends PkRefManager {
  public static $refArr = [
      10 => 'Bank of Altair',
      20 => 'Sushi Bank',
      30 => 'Friendly Savings and Loans',
      40 => 'Reliable Trust Company',
      50 => 'Stellar Capital',
      ];
}
