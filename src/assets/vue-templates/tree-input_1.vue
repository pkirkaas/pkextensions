<!-- Copyright (C) 2018 by Paul Kirkaas - All Rights Reserved -->
<!-- Copy before I make major changes 23 Jan 19 -->
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
     if false, submit nameunset
     if indeterminate submit nothing - that is
  children - array of children submodels
-->
<!-- the module tree template -->
<template>
<ul v-if="!innerc" id="tree-input-root" class='triangle-tree'>
<h2 class="bg400 fceee">Yes, this is the combined</h2>
    <tree-input
      class="item"
      :top="true"
      :innerc="true"
      :model="model">
    </tree-input>
  </ul>

  <li v-else>
      <div v-if="model">
        <div :class="this.nodeClass" @click.stop="triangleClick"></div>
        <div style="display: inline-block;" @click="checkboxClick">
            <input type='checkbox' class='module-tree-checkbox'
              :class="inputCheckbox" :checked.prop="hasSelected"
                :indeterminate.prop='isIndeterminate'
        @click="processClick"
        />
        {{model.name}}
    </div>

    <ul v-show="open" v-if="isFolder">
      <tree-input
        class="item"
        v-for="model in model.children" :key=model.id
        :model="model"
        :top="false"
        :innerc="true"
        >
      </tree-input>
    </ul>
    </div>
  </li>
</template>


<script>
export default {
  name: 'tree-input',
  props: ['model', 'top', 'innerc'],
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
      open: this.top,
      leaf: false,
      singleClick: true, //To distinguish between single-click & dbl
    }
  },
  mounted: function() {
    console.log("From the one page Tree vue template");
  },
  computed: {
    isFolder: function () {
      return this.model && !isEmpty(this.model.children);
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
    },

    toggle: function () {
      if (this.isFolder) {
        this.open = !this.open;
      }
    },
    appendPointRow: function(cpt) {
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
      console.log("we have clicked the checkbox");
      var me = this;
        this.recursivelyToggle(this.model, this.allSelected);
    },
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

/* Triangles depending if tree branch is open.... */


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


</style>
