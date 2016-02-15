<div class='inline-login'>
<!--
-->
  <form class='inline-login inline-form' method='POST' action='{{url('/auth/login')}}' >
    {!! csrf_field() !!}
    <fieldset class='form-group inline'>
   <input class='form-control inline' type='text' name='email'placeholder='Email' />
    </fieldset>
    <fieldset class='form-group inline'>
   <input class='form-control inline' type='password' name='password'placeholder='Password' />
    </fieldset>
    <fieldset class='form-group inline'>
   <label class='inline-remember' for='remember-inline inline'>Remember Me?</label>
   <input class='checkbox-inline' type="checkbox" name="remember" id='remember-inline'>
    </fieldset>
   <button type="submit" class='btn btn-primary inline'>Login</button>
  </form>

<!--
<a href='{{url('/auth/password')}}' class='inline-login inline'>Forgot Password?</a>
-->
</div>