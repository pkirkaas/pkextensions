/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/*
$(function () {
  var container = $('.outer-container');
  if (isjQuery(container)) console.log("Yes, Container is jquery");
  else  console.log("NO????, Container is NOT jquery");
  //var newset = recursed(container);
  var newset = recursedContent(container);
  console.log("NewSet:", newset[0]);

});

function recursedContent (inner) {
  inner = jQuerify(inner).clone();
  var innershell = $(inner[0].cloneNode());
  var innercontent = inner.contents();
  innercontent.each(function() {
    //var thetype = typeof this;
    ////if (isjQuery(this)) thetype = 'jQuery';
    //console.log("CONTENT TYPE: " + thetype);
    innershell.append(recursedContent(this));
    //console.log("CONTENT TYPE: ", this.nodeName,this);
  });
  //console.log("CONTENT", innercontent);
  return innershell;
}
function recursed (inner) {
  //outer = jQuerify(outer);
  //inner = jQuerify(inner).clone();
  inner = jQuerify(inner).clone();
  var innershell = $(inner[0].cloneNode());
  var innerkids = inner.children();
  innerkids.each(function() {
    //var jqthis = $(this).clone();
    var jqthis = $(this).clone();
    var count = jqthis.attr('data-count');
    if (count === undefined) {
      innershell.append(recursed(jqthis));
    } else {
      cnt = parseInt(count);
      if (isIntish(cnt) && cnt) {
        for (var i = 0 ; i < cnt ; i++) {
          innershell.append(recursed(jqthis));
        }
      }
    }
  });
  return innershell;
}
*/

