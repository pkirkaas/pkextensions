13-May-17
Sites styled AFTER now use this "assets/scss" folder 
Sites styled BEFORE use the legacy "assets/css" folder

//// Dimensions to consider : In portait, keep max width of item 320px
//// If landscape, 468px

Some useful classes:
pre-wrap

From mk-spacing:
Font (N=-50 -> 50)
  Size: fs-N, f-sN, large, x-large, xx-large 
  Weight: fwN (N = 1 -> 9)
  Families: oswald, lato, roboto, open-sans, montserrat, raleway, droid-sans, s-sans-pro

Dimensions: (Where N is 1/3rem; max 90N/30rem) (For some reason I forced -N)
  h-N, hN, heightN, height-N, minh-N, min-h-N, min-heightN, maxhN, etc
  widthN, wN, minwN, minw-N, maxwN, maxw-N, min-widthN

  wNpc, widthNpc, hNpc, heightNpc - where N 1-100%

  NOW! Just simple preset widths in em:
  w10em, w20rem - {width: 10em/10rem;}, etc.


Better Dimensions:
w1em -> w60em, and rem & mxw (max-width) mnw, h same

=========
Combining classes for wrappers,label,content:
content can grow - unpredicable
content classes - pk-val, pk-inp
#Label Above Content:
wrapcls: v-flex  - set width
lblcls: block fg0 full-width
cntcls: block fg1 full-width and grow vertically


#Label Before Content:
wrapcls: inline-flex - set width
lblcls: inline-flex fg0 set width 
cntcls: inline-flex fg1 grow to width


min-line-height - empty div minimum 1 line height

(x = t (top), b (bottom), l (left), r (right), v (vertical), h (horizontal)
Padding:
  (rem) p-rN, pad-rN, p-lN p-rN p-tN p-bN p-vN, p-hN, pvN, pxN
  (em) pe-xN

Margins: (x=v vertical, h horizontal)
  (rem) mN, mxN - positive; mx-N - negative
  (em) mexN (positive);   mex-N (negative)
  mxa : auto

Colors:
  RGB = 0,2,4,6,8,a,c,e,f
  Background: bg-RGB, font: c-RGB, Border: bc-RGB

Buttons:
  site-button, tiny-bty, site-btn

Inner border: inner-border
Box Shadows: big-box-shadow, box-shadow, box-shadow-2
Positioning:
  Text Align: tac, tal, tar
  Centering (Containing el must be relative) h-center, v-center, center
  Flex Centering: fcenter, vfcenter
  absolute, relative, fixed

Boxes & Borders: 
Set the basic 'card' width to 320px, as advised. So I can apportion the box, divid $min-width into 6 dimensions:
.mw66 = $min-width: 320px !default;
.mw56 = $mw56: 5*$min-width/6;
.mw46 = $mw46: 4*$min-width/6;
.mw36 = $mw36: 3*$min-width/6;
.mw26 = $mw26: 2*$min-width/6;
.mw16 = $mw16: $min-width/6;

To fit div to content!
hft - height fit content
wft - width fit content.

Boxes:
.basic-box,bb - just  m66 width
.bb-fc @extend .ms66; height: fit-content;
.bbborder { @extend .mw66; @extend .border-rad;
.bbfcborder { @extend .bb-fc; @extend .border-rad;
.flex-row { flex-wrap: wrap; }
Tables: 
  No Borders: table.no-borders
  Collapsed: table.pk-tbl

Zooms: z1, z2, etc increase zoom by 11, 12, etc
   z-1, z-2 to 9, 8

Headings:
  template-heading
  page-template page-title, page-template page-titleinverse
  page-template page-subtitle, page-template page-subtitleinverse
  'sh -> "site heading' r = red, b=blue, i=inverse, n=size
  shN, shrN, shbN, shibN, shirN (N = -5 -> 20)

Bunch of Avatar classes - in 'sitestyles'

RESPONSIVE (for each bp):
d-block-below-#{$bp} - display block below BP

d-block-within-below-#{$bp} > div 
d-block-within-below-#{$bp} 
  //Better - put on the wrapping flex container, applies all divs within

CAN COMBINE IN A ROW CONTAINING FIXED WIDTH LABEL & stretching inputs:
<div class="h-flex d-block-within-below-md">
  <div class="fi-fixed w60">...</div>
  <div class="fi-resize">...</div>
  <div class="fi-resize">...</div>
</div>
(See below for paired label/data/inputs, & sets)

d-none-below-#{$bp} //Hide below


Flexing:
v-flex - flex, direction column
h-flex - flex, normal, row
flex-wrap - wraps contained flex items
flex-center - tries to center everything
flex-between: Evenly spaces content

fi-XXX are flex-item classes
fi-fixed: grow, shrink 0. Set a width as well.
fi-resize: grow, shrink 1
fg,fg1,fgN - flex-grow:1 (N)
fg0 - flex-grow:0
fs,fs1 - flex-shrink: 1
fs0 - flex-shrink:0
fw - flex-wrap: wrap


Fonts:
oswald lato roboto open-sans montserrat raleway 
droid-sans s-sans-pro 


Input sets (with or without Vue) - ALL pairs in the set 50 em & all labels 20:
<div class="pk-set lw20 sw50">
  <div class="pk-pair">
    <div class="pkp-lbl">Name:</div>
    <div class=pkp-data pk-inp"><input type="text" name=.../></div>
  </div>
  <div class="pk-pair">
    <div class="pkp-lbl">Email:</div>
    <div class=pkp-data pk-inp"><input type="text" name=.../></div>
  </div>
</div>

============   @media print


@media print {
  .print-button {
    display: none ! important;
  }

  .no-print {
    display: none ! important;
  }

  .print-black {
    color: black ! important;
    text-decoration: none ! important;
    }
    .break-after {
      page-break-after: always;
    }
    .break-before {
      page-break-before: always;
    }
}

@media screen {
  .print-only, .no-screen {
    display: none;
  }
}

