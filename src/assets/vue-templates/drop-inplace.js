/* 
 * The shared mixin for "drop-inplace" components
 */
require("./vue-data-components.js");

window.dropInPlaceMixin = {
    name: 'drop-inplace',
    props: ['params', 'instance'], 
    mixins: [window.utilityMixin],
    data() {
      //console.log("Data/drop-inplace");
              
      return {
      defaulturl: this.params.defaulturl ||  "/mixed/img/generic-avatar-1.png" ,
      //defaulturl: null,//  "/mixed/img/generic-avatar-1.png" ,
      status: this.params.status || '',//'extant' - part of existing object, need only model & ID
      file: null,
      title:  this.params.title || 'upload',
      desc: '',
      imgblob: null,
      fetchargs:{},
      saveargs:{},
      delargs:{},
      urlmap:{},//What returned attribute to use for URL - default: {name}_url

      uploadopts: this.params.uploadopts | [],
      mediatype: this.params.mediatype,
      //chain: this.params.chain || [],
      
      //url: this.params.url ||  "/mixed/img/generic-avatar-1.png" ,
      url: '',//this.params.url ||  "/mixed/img/generic-avatar-1.png" ,
      attribute: this.params.attribute || null,//the relation name, like avatar
      relation: this.params.relation || null, // same as attribute,
      name: this.params.name || null,//the or relation name, like avatar
      method: this.params.method,
      foreignkeyname: this.params.foreignkeyname,
      id: this.params.id || null, //The id of the uploaded object
      model: this.params.model || null, //The upload model 
      ownerid: this.params.ownerid,
      ownermodel: this.params.ownermodel,
      action: this.params.action,
      saveurl: this.params.saveurl || '/ajax/upload',
      seturl: this.params.saveurl || '/ajax/set',
      fetchurl: this.params.fetchurl || '/ajax/fetchattributes',
      deleteurl: this.params.deleteurl || '/ajax/delete',
      embedded: this.params.embedded,
      degree: 0,// 0 => embedded, 1 => 1 to 1,, 2=>1 to many 
      };
    },
    computed: {

/*
      url() {
        return this.params.url;
      },
        */
      deleteparams() {
        return {
          model:this.model,
          id: this.id,
          cascade: true,
          deleteurl:this.deleteurl
        };
      },
    },
    mounted: function() {
      console.log("Mounted");
      this.$nextTick(()=>{
        //console.log("AS SOON AS MOUNTED: dnd.vue Mounted, Params:",this.params, "This.url:", this.url);
        this.defaulturl  = this.$options.extraoptions.defaulturl;
        this.mediatype  = this.$options.extraoptions.type;
        var fields = Object.keys(this.$data);
        this.setData(this,fields,this.params,this.instance);
        this.adjustData();
        this.logThis();
        this.initData();
        //this.setUrl();
        //console.log("In drop-inplace mounted, data:", this.$data);
      });
    },
    methods: {
      adjustData() {//Based on initial data settings, adjust others
        if (!this.attribute && !this.relation) {
          this.degree=0;
          this.embedded = true;
          if (this.name && isEmpty(this.urlmap)) {
            this.urlmap = {url:this.name+'_url'};
          }
        } else {
          if (this.relation) {
            this.attribute = this.relation;
          } else if (this.attribute) {
            this.relation = this.attribute;
          }
          this.embedded = false;
          if (!this.degree) {
            this.degree = 1;
          }
        }
      },
      testHandler(event) {
        console.log("Clicked on the test handler");
      },
      fiClicked(event, data) {

        //if (event.target === "img.img-upload") {
//         console.log ("Target was ",event.target," send to file input");
         $(event.target).cousin('input.click-target','div.drop').trigger('click');
        ////}
        //console.log("The input got the click, ev:",event,"data:", data);
      },
      delete() {
        if (this.embedded) { //Embedded - don't delete object, just field
          var dargs = {
            model: this.model,
            id: this.id,
            values: {
              [this.name]:null,
            },
          };

          axios.post(this.seturl, dargs).
            then(response=>{console.log("Seems to have deleted entry successfully:",
                  response);
                  this.setEmbeddedData(response.data);
            }).catch(defaxerr);
          return;
        }
        if (this.status==='extant') {
          axios.post("/ajax", {
                model:this.model,
                id:this.id,
                action:'execute',
                args: [this.title],
                method:'deleteEntry'}).
                   then(response=>{console.log("Seems to have deleted entry successfully:",
                   response);
                   this.initData(response.data);
                   this.url = response.data.url;
                 }).catch(defaxerr);
        } else {
        var delparm = {model:this.model,id:this.id,cascade:true};
        //console.log("About to delete w. params:",delparm);
        //axios.post(this.deleteurl,{model:this.model,id:this.id,cascade:true}).
        axios.post(this.deleteurl,delparm).
          then(response=>{console.log("The delete was successful:",response);
            this.initData();
            //this.$parent.$refs.dropavatar.initData();
          }).
          catch(defaxerr);
        }
      },
      logThis() {
        var comatts = ['defaulturl', 'image', 'file', 'desc', 'imgblob', 'uploadopts', 'mediatype',
            'url', 'attribute', 'foreignkeyname', 'id', 'model', 'ownerid', 'ownermodel',
          'saveurl', 'fetchurl', 'deleteurl'];
        var currvals = {};
        var me = this;
        comatts.forEach(function(key){
          currvals[key] = me[key];
        });
        //console.log("This is:",currvals);
        return currvals;
      },
      setEmbeddedData(data) {
        data = this.asObject(data);
        var url = data[this.urlmap.url];
        //console.log("setEmbedded Data, this$datta:",this.$data,"Passed data:",data,"url",url);
        if (!url)  {
          url = this.defaulturl;
        }
        this.url = url;
        return;
      },

      initData(data) { //Data may be empty or have all we need
        //console.log ("Init it start data:", data);
        var me = this;
        if (this.embedded) {
          axios.post(this.fetchurl, {model:this.model,id:this.id,keys:[this.urlmap.url]}).
            then(response=> {
              //console.log("Response data for set url",response.data);
              this.setEmbeddedData(response.data);
          }).catch(defaxerr);
          return;
        } else {
          var keystoset = ['attribute', 'title', 'method', 'args','mediatype','id','url','model'];
        }
        var searchkeys1 =['model','id','title','attribute'];
        var searchkeys2 =[ 'ownermodel','ownerid','title','attribute'];
        //Check data is object & not null
        if (!this.isObject(data)) {
          data = {};
        }
        if (!this.attribute) { //Embedded - direct fetch
          if (this.name && isEmpty(this.fetchargs)) {
            var att =  this.name + '_url';
            this.urlmap.url = att;
            this.fetchargs = {
              model:this.model,
              id: this.id,
              keys:[att],
            };
          }
        }
       //Check if we have enough to query
        var all = true;
        var querydata ={};
        for( let key of searchkeys1) {
           var tmp = data[key] || me[key];
           if (!tmp) {
             all=false;
             break;
           }
           querydata[key]=tmp;
         }
         if (!all) { //We don't have enough to query
           all=true;
           querydata = {};
           for( let key of searchkeys2) {
             var tmp = data[key] || me[key];
             if (!tmp) {
               all=false;
               break;
             }
           querydata[key]=tmp;
         }
       }
       if (all) {//We have a query set
         querydata.extra=JSON.stringify(['url','model']);
        //console.log("In initData, trying to query:", querydata);
        axios.post(this.fetchurl,querydata).
          then(response=> {
            //console.log("Search Results:", response);
            var rdata = response.data;
            //console.log("Search Results:", response.data);
            if (!rdata.id) { //No Match
              this.url = "/mixed/img/generic-avatar-1.png" ;
              return;
            }
            //console.log("Keystoset?",keystoset);
            keystoset.forEach(function(key) {
              me[key] = rdata[key];
            });
          }).
          catch(defaxerr);
       } else { //Maybe we got good data in data
          for( let key of searchkeys2) {
            if (data[key]) {
              me[key] = data[key];
            }
          }
        }
      },
      onDrop: function(e) { //Just want to upload & save it right away
        console.log("Just dropped something - what?",e);
        e.stopPropagation();
        e.preventDefault();
        var files = e.dataTransfer.files;
        this.createFile(files[0]);
      },
      onChange(e) {
        var files = e.target.files;
        //console.log("Change triggered - files?",files);
        this.createFile(files[0]);
      },
      createFile(file) {
        //if (file && !file.type.match('image.*')) {
        if (file && (this.fileType(file.type) !== this.mediatype)) {
          //console.log("The file filetype: ",file.type,"this.fileType: ", this.fileType(file.type),
          //"this.mediatype:", this.mediatype);
          alert('Select file of type '+this.mediatype);
          return;
        }
        this.file = file;
        var img = new Image();
        var reader = new FileReader();
        var blobreader = new FileReader();
        var vm = this;
        blobreader.onload = function(e) {
          vm.imgblog = e.target.result;
        }
        reader.onload = function(e) {
          //vm.image = e.target.result;
          vm.url = e.target.result;
        }
        reader.readAsDataURL(file);
        blobreader.readAsArrayBuffer(file);
        this.saveFile();
      },
      saveFile() {
        //console.log("About to save. These are the values? this.$data:", this.$data, "this.name", this.name);
        //this.logThis();

        if (!this.isObject(this.file)) {
          console.error("Somehow trying to save non-existant file");
          return;
        }
        var fd = new FormData();
        fd.append('file',this.file,this.file.name);
        if (this.embedded) {
          fd.append('model',this.model);
          fd.append('id',this.id);
          fd.append('values',JSON.stringify({[this.name]:"1"}));
          /*
          var args = {
            model: this.model,
            id : this.id,
            values: {[this.name]:"1"},
            fd:fd,
          };
    */
          /*
          axios.post(this.seturl,args,
            header: {
            'Accept': 'application/json',
            'Content-Type': 'multipart/form-data',
          },
          axios({method:"post",
                url:this.seturl,
                data:fd,
                header: {
                  'Accept': 'application/json',
                  'Content-Type': 'multipart/form-data',
                },
    */
          axios.post(this.seturl,fd,
            { header: {
              'Accept': 'application/json',
              'Content-Type': 'multipart/form-data',
            }, }).then(response=>{
            this.setEmbeddedData(response.data);
          }).catch(defaxerr);
          return;
        }
        if (this.status==='extant') {
          ///var savekeys = ['model','id','name'];
          //var savekeys = ['model','id','chain', 'title', 'name'];
          var savekeys = ['model','id', 'title', 'name'];
        } else { //It's a separate media object, with an "owner" object
          var savekeys = [ //The keys required to save the upload
            'ownermodel','ownerid','model','foreignkeyname','attribute',
            'mediatype','uploadopts'];
        }
         //savekeys.push('title');
         //console.log("We decided the keys were:", savekeys, "cus status:",this.status);
         var me = this;
         savekeys.forEach(function(key) {
            fd.append(key,me[key]);
            //console.log("Key:",key,"me[key]",me[key]);
          });
        //this.params.action='typedprofileupload';
        /*
        for (var key in this.params) {
          fd.append(key, this.params[key]);
        }
        */
        //fd.append('desc',this.desc);
        fd.append('extra',JSON.stringify(['url']));
        //console.log("In drop-inplace Save, URL:", this.saveurl,"; FD:", afd(fd));
        //var me = this;
        axios.post(this.saveurl,fd).
          then( response=> { console.log("File Upload Save Response:",response.data);
          this.initData(response.data)
          //this.$parent.$refs.dropavatar.initData();
          //this.$emit('refresh');
          }).
          catch(defaxerr);
      },
    },
  }


