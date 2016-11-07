<?php
namespace App\References;
use PkExtensions\PkRefManager;
class LoanPurposeRef extends PkRefManager {
  public static $refArr = [
      0 => '(Undeclared)',
      10 => 'Refinancing',
      20 => 'Property Acquisition',
      30 => 'Furniture & Fixtures',
      40 => 'Inventory Purchase',
      50 => 'Machinery/Equipment',
      60 => 'Working Capital',
      70 => 'Construction/Repairs',
      80 => 'Marketing',
      90 => 'Other',
      ];
}
