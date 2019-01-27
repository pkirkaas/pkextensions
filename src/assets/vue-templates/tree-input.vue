
<!--  #############   Tree Selection Control #################################  -->
<!--
This is a complex, recursive tree form input, configurable in many ways.
Each "model" node has up to 6 properties:
  label - The label of the node
  value - the value of the node IF IT IS SELECTED (default: 1)
  unset - the value if unselected (default: 0)
  name - the name/key of this node 
  selected - 2 or 3 states? true/false/indeterminate
     if true, submit name:value
     if false, submit name:unset
     if indeterminate submit nothing - that is
  nodes - array of nodes submodels
-->
<!-- the module tree template -->
<template>
<ul v-if="!innerc" id="tree-input-root" class='triangle-tree' :class="editable">
<h2 class="bg400 fceee">Yes, this is the combined</h2>
<div class="tst-btn" @click="dumpTree">Dump Object</div>
    <tree-input
      class="item"
      :top="true"
      :innerc="true"
      :model="model"
      :name="name"
      :edit="edit || vedit"
      :ajax="ajax">
    </tree-input>
  </ul>

  <li v-else>
      <div v-if="model">
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
        {{model.label}}
    </div>

    <ul v-show="open" v-if="isFolder">
      <tree-input
        class="item"
        v-for="model in model.nodes" :key=model.id
        :model="model"
        :edit="edit || vedit"
        :top="false"
        :innerc="true"
        >
      </tree-input>
    </ul>
    </div>
  </li>
</template>


<script>

//require('../../node_modules/assets/vue-templates/vue-data-components.js');
require('./vue-data-components.js');
//import { pinputMixin, formatMixin, utilityMixin, refreshRefsMixin, controlMixin }
  // from '/vue-data-components' ;
   //from '../../node_modules/assets/vue-templates/vue-data-components.js';
 //  from '../../node_modules/assets/vue-templates/vue-data-components';

//"C:\www\Laravels\StartupHookup\laravel\node_modules\assets\vue-templates\vue-data-components.js"

export default {
  name: 'tree-input',
  props: ['model', 'top', 'innerc', 'edit','ajax','name'],
  mixins: [window.utilityMixin, window.pinputMixin, window.formatMixin],
  /*
  data: function () {
    return {
      open: true,
      top: true,
    };
  },
  */
  data: function () {
    return {
      vedit: this.edit,
      open: this.top,
      leaf: false,
      singleClick: true, //To distinguish between single-click & dbl
    }
  },
  mounted: function() {
    /*
    console.log("From the one page Tree vue template - this.edit: ",this.edit, "this.model:", this.model, "this.top",this.top,"this.innerc", this.innerc,"this.vedit", this.vedit);
    */
  },
  computed: {
    editable: function() {
      return {
        "is-editable": this.edit,
        "not-editable": !this.edit,
      };
    },
    isFolder: function () {
      return this.model && !isEmpty(this.model.nodes);
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
      return this.model.selected;
    },
    deSelected: function() {
      return !this.model.selected;
    },
    hasSelected: function() {
      return this.modelHasSelected(this.model);
    },
    allSelected: function() {
      return this.modelAllSelected(this.model);
    }

  },
  methods: {
    getTop: function() {
      if (this.top) return this;
      return this.$parent.getTop();
    },
    //Does the folder or leaf have a casepoint in selected-casepoints?
    modelHasSelected: function(pmodel) {
      if (pmodel && pmodel.nodes && pmodel.nodes.length) { //Not leaf
        for (var i=0; i < pmodel.nodes.length ; i++) {
          if (this.modelHasSelected(pmodel.nodes[i])) {
            return true;
          }
        }
        return false;
      } // it's a leaf/casepoint, is it selected?
    },

   dumpTree() {
     console.log("The Model:", this.model);
     //console.log("The raw tree?",this.model.getTreeRoot());
   },

    //Does the folder or leaf have ALL casepoints in selected-casepoints?
    modelAllSelected: function(pmodel) {
      if (pmodel && pmodel.nodes && pmodel.nodes.length) { //Not leaf
        for (var i=0; i < pmodel.nodes.length ; i++) {
          if (!this.modelAllSelected(pmodel.nodes[i])) {
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
    openAndRecursivelyAdd: function (amodel) {
      if (amodel.nodes && amodel.nodes.length) {
        var me = this;
        amodel.nodes.forEach(function (child) {
          me.openAndRecursivelyAdd(child);
        });
      } else if (amodel.casepoint) {
        this.appendPointRow(amodel.casepoint);
      }
    },
    */
    //This was to automatically select all subnodes when
    // a parent node is selected - but don't necessarilly 
    //want to do that
    //In fact, maybe the other way? When a child node is
    //Selected, make sure all ancestor nodes are selected
    
    recursivelyToggle: function (amodel, removePts) {
      if (amodel.nodes && amodel.nodes.length) {
        var me = this;
        amodel.nodes.forEach(function (child) {
          me.recursivelyToggle(child, removePts);
        });
      } else if (amodel.casepoint) {
        if (removePts) {
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
    whereAmI: function() {
      console.log("Am I at the top? Top", this.top,"Size of model:");
    },
    checkboxClick: function () {
      console.log("we have clicked the checkbox");
      var mytop=this.getTop();
      mytop.whereAmI();
      
      if (!this.edit) {
        console.log("Not Editable");
        return;
      }
      this.model.selected = !this.model.selected;
      if (this.ajax) { //Submit & refresh...
      }
      //var me = this;
      //this.recursivelyToggle(this.model, this.allSelected);
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
        if (this.modelHasSelected( this.model)) {
        } else {
          this.appendPointRow(this.model.casepoint);
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
