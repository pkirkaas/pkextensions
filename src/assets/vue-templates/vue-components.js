'use strict';
/** Smaller Vue components that don't require an entire *.vue page template */

/** CVue instances will be created new for each use.
 * Components have to be composed from the top down - so the outermost component
 * has to be attached/mounted first. So the mount method should be called by the
 * main application. This will be for components that are built and then destroyed,
 * so we don't have to worry about the components corrupting the initial data-
 * so we can pass it in as a static object/array initdata:{..}. The data function
 * just returns it.
 * Also include cvues array to attach to the template where the parent selector 
 * is the key to the instance.
 * cvues:[  {selector:cvue}, {selector:cvue},...]
 * @type type
 */
///!!!!!!!!!!!!!!!!!  NOTE THIS VERSION IS DEPRECATED (HENCE XCVUE) -- LATEST BELOW 
window.PkVue = require('./pkvue.js');

window.CVue = PkVue.extend({
  me: 'Base CVue',
  selector: '.cvue-anchor', //Can be overridden
  data: function() {return this.$options.initdata;},
  initdata: {},
  cvues: [],
  methods: {
    isMounted: function(prt) {
      if (!prt) {
        return this._isMounted;
      } else {
        if (this._isMounted) {
//          console.log ("The Component is MOUNTED");

        } else {
    //      console.log ("Pooh NOT Component is MOUNTED");
        }
      }
    },
    me: function() {return this.$options.me;},
    //el ALWAYS has to be a DOM element
    mount: function(el, sel) {
      if(!this.isMounted()) {
        this.$mount();
      }
     // console.log("IN Mount, this.data:", this.$options.initdata);
      if (this instanceof CVue) {
      //  console.log("This is a cvue:",this);
      } else {
       // console.log("This is NOT a cvue:",this);
      }

    //  console.log("sel ", sel,"topsel: ",this.$options.selector);
      selector = sel || this.$options.selector;
      el = el || Document;
     // console.log("Appd Child to selector:" + selector + " and el: ", this.$el);
      el.querySelector(selector).appendChild(this.$el);
      return;
    },
    mountAll: function(item,sel) { //each cv is either a cvue, a selector:cvu, or array
      if(!this.isMounted()) {
        this.$mount();
      }
      this.mount(item,sel);
      var el = this.$el;
      //console.log("CVUES:",this.$options.cvues);
      var self = this;
      if (Array.isArray(this.$options.cvues) && this.$options.cvues.length) {
        this.$options.cvues.forEach(function (cv) {
       //   console.log("This: is about to monthis ",  cv);
            cv.mount(el);
        });
      }
    },
    destroy: function() {
      var ctree = this;
      while (!ctree.$parent instanceof CVue) {
        ctree = ctree.$parent;
      }
      ctree.$el.remove();
    }
  }
  });

var XCVue = PkVue.extend({
  me: 'Base CVue',
  pselector: '.cvue-anchor', //Can be overridden
  data: function() {return Object.assign(this.$options.defaultdata,
    this.$options.initdata);},
  initdata: {},
  defaultdata: {},
  cvues: [],
  methods: {
    //mes: function() {return this.$options.me;},
    //mount: function(cv, selector) {
    mount: function(item, sel) {
      //console.log("IN Mount, this.data:", this.$options.initdata);
      if (this instanceof CVue) {
        //console.log("This is a cvue:",this);
      } else {
       // console.log("This is NOT a cvue:",this);
      }
      if (!item || (typeof item === 'string') || (item instanceof Element)) {
        //Mount self, to parent 
        if (item instanceof Element) {
          el = item;
          selector = sel || this.$options.selector;
        } else {
          selector = item || this.$options.selector;
          el = Document;
        }
      //console.log("Appd Child to selector:" + selector + " and el: ", this.$el);
        el.querySelector(selector).appendChild(this.$el);
        return;
      }
      if (item instanceof CVue) {
        cv = item;
        selector = this.$options.selector;
      } else if (Object.keys(item)) { //Should just be selector:cv
        selector = Object.keys(item)[0];
        cv = item[selector];
      } else if (Array.isArray(item)){
        die;
        item.forEach(function (el) {
        });//do something
      } else {
        die;
      }
      //console.log("Appeend Child to selector:" + selector + " and el: ", cv.$el);
      this.$el.querySelector(selector).appendChild(cv.$el);
    },
    //Only the TOP CVue accepts & REQUIRES an arg to mountAll()
    mountAll: function(item,sel) { //each cv is either a cvue, a selector:cvu, or array
      if (item) {
        this.mount(item,sel);
      }
      //console.log("CVUES:",this.$options.cvues);
      var self = this;
      if (Array.isArray(this.$options.cvues) && this.$options.cvues.length) {
        this.$options.cvues.forEach(function (cv) {
        //  console.log("This: is about to monthis ",  cv);
            self.mount(cv);
        });
      }
    },
    destroy: function() {
      var ctree = this;
      while (!ctree.$parent instanceof CVue) {
        ctree = ctree.$parent;
      }
      ctree.$el.remove();
    }
  }
  });

const BigImg = CVue.extend({
  template: `<div class='border p-5 m5' style='max-width: 100%; max-height: 100%;'>
     <img class='fullheight fullwidth' :src="href"></div> `,
  me:'Big Image',
  initdata: {href:'',title:"My Photography"},
  methods: {
    setHref: function(url) {
     // console.log("The URL: " + url);
      this.href = url;
      return url;
    }
  }
  });

const TextModal = CVue.extend({
  template: `<div class='border p-5 m5' style='max-width: 100%; max-height: 100%;'>
  <div class='pre-wrap text-wrap'>{{content}}</div> </div> `,
  defaultdata: {content:'',title:""}
});



const TestHuge = CVue.extend({
  template: `<div style=" top: 10%; border: solid blue 3px; background-color: #ecc;" class="inline h-center resizable"><div class='vue-popup sh2 tac' v-if="title">{{title}}</div><div class='cvue-anchor'></div> <button @click='close'>Close</button></div>`,
  me:'HUGE TEST!',
  initdata: {dynamicComponent:''},
  methods: {
    close: function() {
      this.destroy();
    },
    setDynamicComponent: function(component) {
      this.dynamicComponent = component;
    } 
  }
});

const FormPopup = CVue.extend({
  template: `<div style=" top: 10%; border: solid blue 3px; background-color: #ecc;" class="inline h-center resizable"><div class='sh2 tac' v-if="title">{{title}}</div><div class='cvue-anchor'></div> <button @click='submit'>{{post}}</button><button @click='close'>Cancel</button></div>`,
  me:'Form Popup Frame',
  defaultdata: {post:"Save",title:"",dynamicComponent:''},
  methods: {
    close: function() {
      this.destroy();
    },
    submit: function(event) {
    }
  }
});
//Try making reusable inputs
// inputs
    //<div :class="lblclass" v-html="label"><input type="text" :value="value"
    //
    //
// Actually, if in a v-for, prop should be an object, so see below
CVue.component('text-input',{
  template: `
  <div :class="wrapclass">
    <div :class="lblclass">{{label}}<input type="text" :value="value"
    :name="name" :class="inputclass" class='border bg-444' placeholder="Come on, Dish!"></div></div>`,
  props:['lblclass', 'label', 'value','name','inputclass','wrapclass']
});




/*** Make my own input components for inclusion in arrays***/

// Actually, if in a v-for, prop should be an object, so
CVue.component('pk-input-arr',{
  type: 'input',
  template: `
  <div :class="inpopt.wrapclass">
    <div :class="inpopt.lblclass">{{inpopt.label}}<input :type="inpopt.type" v-model="inpopt.value"
    :name="inpopt.name" :class="inpopt.inputclass"
    class='border' ></div></div>`,

  props:['inpopt'],//'lblclass', 'label', 'value','name','inputclass','wrapclass']
  created: function() {
    if (!this.inpopt.type) {
      this.inpopt.type = 'text';
    }
  },
  methods: {
  }

});

/** 
 * Bare input element
 */
window.Vue.component('pk-input',{
  name: 'pk-input',
  template: `
  <input :type="type" v-model="inpopt.value" :placeholder="inpopt.placeholder"
     :name="inpopt.name" :class="inpopt.inpcls" />
  `,
  props:['inpopt'],
  computed: {
    type: function() {return this.inpopt.type || 'text';}
  }
});

/** Checkbox */
CVue.component('pk-checkbox-arr', {
  inptype: 'checkbox',
  template: `
  <div :class="inpopt.wrapclass"> <div :class="inpopt.lblclass">{{inpopt.label}}
  <input type="checkbox" :value="inpopt.value" :name="inpopt.name" :class="inpopt.inputclass"
       v-model="inpopt.checked"  @change="inpopt.value = +!inpopt.value" />
    </div></div>
`,
  props: ['inpopt'],
  created: function() {
  }

});

/** Select */
CVue.component('pk-select-arr', {
  inptype: 'select',
  template: `
  <div :class="inpopt.wrapclass"> <div :class="inpopt.lblclass">{{inpopt.label}}
  <select :name="inpopt.name" :class="inpopt.inputclass" v-model="inpopt.value">
    <option v-for="(option, idx) in inpopt.options" :value="option.value">
        {{option.label}}
    </option>
  </select>
    </div></div>
`,
  props: ['inpopt']

});

/*
CVue.component('pk-input-arr',{
  type: 'input',
  template: `
  <div :class="inpopt.wrapclass">
    <div :class="inpopt.label<input type="text" v-model="inpopt.value"
    :name="inpopt.name" :class="inpopt.inputclass"
    class='border' ></div></div>`,
  props:['inpopt'],//'lblclass', 'label', 'value','name','inputclass','wrapclass']
  methods: {
  },
});
*/


/** Takes an array or object of multiple input options (type, name, value)
 * & iterates through to build a multi-input div, that can be submitted.
 */
CVue.component('pk-input-form',{
  template: `
  <div :class="formopts.frmclass" class="mini-input-form">
    <div v-for='(inpopt, idx) in inpopts'>
      <pk-select-arr v-if="inpopt.inptype === 'select'" :inpopt="inpopt"></pk-select-arr>
      <pk-checkbox-arr v-else-if="inpopt.inptype === 'checkbox'" :inpopt="inpopt"></pk-checkbox-arr>
      <pk-input-arr v-else="inpopt.inptype = 'input'" :inpopt="inpopt"></pk-input-arr>

    </div>
    <button @click='submit'>{{formopts.save}}</button>
    <button @click='close'>Cancel</button></div>
  </div>`,
  props:['inpopts', 'formopts'],
  //txtinps: obj array of  'lblclass', 'label', 'value','name','inputclass','wrapclass'
  //formopts: formopts
  methods: {
    submit: function(ev) {
      ev.preventDefault();
      var fd = new FormData(this.$el);
      var jqints = $(this.$el).find(':input');
      jqints.each(function(idx, el) {
        var $el = $(el);
        fd.append($el.attr('name'), $el.val());
      });
      console.log ("This El:", this.$el,"The formdata to post:", fd, "jqints", jqints);

      var url = this.formopts.url; 
      var me = this;
      axios.post(url, fd).then(response=> {
        console.log("The response was:", response);
      });
    },

    close: function(ev) {
      console.log("Cancelled Update");
    }

  }
});

CVue.component('small-txt', {
  template: `
  <div :class='colclass'>
    <div :class='mxtxtclass' @click='clicked'>{{content}}</div>
  </div> `,
  data: function() {return {mxtxtclass: 'small-text bg-ccf border p-2 m-2 actionable'};},
  methods: {
    clicked: function(event) {
      let inner = new TextModal({initdata:{content:this.content}});
      let huge = new TestHuge({cvues:[inner],title:this.title});
      vbus.mount(huge );
    }
  },
  props: ['content', 'title', 'colclass']
});

CVue.component('tiny-img', {
  template: `
  <div :class='colclass'>
    <img :src='url' :class='mximgclass' @click='clicked'>
  </div>
`,
  data: function() {
    return {mximgclass: 'img-thumbnail actionable'};
  },
  methods: {
    clicked: function(event) {
      let aBigImg = new BigImg({initdata:{href:this.url}});
      let huge = new TestHuge({cvues:[aBigImg]});////.$mount();
      vbus.mount(huge );
    }
  },
  props: ['url', 'imgclass', 'colclass']
});

/** A button to invite a friend, send a message, etc */
CVue.component('profile-btn', {
  template: `
  <div :class='mxcolclass'>
    <a :class='mxbtnclass' :data-tootik="tootik" :href='href' :text="label">{{label}}</a> 
  </div>
`,
  data: function() {
    return {
      href:this.baseurl+'/'+this.profile.rlink,
      mxbtnclass:'btn site-btn btn-success '+this.btnclass,
      mxcolclass:this.colclass
    };
  },
  props: ['profile','label','baseurl', 'tootik', 'btnclass','colclass']
});

const ContactBody = CVue.extend ({
  template: `
   <div class='contact-body-wrapper vue-popup'>
  <div class='contact-header'>{{header}}</div>
  <div class='contact-sub-wrap'><input type='text' name='subject' class='cont-sub-inp'></div>
  <div class='contact-ta-wrap'><textarea name='content' class='contact-ta'></textarea></div>
  
  <div class='button-row'>
    <button class='popup-btn btn btn-success' @click.prevent='submit'>{{post}}</button>
    <button class='popup-btn btn btn-warning' @click.prevent='closeit'>{{cancel}}</button>
  </div>

</div>
`,
  defaultdata: {
    pkmodel:'',
    profile_from:'',
    profile_to:'',
    header:'',
    post:'Send',
    cancel:'Cancel'
  },
  methods: {
    submit: function(e){
    },
    closeit: function(e){
      this.destroy();
    }
  }   
    
});

const InviteBody = ContactBody.extend ({
  initdata:{
    pkmodel:'',
    profile_from:'',
    profile_to:'',
    header:'Invite a New Friend',
    post:'Send',
    cancel:'Cancel'
  }
});
const MessageBody = ContactBody.extend ({
  initdata:{
    pkmodel:'',
    profile_from:'',
    profile_to:'',
    header:'Send a Message & Keep in touch',
    post:'Send',
    cancel:'Cancel'
  }
});

/** Another button to invite a friend, send a message, etc - but for a popup*/
CVue.component('contact-btn', {
  popups: {message: MessageBody, invite:InviteBody},
  template: `
    <button class='popup-btn btn btn-success' :data-tootik="tootik" @click.prevent='submit'>{{label}}</button>
`,
  contact_type:{
    label:'',
    tootik:'',
    popup:'',
    post: ''

  },
  methods: {
    submit: function(e) {
      let co = this.$options.contact_type;
      popup = new co.popup(co);
      vbus.mount(popup );
    }
  },

  data: function() {
    return this.$options.contact_type;}
  //props: ['profile','label','baseurl', 'tootik', 'btnclass','colclass'],
});

/*
CVue.component('invite-btn', {
  extends: contact-btn,
*/
CVue.component('invite-btn', {
//const InviteBtn =  {
  extends: CVue.component('contact-btn'),
  contact_type: {
    label: "Friend",
    tootik: "Send a Friend Invitation",
    popup: InviteBody,
    post: 'Invite'
  }
  });

//CVue.component('invite-btn',new InviteBtn());

CVue.component('message-btn', {
  extends: CVue.component('contact-btn'),
  contact_type: {
    label: "Message",
    tootik: "Send a message",
    popup: MessageBody,
    post: 'Message'
  }
});

/** AJAX delete button for PkModels
 * @props params
 *   classname - required
 *   id - required
 *   btncls - default: btn-cls
 *   url - default: /ajax/delete
 *   cascade - default: false
 *   delfromdom - the row class to remove - eg, '.rt-rowcls'
 */
Vue.component('delete-btn', {
  name: 'delete-btn',
  template: `
   <div :class="btncls"
      @click.stop="del()">
      Delete</div>
`,
  props:['params'],
  computed: {
    btncls: function (){
      console.log("Btn class param:", this.params);
      return this.params.btncls || " pkmvc-button inline m-v-1 m-h-1";}
  },
  methods: {
    del: function() {
      var delfromdom=this.params.delfromdom;
      if (delfromdom && !(typeof delfromdom === 'string')) {
        delfromdom = ".js-resp-row";
      }
      if (!this.params.classname || !this.params.id) {
        if (delfromdom) {
          $(this.$el).closest(delfromdom).remove();
        }
        return;
      }

      var url = this.params.url || "/ajax/delete";
      var params = {
        model: this.params.classname,
        id: this.params.id,
        cascade: this.params.cascade
      };
      axios.post(url,params).then(response=> {
        if (delfromdom) {
          $(this.$el).closest(delfromdom).remove();
        }
        console.log("\nDelete Success w. response:\n", response);
      }).catch(error=>{
        console.log("\nDelete Failed w. error:\n", error);
      });
    }
  }
}); 

/** NEW button for PkModels - DOESN'T CREATE VIA AJAX - just in the DOM
 * -- still have to save/POST
 * @props params
 */
Vue.component('new-btn', {
  name: 'new-btn',
  template: `
   <button :class="btncls"
      @click.stop="create()">
      New</button>
`,
  props:['params'],
  computed: {
    btncls: function (){
      console.log("Btn class param:", this.params);
      return this.params.btncls || " pkmvc-button inline ";}
  },
  methods: {
    create: function() {
      console.log("Trying to create new row");
      /*
      var delfromdom=this.params.delfromdom;
      var url = this.params.url || "/ajax/delete";
      var params = {
        model: this.params.classname,
        id: this.params.id,
        cascade: this.params.cascade,
      };
      axios.post(url,params).then(response=> {
        if (delfromdom) {
          $(this.$el).closest(delfromdom).remove();
        }
        console.log("\nDelete Success w. response:\n", response);
      }).catch(error=>{
        console.log("\nDelete Failed w. error:\n", error);
      });
      */
        
    }
  }
}); 



/********************  Reactive Tables ************************/

//Two Versions - the first runs the data down the columns - the
//second runs them across the rows, which seems to make more sense

//FIRST SET - Columns in a Row
//Two components - the frame, that may hold any number of columns
//The column - which has a header/label & number of values below it,
//but below a certain width, each field has the label next to it (
// Or above it? Has to be both configurable, and work with bs4
/*
  <div class="d-none d-lg-inline-block">Only above width</div>
  <div class=" d-lg-none">Only BELOW width</div>
*/

//'label' prop is a single value. 'fields' is an array
// 'bp' is xs, sm, md, lg, xl
window.Vue.component('responsive-column', {
//CVue.component('responsive-column', {
  name: 'responsive-column',
  template: `<div :class="colcls">
  <div :class="rowcls+' '+ show_bg_flex">
    <div :class="lblcls" v-html="label"></div>
  </div>
  <div v-for="(field,idx) in fields" :class="rowcls">
     <div :class="show_sm + ' '+ lblcls" v-html="label"></div>
     <div :class="fldcls" v-html="field"></div>
  </div>
  
  </div>
  `,
  props: ['label','fields','colcls',
    'lblcls','fldcls', 'bp', 'rowcls'],
  computed: {
    show_sm: function() {return  " d-"+this.bp+"-none ";},
    show_bg_inline: function() {return  " d-none d-"+this.bp+"-inline-block ";},
    show_bg_flex: function() {return  " d-none d-"+this.bp+"-flex ";}
    /*
    clc_lbl_sm: function() {return this.lblcls + " d-"+this.bp+"-none ";},
    clc_lbl_bg: function() {
      var lblbg = this.lblcls + " d-none d-"+this.bp+"-inline-block ";
      console.log("lblg", lblbg);
      return this.lblcls + " d-none d-"+this.bp+"-inline-block ";}
    */
  }
});

/** 
 * (NOTE: Superceded by resp-tbl & resp-row below, for row-based tables!)
 * Composes a responsive table from responsive-columns
 * Props - single prop object:
 * tbldata: Object:
 *    head:Table Header
 *    headcls: Head CSS  class
 *    tblcls: Table Class
 *    coldefs: Object - column defaults - for coldata entries, unless they exist
 *          like: lblcls, bp, rowcls, fldcls 
 *    coldata: array of prop objects for responsive-column above - each el:
 *        label: Column Label
 *        fields: array - field values
 *        (opt, or in tbldata.coldefs)
 *        bp - breakpoint for when to label each field
 *        rowcls- column row class
 *        lblcls - label class
 *        fldcls - field class
 *        
 *   
 */
/*
 * 
  template: `
  <div :class='tbldata.tblcls' class="row">
    <div :class="tbldata.headcls" v-html="tbldata.head"></div>
      <responsive-column v-for="(coldtm,idx) in cmp_coldata"
          :label="coldtm.label" :fields="coldtm.fields"
         :lblcls="coldtm.lblcls" :fldcls="coldtm.fldcls" :bp="coldtm.bp"
         :rowcls="coldtm.rowcls"></responsive-column>
  </div>
`,
 */
/** Part of turning tables on their side, so big container is column, contain
 * many rows, eche row contains mini-columnt again.
 */
window.Vue.component('responsive-table', {
  name: 'responsive-table',
//CVue.component('responsive-table', {
/**
  template: `
  <div :class='tbldata.tblcls'>
    <div :class="tbldata.headcls" v-html="tbldata.head"></div>
  <div class='row'>
   
      <responsive-column  v-for="(coldtm,idx) in cmp_coldata" :label="coldtm.label" :fields="coldtm.fields"
         :lblcls="coldtm.lblcls" :fldcls="coldtm.fldcls" :bp="coldtm.bp"
         :rowcls="coldtm.rowcls"></responsive-column>
    
    </div>
  </div>
`,
  */
  template: `
  <div :class='tbldata.tblcls' class="row">
    <div :class="tbldata.headcls" v-html="tbldata.head"></div>
      <responsive-column v-for="(coldtm,idx) in cmp_coldata"
          :label="coldtm.label" :fields="coldtm.fields" :colcls="coldtm.colcls"
         :lblcls="coldtm.lblcls" :fldcls="coldtm.fldcls" :bp="coldtm.bp"
         :rowcls="coldtm.rowcls"></responsive-column>
  </div>
`,
  //props: ['head', 'headcls', 'coldata', 'tbldata','tblcls'],
  props: ['tbldata'],
  computed: {
    //Iterate over coldata & add defaults if they exist
    cmp_coldata: function() {
      if (!this.tbldata.coldefs) {
        console.log("\n\nNo coldefs in tbldata\ntbldata:", this.tbldsata);
        return this.tbldata.coldata;
      }
      var tbldata = this.tbldata;
      this.tbldata.coldata.forEach(function(coldtm,idx) {
        for (var aprop in tbldata.coldefs) {
          if (!coldtm[aprop]) {
            coldtm[aprop] = tbldata.coldefs[aprop];
          }
        }
      });
      console.log("\nEnhanced coldefs:\n",this.tbldata.coldata);
      return this.tbldata.coldata;
    }
  },
  methods: {
    cmpval: function(valname) { //Returns the 
    }
  }
  });

//////////////////  End Column Based Tables ///////////////


//////////////////  Start Row Based Tables ///////////////
//Two components - the frame, that may hold any number of rows,
//The row - which has a header/label & number of values below it,
//but below a certain width, each field has the label next to it (
// Or above it? Has to be both configurable, and work with bs4
/*
  <div class="d-none d-lg-inline-block">Only above width</div>
  <div class=" d-lg-none">Only BELOW width</div>
*/

/**
 * Shows a row of Labels/Data in the table -- included by resp-tbl
  'celldataarr' is an array of objects containing information about each cell
      field: Value to Display in the cell
      label: Label for the cell, if small viewport
      lblcls: Label CSS for the cell, if small viewport
      fldcls: Field CSS Class
      cellcls: Cell CSS (if small viewport, so includes both label & field
  'rowinfo' - object w. whole-row data:
      delete: {Required: classname: model, id:id 
          optional: url:'/ajax/delete", btncls:'btn-cls', cascade:false,
          delfromdom:??  -- all other celldata opts


      celldefs: object with default values for each cell, if not given  
      rowcls: The CSS class of the row
      islbl: Is a lable row?
  bp:  xs, sm, md, lg, xl
      
// 'bp' = the breakpoint to change the row.`
// The first is probably special, since it is the row headings/labels
// each of which contains info about the item, including possible delete buttons, 
// the ID of the object, and the label for the item.
// First row might just be labels if no data
// 'bp' is xs, sm, md, lg, xl
*/
  //<div :class="rowcls + show_bg_flex_lbl + block_below">
window.Vue.component('resp-row', {
  name: 'resp-row',
  template: `
  <div class="js-resp-row" :class="rowcls + show_bg_flex_lbl + display_below">
    <input type='hidden' :name='id_name' :value="rowinfo.id" />
    <input type='hidden' name='model' :value="rowinfo.classname" />
    <div v-for="(celldata,idx) in cmp_celldataarr"
        :class="celldata.cellcls" :style="celldata.cellstyle">
      <div :class="show_sm + ' '+ celldata.lblcls" v-html="celldata.label"></div>
      <div :class="celldata.fldcls" v-html="celldata.field"></div>
    </div>
    <div v-if="del.id || del.delfromdom" :class="del.cellcls" :style="del.cellstyle">
       <delete-btn :params="del"></delete-btn>
    </div>
    <div v-else-if="del" :class="del.cellcls" :style="del.cellstyle"></div>
  
  </div>
  `,
  props: ['celldataarr','rowinfo', 'bp'],
  computed: {
    id_name: function() {
      //console.log("This rowinfo:", this.rowinfo);
      return this.rowinfo.relation+'['+this.rowinfo.cnt+'][id]';
    },
    rowcls: function() {return this.rowinfo.rowcls || ' rt-rowcls ';},
    show_sm: function() {return  " d-"+this.bp+"-none ";},
    //show_sm_block: function(){return  " d-"+this.bp+"-block ";},
    display_below: function(){
      if (this.rowinfo.islbl) {
        return  " d-none-below-"+this.bp + " ";
      } else {
        return  " d-block-below-"+this.bp + " ";
      }
    },
    block_below: function(){return  " d-block-below-"+this.bp + " ";},
    none_below: function(){return  " d-none-below-"+this.bp + " ";},
    show_bg_inline: function() {return  " d-none d-"+this.bp+"-inline-block ";},
    show_bg_flex: function() {return  " d-none d-"+this.bp+"-flex ";},
    show_bg_flex_lbl: function() {
      if (this.rowinfo.islbl) {
        return  " d-none d-"+this.bp+"-flex ";
      } else {
        return " ";
      }
    },
    del: function() {
      var del = this.rowinfo.delete;
      if (del === 'undefined') {
        return false;
      }
      var celldatadefs = this.celldefaults();
      var deldata = Object.assign({},celldatadefs,this.rowinfo.celldefs, del);
      deldata.cellstyle=" width: 50px; ";
      return deldata;
    },
    cmp_celldataarr: function() {
      var celldatadefs = this.celldefaults();
      var dataarr = [];
      var me = this;
      this.celldataarr.forEach(function(celldata, idx) {
        if (celldata.width) {
          celldata.cellstyle= " width:"+celldata.width+"px; ";
        }
        dataarr.push(Object.assign({},celldatadefs, me.rowinfo.celldefs,celldata));
      });
      console.log("In cmp_celldata, dataarr:", dataarr);

      return dataarr;
    }
  },
  methods: {
    celldefaults: function() {
      var cnt = 0;
      var offset = 0;
      this.celldataarr.forEach(function(celldata,idx) {
        console.log("In CellDefaults, celldata:",celldata);
        if (celldata.width) {
          offset += celldata.width;
        } else {
          cnt++;
        }
      });
      //var cnt = this.celldataarr.length;
      if ('delete' in this.rowinfo) {
        //offset = " "+60/cnt+"px ";
        offset += 60;
      }
      console.log("after iter: cnt:",cnt,"offset",offset);
      offset = " "+offset/cnt+"px ";
      var pc = 100/cnt;
      var style = " flex-basis: calc("+pc+"% - "+offset+"); ";
      //var style = " flex-basis: "+pc+"%; ";
      if (this.rowinfo.islbl) {
        var fldclass = " rt-fldcls rt-lblcls ";
      } else {
        var fldclass = " rt-fldcls ";
      }
      
      var celldatadefs = {
        cellcls: " rt-cellcls ",
        fldcls: fldclass,
        lblcls: " rt-lblcls ",
        rowcls: " rt-rowcls ",
        cellstyle: style
      };
      return celldatadefs;
    }
  }
});

///////////////////  Table that uses resp-rows above
/** 
 * Composes a responsive table from responsive rows
 * Props - 
 *   tbldata: object containing the table data:
 *     head:Table Header
 *     headcls: Head CSS  class
 *     tblcls: Table Class
 *     bp:  xs, sm, md, lg, xl
 *     rowinfo: (Overridden in rowdataarr)
 *     rowdataarr: array of row objects containing data for each resp-row:
 *      celldataarr: the cell data array for the row
 *      rowinfo
 *    new - opt - array required to make new row
 *        
 *   
 */
window.Vue.component('resp-tbl', {
  name: 'resp-tbl',
//CVue.component('responsive-table', {
  template: `
  <div :class='tblcls' class="js-resp-tbl">
    <input type='hidden' :name='tbldata.relation'>
    <div v-if="tbldata.head" :class="headcls" v-html="tbldata.head"></div>
    <resp-row v-for="(rowdata,idx) in rowdataarr" v-if="!rowdata.rowinfo.new"
        :celldataarr="rowdata.celldataarr"
        :rowinfo="rowdata.rowinfo"
        :bp="bp">
    </resp-row>
    <div v-if="newbtn" >
       <div class="pkmvc-button inline" @click.stop="addrow()">New</div>
    </div>
    <div v-if="savebtn" >
       <div class="pkmvc-button inline" @click.stop="submit()">Save</div>
    </div>
    <div v-if="tbldata.foot" :class="footcls" v-html="tbldata.foot"></div>
    
  </div>
`,
       //<new-btn :params="newbtn"> </new-btn>
  //props: ['head', 'headcls', 'coldata', 'tbldata','tblcls'],
  props: ['tbldata'],
  methods: {
    submit: function(ev) {
      var fd = new FormData(this.$el);
      var jqints = $(this.$el).find(':input');
      jqints.each(function(idx, el) {
        var $el = $(el);
        fd.append($el.attr('name'), $el.val());
      });
      console.log ("This El:", this.$el,"The formdata to post:", fd, "jqints", jqints);

      //var url = this.formopts.url; 
      var me = this;
      axios.post(this.savebtn.url, fd).then(response=> {
        console.log("The response was:", response);
      });
    },
    addrow: function() {
      //var lblrow = Object.assign({},this.rowdataarr[this.rowdataarr.length-1]);
      //var cnt = this.rowdataarr.length-1;
      var cnt = this.tbldata.rowdataarr.length-1;
      var lblrow = _.cloneDeep(this.rowdataarr[0]);
      lblrow.celldataarr.forEach(function(celldata,idx) {
        celldata.field = celldata.field.replace(/__CNT_TPL__/g, cnt-1);
      });
      lblrow.rowinfo.cnt = cnt-1;
      lblrow.rowinfo.islbl=false;
      lblrow.rowinfo.new=false;
      console.log("ADD ROW lblrow:",lblrow);
      this.tbldata.rowdataarr.push(lblrow);
    }
  },

  computed: {
    savebtn: function() {
      var savebtn = this.tbldata.savebtn;
      if (!savebtn) {
        return null;
      }
      return {
        url: savebtn.url || '/ajax/save'
      };

    },
    newbtn: function() {
      if (!this.tbldata.newbtn) {
        return null;
      }
      return this.tbldata.newbtn;
    },
    rowdataarr: function() {
      var rowdataarr = [];
      var me = this;
      this.tbldata.rowdataarr.forEach(function(rowdata,idx) {
        rowdataarr.push(Object.assign({}, {rowinfo:me.tbldata.rowinfo},rowdata));
      });
      return rowdataarr;
    },
    bp: function() {return  this.tbldata.bp || "md";},
    headcls: function() {return this.tbldata.headclass || "rt-headcls";},
    tblcls: function() {return this.tbldata.tblclass || "rt-tblcls";}
  }

  });

//////////////////  End Column Based Tables ///////////////








/******************** END  Reactive Tables ************************/
/**
 * 
 *  This didn't work at all
CVue.component('lqp-interest-inp', {
  template: `<div class='lqp-interest-item'>
<input type='text' id='interest_id' name='interest_id' @change='announce' v-model='interest_id'>
<input type='text' id='interest' class='ac-interest' name='interest' value=''>
  </div>
`,
  props: ['interest', 'interest_id'],
data: function() {
  return {
    interest :'',
    interest_id:''
  };
},
watch: {
  interest_id: function(oldVal, newVal) {
    console.log ("Interrest ID Changed, from ",oldVal,' to ', newVal);
  }
},
methods: {
  announce: function() {console.log("In methods, interest_id changed");}
}
            
});
*/
/*

CVue.component('blog-post-form', {
  template: `<div class='blog-post-wrap'>
  <form class='wysiwyg' @submit.prevent="onSubmit">
  <input type='hidden' name='id' value="id">
<input type='text' name='title' v-model='title' class='post-title'>
  <textarea name='body' id='body' v-html='body' class='post-body'
  ></textarea>
  <button type='submit'>Publish</button>
  </form>
  </div>
`,
 data: function() {
   return {
     id: '',
     title: '',
     body: ''
   };
 },
 methods: {
   onSubmit: function(event) {
     tinyMCE.triggerSave();
     var form = $('form.wysiwyg')[0];
     console.log("We caught the submit, the event:",event, 'form',form);
     var formData= new FormData(form);
     $.ajax({
       type: 'POST',
       url:'/ajax',
       data:formData,
       contentType: false,
       processData: false,
       success: function(data) {
         console.log('We got',data);
       }
     });
   }
 },
 mounted: //function() {  CKEDITOR.replace( 'body' );}
            function() {
              tinymce.init({
                mode:'textareas',

                setup: function (editor) {
        editor.on('change', function () {
            editor.save();
        });
      },
  selector: 'textarea.post-body',
  height: 500,
  menubar: false,
  plugins: [
    ' autolink  link image  anchor',
    ' media '
  ],
  
 // plugins: [
 //   'advlist autolink lists link image charmap print preview anchor',
//    'searchreplace visualblocks code fullscreen',
//    'insertdatetime media table contextmenu paste code'
//  ],
  toolbar: 'undo redo | insert | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
  content_css: [
    '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
    '//www.tinymce.com/css/codepen.min.css']
});
            }
});

*/

CVue.component('tabs', {
    template: `
        <div>
            <div class="tabs">
                <ul>
                    <li v-for="tab in tabs" :class="{ 'is-active': tab.isActive }">
                        <a :href="tab.href" @click="selectTab(tab)">{{ tab.name }}</a>
                    </li>
                </ul>
            </div>

            <div class="tabs-details">
                <slot></slot>
            </div>
        </div>
    `,

    data() {
        return { tabs: [] };
    },

    created() {
        this.tabs = this.$children;
    },

    methods: {
        selectTab(selectedTab) {
          alert("We have clicked!");
            this.tabs.forEach(tab => {
                tab.isActive = (tab.href == selectedTab.href);
            });
        }
    }
});


CVue.component('tab', {
    template: `
        <div v-show="isActive"><slot></slot></div>
    `,

    props: {
        name: { required: true },
        selected: { default: false }
    },

    data() {
        return {
            isActive: false
        };
    },

    computed: {
        href() {
            return '#' + this.name.toLowerCase().replace(/ /g, '-');
        }
    },

    mounted() {
        this.isActive = this.selected;
    }
});

module.exports = PkVue;
