<!-- Drop & immediately upload, in place -->
<!-- Default is images, but support PDF -->

<!-- Since I clearly forgot -- I seem to have made this specifically for 
 separate uploaded/file/media objects, NOT to be included as part of just a 
regular object, which sucks. 

Feb 2019 - So Fix

@params:
  attribute: string: name of the relation, or null, an embedded file
    if embedded, simple:
      model, id, fetchargs, deleteargs, saveargs  
        if have name, assume mapping of url to {name}_url, don't need fetch args

Embeddeds have the convention that the 
  

-->
<template>
  <div class="drop display-inline align-center" @dragover.prevent @drop="onDrop" >
      <delete-x data-tootik="Delete This?"></delete-x>
      <h2 class="border m-2 p-1 bg-aaa"  @click="fiClicked($event,'fi')">Try Clicking</h2>
      <div class=" align-center embed-wrapper "   @click="fiClicked($event,'fi')">
        <input class="abs-hidden click-target" type="file" name="doc" @change="onChange" @click="fiClicked($event,'fi')">

   <div class='embed-wrapper tac'  @clicked="fiClicked($event,'doc')" data-tootik='Click to Upload'>
      <iframe :src="url || defaulturl" @clicked="fiClicked($event,'doc')" class='doc inline doc-upload' >
      </iframe>
  </div>
  </div>
  </div>
</template>

<script>
//var app = new Vue({
require ("./drop-inplace.js");
export default {
    name: 'drop-doc-inplace',
    mixins: [window.dropInPlaceMixin],
    extraoptions: {
      defaulturl:   "/mixed/img/generic-embed-doc.html" ,
      type: 'doc',
    },
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

iframe.doc-upload {
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

.doc-wrapper {
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


