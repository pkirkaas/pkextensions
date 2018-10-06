<!-- For single images - saves instantly, dropping another replaces, & little red delete x -->
<template>
  <div class='pk-dragdrop-show'>
  	<div class="frame drop display-inline align-center" :class="param.frameclass"
         @dragover.prevent @drop="onDrop" data-tootik="tooltip">
	        <img class='img-icon' :class="params.imgclass" :src="href" @change="onChange"
          />
  </div>
</div>
</template>

<script>

  //Assumptions - it will belong to another object, and it will be an attribute of that obj
  //Required: \\App\\Models\\Owner id(of owner) foreign_key name for this (owner_id)
  //id (of this object) attribute name of this object (presumably an uploaded model
  //To be general: mediatype[default image], dimensions, styling, URL & params to 
  //retrieve it, url & params to delete

export default {
  name: 'pk-dragdrop-show',
  data: function() {
    return {
      url:  this.params.url ||  "/mixed/img/generic-avatar-1.png" ,
      tooltip:this.params.tooltip ||  "Select or Drag 'n Drop your Main Image" ,
      mediatype:this.params.mediatype || 'image',
      saveurl: this.params.saveurl || '/ajax/upload',
      fetchurl: this.params.fetchurl || '/ajax/upload',
      deleteurl: this.params.fetchurl || '/ajax/delete',
      owningmodel: this.params.owningmodel,
      owningid: this.params.owningid,
      uploadmodel: this.params.uploadmodel,
      id: this.params.id,
      foreigkeyname: this.params.foreigkeyname,
      foreigkeyvalue: this.params.foreigkeyvalue,
      attribute: this.params.attribute,
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
       }
      reader.readAsDataURL(file);
      var fd = new FormData();
      for (var key in this.params) {
        fd.append(key, this.params[key]);
      }
      fd.append('file',this.file,this.file.name);
      axios.post(this.saveurl,fd).
        next(response=>{//Need at least enough to delete it
          //owningmodel,owningid,foreignkeyname,foreignkeyvalue,uploadmodel
          //tooltip,saveurl,fetchurl,deleteurl,mediatype : unchanged
          var data = response.data;
          this.uploadedid = data.id;
          this.url = data.url;
          this.initData();
        }).catch(error=>{console.log("Error uploading File:",error.response);});
      },

    mounted: function() {this.initData();}
    initData: function() {
    }
}