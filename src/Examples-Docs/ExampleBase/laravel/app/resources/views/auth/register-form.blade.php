<!-- The form for registering users -->
@extends('app')
@section('content')
<h2>Please register as a potential {{$category}}</h2>
{!! PkForm::open() !!}
<div>
					{!!  PkForm::label('name', 'Name?'); !!}
					{!!  PkForm::text('name'); !!}	
</div>
<div>

					{!!  PkForm::label('email', 'Email?'); !!}
					{!!  PkForm::text('email'); !!}	

</div>
<div>
					{!!  PkForm::label('password', 'Password?'); !!}
					{!!  PkForm::password('password'); !!}	

</div>
<div>

					{!!  PkForm::label('password_confirmation', 'Password Confirmation'); !!}
					{!!  PkForm::password('password_confirmation'); !!}	

</div>
<div>
					{!! PkForm::button('Register', ['name'=>'submit', 'type'=>'submit', 'value'=>'save_changes', 'class'=>'btn btn-primary']) !!}
</div>
{!! PkForm::close() !!}
@stop