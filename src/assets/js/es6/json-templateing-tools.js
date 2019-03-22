/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/* 
 *Will take JSON arrays & build complex structures, suited to 
 *noSQL/JSON storage in DB
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/** Just initial experiment - try get PT like selection tree to drill down to slills. Will be divided 3 components - sympoy array of structure & labels & want content. 
 * 
 * Then the content component that'ss be same for each node, so only define once,.
 * 
 *Then combining automating building the treee & keeping synched with data.
 *Probably do all in JS. 
 * 
 * @type Number
 */

var cnt = 0;
const TreeCell = class {
  constructor(datastruct, dataformat, dataextract) {
    if (!datastruct) {
      return;
    }
    console.log("Cnt:",cnt++, "data struct:", datastruct);
    var dataitem = datastruct.dataitem;
    var datastructarr = datastruct.datastructarr;
    console.log('Data Item:',dataitem);
    this.label = datastruct.dataitem.label;

    this.dataextract = dataextract;
    this.data = dataformat(datastruct.dataitem.data);
    

    if (!Array.isArray(datastruct.datastructarr) || !datastruct.datastructarr.length) {
      console.log("Doesn't think I'm an array?", datastruct.datastructarr);
      return;
    }
    this.children = [];
    datastruct.datastructarr.forEach(ndatastruct=> {
      console.log("Looping!");
      this.children.push(new TreeCell(ndatastruct,dataformat,dataextract));
    });
  }
  restore(path=[0]) {
    console.log("Trying to restore - the obj:",this);
    var arr = [];

    var i=0;
    if (Array.isArray(this.children) && this.children.length) {
      this.children.forEach(function(child) {
        arr.push(child.restore(path[i++]));
      });
    }

    var exp = {datitem:{label:this.label,data: this.dataextract(this.data)},
        path:path, arr:arr};
    return exp;
  }
}

function dataformat(data) {
  return [data];
}

function dataextract(data) {
  if (!Array.isArray(data) || !data.length) {
    console.log("Data extracted is:", data);
    throw "In Extract, data was supposed be array but is not";
  }
  return data[0];
}


