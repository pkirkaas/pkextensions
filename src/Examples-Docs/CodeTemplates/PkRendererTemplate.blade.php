<?php
use PkExtensions\PkHtmlRenderer;
$out = new PkHtmlRenderer();
?>
<div class="pkh5">Details</div>

<?php
$out->div(RENDEROPEN,'row inner-border');
  $out->div(RENDEROPEN,'col-md-8');
    $out->div(RENDEROPEN,'row inner-border');
      $out->wrap( dollar_format($user->amount),'Amount:','tac', 'pkl1','col-md-6' );
      $out->wrap($user->type_id_DV,'Type:','tac', 'pkl1','col-md-6' );
    $out->RENDERCLOSE(); // Finish the first row

    $out->div(RENDEROPEN,'row inner-border');
      $out->wrap(pk_showcheck($user->profitable),'Profitable:','tac', 'pkl1','col-md-4' );
      $out->wrap(dollar_format($user->cc),'Credit Card:','tac', 'pkl1','col-md-4' );
      $out->wrap($user->type2_id_DV,'Type 2:','tac', 'pkl1','col-md-4' );
    $out->RENDERCLOSE();
  $out->RENDERCLOSE();
$out->RENDERCLOSE();
echo $out;
?>
@include('system-partials.grid-part',
       ['collection'=>$user->debts, #Collection of "Debt" model instances
       'fieldmap' => [ #Map of $debt->attribute => 'Attribute Label'
           'balance_DV'=>'Balance', 
           'debttype_id_DV'=>'Type', 
           'maturity'=>'Maturity',
           'monthly_DV'=>'Monthly Payment',
           ], ])

