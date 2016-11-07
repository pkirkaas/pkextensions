@extends('app')
@section('content')
<h4>You have {{count($favorites)}} favorites</h4>
@include('borrower.parts.searchresults-bs-part',['borrowers' => $favorites])
@stop