<?php
/**
 * Formats the address
 * @param $address - the street address
 * @param $city
 * @param $state_abr
 * @param $zip
 * @param $cssclass
 */
?>

@if (isset($address))
{{$address}}<br>
@endif

@if (isset($city))
{{$city}},
@endif
{{$state_abr or ''}}
{{$zip or ''}}