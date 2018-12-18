/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/*  ***********  Module/Case/Point Selection Components ***********************/
//-----------------------------------------------------------------------

// Module Tree Component
Vue.component('module-tree', {
  name: 'module-tree',
  template: '#module-tree-template',
  store: store,
  data: function () {
    return {
      treeData: window.fbkgbl.vueModuleTree,
      model: window.fbkgbl.vueModuleTree,
      open: true,
      top: true,
    };
  },
});

// Module Item component
Vue.component('module-item', {
  store: store,
  template: '#module-item-template',
  props: ['model', 'top'],
  data: function () {
    return {
      open: this.top,
      leaf: false,
      singleClick: true, //To distinguish between single-click & dbl
    }
  },
  computed: {
    isFolder: function () {
      return this.model && this.model.children &&
        this.model.children.length
    },
    nodeClass: function() {
      return {
        triangle: true,
        open: this.isFolder && this.open,
        closed: this.isFolder && !this.open,
        leaf: !this.isFolder,
        //bold: this.isFolder,
        // 'blue-highlight': this.hasSelected,
      }
    },
    isIndeterminate: function() {
      if (this.hasSelected && !this.allSelected) {
        return true;
      }
      return false;
    },
    inputCheckbox: function() {



    },
    checkboxClass: function() {

      return {
        box: true,
        'all-selected': this.allSelected,
        'none-selected': !this.hasSelected,
        'some-selected': !this.allSelected && this.hasSelected,
        // 'blue-highlight': this.hasSelected,
      }
    },
    hasSelected: function() {
      return this.modelHasSelected(this.model);
    },
    allSelected: function() {
      return this.modelAllSelected(this.model);
    }

  },
  methods: {
    //Does the folder or leaf have a casepoint in selected-casepoints?
    modelHasSelected: function(pmodel) {
      if (pmodel && pmodel.children && pmodel.children.length) { //Not leaf
        for (var i=0; i < pmodel.children.length ; i++) {
          if (this.modelHasSelected(pmodel.children[i])) {
            return true;
          }
        }
        return false;
      } // it's a leaf/casepoint, is it selected?
      if (this.$store.state.cparr.indexOf(pmodel.casepoint) !== -1) {
        return true;
      }
    },

    //Does the folder or leaf have ALL casepoints in selected-casepoints?
    modelAllSelected: function(pmodel) {
      if (pmodel && pmodel.children && pmodel.children.length) { //Not leaf
        for (var i=0; i < pmodel.children.length ; i++) {
          if (!this.modelAllSelected(pmodel.children[i])) {
            return false;
          }
        }
        return true;
      } // it's a leaf/casepoint, is it selected?
      if (this.$store.state.cparr.indexOf(pmodel.casepoint) !== -1) {
        return true;
      }
    },

    toggle: function () {
      if (this.isFolder) {
        this.open = !this.open;
      }
    },
    appendPointRow: function(cpt) {
      this.$store.commit('addPt',cpt);
    },

    openAndRecursivelyAdd: function (amodel) {
      if (amodel.children && amodel.children.length) {
        var me = this;
        amodel.children.forEach(function (child) {
          me.openAndRecursivelyAdd(child);
        });
      } else if (amodel.casepoint) {
        this.appendPointRow(amodel.casepoint);
      }
    },
    recursivelyToggle: function (amodel, removePts) {
      if (amodel.children && amodel.children.length) {
        var me = this;
        amodel.children.forEach(function (child) {
          me.recursivelyToggle(child, removePts);
        });
      } else if (amodel.casepoint) {
        if (removePts) {
          this.$store.commit('removePt',amodel.casepoint);
        } else {
          this.appendPointRow(amodel.casepoint);
        }
      }
    },
    // processDblClick: function () {
    //   //this.processClick();
    //   this.singleClick = false;
    //   var me = this;
    //   setTimeout(function(){
    //       me.singleClick = true;
    //   }, 500);
    //   if (this.isFolder) { //Recursively open & add points
    //     //this.recursivelyToggle(this.model, this.hasSelected);
    //     this.recursivelyToggle(this.model, this.allSelected);
    //   }
    // },

    triangleClick: function () {
      this.toggle();
      //if (this.singleClick == true) {
        //this.recursivelyToggle(this.model, this.hasSelected);
      //}
    },
    checkboxClick: function () {
      // console.log("we have clicked the checkbox");
      var me = this;
        this.recursivelyToggle(this.model, this.allSelected);
    },
    processClick: function () {
      //console.log("Single click on Module tree");
      if (this.isFolder) {
        var me = this;
        setTimeout(function(){
          if (me.singleClick) {
            me.open = !me.open
          }
          //me.singleClick = true;
        }, 500);
      } else {  // It's a leaf-point - add it
        var el = this.$el;
        if (this.modelHasSelected( this.model)) {
          this.$store.commit('removePt',this.model.casepoint);
        } else {
          this.appendPointRow(this.model.casepoint);
        }
      }
    },
  }
});
/*  ***********  END Module Selection Components ***********************/



/** Takes a flat Cat/Module/Case/Point data object & builds a Vue Data tree to display 
 * in VueTree. entries is an optional array/keyed obj of array or keyed object of user
 * submitted image entries that have the point guid so they can be attached to the appropriate
 * point and ordered by dated.
 * Both points & entries can be either a flat keyed object or an array -
 * they will be converted to arrays. Furthermore, arrays are objects that can have properties,
 * so entries could be [ptarr].entries
 * @param extras - optional object whose keys/attributes you add to all nodes of the tree-
 * this can be esp useful with adding methods...
 * @param student - optional, if present, the student's results if any for the module 
 * are attached.
 *
 */
window.mkVueTreeFromPoints = function(points,extras, student) {
  if (isEmpty(extras)) {
    extras = {};
  }
  var vueTree = {
    name: "Modules",
    type: 'Root',
    level: 0,
    children: [],
  };
  Object.assign(vueTree,extras);
  _.values(points).forEach(function(point) {
    var mnameobj = mkOrReturnObjInArrayWithAttName(
      vueTree.children, 
      point.module_name,
      Object.assign({type:'module','level':1,module_guid:point.module_guid,
        modres:getModResFromStud(student,point.module_guid 
        ),coverImage: 
          //modulesObj[point.module_guid].coverImage, },extras));
          window.modulesObj[point.module_guid] ? window.modulesObj[point.module_guid].coverImage : null,
           },extras));

    var cnameobj = mkOrReturnObjInArrayWithAttName(mnameobj.children, point.case_name,
      Object.assign({type:'case','level':2,case_guid:point.case_guid},extras));

    var pnameobj = mkOrReturnObjInArrayWithAttName(cnameobj.children, point.point_name,
      Object.assign({type:'point','level':3,point_guid:point.point_guid},extras));

    delete pnameobj.children;
    pnameobj.casepoint = point;
  });
  mkOrderedTree(vueTree);
  return vueTree;
}

/* Takes a recursive tree object composed of AT LEAST nodes like:
 * node[orderby] = string
 * node[arratt] = [similar nodes]
  */
window.mkOrderedTree = function(tree, orderby, arratt) {
  if (!orderby) orderby = 'name';
  if (!arratt) arratt = 'children';
  if (!Array.isArray(tree[arratt]) || !tree[arratt].length) {
    return;
  }
  tree[arratt].forEach(function (node) {
    mkOrderedTree(node, orderby, arratt);
  });

  window.compFunc = function(a, b) {
    //return a[orderby].localeCompare(b[orderby]);
    return sortAlphaNumCI(a[orderby], b[orderby]);
  }
  tree[arratt].sort(compFunc);
}


