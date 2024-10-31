<?php
/*
Shamelessly stolen and modified from:

Author: Ohad Raz
Author URI: http://generatewp.com
*/
class nexternal_shortcode{
	/**
	 * $shortcode_tag 
	 * holds the name of the shortcode tag
	 * @var string
	 */
	public $shortcode_tag = 'nexternal';

	/**
	 * __construct 
	 * class constructor will set the needed filter and action hooks
	 * 
	 * @param array $args 
	 */
	function __construct($args = array()){
		//add shortcode
		add_shortcode( $this->shortcode_tag, array( $this, 'shortcode_handler' ) );
		
		if ( is_admin() ){
			add_action('admin_head', array( $this, 'admin_head') );
			add_action( 'admin_enqueue_scripts', array($this , 'admin_enqueue_scripts' ) );
		}
	}

	/**
	 * shortcode_handler
	 * @param  array  $atts shortcode attributes
	 * @param  string $content shortcode content
	 * @return string
	 */
	function shortcode_handler($atts , $content = null){

		if($content) {
			error_log('nexternal shortcode passed content (should not take any content): '.$content);
		}
	
		// output
		$out = '';

		// get plugin setting data
		$data = get_option('nexternal');
		if ($data['userName'] == '') return false; // abort if there is no active user
		if (!isset($data['productData'])) $data['productData'] = array(); // get productData ready if undefined
		if (!isset($data['productDataById'])) $data['productDataById'] = array(); // get productData ready if undefined
		$NPHTML = null;
		if (!isset($data['nphtml'])) {
			include "lib/np-variables.php";
			$data['nphtml'] = $NPHTML; // use html as registered
		}
		$customLinkAttributes = $data['customLinkAttributes'];
		$randomId = "default-" . dechex(rand(0, hexdec('ff')));

		$carousel =
		$productsinview =
		$displayproductrating =
		$displayproductprice =
		$displayproductoriginalprice =
		$displayproductimage =
		$displayproductname =
		$displayproductshortdescription =
        $displayproductaddtocart =
		$displayaddtocart =
		$gridsizerows =
		$gridsizecolumns =
		$productskus =
		$productids =
		$fieldorder =
		$style =
		$id = null;

 		// Attributes
		extract( shortcode_atts(
			array(
				'carousel' => 'none',
				'productsinview' => '3',
				'displayproductrating' => 'false',
				'displayproductprice' => 'false',
				'displayproductoriginalprice' => 'false',
				'displayproductimage' => 'true',
				'displayproductname' => 'true',
				'displayproductshortdescription' => 'false',
                'displayproductaddtocart' => 'false',
				'displayaddtocart' => 'false',
				'gridsizerows' => '3',
				'gridsizecolumns' => '3',
				'productskus' => 'false',
				'productids' => 'false',
				'fieldorder' => $data['defaultFieldOrder'],
				'style' => 'none',
				'id' => $randomId
			), $atts )
		);

        $tagextras = '';
        foreach($atts as $k=>$v) {
            if(preg_match('/^owl_/',$k)) {
                $k = preg_replace('/^owl_/','',$k);
                if($v != 'true' && $v != 'false' && floatval($v) !== $v) {
                    $v = '"'.$v.'"';
                }
                $tagextras .= ' '.$k.':'.$v.','."\n";
            }

        }

		$sku = explode(",", $productskus);
		$ids = explode(",", $productids);
		$useIDs = 0;
		//error_log('useIDs FALSE ('.$useIDs.')');
		if($ids[0] && isset($ids[0]) && $ids[0] != false && $ids[0] != 'false') $useIDs = 1;
		//error_log('useIDs checked ('.$useIDs.')');
		$items = $useIDs?$ids:$sku;
		$datas = $useIDs?$data['productDataById']:$data['productData'];

		if ($style == 'none') $style = $data['defaultStyle'];

		// use 'grid' instead of 'none' for carousel for readability in CSS
		if ($carousel == 'none') $carousel = 'grid';

		// determine how many items to render
		$itemsToDisplay = $gridsizerows * $gridsizecolumns;
		if ($carousel == 'horizontal' || $carousel == 'vertical') $itemsToDisplay = count($items);
		if ($carousel == 'single') $itemsToDisplay = 1;

		// remove the .css extension from $style
		$style = substr($style, 0, strlen($style)-4);

		// display navigation button if this a carousel
		if ($carousel == 'horizontal' || $carousel == 'vertical') $out .= "<a class='nexternal-$style-$carousel-previous $id-previous'></a>";

		if ($carousel == 'owl' || $carousel == 'plain') {
			$out .= "<div id='$id' class='np-container'>";
		} else {
			$out .= "<div class='nexternal-$style-$carousel $id'><ul>";
		}

		if ($itemsToDisplay > count($items)) $itemsToDisplay = count($items);

		// check if we have a local store
		$hasProductData = post_type_exists('nexternal_product');

		// turn off for now
		$hasProductData = false;


		for ($i = 0; $i < $itemsToDisplay; $i++) {

			//error_log('Working with ('.$useIDs.'): '.$items[$i]);

			$productIdentifier = $items[$i];

			$productData = $datas[$productIdentifier];


			// if the product data is not available OR the product data has expired, reload it from nexternal
			if($hasProductData) {
				// look up product data from local store
				if($useIDs) {
					$posts = get_posts(array(
						'post_type'=>'nexternal_product',
						'meta_key'=>'productNum',
						'meta_value'=>$productIdentifier
					));
				} else {
					$posts = get_posts(array(
						'post_type'=>'nexternal_product',
						'meta_key'=>'sku',
						'meta_value'=>$productIdentifier
					));
				}
				$p = $posts[0];
				$pd = get_post_meta($p->ID,'',true);
				$productData['price'] = $pd['discountedPrice'][0]?$pd['discountedPrice'][0]:$pd['price'][0];
				$productData['originalPrice'] = $pd['price'][0];
				$productData['productSKU'] = $pd['sku'][0];
				$productData['productID'] = $pd['productNum'][0];
				$productData['name'] = $pd['name'][0];
				$productData['image'] = $pd['thumb'][0]; // optionally ->Main or ->Large
				$productData['url'] = $pd['_links_to'][0];
				$productData['directCheckout'] = $pd['directCheckout'][0];
				$productData['shortDescription'] = $pd['description'][0];

			} elseif (!$productData or $productData['expires'] < time()) { //} or !$useIDs) {
				// retreive product data
				$url = "https://www.nexternal.com/shared/xml/productquery.rest";
				$xml = $useIDs?
				generateProductQueryById($data['accountName'], $data['userName'], $data['pw'], $productIdentifier)
				:
				generateProductQuery($data['accountName'], $data['userName'], $data['pw'], $productIdentifier)
				;
				$xmlResponse = curl_post($url, $xml);
				$xmlData = new SimpleXMLElement($xmlResponse);

				// calculate average rating
				if ($xmlData->Product->ProductReviews->ProductReview) {
					$totalRating = 0;
					$reviews = $xmlData->Product->ProductReviews->ProductReview;
					foreach ($reviews as $review) $totalRating += $review->Rating;
					$productData['rating'] = $totalRating / count($reviews);
				} else {
					$productData['rating'] = '';
				}

				// calculate original and current price
				$price = floatval($xmlData->Product->Pricing->Price);
				$originalPrice = $price;
				if ($xmlData->Product->Pricing->Price['PercentDiscount']) {
					$percentDiscount = floatval($xmlData->Product->Pricing->Price['PercentDiscount']);
					//error_log('seeing percent discount as: '.$price.' * 1-'.$percentDiscount);
					$discountPrice = floatval($price * floatval(100-$percentDiscount));
					$price = round($discountPrice)/100;
				}
				if($xmlData->Product->SKU) {
					foreach ($xmlData->Product->SKU as $SKU) {
						if($SKU['SKU'] == $productIdentifier) {
							$tp = $SKU->Pricing->Price;
							if($tp) {
								$price = floatval($tp);
								$originalPrice = $price;
								$percentDiscount = floatval($SKU->Pricing->Price['PercentDiscount']);
								//error_log('seeing SKU percent discount as: '.$price.' * 1-'.$percentDiscount);
								$discountPrice = floatval($price * floatval(100-$percentDiscount));
								$price = round($discountPrice)/100;
							}
						}
					}
					if ($price == 0) {
						foreach ($xmlData->Product->SKU as $SKU) {
							foreach ($SKU as $element) if ($element->getName() == "Default") {
								$price = $SKU->Pricing->Price . '';
								$originalPrice = $price;
							}
						}
					}
				}

				$productData['price'] = $price;
				$productData['originalPrice'] = $originalPrice;


				$productID = $xmlData->Product->ProductNo . '';
				$productSKU = $xmlData->Product->ProductSKU . '';
				$productData['productSKU'] = $productSKU;
				$productData['productID'] = $productID;
				$productData['name'] = $xmlData->Product->ProductName . '';
				$productData['image'] = $xmlData->Product->Images->Thumbnail . ''; // optionally ->Main or ->Large
				$productData['url'] = $xmlData->Product->ProductLink->StoreFront . '';
                if(!$xmlData->Product->Attributes || !$xmlData->Product->Attributes->Attribute[0]) {
                    $productData['directCheckout'] = $productData['url'].'?addQuantity=1';
                } else {
                    $productData['directCheckout'] = '';
                }

                $productData['shortDescription'] = $xmlData->Product->Description->Short . '';

				$cacheDurationMin = 23*60*60;
				$cacheDurationMax = 25*60*60;
				$productData['expires'] = time() + rand($cacheDurationMin, $cacheDurationMax); // expire randomly, between 23 and 24 hours from now

				$data['productData'][$productSKU] = $productData;
				$data['productDataById'][$productID.''] = $productData;
				//$data['productData'][$productSKU.''] = $productData;
				//error_log(var_dump($data,true));
				update_option('nexternal', $data);
			}

			// extract the values to show from the cache/datastore
			$productName = $productData['name'];
			$price = $productData['price'];
            $productID = $productData['productID'];
            $productSKU = $productData['productSKU'];
			$productPrice = number_format($productData['price'],2);
			$productUrl = $productData['url'];
			$productImage = $productData['image'];
			$productOriginalPrice = number_format($productData['originalPrice'],2);
			$productShortDescription = $productData['shortDescription'];
			$productRating = $productData['rating'];
            $directCheckout = $productData['directCheckout'];

			$productRatingWidth = $productRating * 20;
			$pclass = ($productOriginalPrice && $productOriginalPrice != $productPrice?'on-sale':'');

			if ($carousel == 'owl' || $carousel == 'plain') {
				if($productName != '') {

					$t = "<div class=\"np-product $pclass\">";
					$didPrice = false;
					foreach(explode(',',$fieldorder) as $f) {
                        if(${'displayproduct'.strtolower($f)} != 'true') { continue; }
						if($f == 'image') {
							$t .= "
							  <div class=\"np-image-outer\">
								<div class=\"np-image-inner\">
								  <a href=\"".$productUrl."\" ".stripslashes($customLinkAttributes)."><img src=\"".$productImage."\"/></a>
								</div>
							  </div>
							";
						}
						if($f == 'name') {
							$t .= "
							  <div class=\"np-title\">
								<a href=\"".$productUrl."\" ".stripslashes($customLinkAttributes).">".$productName."</a>
							  </div>
							";
						}
						if($f == 'shortDescription') {
							$t .= "
							  <div class=\"np-description\">
								$productShortDescription
							  </div>
							";
						}
						if($f == 'price' || $f == 'originalPrice') {
							if(!$didPrice) {
								$t .= "
								  <div class=\"np-price-container\">
									<span class=\"np-orig-price\">".(($productOriginalPrice && $productOriginalPrice != $productPrice)?'$'.$productOriginalPrice:'')."</span>
									<span class=\"np-final-price\">\$$productPrice</span>
								  </div>
								";
								$didPrice = true; // only show price once
							}
						}
						if($f == 'rating') {
							$t .= "
							  <div class=\"np-ratings-container\">
								<div class=\"np-ratings\" style=\"width:".$productRatingWidth."px\"></div>
							  </div>
							";
						}
                        if($f == 'addToCart') {
                            $t .= "
							  <div class=\"np-addtocart\">
							";
                            if($directCheckout) {
                                $t .= "Qty: <input type='text' value='1' name='prodQuant' size='3' id='changeQuant_".$productSKU."'/> <a href='javascript:void(0);' onclick=\"var l = '".$directCheckout."'; l=l.replace('=1','='+document.getElementById('changeQuant_".$productSKU."').value); location.href=l;\" " . stripslashes($customLinkAttributes) . ">Add To Cart</a>";
                            } else {
                                $t .= "<a href='$productUrl' " . stripslashes($customLinkAttributes) . ">Select Options</a>";
                            }
                            $t .= "
							  </div>
							";
                        }
					}
					$t .= "</div>";

					/*
					error_log('saw prices as: '.$productOriginalPrice.' / '.$price);
					$t = $data['nphtml'];
					$t = str_replace('[[IMG]]','<a href="'.$productUrl.'" '.stripslashes($customLinkAttributes).'><img src="'.$productImage.'"/></a>',$t);
					$t = str_replace('[[TITLE]]','<a href="'.$productUrl.'" '.stripslashes($customLinkAttributes).'>'.$productName.'</a>',$t);
					$t = str_replace('[[DESCRIPTION]]',$productShortDescription,$t);
					$t = str_replace('[[PCLASS]]',$class,$t);
					$t = str_replace('[[OPRICE]]',($productOriginalPrice != $productPrice?'$'.$productOriginalPrice:''),$t);
					$t = str_replace('[[FPRICE]]','$'.$productPrice,$t);
					$t = str_replace('[[RATING]]','<div class="np-ratings" style="width:'.$productRatingWidth.'px"></div>',$t);
					*/
					$out .= $t;
				}
			} else {
				// if we're generating a grid and at the end of the row, we need to step to the next row by closing the <ul> and starting another
				if ($carousel == 'grid' && $i % $gridsizecolumns == 0 && $i != 0) $out .= "<br style='clear: both;'>";

				if ($productName != '') {

					$out .= "<li class='nexternal-$style-product nexternal-$style-product-$i nexternal-$style-product-sku-$productSKU $pclass'>";

					if ($displayproductname == 'true')
						$out .= "<div class='nexternal-$style-product-name nexternal-$style-product-name-$productSKU'><a href='$productUrl' " . stripslashes($customLinkAttributes) . ">$productName</a></div>";

					if ($displayproductimage == 'true')
						$out .= "<div class='nexternal-$style-product-image nexternal-product-image-$productSKU'><a href='$productUrl' " . stripslashes($customLinkAttributes) . "><img src='$productImage' border='0'></a></div>";

                    if ($displayproductaddtocart == 'true' || $displayaddtocart == 'true') {
                        if($directCheckout) {
                            $out .= "<div class='nexternal-".$style."-product-addtocart'>Qty: <input type='text' value='1' name='prodQuant' size='3' id='changeQuant_".$productSKU."'/> <a href='javascript:void(0);' onclick=\"var l = '".$directCheckout."'; l=l.replace('=1','='+document.getElementById('changeQuant_".$productSKU."').value); location.href=l;\" " . stripslashes($customLinkAttributes) . ">Add To Cart</a></div>";
                        } else {
                            $out .= "<div class='nexternal-".$style."-product-addtocart'><a href='$productUrl' " . stripslashes($customLinkAttributes) . ">Select Options</a></div>";
                        }
                    }

                    if ($displayproductrating == 'true' and $productRating != '')
						$out .= "<div class='nexternal-$style-product-rating nexternal-$style-product-rating-$productSKU' style='width: ".$productRatingWidth."px'></div>";

					if ($displayproductshortdescription == 'true')
						$out .= "<div class='nexternal-$style-product-shortdescription nexternal-$style-product-shortdescription-$productSKU'>$productShortDescription</div>";

					if ($displayproductoriginalprice == 'true' and $productOriginalPrice != $price)
						$out .= "<div class='nexternal-$style-product-original-price nexternal-$style-product-original-price-$productSKU'>$$productOriginalPrice</div>";

					if ($displayproductprice == 'true')
						$out .= "<div class='nexternal-$style-product-price nexternal-$style-product-price-$productSKU'>$$productPrice</div>";

					$out .= "</li>";
				}

			}



		}

		if ($carousel == 'owl' || $carousel == 'plain') {
			$out .= "</div>";
		} else {
			$out .= "</ul></div>";
		}

		if ($carousel == 'owl') {
			// create the carousel via javascript

			$out .= '<script type="text/javascript">';
			$out .= '    jQuery(function($) {';
			$out .= '        $("#'.$id.'").owlCarousel({';
            $out .= $tagextras;
			$out .= '            responsive:{';
			$out .= '              0:   { items:1 },';
			$out .= '              480: { items:2 },';
			$out .= '              768: { items:3 },';
			$out .= '              1024: { items:4 }';
			$out .= '            }';
			$out .= '        });';
			$out .= '    });';
			$out .= '</script>';
		}


		// display navigation button if this a carousel
		if ($carousel == 'horizontal' || $carousel == 'vertical') $out .= "<a class='nexternal-$style-$carousel-next $id-next'></a>";

		if ($carousel == 'horizontal' || $carousel == 'vertical') {
			// create the carousel via javascript

			$out .= '<script type="text/javascript">';
			$out .= '    jQuery(function() {';
			$out .= '        jQuery(".'.$id.'").jCarouselLite({';
			$out .= '        btnNext: ".'.$id.'-next",';
			$out .= '        btnPrev: ".'.$id.'-previous",';
			$out .= '        visible: '.$productsinview.'';
			if ($carousel == 'vertical') $out .= ',        vertical: true';
			$out .= '    });';
			$out .= '});';
			$out .= '</script>';
		}

		return $out;
	}

	/**
	 * admin_head
	 * calls your functions into the correct filters
	 * @return void
	 */
	function admin_head() {
		// check user permissions
		if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
			return;
		}
		
		// check if WYSIWYG is enabled
		if ( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array( $this ,'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array($this, 'mce_buttons' ) );
		}
	}

	/**
	 * mce_external_plugins 
	 * Adds our tinymce plugin
	 * @param  array $plugin_array 
	 * @return array
	 */
	function mce_external_plugins( $plugin_array ) {
		$plugin_array[$this->shortcode_tag] = plugins_url( 'js/mce-button.js' , __FILE__ );
		return $plugin_array;
	}

	/**
	 * mce_buttons 
	 * Adds our tinymce button
	 * @param  array $buttons 
	 * @return array
	 */
	function mce_buttons( $buttons ) {
		array_push( $buttons, $this->shortcode_tag );
		return $buttons;
	}

	/**
	 * admin_enqueue_scripts 
	 * Used to enqueue custom styles
	 * @return void
	 */
	function admin_enqueue_scripts() {
		 wp_enqueue_style('nexternal_shortcode', plugins_url( 'css/mce-button.css' , __FILE__ ) );
	}
}//end class

new nexternal_shortcode();