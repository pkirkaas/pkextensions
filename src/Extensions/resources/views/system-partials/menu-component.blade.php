<!--   Wraps the body of the main menu  & inserts it into $slot -->

    <!-- New BS 4 Nav --> <!-- Navbar -->

    <?php /*
    {{$namedslotcontent}}
     * 
     */ ?>
    <nav class="navbar pk-fixed-menu navbar-expand-md navbar-inverse bg-inverse pk-top-menu main-menu no-print">
      <!-- Toggle Button -->
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#nav-content">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- Nav Content -->
      <div class="navbar-collapse collapse main-menu" id="nav-content">
      <ul class="nav navbar-nav">
        @if ( 0)
        <li class="navbar-brand nav-item site-brand-li">
            <a class="nav-link" data-tootik="Home" data-tootik-conf='bottom'
               title='Home' href="{{route('home')}}">
              <img class="head-img img-fluid tpm-head-img" 
                   src="{{asset(Config::get('app.menu_icon'))}}">
            </a>
        </li>
        @endif

{{ $slot }}

      </ul>
      </div>
    </nav>
    <?php if (!empty($sub_menu)) {
      echo $sub_menu;
      //pkdebug("In menu-component blade - submenu",$sub_menu);
    }
      ?>
    <?php /** if (!empty($no_user) && !Auth::user()) view("forms.DropLoginRegisterDialog") */ ?>
