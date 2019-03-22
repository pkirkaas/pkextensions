/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/**
*  @param simple object dataitem  containing the constant label, and the data for the cell
* (either for input/update, or for display). 
* @param datastruct - {dataitem:{labal:label, data:data}, datastructarr:datastructarr}
  @param object/function dataformat - responsible for representing the data -
  *as inputs, one, several, formatting...
  @return tree structure for complicated nested selection & comments
*/

var cnt = 0;
const TreeCell = class {
  constructor(datastruct, dataformat, dataextract) {
    if (!datastruct) {
      return;
    }
    //console.log("Cnt:",cnt++, "data struct:", datastruct);
    var dataitem = datastruct.dataitem;
    var datastructarr = datastruct.datastructarr;
    //console.log('Data Item:',dataitem);
    this.label = datastruct.dataitem.label;

    this.dataextract = dataextract;
    this.data = dataformat(datastruct.dataitem.data);
    

    if (!Array.isArray(datastruct.datastructarr) || !datastruct.datastructarr.length) {
      //console.log("Doesn't think I'm an array?", datastruct.datastructarr);
      return;
    }
    this.children = [];
    datastruct.datastructarr.forEach(ndatastruct=> {
      //console.log("Looping!");
      this.children.push(new TreeCell(ndatastruct,dataformat,dataextract));
    });
  }
  restore(path=[0]) {
    //console.log("Trying to restore - the obj:",this);
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
    //console.log("Data extracted is:", data);
    throw "In Extract, data was supposed be array but is not";
  }
  return data[0];
}
////////////////////

var dataform = {
  input: {selected:{desc:"Check any skill"},
          rating:{desc:"Self Rating 1-10",type:'int', size:'small'},
          desc:{desc:"Highligh your experience",type:"text", size:"medium" },
  },
  data: ['path','rating','desc'],
  };
//Tree Builder 
var nodes = [
  {path:[0], label:"Technical Development",},
  {path:[0,0], label:"Web",},
  {path:[0,0,0], label:"Python",},
  {path:[0,0,1], label:"JavaScript",},
  {path:[0,0,2], label:"PHP",},
  {path:[0,0,3], label:"Ruby",},
  {path:[0,0,4], label:"C/C++",},
  {path:[0,1], label:"Mobile",},
  {path:[0,1,0], label:"iOS",},
  {path:[0,1,1], label:"Android",},
  {path:[0,1,2], label:"Multiplatform",},
  {path:[0,2], label:"Application",},
  {path:[0,3], label:"Engineering",},
  {path:[0,3,0], label:"Elictrical Engineering",},
  {path:[0,3,1], label:"Mechanical Engineering",},


  {path:[1], label:"Marketing",},
  {path:[1,0], label:"Social Media Marketing",},
  {path:[1,1], label:"Traditinal Corporate Marketing",},

  {path:[2], label:"Business",},
  {path:[2,0], label:"Business Development",},
  {path:[2,1], label:"Business Strategy",},
  {path:[3], label:"Design",},
  {path:[3,0], label:"UX Design",},
  {path:[3,1], label:"Graphic Design",},
  {path:[3,2], label:"Art Direction",},
  {path:[3,3], label:"Engineering Graphics",},
  {path:[2,3], label:"Business Networking",},
  {path:[4,0], label:"Financial Planning & Management",},
  {path:[4,1], label:"Procuring Funding",},
  {path:[5], label:"Customer Relationship",},
  {path:[4], label:"Finance",},
  {path:[6], label:"Technical Magagement",},
  {path:[7], label:"Sales",},
];
var settings = {
  //Just filter by lenth, then order.
  params:{idx:null, ln:1},
  reset: function(xparams) {
    this.params = xparams;
  },
  showparams: function() {
    //console.log("These params: ",this.params);
  },
  filter: function(el) {
    return (el.path.length <= this.params.ln);
  }
}

settings.filter = settings.filter.bind(settings);
settings.reset = settings.reset.bind(settings);


class TreeNode {
  constructor(init) {
    this.path = init.path;
    this.label = init.label;
    this.tooltip = init.tooltip;
    this.children = {};
    this.parent = null;
    this.next = null;
    this.prev = null;
  }
  
  depth() {
    return this.path.length -1; //Zero based
  }
  segment(n) {
    if (this.depth()<n) {
      return null;
    } else {
      return this.path[n];
    }
  }
  simple_object() {
    //console.log("Okay, calling/compiling");
    var children = {};
    for (var key in this.children) {
      children[key] = this.children[key].simple_object(); 
    }
    var obj = {
      path: this.path,
      label: this.label,
      tooltip: this.tooltip,
      data: this.data,
      name: this.label,
      //type:"WHATEVER",
      level: this.depth(),
      children: children,
      }
    return obj;
  }
}


/** Builds and Contains the Tree Node objects from array data 
 * Will probably be bound to a single JSON field, so the entire Tree
 * will probably be attached to a single DB field, & this component will probably
 * be responsible for combining static info (like labels & tooltips & values for
 * the unselected boxes) with the user data. Can also be used for input or output
 * @type type
 */

class TreeNodes  {
  // Could be data, or an arry of treeNode instances
   constructor(treeNodes) {
     this.treeNodes = {};
     this.root = {children:{}};
     treeNodes.sort(this.orderbypath);
     //console.log("Num TNs:,",treeNodes.length);

     //var tmparr = [];
     treeNodes.forEach(el => {
       //console.log("Iterating els:,", el); 
       var tn = new TreeNode(el);
       this.place(tn);
     });
     //console.log("This treenodes:", this.treeNodes);
     //Now traverse & build
   }

   place(tn) {
     var depth = tn.depth();
     var parent = this.root;
     //console.log("Depth for ",tn,"is",depth);
     for (var i = 0 ; i <= depth ; i++) {
       var s = tn.segment(i);
       var last = (i === depth);
       if (last) {
         tn.parent = parent;
         if (parent.children[s]) {
           //console.log("Parent:", parent, "me", tn,"s",s,"i",i, 'depth', depth, "other guy", parent.children[s]);
           if (parent.children[s].placeholder) {
             tn.children = parent.children[s].children;
           }  else {
             throw "Duplicate entry for this treeNode;";
           }
         }
         parent.children[s] = tn;
         return;
     } else { //Traverse down
         if (parent.children[s]) {
           parent = parent.children[s];
         } else { // make a placeholder object
          var placeholder = {
            placeholder: true,
            parent: parent,
            children: {[s]:tn, }
          };
          parent.children[s] = placeholder;
        }
      }
    }

    throw "Didn't find a place for this tree node";
   }


   orderbypath(a,b) {
      var len = Math.min(a.path.length, b.path.length);
      for (var i = 0 ; i < len ; i++) {
       if (a.path[i] < b.path[i]) {
          return +1;;
        } else if  (a.path[i] > b.path[i]) {
          return -1;
        }
      }
      return a.path.length - b.path.length;
    }
    simple_object() {
      var children = {};
      for (var key in this.root.children) {
        children[key] = this.root.children[key].simple_object(); 
      }
      return {children:children};
    }

    json_export() {
      return JSON.stringify(this.simple_object());
    }
}


//var lvl1 = nodes.filter(settings.filter);
//console.log("lvl1 = ",lvl1, " Now sort?");
//lvl1.sort(orderbypath);


//First, get & order all the top level skills:
//var top = nodes.filter(


window.TreeNode = TreeNode;
window.TreeNodes = TreeNodes;
