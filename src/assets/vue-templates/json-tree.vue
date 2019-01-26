<!-- Component for visually building JSON/JS Objects -->
<template>
    <div class="jbld-el" :class="mytype">
      <div class="mod-el-wrap">
        <div class="jbld-del-btn"  @click="deleteFromParent">Delete</div>
        <div v-if="canAdd" class='add-jbval-container'>
          <div class='add-lbl' @click="showNew = ! showNew">New?</div>
          <div v-show="showNew" class="new-wrap">
            <div v-if='isObj' class='new-key-wrap'>
              <div class='new-key-lbl'>Key:</div>
              <input v-if="isObj" class='key-inp' v-model='newkey'>
            </div>
            <div class='add-btn' @click='addType("scalar")'>Scalar?</div>
            <div class='add-btn' @click='addType("list")'>Array?</div>
            <div class='add-btn' @click='addType("object")'>Object?</div>
          </div>
        </div>
      </div>

      <div v-if="isScalar" class="jb-input-wrap jb-wrap">
         <input v-model="jbval">
      </div>

      <div v-if="canAdd" v-for="(mval,mkey) in jbval" class="jb-wrap" :class="mytype">
        <div class="jb-el-key">{{mkey}}</div>
        <json-tree :pkey="mkey" :jbval="mval"></json-tree>
      </div>
    </div>
</template>

<script>





//Simple stuff, like non-null object, & not empty
window.utilityMixin = {
  methods: {
    isObject(tst) { 
      if ((typeof tst === 'object') && (tst !== null)) {
        return tst;
      }
      return false;
    },
    //If tst is not an object, return empty object
    asObject(tst) {
      return this.isObject(tst) ? tst : {};
    },
    //I don't like any of those below this one
    //Takes an array of data keys & an object (?params?), and a second
    //object (instance?) and only overwrites
    //the data fields if there is a key/value in the object.
    //"instance" should be {model:mname, id:iid}
    //If params has a key "instance", should also=> {model:, id:}
    setData: function(object,fields, params, instance) {
      if (!(this.isObject(params) || this.isObject(instance)) 
              || !Array.isArray(fields)) {
        return object;
      }
      var me = object;
      var pc = this.isObject(params) ? _.cloneDeep(params) : {};
      var ins = this.isObject(instance) ? 
          _.cloneDeep(instance) : _.cloneDeep(this.asObject(pc.instance));
      //Merge / override instance into params
      pc = Object.assign({},pc,ins);
      //Now 3 ways params can have model/id set 
      fields.forEach(function(field) {
        if (typeof pc[field] !== 'undefined') {
          me[field] = pc[field];
        }
      });
     return object;
    },
    /** Takes an array of refs names, checks they exist, then calls the method
     * on each of them.
     */
    callMethodOnRefs: function(refs,method,arg) {
      if (typeof refs === 'string') {
        refs = [refs];
      }
      if (!Array.isArray(refs)) {
        console.error("Wrong refs arg to callMethodOnRefs:",refs);
        return;
      }
      var me = this;
      refs.forEach(function (ref) {
        if (me.$refs[ref] && me.$refs[ref][method]) {
          me.$refs[ref][method](arg);
        }
      });
    },
//Probably better than below. Data can use sensible defaults, & only params that have keys can override first from settings, second from params
    overrideDefaults: function(settings) {
      if (!this.isObject(settings)) {
        settings = {};
      }
      if (!this.isObject(this.params)) {
        this.params = {};
      }
      var datakeys = Object.keys(this.$data);
      datakeys.forEach((key,idx) => {
        if (key in settings) {
          this[key] = settings[key];
        } else if (key in this.params) {
          this[key] = this.params[key];
        }
      });
    },
    //Called from mounted, defaults an object w. param keys & defaults to set data to
    // Assumes "params" in props
    initDataFromParams(defaults) {
      for (var key in defaults) {
        this[key] = this.params[key] || defaults[key];
      }
      var lblwidth = this.params.lblwidth || defaults.lblwidth;
      if (lblwidth && this.label) {
        this.lblstyle = this.lblstyle + " width: "+lblwidth+"; ";
      }
      //Do same for input width, like checkbox:
      var inpwidth  = this.params.inpwidth || defaults.inpwidth;
      if (inpwidth) {
        this.inpstyle += " width: "+inpwidth+"; ";
      }
    },
    showThisFromDefaults(defaults,lbl) {
      if (!defaults) defaults=this.defaults;
      var vals = {};
      for (var key in defaults) {
        vals[key] = this[key];
      }
      //console.log(lbl+ " Current this values:",vals);
      return vals;
    },
    chunkArr: function(anarr,sz) {
      sz = sz || 2;
      return _.chunk(anarr,sz);
    }
  }
};








export default {
  name: 'json-tree',
  mixins:[window.utilityMixin],
  props:['pkey','jbval'],
  data: function() {
    return { 
      tpkey: null, 
      tval: null,
      newkey: null,
      showNew: false,
    };
  },
  created: function() {
    console.log("On Create - pkey:",this.pkey,"; jbval",this.jbval);
  },
  computed: {
    mytype: function() {
      if (this.jbval === null) return 'null';
      if (Array.isArray(this.jbval)) return 'list';
      if (this.isObject(this.jbval)) return 'object';
      return 'scalar';
    },
    canAdd: function (){
      return this.isObject(this.jbval);
    },
    isScalar: function() {
      return !this.isObject(this.jbval);
    },
    isArray: function() {
      return Array.isArray(this.jbval);
    },
    isObj: function() {
      return !Array.isArray(this.jbval) && this.isObject(this.jbval);
    },
  },
  methods: {
    addType(type) {
      if (this.isScalar) {
        console.error("Trying add to a scalar");
        throw "Trying add to a scalar";
      }
      var newel;
      switch (type) {
        case 'list':
          newel = [];
          break;
        case 'scalar':
          newel = '';
          break;
        case 'object':
          newel = {};
          break;
        default:
          console.error("Trying add unknown element type:",type);
          throw "Trying add unknown element type: "+type;
      }
      if (this.isObj) {
        if (!this.newkey) {
          console.error("Trying add element without a key: ",key);
          throw "Trying add element without a key";
        }
        this.jbval[this.newkey]=newel;
        this.newkey = null;
        return;
      }
      if (this.isArray) {
        this.jbval.push(newel);
        this.newkey = null;
        return;
      }
      console.error("Trying add element - but I don't know my type!",this.jbval);
      throw "Trying add element to unknown type";
    },
    addJbl: function(value,key) {
      if ((this.mytype === 'scalar') || (this.mytype === 'null')) {
        console.error("Trying to add item to scalar :",this.jbval);
        throw "json-tree.addJbl: trying to add element to scalar";
      }
      if (this.mytype === 'list') {
        this.jbval.push(value);
        return;
      }
      if (this.mytype === 'object') {
        if (!key) {
          console.error("Trying add element without a key: ",key);
          throw "Trying add element without a key";
        }
        if (isObject(key)) {
          console.error("Trying use an object for a key: ",key);
          throw "Trying add an object key";
        }
        this.jbval[key]=value;
        return;
      }
      console.error("Shouldn't have got here: jbval",this.jbval);
      throw "Didn't catch this type: "+typeof this.jbval;
    },
    changeEntry: function(key,value) {
      if ((this.mytype === 'scalar') || (this.mytype === 'null')) {
        console.error("Trying to add item to scalar :",this.jbval);
        throw "json-tree.addJbl: trying to add element to scalar";
      }
      if (this.mytype === 'list') {
        this.jbval.splice(key,1,value);
        return;
      }
      if (this.mytype === 'object') {
        if (!key) {
          console.error("Trying add element without a key: ",key);
          throw "Trying add element without a key";
        }
        if (isObject(key)) {
          console.error("Trying use an object for a key: ",key);
          throw "Trying add an object key";
        }
        this.jbval[key]=value;
        return;
      }
      console.error("Shouldn't have got here: jbval",this.jbval);
      throw "Didn't catch this type: "+typeof this.jbval;
    },
    deleteFromParent() {
      this.$parent.deleteEntry(this.pkey);
    },
    deleteEntry: function(key) {
      if ((this.mytype === 'scalar') || (this.mytype === 'null')) {
        console.error("Trying to delete item from scalar :",this.jbval);
        throw "json-tree.addJbl: trying to delete element from scalar";
      }
      if (this.mytype === 'list') {
        this.jbval.splice(key,1);
        return;
      }
      if (this.mytype === 'object') {
        if (!key) {
          console.error("Trying delete element without a key: ",key);
          throw "Trying delete element without a key";
        }
        if (isObject(key)) {
          console.error("Trying use an object for a key: ",key);
          throw "Trying add an object key";
        }
        Vue.delete(this.jbval,key);
        return;
      }
      console.error("Shouldn't have got here: jbval",this.jbval);
      throw "Didn't catch this type: "+typeof this.jbval;
    }
  },
}

</script>


<style>
.jbld-el {
  border: solid grey 1px;
}

.jbld-el.object {
  display: flex;
  margin-left: 5px;
}

.jbld-el.list {
  display: flex;
  flex-direction: column;
}
/*

" :class="mytype">
      <div v-if="isScalar" class="jb-input-wrap jb-wrap">
*/
.jb-el-key {
  border: solid yellow 1px;
  display: inline-block;
  color:white;
  background-color:black;
}

.jb-input-wrap jb-wrap {
  border: solid green 1px;
}
.jb-wrap.list {
  display: flex;
  flex-direction: column;
  border: solid red 1px;
}

.jb-wrap.object {
  display: flex;
  flex-direction: column;
  border: solid blue 1px;
}
.jbld-del-btn {
  display: inline-block;
  padding: 2px;
  margin: 2px;
  border: solid black 1px;
  background-color: red;
  color: white;
}

.mod-el-wrap {
  display: inline-flex;
  flex-direction: column;
  border: solid #aaa 1px;
  background-color: #ccc;
}
.add-jbval-container {
  display: inline-flex;
  flex-direction: column;
  background-color: #eee;
}
.add-lbl {
  display: inline-block;
  background-color: #faa;
}
.add-btn {
  display: inline-block;
  border: solid blue 1px;
  background-color: #ddf;
}
.new-key-wrap {
  display: inline-block;
}
.new-key-lbl {
  display: inline-block;
  background-color: #faf;
}
</style>