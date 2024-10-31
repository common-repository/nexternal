<?php

/*
+----------------------------------------------------------------+
+	nexternalPlugin-tinymce V1.60
+	based on code by Deanna Schneider
+   required for nexternalPlugin and WordPress 2.5
+----------------------------------------------------------------+
*/

include_once ('../../../../wp-load.php');
include_once('../lib/nexternal-api.php');

global $wpdb;

// check for rights
if ( !is_user_logged_in() || !current_user_can('edit_posts') )
	wp_die(__("You are not allowed to be here"));

$errorMessage = '';

// load variables from data to display in HTML form
$data = get_option('nexternal');
$defaultGridSizeRows = $data['defaultGridSizeRows'];
$defaultGridSizeColumns = $data['defaultGridSizeColumns'];
$defaultProductsInView = $data['defaultProductsInView'];
$ctype = '';
$uniqueID = '';
$carouselExtras = '';

if(isset($_GET['mode']) && $_GET['mode'] == 'edit') {
$displayProductRatingChecked = nexternal_convertDataToChecked($_GET['displayProductRating']);
$displayProductPriceChecked = nexternal_convertDataToChecked($_GET['displayProductPrice']);
$displayProductOriginalPriceChecked = nexternal_convertDataToChecked($_GET['displayProductOriginalPrice']);
$displayProductImageChecked = nexternal_convertDataToChecked($_GET['displayProductImage']);
$displayProductNameChecked = nexternal_convertDataToChecked($_GET['displayProductName']);
$displayProductShortDescriptionChecked = nexternal_convertDataToChecked($_GET['displayProductShortDescription']);
$displayProductShortAddToCartChecked = nexternal_convertDataToChecked($_GET['displayProductAddToCart']);
    $defaultGridSizeRows = $_GET['gridSizeRows']?$_GET['gridSizeRows']:$data['defaultGridSizeRows'];
    $defaultGridSizeColumns = $_GET['gridSizeColumns']?$_GET['gridSizeColumns']:$data['defaultGridSizeColumns'];


$defaultCarouselTypeHorizontalSelected = ($_GET['carousel'] == 'horizontal') ? ("SELECTED"):('');
$defaultCarouselTypeVerticalSelected = ($_GET['carousel'] == 'vertical') ? ("SELECTED"):('');
$defaultCarouselTypeNoneSelected = ($_GET['carousel'] == 'none') ? ("SELECTED"):('');
$defaultCarouselTypeSingleSelected = ($_GET['carousel'] == 'single') ? ("SELECTED"):('');
$ctype = $_GET['carousel'];
    if($ctype == 'owl' || $ctype == 'plain') {
        $shouldMethod = 'N';
    } else {
        $shouldMethod = 'O';
    }
$uniqueID = $_GET['id'];

    foreach($_GET as $k=>$v) {
        if(preg_match('/^owl_/',$k)) {
            $k = preg_replace('/^owl_/','',$k);
            $carouselExtras .= $k.':'.$v."\n";
        }
    }


} else {
$displayProductRatingChecked = nexternal_convertDataToChecked($data['defaultDisplayProductRating']);
$displayProductPriceChecked = nexternal_convertDataToChecked($data['defaultDisplayProductPrice']);
$displayProductOriginalPriceChecked = nexternal_convertDataToChecked($data['defaultDisplayProductOriginalPrice']);
$displayProductImageChecked = nexternal_convertDataToChecked($data['defaultDisplayProductImage']);
$displayProductNameChecked = nexternal_convertDataToChecked($data['defaultDisplayProductName']);
$displayProductShortDescriptionChecked = nexternal_convertDataToChecked($data['defaultDisplayProductShortDescription']);
$displayProductShortAddToCartChecked = nexternal_convertDataToChecked($data['defaultDisplayProductAddToCart']);

$defaultCarouselTypeHorizontalSelected = ($data['defaultCarouselType'] == 'horizontal') ? ("SELECTED"):('');
$defaultCarouselTypeVerticalSelected = ($data['defaultCarouselType'] == 'vertical') ? ("SELECTED"):('');
$defaultCarouselTypeNoneSelected = ($data['defaultCarouselType'] == 'none') ? ("SELECTED"):('');
$defaultCarouselTypeSingleSelected = ($data['defaultCarouselType'] == 'single') ? ("SELECTED"):('');
$ctype = $data['defaultCarouselType'];
    $shouldMethod = 'N';
$uniqueID = 'productList-'.dechex(rand(0, hexdec('ffff')));
}
// these represent whether or not to initially display the specific option divs
$carouselOptionsDisplay = 'none';
$gridOptionsDisplay = 'none';
$carouselSingleDisplay = 'none';
if ($ctype == 'horizontal' or $ctype == 'vertical') $carouselOptionsDisplay = 'block';
if ($ctype == 'none') $gridOptionsDisplay = 'block';
if ($ctype == 'single') $carouselSingleDisplay = 'block';

// load products for product drop down
$productOptions = '';
$productIDs = '';
if ($data['userName'] == '' || $data['accountName'] == '') {
    wp_die(__("Unable to load product list, please make sure you<br>are linked to an account in the Nexternal Plugin Configuration. (1)"));
} else {
    $url = 'https://www.nexternal.com/shared/xml/productquery.rest';
    $xml = generateProductQueryRequest($data['accountName'], $data['userName'], $data['pw']); 
    //error_log('XML: '.$xml);
    $xmlResponse = curl_post($url, $xml);
    //error_log('RESP: '.$xmlResponse);
    if ($xmlResponse == '') wp_die(__("Unable to load product list, please make sure you<br>are linked to an account in the Nexternal Plugin Configuration. (2)"));
    $xmlData = new SimpleXMLElement($xmlResponse);
    foreach ($xmlData->CurrentStatus->children() as $node) {
            $attributes = $node->attributes();
            $productName = addslashes($attributes["Name"]);
            $productID = $attributes["No"];
            $productSKU = $attributes["SKU"];
            $productOptions .= "\"$productName\",";
            $productIDs .= "productIDs['$productName'] = '$productID';\n";
            $productSKUs .= "productSKUs['$productName'] = '$productSKU';\n";
    }
    $productOptions = substr($productOptions, 0, -1); // remove the last comma from product
    if ($productIDs == '') wp_die(__("There are no products in the Nexternal store: <b>" . $data['accountName'] . "</b><br>You will be unable to add a Product List to your post."));
}

// load available styles
$styleOptions = "";
$path = ABSPATH.'wp-content/plugins/nexternal/styles';
if ($dh = opendir($path)) {
    while (($file = readdir($dh)) !== false) {
        if (nexternal_endsWith($file, '.css', false)) {
            if ($data['defaultStyle'] == $file) $selected = 'SELECTED';
            else $selected = '';
            $fileData = get_file_data($path . '/' . $file, array( 'Name' => 'Style Name'));
            $styleName = $fileData['Name'];
            $styleOptions .= "<option value='$file' $selected>" . $fileData['Name'] . " ($file)</option>";
        }
    }
    closedir($dh);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Nexternal Product List Options</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<script language="javascript" type="text/javascript" src="<?php echo str_replace('http:','',get_option('siteurl')) ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo str_replace('http:','',get_option('siteurl')) ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo str_replace('http:','',get_option('siteurl')) ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>


	<link rel="stylesheet" href="//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css">
        <script src="//code.jquery.com/jquery-1.9.1.js" type="text/javascript"></script>
        <script src="//code.jquery.com/ui/1.10.3/jquery-ui.js" type="text/javascript"></script>

    <script type="text/javascript">
        //<![CDATA[

        function setMethod(mtype) {
            $('.forMethod_N').hide();
            $('.forMethod_O').hide();
            $('.forMethod_'+mtype).show();
            $('#ss_N').prop('checked',false);
            $('#ss_O').prop('checked',false);
            $('#ss_'+mtype).prop('checked',true);
            updateEvent($('#carousel').val());
        }

        $(function() {
            var availableTags = [
                <?php echo $productOptions; ?>
            ];
            $( "#product" ).autocomplete({
                source: availableTags
            });
            setMethod('<?php echo $shouldMethod; ?>');
            //$('#productIDs').css('width','100%').resizable({minWidth:300,minHeight:150});

            $('#itemSort').sortable({
                update: function() { // don't need params (event, ui)
                    $('#productIDs').val($('#itemSort').sortable('toArray',{'attribute':'data-id'}));
                }
            }).sortable('option','update')(null,null);

            $('.nextFieldContainer').sortable({
                cursor: 'move',
                update: function() { // don't need params (event, ui)
                    var nfc = $('.nextFieldContainer');
                    console.log(nfc.sortable('toArray',{'attribute':'data-fname'}).join(','));
                    $('#fieldOrder').val(nfc.sortable('toArray',{'attribute':'data-fname'}).join(','));
                }
            });


        });

    // generate SKUs javascript, since they need to be converted from productName to productSku via the ProductSku javascript scoped array
    var productIDs = [];
    <?php echo $productIDs; ?>
    var productSKUs = [];
    <?php echo $productSKUs; ?>
    var preloadIDs = '<?php echo $_GET['productIDs']; ?>';
    var preloadSKUs = '<?php echo $_GET['productSKUs']; ?>';

	function init() {
		//tinyMCEPopup.resizeToInnerSize();
	}
	
	
	jQuery(function() {
		//tinyMCEPopup.resizeToInnerSize();
		if(preloadIDs) {
		  var ids = preloadIDs.split(',');
		  preAddProduct(ids);
		}
        if(preloadSKUs) {
            var skus = preloadSKUs.split(',');
            preAddProductSKU(skus);
        }
	});

    function attributeFor(identifier) {
        var value = document.getElementById(identifier).value;
        if (value != '') return " " + identifier + "=\"" + value + "\"";
        return "";
    }

    function attributeForCheckbox(identifier) {
        var value = document.getElementById(identifier).checked;
        return " " + identifier + "=\"" + value + "\"";
    }

	function insertShortcode() {

        if (document.getElementById('product').value != '') {
            alert("You entered a product but did not select 'Add'");
            document.getElementById('product').style.background = '#FF9996';
            return false;
        }
        if (document.getElementById('id').value == '') {
            alert("You must enter a unique identifier");
            document.getElementById('id').style.background = '#FF9996';
            return false;
        }
        if (document.getElementById('productIDs').value == '') {
            alert("You must enter at least 1 product.");
            return false;
        }

        var tagtext = "[nexternal ";

        tagtext += attributeFor('id');
        tagtext += attributeFor('carousel');

        if (document.getElementById('carousel').value == 'horizontal' || document.getElementById('carousel').value == 'vertical') {
            tagtext += attributeFor('productsInView');
        } else if (document.getElementById('carousel').value == 'none') {
            tagtext += attributeFor('gridSizeRows');
            tagtext += attributeFor('gridSizeColumns');
        }

        tagtext += attributeForCheckbox('displayProductRating');
        tagtext += attributeForCheckbox('displayProductPrice');
        tagtext += attributeForCheckbox('displayProductOriginalPrice');
        tagtext += attributeForCheckbox('displayProductImage');
        tagtext += attributeForCheckbox('displayProductName');
        tagtext += attributeForCheckbox('displayProductShortDescription');
        tagtext += attributeForCheckbox('displayProductAddToCart');
        tagtext += attributeFor('style');
        tagtext += attributeFor('fieldOrder');

        var ce = $('textarea[name=carouselExtras]').val().split("\n");
        for(var i=0;i<ce.length;i++) {
            var l = ce[i].split(':');
            if(ce[i] && l[1]) {
                //if(l[1] != 'true' && l[1] != 'false' && parseFloat(l[1]) !== l[1]) {
                //    l[1] = '"'+l[1]+'"';
                //}
                tagtext += ' owl_'+l[0].trim()+'="'+l[1].trim()+'"';

            }
        }

        tagtext += ' productIDs = "';
        
        tagtext += $('#productIDs').val();

        tagtext += '"';

        tagtext += "]";

		if(window.tinyMCE) {
			    /* get the TinyMCE version to account for API diffs */
			    var tmce_ver=window.tinyMCE.majorVersion;

			    if (tmce_ver>="4") {
				window.tinyMCE.execCommand('mceInsertContent', false, tagtext);
			    } else {
				window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
			    }			
			//Peforms a clean up of the current editor HTML.
			//tinyMCEPopup.editor.execCommand('mceCleanup');
			//Repaints the editor. Sometimes the browser has graphic glitches.
			tinyMCEPopup.editor.execCommand('mceRepaint');
			tinyMCEPopup.close();
		}
	}

    function updateEvent(eventValue) {
        if (eventValue == 'plain') {
            document.getElementById("gridOptions").style.display = 'none';
            document.getElementById("carouselOptions").style.display = 'none';
            document.getElementById("singleOptions").style.display = 'none';
        }
        if (eventValue == 'owl') {
            document.getElementById("gridOptions").style.display = 'none';
            document.getElementById("carouselOptions").style.display = 'none';
            document.getElementById("singleOptions").style.display = 'none';
        }
        if (eventValue == 'none') {
            document.getElementById("gridOptions").style.display = 'block';
            document.getElementById("carouselOptions").style.display = 'none';
            document.getElementById("singleOptions").style.display = 'none';
        }
        if (eventValue == 'horizontal' || eventValue == 'vertical') {
            document.getElementById("gridOptions").style.display = 'none';
            document.getElementById("carouselOptions").style.display = 'block';
            document.getElementById("singleOptions").style.display = 'none';
        }
        if (eventValue == 'single') {
            document.getElementById("gridOptions").style.display = 'none';
            document.getElementById("carouselOptions").style.display = 'none';
            document.getElementById("singleOptions").style.display = 'block';
        }
    }

    function addProduct() {
        var productName = document.getElementById('product').value;

        var sku = productIDs[productName];
        if (!sku) { alert("The product " + productName + " is invalid."); return false; }
        
	var mk = $('<div class="product-marker" data-id="'+productIDs[productName]+'">'+productName+'<a href="#">X</a></div>');
	mk.find('a').click(function() { $(this).parent().remove(); $('#itemSort').sortable('option','update')(null,null); });
	$('#itemSort').append(mk);


	/*
        var newOption = document.createElement("option");
        newOption.text = productName;
        newOption.value = productName;

        document.getElementById('productIDs').options.add(newOption);
        document.getElementById('productIDs').style.background = 'white';
     */
        $('#productIDs').val($('#itemSort').sortable('toArray',{'attribute':'data-id'}));
        document.getElementById('product').value = '';
        document.getElementById('product').focus();
        document.getElementById('product').style.background = 'white';
        return false;
    }
    function preAddProduct(ids) {
        for(var id in ids) {
            if(ids.hasOwnProperty(id)) {
                //console.log('trying for '+ids[id]);
                for(var pn in productIDs) {
                    if(productIDs.hasOwnProperty(pn) && productIDs[pn] == ids[id]) {
                        //console.log('found '+pn+' -- '+ids[id]+' / '+productIDs[pn]);
                        var mk = $('<div class="product-marker" data-id="'+ids[id]+'">'+pn+'<a href="#">X</a></div>');
                        mk.find('a').click(function() { $(this).parent().remove(); $('#itemSort').sortable('option','update')(null,null); });
                        $('#itemSort').append(mk);
                        break;
                    }
                }
            }
        }
        $('#productIDs').val($('#itemSort').sortable('toArray',{'attribute':'data-id'}));
        return false;
    }
    function preAddProductSKU(skus) {
        for(var sku in skus) {
            if(skus.hasOwnProperty(sku)) {
                //console.log('trying for '+ids[id]);
                for(var pn in productSKUs) {
                    if(productSKUs.hasOwnProperty(pn) && productSKUs[pn] == skus[sku]) {
                        //console.log('found '+pn+' -- '+ids[id]+' / '+productIDs[pn]);
                        var mk = $('<div class="product-marker" data-id="'+skus[sku]+'">'+pn+'<a href="#">X</a></div>');
                        mk.find('a').click(function() { $(this).parent().remove(); $('#itemSort').sortable('option','update')(null,null); });
                        $('#itemSort').append(mk);
                        break;
                    }
                }
            }
        }
        $('#productIDs').val($('#itemSort').sortable('toArray',{'attribute':'data-id'}));
        return false;
    }

    //function removeProduct() {
    //    var selected = document.getElementById('productIDs');
    //    for(var i = selected.options.length - 1; i >= 0; i--)
    //        if(selected.options[i].selected) selected.remove(i);
    //}

        //]]>
	</script>
	<style type="text/css">
	
	.ui-resizable-se { bottom: 18px; right: 25px; }
	
	.product-marker { padding:2px;font-size:80%;border:1px solid #999999;background:#efefef;border-radius:5px;margin:5px; }
	.product-marker a { float:right;font-size:110%;font-weight:bold;text-decoration:none;color:#999999; }
	.product-marker:hover a { color:red; }
	
	      .nextFieldContainer { max-width:400px; padding:10px; }
	      .nextFieldListing { padding:3px;margin:3px;border-radius:5px;border:1px solid #cccccc;background:#efefef; }
	    </style>

	<base target="_self" />
</head>

<body id="link" onload="tinyMCEPopup.executeOnLoad('init();'); document.body.style.display=''; document.getElementById('product').focus();" style="display: none">
  <form name="nexternalPlugin" action="#">
    <div style="padding:0 1%;">
      <div id="options_panel">
        <h4 style="color: black">Products in Carousel/Grid:</h4>

	<p><input id="product" type="search" name="product" placeholder="start typing to search..." style="width: 88%;margin-right:1%;font-size:100%;padding:3px;box-sizing:border-box;" /> <input type="submit" style="width:10%;box-sizing:border-box;" id="insert" onclick="return addProduct();" value="Add" /></p>
	<div style="max-width:600px;margin:auto;">
	<input type="hidden" name="productIDs" id="productIDs" value=""/>
	<div style="width:100%;" id="itemSort">
	  
	</div>
	</div>
	<hr style="color: #919B9C;">
	<h4 style="color: black">Display Options</h4>
	<p><input name="set_style" id="ss_N" checked onclick="var uc=$('#use_carousel');setMethod('N');uc.click();uc.click();" type="radio" value="N" /><label for="ss_N">New Layout</label> /
        <input name="set_style" id="ss_O" onclick="setMethod('O');" type="radio" value="O" /><label for="ss_O">Old Style</label></p>
	<p class="forMethod_N"><label for="use_carousel">Use Carousel Display?</label>
	  <input type="checkbox" id="use_carousel"<?php echo $ctype=='owl'?' checked':'' ?> name="use_carousel" value="Y" onclick="if(this.checked) { $('#carousel').val('owl'); } else { $('#carousel').val('plain'); }"/>
	</p>
	<p class="forMethod_O"><label for="carousel">Display Type</label>
	  <select id="carousel" name="carousel" style="width: 150px;" onChange="updateEvent(this[this.selectedIndex].value);">
	    <option<?php echo $ctype=='plain'?' SELECTED':''; ?> value="plain">Plain (New Grid)</option>
	    <option<?php echo $ctype=='owl'?' SELECTED':''; ?> value="owl">Carousel (New Carousel)</option>
	    <option<?php echo $ctype=='none'?' SELECTED':''; ?> value="none">Grid (Old Style)</option>
	    <option<?php echo $ctype=='single'?' SELECTED':''; ?> value="single">Single Product (Old Style)</option>
	    <option<?php echo $ctype=='horizontal'?' SELECTED':''; ?> value="horizontal">Horizontal Carousel (Old Style)</option>
	    <option<?php echo $ctype=='vertical'?' SELECTED':''; ?> value="vertical">Vertical Carousel (Old Style)</option>
	  </select>
	</p>
        <p class="forMethod_O"><label for="style">Select a Style:</label>
          <select id='style' name='style'>
            <?php echo $styleOptions; ?>
          </select>
        </p>
	<div class="forMethod_O" id="gridOptions" style="display: <?php echo $gridOptionsDisplay;?>;">
	  <label for="gridSizeRows"><label for="gridSizeColumns">Grid Size</label> <label for="gridSizeRows">(Width, Height):</label> (
	  <input type="text" id="gridSizeColumns" name="gridSizeColumns" style="width: 25px" value="<?php echo $defaultGridSizeColumns;?>">,
	  <input type="text" id="gridSizeRows" name="gridSizeRows" style="width: 25px" value="<?php echo $defaultGridSizeRows;?>"> )
	</div>
	<div class="forMethod_O" id="carouselOptions" style="display: <?php echo $carouselOptionsDisplay?>;">
	  <label for="productsInView">Number of Visible Products in Carousel:</label> <input type="text" id="productsInView" name="productsInView" style="width: 25px" value="<?php echo $defaultProductsInView;?>">
	</div>
	<div class="forMethod_O" id="singleOptions" style="display: <?php echo $carouselSingleDisplay?>;">
	  <strong>Note:</strong> The first product in the Product List (above) will be displayed.
	</div>
        <div>
	  <div>Product Info:</div>
	  


         <?php
         if(!isset($data['defaultFieldOrder'])) {
             $data['defaultFieldOrder'] = 'image,name,shortDescription,price,originalPrice,rating,addToCart';
         }
if(isset($_GET['mode']) && $_GET['mode'] == 'edit') {
    if (!isset($_GET['fieldOrder'])) {
        $_GET['fieldOrder'] = $data['defaultFieldOrder'];
      }
	echo  '<input type="hidden" id="fieldOrder" name="fieldOrder" value="'.$_GET['fieldOrder'].'"><div class="nextFieldContainer">';
	$flds = explode(',',$_GET['fieldOrder']);
	foreach($flds as $fld) {
	    
	    $ischecked = nexternal_convertDataToChecked($_GET['displayProduct'.ucfirst($fld)]);
	    $fname = ucfirst(preg_replace('/([A-Z])/',' $1',$fld));
	    $flbl = ucfirst($fld);
	    ?>

        <p class="nextFieldListing" data-fname="<?php echo $fld; ?>">
          <input type="checkbox" id="displayProduct<?php echo $flbl; ?>" name="displayProduct<?php echo $flbl; ?>" <?php echo $ischecked; ?>> <label for="displayProduct<?php echo $flbl; ?>"><strong><?php echo $fname; ?></strong></label>
        </p>
		<?php
	}
} else {
	echo  '<input type="hidden" id="fieldOrder" name="fieldOrder" value="'.$data['defaultFieldOrder'].'"><div class="nextFieldContainer">';
	$flds = explode(',',$data['defaultFieldOrder']);
	foreach($flds as $fld) {
	    
	    $ischecked = nexternal_convertDataToChecked($data['defaultDisplayProduct'.ucfirst($fld)]);
	    $fname = ucfirst(preg_replace('/([A-Z])/',' $1',$fld));
	    $flbl = ucfirst($fld);
	    ?>

        <p class="nextFieldListing" data-fname="$fld">
          <input type="checkbox" id="displayProduct<?php echo $flbl; ?>" name="displayProduct<?php echo $flbl; ?>" <?php echo $ischecked; ?>> <strong><?php echo $fname; ?></strong>
        </p>
		<?php
	}
}
    ?>


	  </div>
	  <br style="clear:both;"/>
	</div>
        <a href="#" onclick="$('#advOptions').toggle();return false;">advanced options</a>
        <div id="advOptions" style="display:none;">
	<p><label for="id"><strong>Unique Identifier</strong>:</label> <input id="id" name="id" value="<?php echo $uniqueID; ?>" size="15"/> <br/><span style="font-size:80%;color:#666666;">(A-Z, 0-9 and hyphens only)</p>

            <p>additional carousel properties:<br/><textarea placeholder="autoplay:true" name="carouselExtras" style="width:300px;height:100px;"><?php echo $carouselExtras; ?></textarea><br/><em>add each property on a new line, separated by a colon, as shown above</em></p>
            </div>
      </div>
      <div class="mceActionPanel" style="padding:0 1% 1% 1%;">
      <div style="float: left">
        <input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
      </div>
      <div style="float: right">
        <input type="button" id="insert" name="insert" value="Insert" onclick="insertShortcode();"  />
      </div>
      <br style="clear:both;"/>
    </div>
  </form>
</body>
</html>
