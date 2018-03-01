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
    <?php /*
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700|Open+Sans:400,700|Oswald:400,700|Lato:400,700|Roboto:400,700|Raleway:400,600,700"
          rel="stylesheet">
     */?>
    <title>{{SITE_NAME}}</title>
    <link href="{{ asset('/gulped/css/stylesheets.css') }}" rel="stylesheet">
    <!--
    <link href="{{ asset('/mixed/css/sassed.css') }}" rel="stylesheet">
    -->
    <script>
      // Only Chrome & Opera pass the error object.
      window.onerror = function (message, file, line, col, error) {
        console.log(message, "from", error.stack);
      };
      // Only Chrome & Opera have an error attribute on the event.
      window.addEventListener("error", function (e) {
        console.log(e.error.message, "from", e.error.stack);
      });
    </script>
    <script language="javascript" src="{{asset('/mixed/js/scripts.js')}}"></script>
    <!--
    -->
    {!!PopAtts::jsInit()!!}
