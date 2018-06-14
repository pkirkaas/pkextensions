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
var XCVue = Vue.extend({
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

const CVue = Vue.extend({
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
    },
  }
});
//Try making reusable inputs
// inputs
    //<div :class="lblclass" v-html="label"><input type="text" :value="value"
    //
    //
// Actually, if in a v-for, prop should be an object, so see below
Vue.component('text-input',{
  template: `
  <div :class="wrapclass">
    <div :class="lblclass">{{label}}<input type="text" :value="value"
    :name="name" :class="inputclass" class='border bg-444' placeholder="Come on, Dish!"></div></div>`,
  props:['lblclass', 'label', 'value','name','inputclass','wrapclass'],
});




/*** Make my own input components for inclusion in arrays***/

// Actually, if in a v-for, prop should be an object, so
Vue.component('pk-input-arr',{
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
  },

});

/** Checkbox */
Vue.component('pk-check-arr', {
  inptype: 'checkbox',
  template: `
  <div :class="inpopt.wrapclass"> <div :class="inpopt.lblclass">{{inpopt.label}}
  <input type="checkbox" :name="inpopt.name" :class="inpopt.inputclass"
      :value="inpopt.value" v-model="inpopt.checked/>
    class='border' ></div></div>
`,
  props: ['inpopt'],
  created: function() {
    if (!this.inpopt.value) {
      this.inpopt.value = 1;
    }
  },
    /*
    if (!this.inpopt.truevalue) {
      this.inpopt.truvalue = 1;
    } 
    if (!this.inpopt.falsevalue) {
      this.inpopt.falsevalue = 0;
    }
    */

});

/** Select */
Vue.component('pk-select-arr', {
  inptype: 'select',
  template: `
  <div :class="inpopt.wrapclass"> <div :class="inpopt.lblclass">{{inpopt.label}}
  <select :name="inpopt.name" :class="inpopt.inputclass" v-model="inpopt.value">
    <option v-for="(option, idx) in inpopt.options" :value="option.value">
        {{option.label}}
    </option>
  </select>

    class='border' ></div></div>
`,
  props: ['inpopt'],

});

/*
Vue.component('pk-input-arr',{
  type: 'input',
  template: `
  <div :class="inpopt.wrapclass">
    <div :class="inpopt.lblclass">{{inpopt.label}}<input type="text" v-model="inpopt.value"
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
window.Vue.component('pk-input-form',{
  template: `
  <div :class="formopts.class" class="mini-input-form">
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
    },

  }
});

Vue.component('small-txt', {
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

Vue.component('tiny-img', {
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
Vue.component('profile-btn', {
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
  props: ['profile','label','baseurl', 'tootik', 'btnclass','colclass'],
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
Vue.component('contact-btn', {
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
Vue.component('invite-btn', {
  extends: contact-btn,
*/
Vue.component('invite-btn', {
//const InviteBtn =  {
  extends: Vue.component('contact-btn'),
  contact_type: {
    label: "Friend",
    tootik: "Send a Friend Invitation",
    popup: InviteBody,
    post: 'Invite'
  }
  });

//Vue.component('invite-btn',new InviteBtn());

Vue.component('message-btn', {
  extends: Vue.component('contact-btn'),
  contact_type: {
    label: "Message",
    tootik: "Send a message",
    popup: MessageBody,
    post: 'Message'
  }
});










/**
 * 
 *  This didn't work at all
Vue.component('lqp-interest-inp', {
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

Vue.component('blog-post-form', {
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

Vue.component('tabs', {
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


Vue.component('tab', {
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
    },
});
