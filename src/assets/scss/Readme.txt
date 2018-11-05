13-May-17
Sites styled AFTER now use this "assets/scss" folder 
Sites styled BEFORE use the legacy "assets/css" folder

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


min-line-height - empty div minimum 1 line height

(x = t (top), b (bottom), l (left), r (right), v (vertical), h (horizontal)
Padding:
  (rem) p-rN, pad-rN, p-lN p-rN p-tN p-bN p-vN, p-hN, pvN, pxN
  (em) pe-xN

Margins:
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

Tables: 
  No Borders: tableno-borders
  Collapsed: tablepk-tbl

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
fg - flex-grow:1
fs - flex-shrink: 1


Fonts:
oswald lato roboto open-sans montserrat raleway 
droid-sans s-sans-pro 




