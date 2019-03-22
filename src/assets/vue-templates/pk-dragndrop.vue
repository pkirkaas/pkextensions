<!-- Copyright (C) 2018 by Paul Kirkaas - All Rights Reserved -->

<template>
  <div class='pk-dragndrop-container'>
   	<div class="helper"></div>
  	<div class="drop display-inline align-center" @dragover.prevent @drop="onDrop">
    <div class="helper"></div>
	<label v-if="!image" class="btn display-inline">
	        SELECT OR DROP AN IMAGE
	        <input type="file" name="image" @change="onChange">
      	</label>
      <div class="hidden display-inline align-center" v-else v-bind:class="{ 'image': true }">
        <img :src="image" alt="" class="img" />
        <br>
        <h3>What's the photo about?</h3>
        <textarea placeholder="Description" class="text-desc" v-model="desc"></textarea>
        <br>
        <button class="btn" @click="removeFile">REMOVE</button>
        <button v-show="image" class="btn" @click="saveFile">Save</button>
      </div>
    </label>
  </div>
  </div>
</template>

<script>
//var app = new Vue({
export default {
    name: 'pk-dragndrop',
    data() {
      return {
      image: '',
      file: null,
      desc: '',
      imgblob: null,
      action: this.params.action || 'typedprofileupload',
      url: this.params.url || '/ajax/upload',
      };
    },
    props: ['params'],
    mounted: function() {
      //console.log("dnd.vue Mounted, Params:",this.params, "This.url:", this.url);
    },

    methods: {
      onDrop: function(e) {
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
        //console.log("In Save, FD:", fd);
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
      /*
      saveFileOld() {
        //console.log("File: ", this.file, "Image: ", this.image, "Desc:", this.desc);
        var fd = new FormData();
        fd.append('desc',this.desc);
        fd.append('file',this.file,this.file.name);
        fd.append('params',this.params);
        var me = this;
        console.log("FD:", fd);
        axios.post('/ajax/upload',fd).then( response=> {
          console.log("Response:",response);
                  me.removeFile(); });
      },
      */
      removeFile() {
        this.image = '';
      }
    }
  }
</script>
  

<style>
  * {
  font-family: 'Arial';
  font-size: 12px;
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

/*
html, body {
	height: 100%;
  text-align: center;
}
*/

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

img.img {
  border: 1px solid #f6f6f6;
  display: inline-block;
  max-width: 100px;
  max-height: 100px;
  /*
  height: auto;
  max-height: 80%;
  max-width: 80%;
  width: auto;
  */
}
div.pk-dragndrop-container {
  position: relative;
  margin-left: auto;
  margin-right: auto;
  width: 30em;
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

.drop {
  background-color: #f2f2f2;
  border: 4px dashed #ccc;
  background-color: #f6f6f6;
  border-radius: 2px;
  max-height: 400px;
  max-width: 600px;
  width: 100%;
}
</style>

