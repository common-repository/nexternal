<?php

// NPCSS - original CSS for product display, saved to site options
$NPCSS = <<<NPCSS
/* display container */
.np-container {
  display:block;		/* use left float for old browsers */
  display:flex;			/* use flex for row alignment in CSS3 browsers */
  flex-direction:row;
  flex-wrap:wrap;
  align-items:flex-start;
  position:relative;
  box-sizing:border-box;
}

/* product container */
.np-product {
  padding:3%;
}
.np-container>.np-product {
  width:25%;			/* 20% means 5 per row, note the changes below for responsive widths */
  float:left;
}
/* responsive widths for product ele */
@media only screen and (max-width: 1024px) { .np-container>.np-product { width:33.3%; } }
@media only screen and (max-width: 768px) { .np-container>.np-product { width:50%; } }
@media only screen and (max-width: 480px) { .np-container>.np-product { width:100%; } }

/* product image */
.np-product .np-image-outer {
  position:relative;
  padding-top:100%;		/* force outer container to square ratio */
}
.np-product .np-image-inner {
  position:absolute;
  top:0; left:0; right:0; bottom:0;	/* force inner container to full size of square */
  display: flex;
  justify-content: center;
  align-items: center;
}
.np-product .np-image-inner img {
  max-width:100%;
  max-height:100%;
  margin:auto;
  display:block;
}

/* product title */
.np-product .np-title {
  text-align:center;
  font-size:90%;
  padding:5px;
}
.np-product .np-title a {
  text-decoration:none;
}

/* product description */
.np-product .np-description {
  font-size:100%;
}

/* product price */
.np-product .np-price-container {
  font-size:120%;
  text-align:center;
}
/* product original price -- displayed if product contains both price and discount price */
.np-product .np-price-container .np-orig-price {
  font-weight:normal;
  text-decoration: line-through;
  color:#777777;
}
/* product final price -- displays price by default or discount price if it exists */
.np-product .np-price-container .np-final-price {
  font-weight:bold;
  color:inherit;
}

/* product ratings -- number of stars based on width of element generated by system */
.np-product .np-ratings-container {
}
.np-product .np-ratings-container .np-ratings {
    width: 100px;
    height: 20px;
    background: url(/wp-content/plugins/nexternal/star.jpg) left repeat-x;
    margin: auto;
}

.np-product.on-sale:after {
    content:'Sale!';
    position: absolute;
    left: 0;
    top: 20px;
    z-index: 9;
    text-transform: uppercase;
    pointer-events: none;
    opacity: .95;
    width: 55px;
        height: 55px;
    border-radius: 999px;
    background-color: #d26e4b;
    padding-top:17px;
    text-align: center;
    font-size: 16px;
    line-height: 16px;
    color: #ffffff;
    font-weight: bold;
}

.np-product .np-addtocart a {
    border-radius: 15px;
    white-space: nowrap;
    padding: 3px 20px;
    margin-top: 3px;
    background: #d4d4d4;
}

.np-product .np-addtocart a:hover {
    background: #cccccc;
}



NPCSS;


// NPHTML - original HTML for product display, saved to site options
$NPHTML = <<<NPHTML
<div class="np-product [[PCLASS]]">
  <div class="np-image-outer">
    <div class="np-image-inner">
      [[IMG]]
    </div>
  </div>
  <div class="np-title">
    [[TITLE]]
  </div>
  <div class="np-description">
    [[DESCRIPTION]]
  </div>
  <div class="np-price-container">
    <span class="np-orig-price">[[OPRICE]]</span>
    <span class="np-final-price">[[FPRICE]]</span>
  </div>
  <div class="np-ratings-container">
    [[RATING]]
  </div>
  <div class="np-addtocart">
    [[ADDTOCART]]
  </div>
</div>
NPHTML;
