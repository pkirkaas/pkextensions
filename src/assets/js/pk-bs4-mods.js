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
    $('.content-main').css('top', 0);
  }
}

/** Puts the item at the bottom of them menu, for any window width over 'over'
 * 
 * @param {type} item
 * @param {type} over
 * @returns {undefined}
 */
function setundermenu(item, over) {
  over = over || 0;
  var top = 0;
  if ($(window).width() > over) {
    top =  $("nav.navbar.pk-top-menu.main-menu").outerHeight();
  }
  jQuerify(item).css('top', top);
}