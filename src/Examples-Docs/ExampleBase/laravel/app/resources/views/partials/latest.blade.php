<div class="partial latest">
  <p>Latest Applicants</p>
  @foreach ($borrowers as $borrower)
  <div class='row'>
    <div class='col-sm-3'>{{$borrower->user->name}}</div>
    <div class='col-sm-3'>{{dollar_format($borrower->loanamt)}}</div>
    <div class='col-sm-3'>{{sqlDateToFriendly($borrower->updated_at)}} </div>
  </div>
  @endforeach
</div>