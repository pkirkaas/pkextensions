<?php
namespace App\References;
use PkExtensions\PkRefManager;
/**
 * Ridiculous to have a different file for every little class. Require this in
 * index.php, don't have to worry about autoloading
 */
class LiquidityRef extends PkRefManager{
  static $refArr = [
      1 => 'Current Asset (1 Year)',
      2 => 'Long Term',
  ];
}
class AssetTypeRef extends PkRefManager{
  static $refArr = [
      10 => 'Cash',
      20 => 'Bonds',
      30 => 'Real Estate',
      40 => 'Intellectual Property',
      50 => 'Stocks',
      60 => "Brand Value",
      70 => "Other",
  ];
}

/** Keyed by Borrower Table Field Name, value is verbose description */
class DueDiligenceRef extends PkRefManager {

  //public static $multival = ['key','value','description'];
  public static $refArr = [
  'dd_pledged'=>"Has Applicant pledged inventory, accounts receivable, or equipment to secure existing debt?",
  'dd_endorser'=>"Is Applicant an Endorser; Guarantor;  or Co-Maker for any other obligations, including leases?",
  'dd_forsale'=>"Is the business for sale or under any agreement that would transfer ownership?",
  'dd_owetaxes'=>"Does Applicant owe any past due taxes?",
  'dd_cust20'=>"Does Applicant have a customer that accounts for 20% or more of total sales?",
  'dd_expenses'=>"Does Applicant anticipate significant capital expenditures over next 12 months?",
  'dd_otherapp'=>"Does Applicant have any pending credit applications at any other financial institutions?",
  'dd_recent'=>"Recent company developments that would impact your creditworthiness?",
  'dd_liens'=>"Any Liens against you or your company?",
  'dd_bankrupt'=>"Have any principals filed bankruptcy in the last 7 years?",
  'dd_lawsuits'=>"Are any principals involved in pending lawsuits?",
  'dd_sba_work'=>"(SBA Applicants Only) Do any principals or family members work for SBA, SBC, SCORE, or ACE?",
  ];
}
