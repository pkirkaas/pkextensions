/* 
 Support for _pk-bs4-mods.sccs - like if fixed menu, push down content
 */

//
$(function () {
  offsetContent();
  $(window).resize(offsetContent);
 });

function offsetContent() {
  console.log("In offset content");
  var topmenu = $('.pk-top-menu');
  var tmpos = topmenu.css('position');
  if ((tmpos === 'fixed') || (tmpos === 'absolute')) {
    $('.content-main').offset({top: topmenu.outerHeight()});
  }
}