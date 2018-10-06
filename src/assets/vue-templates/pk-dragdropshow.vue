<!-- For single images - saves instantly, dropping another replaces, & little red delete x -->
<!-- Start over again on the one I know works rename to 
<template>
  <div class='pk-dragdrop-show'>
   	<div class="helper"></div>
  
  	<div class="edit-img frame drop display-inline align-center" 
         @dragover.prevent @drop="onDrop" >
          <div class="helper"></div>
	<label v-if="!image" class="btn display-inline">
	        SELECT OR DROP AN IMAGE
	        <input type="file" name="image" @change="onChange">
      	</label>
                <img :src="image" alt="" class="img" />

         <div class="del-x actionable  svg-inline--fa fa-window-close fa-w-16 fa-5x    "
              @click="deleteupload()">X</div>
  </div>
   	<div class="helper"></div>
</div>
</template>

<script>

  //Assumptions - it will belong to another object, and it will be an attribute of that obj
  //Required: \\App\\Models\\Owner id(of owner) foreign_key name for this (owner_id)
  //id (of this object) attribute name of this object (presumably an uploaded model
  //To be general: mediatype[default image], dimensions, styling, URL & params to 
  //retrieve it, url & params to delete

/*
{owningmodel:'\\App\\Models\\Profile',owningid:<?=$profile->id?>,uploadmodel:'\\App\Models\ProfileUpload',foreignkeyname:'profile_id',attribute:'avatar'}
  */
//export default {
//module.exports = {
export default {
  name: 'pk-dragdropshow',
  data() {
    return {
      file: null,
      image: '',
      imgblob: null,
      owningmodel:'\\App\\Models\\Profile',
      owningid:37,
      uploadmodel:'\\App\\Models\\ProfileUpload',  
      foreignkeyname: 'profile_id',
      attribute:'avatar',
      imgclass: " icon inner ", 
      url:   "\/mixed\/img\/generic-avatar-1.png" ,
      tooltip:   "Select or Drag 'n Drop your Main Image" ,
      mediatype:  'image',
      saveurl:  '\/ajax\/upload',
      fetchurl:  '\/ajax\/fetchattributes',
      delurl:  '\/ajax\/delete',
      delcasc: false,        
      frameclass: " THE FRAME ",        
      /*
      imgclass: this.xparams.imgclass, 
      url:  this.xparams.url ,
      tooltip:this.xparams.tooltip,
      mediatype:this.xparams.mediatype,
      saveurl: this.xparams.saveurl,
      fetchurl: this.xparams.fetchurl,
      deleteurl: this.xparams.delurl,
      deletecascade: this.xparams.delcasc,
      owningmodel: this.xparams.owningmodel,
      owningid: this.xparams.owningid,
      uploadmodel: this.xparams.uploadmodel,
      id: this.xparams.id,
      foreigkeyname: this.xparams.foreigkeyname,
      foreigkeyvalue: this.xparams.foreigkeyvalue,
      attribute: this.xparams.attribute,
      frameclass:this.xparams.frameclass,
      */
    };
  },
  props: ['xparams'],
  computed: {
    url: function() {
      return this.xparams.url;
    },
    imgclass: function() {
      return this.xparams.imgclass;
    },
  },
  methods: {
    onDrop: function(e) { //Upload Immediately
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
        vm.image = e.target.result;
      }
      reader.readAsDataURL(file);
      blobreader.readAsArrayBuffer(file);
    },

      saveFile() {
        var fd = new FormData();
        //this.params.action='typedprofileupload';
        for (var key in this.params) {
          fd.append(key, this.params[key]);
        }
        fd.append('desc',this.desc);
        fd.append('file',this.file,this.file.name);
        console.log("In Save, FD:", fd);
        var me = this;
        axios.post(this.url,fd).
          then( response=> { console.log("DD Save Response:",response);
          //this.$parent.$refs.dropavatar.ajaxurl = response.data.url;
          this.$parent.$refs.dropavatar.initData();
          //this.$parent.ajaxurl = response.data.url;
          //this.$parent.$emit('refresh');
          //this.$emit('refresh');
          }).
          catch(error=>{console.log("Error saving:",error.response);});
      },


      removeFile() {
        this.image = '';
      },







/*


      var reader = new FileReader();
      var vm = this;
      reader.onload = function(e) {
          vm.image = e.target.result;
       };
      reader.readAsDataURL(file);
      var fd = new FormData();
      for (var key in this.xparams) {
        fd.append(key, this.xparams[key]);
      }
      fd.append('file',this.file,this.file.name);
      axios.post(this.saveurl,fd).
        next(response=>{//Need at least enough to delete it
          //owningmodel,owningid,foreignkeyname,foreignkeyvalue,uploadmodel
          //tooltip,saveurl,fetchurl,deleteurl,mediatype : unchanged
          var data = response.data;
          this.id = data.id;
          this.url = data.url;
          this.initData();
        }).catch(error=>{console.log("Error uploading File:",error.response);});
      },
    */

    deleteupload: function() {

      var deldata = {
        model:this.uploadmodel,
        id:this.id,
      };
      axiospost(this.deleteurl,deldata).
        then(response=>{ var data = response.data;
          this.url =  this.xparams.url ||  "/mixed/img/generic-avatar-1.png" ,
          this.id = null;
        }).
        catch(error=>{console.error("Couldn't delete "+
          this.uploadmodel+' id '+this.id,error.response);});
    },
    initData: function() {
      var fetchdata ={
        model:this.uploadmodel,
        id:this.id,
        key:'url'
      };
      axios.post(this.fetchurl,fetchdata).
        then(response=>{
          console.log("The Fetch Response was",response);
          this.url = response.data.url;
          this.id = response.data.id;
        }).
          catch(error=>{console.error("Failure fetching:",error.response);});

      }
    },
    mounted: function() {
      console.log("Mounted xParams:",this.xparams);
      this.initData();
    },
}
<style>

/*
  <div class='pk-dragdrop-show'>
  	<div class="edit-img frame drop display-inline align-center" :class="param.frameclass"
         <div class="del-x actionable  svg-inline--fa fa-window-close fa-w-16 fa-5x    " @click="delete">X</div>
         @dragover.prevent @drop="onDrop" data-tootik="tooltip">
	        <img class='img-icon' :class="params.imgclass" :src="href" @change="onChange"
          />
  </div>

*/
div.edit-img {
  position: relative;
  width: 300px;
  height: auto;
  border: solid #999 1px;
  padding: 2px;
  border-radius: 5px;
  margin: 3px;
}

.del-x {
 position: absolute;
 top: 0;
 right: 0;
 width: 15px;
 color: red;
 background-color: white;
 font-weight: 600;
 height: 15px;

}


</style>