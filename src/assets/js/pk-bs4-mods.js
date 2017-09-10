/* 
 Support for _pk-bs4-mods.sccs - like if fixed menu, push down content
 */


/** Testing why links don't work */
$(function () {
  $('body').on('click', 'a.dropdown-item', function (event) {
    console.log("Clicked on link");
    event.stopPropagation();
  });
});

//
$(function () {
  offsetContent();
  $(window).resize(offsetContent);
 });

function offsetContent() {
  //var topmenu = $('.pk-top-menu');
  var topmenu = $('.fixed-menu-container');
  var tmpos = topmenu.css('position');
  if ((tmpos === 'fixed') || (tmpos === 'absolute')) {
    $('.content-main').offset({top: topmenu.outerHeight()});
  } else {
    $('.content-main').css('top',0);
  }
}