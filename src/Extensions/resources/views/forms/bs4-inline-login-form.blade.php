<div class='inline-login'>
<!--
-->
  <form class='inline-login form-inline' method='POST' action='{{url('/auth/login')}}' >
    {!! csrf_field() !!}
   <input class='form-control' type='text' name='email'placeholder='Email' />
   <input class='form-control' type='password' name='password'placeholder='Password' />
   <label class='inline-remember' for='remember-inline'>Remember Me?</label>
   <input class='form-control checkbox-inline' type="checkbox" name="remember" id='remember-inline'>
   <button type="submit" class='btn btn-primary form-control'>Login</button>
  </form>

<!--
<a href='{{url('/auth/password')}}' class='inline-login inline'>Forgot Password?</a>
-->
</div>