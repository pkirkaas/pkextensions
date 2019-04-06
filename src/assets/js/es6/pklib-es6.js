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





/** Allows both string class names & class objects, and later
 * 
 * @param array cssclasses - each a string, an object, or another array 
 * @returns object keys w. class names, value true or false - so a later
 * let b = mkCss(default,{clsname:false});
 * keeps default {clsname:true} but sets b = {clsname:false}
 */
function  mkCss(...cssclasses) {
  if (cssclasses.length === 0) return {};
  let retcls = {};
  for (let el of cssclasses) {
    if(window.isEmpty(el)) {
      continue;
    }
    if (typeof el === 'string') {
      retcls[el]=true;
    } else if (Array.isArray(el)) {
      let tmpcls = {};
      for (let aa of el) {
        tmpcls = _.merge({},tmpcls,mkCss(aa));
      }
      tmpcls = _.cloneDeep(tmpcls);
      retcls = _.merge({},retcls,tmpcls);
    } else if (typeof el === 'object') {
      let acln = _.cloneDeep(el);
      retcls = _.merge({},retcls,acln);
    }
  }
  return retcls;
}

window.mkCss = mkCss;
/** 
 * 
 * @param array nest of objects (must be objects) of vue Object cssclasses
 * @returns copied, merged nest
 */
function meldCss(...nest){
  if (nest.length === 0) return {};
  let retnest = {};
  for (let el of nest) {
    if(window.isEmpty(el) || typeof el !== 'object') {
      continue;
    }
    for (let clstp in el) {
      //retnest[clstp] = _.merge({},retnest[clstp],{[clstp]:mkCss(el[clstp])});
      retnest[clstp] = _.merge({},retnest[clstp],mkCss(el[clstp]));
    }
  }
  return retnest;
}

window.meldCss = meldCss;
/** Makes a set of Vue CSS Classes from object cset
 * @param object cset
 *    keys: the keys of the objset, like "wrapcls"
 *    values: arguments to mkCss 
 * 
 * @returns {undefined}
 */

function mkCsses(cset) {
  let retset = {};
  for (let akey in cset) {
    retset[akey] = _.merge({},mkCss(cset[akey]));
  }
  return retset;
}

window.mkCsses = mkCsses;