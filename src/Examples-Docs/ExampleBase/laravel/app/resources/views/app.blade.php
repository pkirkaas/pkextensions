<!DOCTYPE html>
<?php
use PkExtensions\Models\PkModel;
use PkExtensions\PopAtts;
use App\Models\User;
$user = Auth::user();
?>
<html lang='en'>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Lato">
    <title>{{SITE_NAME}}</title>
    <link href="{{ asset('/gulped/css/stylesheets.css') }}" rel="stylesheet">
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
    {!!PopAtts::jsInit()!!}
  <body class='clearfix sbb' role="document">

    <!-- New BS 4 Nav -->
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-inverse">

      <!-- Toggle Button -->
      <button class="navbar-toggler hidden-sm-up" type="button" data-toggle="collapse" data-target="#nav-content">
        ☰
      </button>

      <!-- Nav Content -->
      <div class="collapse navbar-toggleable-xs main-menu" id="nav-content">
        <ul class="nav navbar-nav">
          <li class="navbar-brand nav-item dropdown site-brand-li">
            <a class="nav-link dropdown-toggle site-brand" data-toggle="dropdown"
               href="#" role="button" aria-haspopup="true" aria-expanded="false">
              {{SITE_NAME}}
            </a>
            <div class="dropdown-menu">
              <a class="dropdown-item" href="{{route('home')}}">Home</a>
              <a class="dropdown-item" href="{{route('index_about')}}">About</a>
              <a class="dropdown-item" href="{{route('index_contact')}}">Contact Us</a>
            </div>
          </li>

            @if($user)
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-toggle="dropdown"
               href="#" role="button" aria-haspopup="true" aria-expanded="false">
              {{$user->getName()}}
            </a>
            <div class="dropdown-menu">
              <a class="dropdown-item" href="{{route('user_editprofile')}}">Edit Profile</a>
              <a class="dropdown-item" href="{{route('auth_logout')}}">Logout</a>
            </div>
          </li>
              @if ($user->isAdmin())
                <li class="nav-item"><a class='nav-link' href="{!!route('admin_tools')!!}">Admin</a></li>
              @endif
            @else
            <li class="nav-item">@include('forms.inline-login-form')</li>
            <li class="nav-item"><a class="nav-link" href='{{url('/auth/password')}}'>Forgot Password?</a></li>
            @endif
        </ul>
      </div>
    </nav>


    <div class="container-fluid theme-showcase content-main clearfix" role="main">
      <!-- CONTENT  -->
      @yield('content')
      <!-- /CONTENT -->
      <!-- The JS Template for the send message dialog box -->
      <?php /*
      @include('jstemplates.sendmessage-dialog')
      */ ?>

    </div> <!-- Close main container fluid -->
    <script language="javascript" src="{{asset('/gulped/js/scripts.js')}}"></script>
  </body>
</html>

