/* 
 * See if I can use ES6 functionality w. Babel in global functions...
 */

/** 
 * Takes arbitrary args, makes arrays out of those that are not,
 * concatenates them, then returns an array of just the unique values
 * @param  array args - arrays or items
 * @returns array of unique values
 */
//function uniqArr(...args) {
window.uniqArr = function(...args) {
  var comb = [];
  args.forEach(function(el) {
    /*
    if (!Array.isArray(el)) {
      el = [el];
    }
    */
    comb = comb.concat(el);
  });
  return Array.from(new Set(comb));
}



