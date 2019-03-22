<!-- Copyright (C) 2018 by Paul Kirkaas - All Rights Reserved -->

<template>
  <div>
  <h1>And we're here!</h1>
  <div class='pk-showimage-container'>
        <img :src="imageprops.url" alt="" class="img" 
          :style="imgstyle"/>
  </div>
  </div>
</template>

<script>
//var app = new Vue({
export default {
    name: 'pk-showimage',
    //params: ajaxurl, ajaxdata,style,class
    //props: ['params'],
    props: ['ajaxparams','ajaxurl','imgstyle'],
    data: function() {
      return {
        imageprops: {
          url: null,
          //style: null,
          ////class: null,
        } };
    },
    methods: {
      initData: function() {
        console.log("ShomImg init: ajaxparams:",this.ajaxparams,"ajaxurl",this.ajaxurl);
        axios.post(this.ajaxurl,this.ajaxparams).
          then(response=>{
            this.imageprops.url = response.data.avatarUrl;
            console.log("ShowImage AJAX Success:", response,"this.imageprops.url:",this.imageprops.url);
          }).
          catch(error=>{console.log("Error fetching img URL:",error.response);});
      },
    },
    mounted: function() {
      this.initData();
      console.log("In image vue, props ajaxurl:",this.ajaxurl, 'ajaxparams',this.ajaxparams);
    },
    /*
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
        this.params.action='typedprofileupload';
        for (var key in this.params) {
          fd.append(key, this.params[key]);
        }
        fd.append('desc',this.desc);
        fd.append('file',this.file,this.file.name);
        console.log("In Save, FD:", fd);
        var me = this;
        axios.post(this.url,fd).then( response=> {
          console.log("Response:",response);
                  me.removeFile(); });

      },
      saveFileOld() {
        //console.log("File: ", this.file, "Image: ", this.image, "Desc:", this.desc);
        var fd = new FormData();
        fd.append('desc',this.desc);
        fd.append('file',this.file,this.file.name);
        var me = this;
        console.log("FD:", fd);
        axios.post('/ajax/upload',fd).then( response=> {
          console.log("Response:",response);
                  me.removeFile(); });
      },
      removeFile() {
        this.image = '';
      }
    }
    */
  }
</script>
  

<style>

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
div.pk-showimage-container {
  position: relative;
  display: inline-block;
  margin-left: auto;
  margin-right: auto;
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

