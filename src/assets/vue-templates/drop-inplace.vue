<!-- Drop & immediately upload, in place -->
<template>
  	<div class="drop display-inline align-center" @dragover.prevent @drop="onDrop">
              <delete-x data-tootik="Delete This?"></delete-x>
      <div class=" align-center img-wrapper"  data-tootik="Click or Drop your profile image here">
               <input class="abs-hidden" type="file" name="image" @change="onChange">
        <img :src="url || defaulturl" alt="" class="img-upload" />
  </div>
  </div>
</template>

<script>
//var app = new Vue({
export default {
    name: 'drop-inplace',
    data() {
      return {
      defaulturl: this.params.defaulturl ||  "/mixed/img/generic-avatar-1.png" ,
      image: '',
      file: null,
      desc: '',
      imgblob: null,
      uploadopts: this.params.uploadopts | [],
      mediatype: this.params.mediatype || 'image',
      url: this.params.url ||  "/mixed/img/generic-avatar-1.png" ,
      attribute: this.params.attribute || 'avatar',//the relation name, like avatar
      foreignkeyname: this.params.foreignkeyname,
      id: this.params.id || null, //The id of the uploaded object
      model: this.params.model || null, //The upload model 
      ownerid: this.params.ownerid,
      ownermodel: this.params.ownermodel,
      //action: this.params.action || 'typedprofileupload',
      saveurl: this.params.saveurl || '/ajax/upload',
      fetchurl: this.params.fetchurl || '/ajax/fetchattributes',
      deleteurl: this.params.deleteurl || '/ajax/delete',
      };
    },
    computed: {
      deleteparams() {
        return {
          model:this.model,
          id: this.id,
          cascade: true,
          deleteurl:this.deleteurl
        };
      },
    },
    props: ['params'], 
    mounted: function() {
      console.log("AS SOON AS MOUNTED: dnd.vue Mounted, Params:",this.params, "This.url:", this.url);
      this.logThis();
      this.initData();
    },

    methods: {
      delete() {
        var delparm = {model:this.model,id:this.id,cascade:true};
        console.log("About to delete w. params:",delparm);
        //axios.post(this.deleteurl,{model:this.model,id:this.id,cascade:true}).
        axios.post(this.deleteurl,delparm).
          then(response=>{console.log("The delete was successful:",response);
            this.initData();
            this.$parent.$refs.dropavatar.initData();
          }).
          catch(error=>{console.error("The delete failed:", error, error.response);});
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
        console.log("This is:",currvals);
        return currvals;
      },
      initData(data) { //Data may be empty or have all we need
        
        var me = this;
        var keystoset = ['attribute','mediatype','id','url','model'];
        var searchkeys1 =['model','id'];
        var searchkeys2 =[ 'ownermodel','ownerid','attribute'];
       //Check data is object & not null
       if ((typeof data !== 'object') || (data === null)) { //Make an empty data object
         data = {};
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
        console.log("In initData, trying to query:", querydata);
        axios.post(this.fetchurl,querydata).
          then(response=> {
            console.log("Search Results:", response);
            var rdata = response.data;
            if (!rdata.id) { //No Match
              this.url = this.defaulturl;
              return;
            }
            console.log("Keystoset?",keystoset);
            keystoset.forEach(function(key) {
              me[key] = rdata[key];
            });
          }).
          catch(error=>{console.error("We had an error with the search:",error,error.response);});
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
        this.createFile(files[0]);
      },
      createFile(file) {
        if (!file.type.match('image.*')) {
          alert('Select an image');
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
        console.log("About to save. These are the values?");
        this.logThis();
        var fd = new FormData();
        var savekeys = [ //The keys required to save the upload
          'ownermodel','ownerid','model','foreignkeyname','attribute',
          'mediatype','uploadopts'];
        var me = this;
        savekeys.forEach(function(key) {
          fd.append(key,me[key]);
        });
        //this.params.action='typedprofileupload';
        /*
        for (var key in this.params) {
          fd.append(key, this.params[key]);
        }
        */
        //fd.append('desc',this.desc);
        fd.append('extra',JSON.stringify(['url']));
        fd.append('file',this.file,this.file.name);
        //console.log("In Save, FD:", fd);
        //var me = this;
        axios.post(this.saveurl,fd).
          then( response=> { console.log("File Upload Save Response:",response.data);
          this.initData(response.data)
          this.$parent.$refs.dropavatar.initData();
          //this.$emit('refresh');
          }).
          catch(error=>{console.log("Error saving:",error,error.response);});
      },
      /*
      removeFile() {
        this.image = '';
      }
      */
    }
  }
</script>
  

<style>
  * {
  font-family: 'Arial';
  font-size: 12px;
}
.drop {
    height: auto;
    width: auto;
}

*,
*:after,
*:before {
  -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  box-sizing: border-box;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
  -webkit-touch-callout: none;
}

.abs-hidden {
  position: absolute;
  visibility: hidden;
}

/*
html, body {
	height: 100%;
  text-align: center;
}

.btn {
  background-color: #d3394c;
  border: 0;
  color: #fff;
  cursor: pointer;
  display: inline-block;
  font-weight: bold;
  padding: 15px 35px;
  position: relative;
}

.btn:hover {
  background-color: #722040;
}

input[type="file"] {
  position: absolute;
  opacity: 0;
  z-index: -1;
}
*/

.align-center {
  text-align: center;
}

.helper {
  /*
  height: 100%;
  */
  display: inline-block;
  vertical-align: middle;
  width: 0;
}

.hidden {
  display: none !important;
}

.hidden.image {
  display: inline-block !important;
}

.display-inline {
  display: inline-block;
  vertical-align: middle;
}

img.img-upload {
  border: 1px solid #f6f6f6;
  display: inline-block;
  width: 200px;
  height: auto;
  /*
  max-width: 200px;
  max-height: 200px;
  */
}
div.pk-dragndrop-container {
  position: relative;
  margin-left: auto;
  margin-right: auto;
  display: inline-flex;
  border: #555 solid 1px;
  border-radius: .5em;
  padding: .5em;
  background: #aaa;
  text-align: center;
}

textarea.text-desc {
  background: white;
  border: solid #aaa 1px;
  border-radius: 5px;
  padding: .3em;
  width: 200px;
  height: 60px;
}

.img-wrapper {
    position: relative;
}

div.drop {
  width: auto;
  /*
  width: 200px;
  height: auto;
  */
}


</style>


