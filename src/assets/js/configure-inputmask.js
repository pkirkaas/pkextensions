/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/* 
 * Sets up input mask to convert to/from formatted input
 * https://github.com/RobinHerbots/Inputmask 
 */

$(function () {
Inputmask.extendDefaults({
  autoUnmask: true,
  removeMaskOnSubmit: true,
});

/*
$('body').on('focus', 'input.inputmask-dollars', function (e) {
    $(this).inputmask({
      autoUnmask: true,
      removeMaskOnSubmit: true,
      digits: 0,
      alias: 'currency'
  });
});
*/

//Make a function that initializes (combines) these - with a selector
//argument
  
$('input.inputmask-ssn').inputmask({
  //autoUnmask: true,
  //removeMaskOnSubmit: true,
  mask: '999-99-9999'
});


$('input.inputmask-percent').inputmask({
  autoUnmask: true,
  removeMaskOnSubmit: true,
  alias: 'percentage'
  /*
  suffix: '%',
  alias: 'numeric'
  */
});


$('input.inputmask-dollars').inputmask({
  //autoUnmask: true,
  //removeMaskOnSubmit: true,
  digits: 0,
  alias: 'currency'
  /*
  prefix: '$ ',
  groupSeparator: ',',
  alias: 'numeric',
  //mask:'$9{*}',
  autoGroup: true
  */
});

$('input.inputmask-phone').inputmask({
  autoUnmask: true,
  removeMaskOnSubmit: true,
  mask: "(999) 999-9999"
});
});



