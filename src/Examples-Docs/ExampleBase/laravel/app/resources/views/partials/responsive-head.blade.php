<?php /* Responsive Head - Bootstrap 4 - Shows logo if big enough
 * Optional Params:
 * $slug - text to show
 * $extraClass - optional additional CSS Class for Slug
 */
if (empty($slug)) $slug = '';
if (empty($extraClass)) $extraClass = '';
?>

<div class="row resp-head">
  <div class="col-md-3 hidden-sm-down head-img-col head-col">
    <div class="head-img-wrapper">
      <img class="head-img img-fluid" src="{{asset('gulped/img/sbc-logo.png')}}">
    </div>
  </div>
  <div class="col-md-9 col-sm-12 head-slug head-col {{$extraClass}}">
    <div class="head-slug-content">
      {!! $slug !!}
    </div>
  </div>
</div>


