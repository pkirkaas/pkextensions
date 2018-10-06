<!-- For single images - saves instantly, dropping another replaces, & little red delete x -->
<template>
  <div class='pk-dragdrop-show'>
  
  	<div class="edit-img frame drop display-inline align-center" 
         @dragover.prevent @drop="onDrop" :data-tootik="tooltip">
         <div class="del-x actionable  svg-inline--fa fa-window-close fa-w-16 fa-5x    "
              @click="deleteupload()">X</div>
	        <img class='img-icon' :class="imgclass" :src="url" />
  </div>
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
export default {
  name: 'pk-dragdropshow',
  props: ['xparams'],
  data: function() {

     // console.log("Data xParams:",this.xparams);
    return {
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
  computed: {
  },
  methods: {
    onDrop: function(e) { //Upload Immediately
      e.stopPropagation();
      e.preventDefault();
      var files = e.dataTransfer.files;
      var file =files[0];
      var reader = new FileReader();
      reader.onload = function(e) {
          this.image = e.target.result;
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