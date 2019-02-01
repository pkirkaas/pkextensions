
<!--  #############   Tree Selection Control #################################  -->
<!--
This is a complex, recursive tree form input, configurable in many ways.
Each "treenode" node has up to 6 properties:
  label - The label of the node
  value - the value of the node IF IT IS SELECTED (default: 1)
  unset - the value if unselected (default: 0)
  name - the name/key of this node 
  selected - 2 or 3 states? true/false/indeterminate
     if true, submit name:value
     if false, submit name:unset
     if indeterminate submit nothing - that is
  nodes - array of nodes subtreenodes
-->
<!-- the module tree template -->
<template>
<ul v-if="!innerc" id="tree-input-root" class='triangle-tree' :class="editable">
<h2 class="bg400 fceee">Yes, this is the combined</h2>
<div class="tst-btn" @click="presavesubmit($event,'Top Submit')">Submit via Ajax</div>
    <tree-input
      class="item"
      :top="true"
      :innerc="true"
      :edit="edit"
      :ajax="ajax"
      :params="params"
      :depth="depth + 1"
      :treenodeprop.sync="treenode"
      >
    </tree-input>
  </ul>

  <li v-else>
      <div v-if="treenode">
        <div :class="this.nodeClass" @click.stop="triangleClick"></div>
        <div style="display: inline-block;" @click="checkboxClick">
          <!--
            <input type='checkbox' class='module-tree-checkbox'
              :class="inputCheckbox" :checked.prop="hasSelected"
                :indeterminate.prop='isIndeterminate'
        @click="processClick"
        />
          -->
          <div class='module-tree-box' :class="checkboxClass">
        </div>
        {{treenode.label}}
    </div>

    <ul v-show="open" v-if="isFolder">
      <tree-input
        class="item"
        v-for="atreenode in treenode.nodes" :key=treenode.id
        :treenodeprop.sync="atreenode"
        :edit="edit"
        :depth="depth + 1"
        :top="false"
        :innerc="true"
        :noprocess="true"
        >
      </tree-input>
    </ul>
    </div>
  </li>
</template>


<script>
window.Vue = require('vue');
require('./vue-data-components.js');
//import { pinputMixin, formatMixin, utilityMixin, refreshRefsMixin, controlMixin }
  // from '/vue-data-components' ;
export default {
  name: 'tree-input',
  //Might take most other props out if using params...
  //props: ['noprocess', 'top', 'innerc', 'treenode', 'edit','ajax','params'],
  props: {noprocess:false, top:true, innerc:true,
    treenodeprop:{}, edit:false,ajax:false,params:{}, depth:0},
  mixins: [window.utilityMixin, window.pinputMixin, window.formatMixin],
  data: function () {
    return {
      componentname: "tree-input",
      treenode: {},
      open: this.top,
      leaf: false,
      singleClick: true, //To distinguish between single-click & dbl
  //    treenode:[],
    }
  },

  mounted: function() {
    this.treenode = _.cloneDeep(this.treenodeprop);
    console.log("This treenode:", this.treenode, "TrenodeProp:", this.treenodeprop, "depth",this.depth,"key");
  },

  computed: {
    editable: function() {
      return {
        "is-editable": this.edit,
        "not-editable": !this.edit,
      };
    },
    isFolder: function () {
      return this.treenode && !isEmpty(this.treenode.nodes);
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
    //inputCheckbox: function() { },
    checkboxClass: function() {

      return {
        box: true,
        'all-selected': this.isSelected,
        'none-selected': !this.isSelected,
        /*
        'all-selected': this.allSelected,
        'none-selected': !this.hasSelected,
        'some-selected': !this.allSelected && this.hasSelected,
      */
        // 'blue-highlight': this.hasSelected,
      }
    },
    isSelected: function() {
      return this.treenode.selected;
    },
    deSelected: function() {
      return !this.treenode.selected;
    },
    hasSelected: function() {
      return this.treenodeHasSelected(this.treenode);
    },
    allSelected: function() {
      r

  },
  methods: {
    presavesubmit: function(event,arg) {
      console.log("Trying AJAX submit");
      if (!this.top) {
        console.error("Shouldn't be able to call presave submit from here");
        return;
      } //Have to prepare top treenode for submitting. Just try?
      //console.log("The top treenode is:", this.treenode);
      console.log("This.treenode:",this.treenode,"this.treenodeprop:", this.treenodeprop);
      var children = this.$children;
      console.log("Children:",children);
      this.value = this.treenode;
      var tst = this.buildTreenodeFromChildren();
      console.log("First run tnfk: ",tst);
      var mynodes = tst.nodes[0].nodes;
      console.log("mynodes:",mynodes);
      this.value = mynodes;
      this.savesubmit(event,arg);
    },
    /*
    overrideInitData: function() {
      //console.log("In tree, overriding init");
      return true;
    },
    */
    stopProcessing: function() {
      return this.noprocess || !this.top || !this.ajax;
    },
    postInitData: function() {
      this.report(['In postInitData this, depth:',this.depth]);
      console.log('inpostinidata thisvalue:',this.value);
      //this.getTop().report(['In postInitData top, depth:',this.getTop().depth]);
      //if (this.stopProcessing() || !this.top) {
       // return;
     // }
      //if (this.value && Array.isArray(this.value)) {
       // this.treenode={nodes:this.value};eturn this.treenodeAllSelected(this.treenode);
    }
      //} else if (this.isObject(this.value)) {
        //this.treenode = Object.values(this.value);
        //this.treenode={nodes:this.value};
//        Vue.set(this,'treenode',this.value);
        this.treenode = {nodes:Object.values(this.value)};
        console.log("Treenode now:", this.treenode, "this. treenodeprop:", this.treenodeprop);
        this.$forceUpdate();

        //Object.values(this.value).forEach((el,idx)=>{
         // this.treenode[idx] = el;
        //});

     // }
    },

    //  this.setData(this,this.fields, this.params, this.instance);
    /*
    postSetData: function(object,fields, params, instance) {
      if (this.
      this.treenode
      return object;
    },
    */
    getTop: function() {
      if (this.top) return this;
      return this.$parent.getTop();
    },
///// These methods ONLY EVER EXECUTED BY TOP LEVEL EL
   




///// END  These methods ONLY EVER EXECUTED BY TOP LEVEL EL
    //Does the folder or leaf have a casepoint in selected-casepoints?
    treenodeHasSelected: function(ptreenode) {
      if (ptreenode && ptreenode.nodes && ptreenode.nodes.length) { //Not leaf
        for (var i=0; i < ptreenode.nodes.length ; i++) {
          if (this.treenodeHasSelected(ptreenode.nodes[i])) {
            return true;
          }
        }
        return false;
      } // it's a leaf/casepoint, is it selected?
    },

   dumpTree() {
     //console.log("The treenode:", this.treenode);
     //console.log("The raw tree?",this.treenode.getTreeRoot());
   },

    //Does the folder or leaf have ALL casepoints in selected-casepoints?
    treenodeAllSelected: function(ptreenode) {
      if (ptreenode && ptreenode.nodes && ptreenode.nodes.length) { //Not leaf
        for (var i=0; i < ptreenode.nodes.length ; i++) {
          if (!this.treenodeAllSelected(ptreenode.nodes[i])) {
            return false;
          }
        }
        return true;
      } // it's a leaf/casepoint, is it selected?
    },

    toggle: function () {
      if (this.isFolder) {
        this.open = !this.open;
      }
    },
    appendPointRow: function(cpt) {
    },

/*
    openAndRecursivelyAdd: function (atreenode) {
      if (atreenode.nodes && atreenode.nodes.length) {
        var me = this;
        atreenode.nodes.forEach(function (child) {
          me.openAndRecursivelyAdd(child);
        });
      } else if (atreenode.casepoint) {
        this.appendPointRow(atreenode.casepoint);
      }
    },
    */
    //This was to automatically select all subnodes when
    // a parent node is selected - but don't necessarilly 
    //want to do that
    //In fact, maybe the other way? When a child node is
    //Selected, make sure all ancestor nodes are selected
    
    recursivelyToggle: function (atreenode, removePts) {
      if (atreenode.nodes && atreenode.nodes.length) {
        var me = this;
        atreenode.nodes.forEach(function (child) {
          me.recursivelyToggle(child, removePts);
        });
      } else if (atreenode.casepoint) {
        if (removePts) {
        } else {
          this.appendPointRow(atreenode.casepoint);
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
    //     //this.recursivelyToggle(this.treenode, this.hasSelected);
    //     this.recursivelyToggle(this.treenode, this.allSelected);
    //   }
    // },

    triangleClick: function () {
      this.toggle();
      //if (this.singleClick == true) {
        //this.recursivelyToggle(this.treenode, this.hasSelected);
      //}
    },
    whereAmI: function() {
      //console.log("Am I at the top? Top", this.top,"Size of treenode:");
    },
    buildTreenodeFromChildren: function() {
      var kids = this.$children;
      var nodes = [];
      kids.forEach(el=>{
        nodes.push(el.buildTreenodeFromChildren());
      });
      var treenode = _.cloneDeep(this.treenode);
      treenode.nodes = _.cloneDeep(nodes);
      return treenode; 
    },
    checkboxClick: function () {
      console.log("we have clicked the checkbox");
      var mytop=this.getTop();
      //mytop.whereAmI();
      
      if (!this.edit) {
        console.log("Not Editable");
        return;
      }
      this.treenode.selected = !this.treenode.selected;
      this.$emit('update:treenode', this.treenode);
      this.$emit('update:treenodeprop', this.treenode);
      if (this.ajax) { //Submit & refresh...
      }
      //var me = this;
      //this.recursivelyToggle(this.treenode, this.allSelected);
    },
    /*
    processClick: function () {
      console.log("Single click on Module tree");
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
        if (this.treenodeHasSelected( this.treenode)) {
        } else {
          this.appendPointRow(this.treenode.casepoint);
        }
      }
    },
    */
  }
}
</script>

<style>
  /*
///////////////  Classes for changing state by JS - checkboxes & triangles
*/

.box, .triangle {
  display: inline;
  cursor: pointer;
}

/* / Three checkboxes states */
/*
.box.all-selected {
  content: "\2611";
  font-size: 1.2em;
}
.box.none-selected {
  content: "\25A2";
  font-size: 0.85em;
}
.box.some-selected {
  content: "\25A3";
}
*/

/* Triangles depending if tree branch is open.... */
/* So all the content (triangle & box) is on the box div */

/**  The box state */
ul.triangle-tree li div.box.some-selected::before {
  content: "\25A3";
  padding-right: 2px;
}
ul.triangle-tree li div.box.all-selected::before {
  content: "\2611";
  font-size: 1.2em;
}
ul.triangle-tree li div.box.none-selected::before {
  content: "\2610";
  font-size: 1.4em;
}

/** The triangle state */
ul.triangle-tree li div.open:before,
ul.triangle-tree li.open:before {
  content: "\25BE";
  font-size: 1.7em;
}
ul.triangle-tree li div.closed:before,
ul.triangle-tree li.closed:before {
  content: "\25B8";
  font-size: 1.7em;
}
ul.triangle-tree li div.leaf:before,
ul.triangle-tree li.leaf:before {
  content: '\2008';
  padding-right: 0.75em;
}

.tst-btn {
    font-size: large;
    color: red;
    font-weight: bold;
    padding: 10px;
    margin: 10px;
    border: solid blue 2px;
    background-color: #aff;
}

</style>
