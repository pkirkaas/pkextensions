<?php
use PkExtensions\PopAtts;
?>
<!-- The generic head for a site layout -->

    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel='stylesheet' href='/mixed/fonts/google-fonts.css' />
    <!--
    <script language="javascript" src="{{asset('/mixed/js/app.js')}}"></script>
    -->
    <script language="javascript" src="{{asset('/mixed/js/es6.js')}}"></script>
    <script language="javascript" src="{{asset('/mixed/js/scripts.js')}}"></script>
    <!--
    Oddly, the below causes errors in the above...
    <script language="javascript" src="{{asset('/mixed/js/vscripts.js')}}"></script>
    -->
    <?php /*
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700|Open+Sans:400,700|Oswald:400,700|Lato:400,700|Roboto:400,700|Raleway:400,600,700"
          rel="stylesheet">
     */?>
    <title>{{Config::get("app.name")}}</title>
    <link href="{{ asset('/mixed/css/stylesheets.css') }}" rel="stylesheet">
    {!!PopAtts::jsInit()!!}
