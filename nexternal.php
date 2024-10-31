<?php
/*
Plugin Name: Nexternal
Description: Allows users to include Nexternal product information into their posts and pages
Author: Already Set Up, Inc.
Author URI: http://AlreadySetUp.com
Plugin URI: http://AlreadySetUp.com/nexternal
Version: 2.01

CHANGELOG:
10/26/21- 2.01 - Registration Change and Final Update.
        - Removed registration requirements. Enjoy.
5/18/16 - 2.0   - Shortcode Update
        - added ability to sort field order
        - replaced primary slider functionality with owl slider
        - created placeholder image in visual editor
        - allow edit of existing shortcode
        - allow sorting of products in shortcode
        - simplified administrative interfaces
        - added jQuery integration for front-end shopping cart display
        - require user registration for plugin
2/12/15 - 1.5   - Remove legacy code for authentication
6/9/14  - 1.4.2 - Fix for carousel jquery product ID support and box-model: border-box
6/9/14  - 1.4.1 - Fix for shortcode representation in pages
6/5/14  - 1.4   - Nexternal authentication updates
        - updated plugin access (settings page, uninstall)
        - separated plugin settings to multiple pages
        - added basic instructions for connecting Nexternal account
        - changed product identification from SKU to productNumber, allowing non-SKU product support
        - altered inline editor height to avoid overlapping content
5/23/14 - 1.3   - updated syntax to avoid depreciation warnings (multiple files)
        - updated enqueue_scripts call to use appropriate hook (nexternal.php)
        - changed authentication method to user/pass instead of activeKey (all files)
4/14/14 - 1.2   - updated jquery and jquery ui cdn references (window.php)
1/1/13  - 1.1.7b- stop curl from getting hung up on SSL certs from nexternal (nexternal-api curl_post)
        - fixed bug where tinymce window wasnt able to find javascript file (window.php jquery-1.7.2.min.js)
5/31/12 - 1.1.6 - fixed jquery UI inclusion bug (another bug)
10/11/11- 1.1.5 - fixed jquery inclusion bug
10/3/11 - 1.1.4 - added strrpos to productOptions generation in window.php. This prevents the list of options from ending in a comma
10/3/11 - 1.1.4 - updated jQuery version in window.php, jQuery moved their hosted javascript files to code.jquery.com
7/29/11 - 1.1.3 - added custom attributes link field to nexternal menu. this is put into the texts and images <a> tag.

*/

include_once(dirname(__FILE__) . "/lib/np-variables.php");
include_once(dirname(__FILE__) . "/lib/nexternal-api.php");
//include_once (dirname (__FILE__)."/tinymce/tinymce.php");
include_once (dirname (__FILE__)."/nexternal_shortcode.php");

define('nexternalPlugin_ABSPATH', WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );
define('nexternalPlugin_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );

function nexternal_scripts() {
  $data = get_option('nexternal');

  wp_register_script( 'nextcart', 'https://store.nexternal.com/'.$data['accountName'].'/cartcontents.js' );
  wp_register_script( 'nextcartdisp', plugins_url( 'js/nextcart.js', __FILE__ ), array( 'nextcart', 'jquery' ));
  wp_register_script( 'owlcarousel', plugins_url( 'js/owl.carousel.min.js', __FILE__ ), array( 'jquery' ));
  wp_enqueue_script('nextcartdisp');
  wp_enqueue_script('owlcarousel');
  wp_register_style( 'nexternal_styles', admin_url('admin-ajax.php?action=nextcss'));
  wp_register_style( 'owlmain', plugins_url( 'js/assets/owl.carousel.min.css', __FILE__ ));
  wp_register_style( 'owltheme', plugins_url( 'js/assets/owl.theme.default.min.css', __FILE__ ));
  wp_enqueue_style( 'nexternal_styles' );
  wp_enqueue_style( 'owlmain' );
  wp_enqueue_style( 'owltheme' );
  //wp_enqueue_style( 'theme_sheet' );
}
add_action( 'wp_enqueue_scripts', 'nexternal_scripts' );

function nexternal_admin_scripts() {
  wp_enqueue_script('jquery-ui-sortable');
}
add_action( 'admin_enqueue_scripts', 'nexternal_admin_scripts' );


add_action('admin_menu', 'nexternal_menu');
add_action('wp_head', 'nexternal_head');


function np_stylesheet() {
    header('Content-type: text/css');
    $data = get_option('nexternal');
    if (!isset($data['npcss'])) {
        $NPCSS = '';
        include "lib/np-variables.php";
        $data['npcss'] = $NPCSS; // use html as registered
    }
    echo $data['npcss'];
    wp_die();
}
add_action( 'wp_ajax_nextcss', 'np_stylesheet' );
add_action( 'wp_ajax_nopriv_nextcss', 'np_stylesheet' );



function nexternal_endsWith($haystack,$needle,$case=true) {
    if($case){return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);}
    return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
}

function nexternal_head() {

    echo '<script type="text/javascript" src="' . get_option('siteurl') . '/wp-content/plugins/nexternal/carousel/jcarousellite.js"></script>' . "\n";

    $data = get_option('nexternal');
    if(isset($data['cartID']) && $data['cartID'] != '' && isset($data['storeURL']) && $data['storeURL'] != '') {
        echo "
            <script type=\"text/javascript\">
                jQuery(function($) {
                  $('".$data['cartID']."').nextCart('".$data['storeURL']."');
                  $('".$data['cartID']." .mini-cart').mouseover(function() { $(this).addClass('active'); });
                  $('".$data['cartID']." .mini-cart').mouseout(function() { $(this).removeClass('active'); });
                });

            </script>
        ";
    }

    $path = ABSPATH.'wp-content/plugins/nexternal/styles';
    if ($dh = opendir($path)) {
        while (($file = readdir($dh)) !== false) {
            if (nexternal_endsWith($file, '.css', false))
                echo "<link rel='stylesheet' type='text/css' media='all' href='" . get_option('siteurl') . "/wp-content/plugins/nexternal/styles/$file'/>" . "\n";
        }
        closedir($dh);
    }

}






function nexternal_menu() {

//    add_menu_page( $page_title, $menu_title, $capability, $menu_slug, null, $icon_url );
//    add_submenu_page( $same_as_add_menu_page_slug, $page_title, $menu_title, $capability, $same_as_add_menu_page_slug, $function )


    add_menu_page("Nexternal Settings", "Nexternal", 'manage_options', 'nexternal_menu', null,'',81);
    //add_submenu_page('nexternal_menu', "Nexternal Settings","Nexternal",'manage_options','nexternal_menu','nexternal_display_menu');
    add_submenu_page('nexternal_menu', "Account Options", "Account Options", 'manage_options', 'nexternal_menu', 'nexternal_display_general_menu');
    add_submenu_page('nexternal_menu', "Display Options", "Display Options", 'manage_options', 'nexternal_displayopt', 'nexternal_display_display_menu');
    add_submenu_page('nexternal_menu', "Instructions", "Instructions", 'manage_options', 'nexternal_instructions', 'nexternal_display_instructions_menu');
}

// converts data value 'on' or empty for a checkbox to checked='yes' or nothing
function nexternal_convertDataToChecked($dataValue) {
    if (strtolower($dataValue) == 'on' || strtolower($dataValue) == 'true') return "checked='yes'";
    return '';
}

function nexternal_display_menu() {
    $currtab = isset($_GET['tab'])?$_GET['tab']:'nexternal_menu';
    $currpage = isset($_GET['page'])?$_GET['page']:'nexternal_menu';
    $tabs = array( 'account' => 'Account', 'display' => 'Default Options', 'instruction' => 'Instructions' );
    $functions = array( 'account' => 'nexternal_display_general_menu', 'display' => 'nexternal_display_display_menu', 'instruction' => 'nexternal_display_instructions_menu' );
    $pages = array( 'account' => 'nexternal_menu', 'display' => 'nexternal_displayopt', 'instruction' => 'nexternal_instructions' );
    $links = array();
    foreach( $tabs as $tab => $name ) :
        if ( $pages[$tab] == $currpage ) :
            $links[] = "<a class='nav-tab nav-tab-active' href='?page=$pages[$tab]'>$name</a>";
        else :
            $links[] = "<a class='nav-tab' href='?page=$pages[$tab]'>$name</a>";
        endif;
    endforeach;
    $returnval = '<Style type="text/css">
        /*
            .nav-tab{
                border: 1px solid #ccc;border-bottom: 0 #f9f9f9;
                color:#c1c1c1;
                text-shadow:rgba(255,255,255,1) 0 1px 0;
                font-size:12px;
                line-height:16px;
                display:inline-block;
                padding:4px 14px 6px;
                text-decoration:none;
                margin:0 6px -1px 0;
                border-radius:5px 5px 0 0;
            }
            .nav-tab-active{
                border-width:1px;
                color:#464646;
                border-bottom:1px solid #f1f1f1;
            }
            h2.nav-tab-wrapper,h3.nav-tab-wrapper{
                border-bottom:1px solid #ccc;
                padding-bottom:0;
            }
            h2 .nav-tab{
                padding:4px 20px 6px;
                line-height:1.5em;
                font-size:135%;
            }

        */

            .wrap {
                max-width: 1000px;
                font-family: Arial, Sans-serif;
                font-size: 1.25em;
            }
            .wrap p, .wrap h3 {
                padding: 10px;
            }
            .nav-tab{
                border: 1px solid #ccc;border-bottom: 0 #f9f9f9;
                color:#c1c1c1;
                text-shadow:rgba(255,255,255,1) 0 1px 0;
                font-size:12px;
                line-height:16px;
                display:inline-block;
                padding:4px 14px 6px;
                text-decoration:none;
                margin:0 6px -1px 0;
                border-radius:5px 5px 0 0;
            }
            .nav-tab-active{
                border-width:1px;
                color:#464646;
                border-bottom:1px solid #f1f1f1;
            }
            h2.nav-tab-wrapper,h3.nav-tab-wrapper{
                border-bottom:1px solid #ccc;
                padding-bottom:0;
            }
            h2 .nav-tab{
                padding:4px 20px 6px;
                line-height:1.5em;
                font-size:135%;
            }
            img.screenshot {
                float: left;
                margin: 10px 20px 10px 10px;
                box-shadow: 2px 2px 3px 3px #666;
            }
            hr.divider {
                width: 75%;
            }
        </style>
        <h2 class="nav-tab-wrapper">';
    foreach ( $links as $link )
        $returnval .= $link;
    $returnval .= '</h2>';

//    $dofunc = $functions[$currtab];
//    $returnval .=$dofunc();
    return $returnval;
}


function empty_account_credentials() {

    $data = get_option('nexternal');
    unset($data['accountName']);
    unset($data['userName']);
    unset($data['pw']);
    unset($data['activeKey']);
    unset($data['storeURL']);
    update_option('nexternal', $data);
}

function nexternal_display_general_menu() {

    if (!current_user_can('manage_options'))  {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    // setup error messages array to be populated with potential errors
    $errorMessages = array();

    // get all nexternal options from Wordpress
    $data = get_option('nexternal');
    //$activeKey = $data['activeKey'];
    $accountName = $data['accountName'];
    $storeURL = $data['storeURL'];
    //$userName = $data['userName'];
    //$password = $data['pw'];



    if(isset($_POST['clear_data']) && $_POST['clear_data']) {
      unset($data['productData']);
      unset($data['productDataById']);
      $errorMessages[] = 'Product Cache Data has been Emptied.';
      update_option('nexternal', $data);
    }

    if(isset($_POST['unlink_account']) && $_POST['unlink_account'] == 'unlink') {
        empty_account_credentials();
        $errorMessages[] = 'Nexternal Account Unlinked.';
    }

    if(isset($_POST['nexternal_storeURL'])) {
        $data['storeURL'] = $_POST['nexternal_storeURL'];
        update_option('nexternal', $data);
    }

    // check to see if the user submitted an accountName, username OR password information
    if (!empty($_POST['nexternal_accountName']) or !empty($_POST['nexternal_username']) or !empty($_POST['nexternal_password'])) {

        // if they are all there
        if (!empty($_POST['nexternal_accountName']) && !empty($_POST['nexternal_username']) && !empty($_POST['nexternal_password'])) {

            // try to get an active key from Nexternal
            $accountName = $_POST['nexternal_accountName'];
            $userName = $_POST['nexternal_username'];
            $password = $_POST['nexternal_password'];

            empty_account_credentials();


            $verified = nexternal_getActiveKey($accountName, $userName, $password);
            if($verified) {
                $verified = nexternal_testCredentials($accountName, $userName, $password);
            }

            // check for failure
            if (!$verified) $errorMessages[] = 'Unable to connect to Nexternal, check username and password.';
            else {
                // if it worked, save the user data to Wordpress
                $data['accountName'] = $accountName;
            $data['userName'] = $userName;
            $data['pw'] = $password;
                update_option('nexternal', $data);
            }

        } else {
            // check for missing fields
            if (empty($_POST['nexternal_accountName'])) $errorMessages[] = 'Please enter your account name.';
            if (empty($_POST['nexternal_username'])) $errorMessages[] = 'Please enter your username.';
            if (empty($_POST['nexternal_password'])) $errorMessages[] = 'Please enter your password.';
        }
    }

    // generate error message div element based on $errorMessages
    $displayErrors = '';
    if (count($errorMessages) > 0) {
        $displayErrors = "<div class='nexternal-errors' style='width: 400px; margin: auto; background: #ffaaaa; padding: 5px; font-weight: bold;'>";
        foreach ($errorMessages as $errorMessage) $displayErrors .= "$errorMessage<br>";
        $displayErrors .= "</div>";
    }

    // determine if a user account has already been established
    $linkStatus = '';
    if ($data['userName'] != '' && $data['pw'] != '' && $data['accountName'] != '') {
        $linkStatus = <<<HTML
            <p>You are currently linked to the account: $accountName. You do not neeed to enter your username and password again.</p>
            <p>

              <input type="button" value="Link to a Different Account" onclick="document.getElementById('nexternal-link').style.display = 'block';">
              <input type="hidden" name="unlink_account" id="unlink_account" value=""/>
              <input type="submit" value="Unlink Account" onclick="if(confirm('This will permanently unlink your Nexternal account and forget all credentials.  To re-link your account you will need to provide fresh, correct credentials.  Are you sure you wish to do this?')) { document.getElementById('unlink_account').value='unlink';return true; } else { return false; }"/>
            </p>
HTML;
        $linkDisplay = 'none';
    } else {
        $linkDisplay = 'block';
    }

    // load variables from data to display in HTML form
    $html = nexternal_display_menu();
    $html .= <<<HTML
    <div class="wrap">
        <div id="icon-edit-pages" class="icon32"><br /></div>
        <h2>Nexternal Account Configuration</h2>

        $displayErrors

        <form method="post">



HTML;

if(isset($data['productData']) || isset($data['productDataById'])) {
    $html .= <<<HTML
        <h2>Product Data Cache</h2>
        <div>
        <input type="hidden" name="clear_data" id="clear_data" value="">
        <p><em>Products using the Nexternal Shortcodes are cached locally for approximately 24 hours before reloading information.</em><br/><br/>
        If products displayed using Nexternal Shortcodes are not showing your most recent product data, you can use the <br/>
        button below to clear local data stores and fetch fresh information from Nexternal on the next page load.</p>
        <input type="submit" name="Submit" onclick="document.getElementById('clear_data').value='clear';" value="Clear Product Cache" /><br/><br/><br/>
    </div>
HTML;
}

    $html .= <<<HTML
        <h2>Account Options</h2>

        $linkStatus

            <p>Enter your store URL (the location of your storefront home page) so we can link to it correctly.</p>

            <p><strong>Store URL:</strong>
            <input type="text" name="nexternal_storeURL" value="$storeURL" placeholder="e.g. https://store.mysite.com/" size="100"/></p>


        <div id="nexternal-link" style="display: $linkDisplay">
            <p>To connect to a Nexternal store front, enter the account name.</p>

            <p><strong>Account Name:</strong>
            <input type="text" name="nexternal_accountName" size="45"/></p>

            <p>Enter your username and password. This information will be used to communicate with the Nexternal API.</p>
            <p>This user should be a Nexternal user of the type 'XML Tools', with the 'ProductQuery' option enabled.</p>

            <p><strong>Username:</strong>
            <input type="text" name="nexternal_username" size="45" /></p>

            <p><strong>Password:</strong>
            <input type="text" name="nexternal_password" size="45" /></p>


        </div>


        <input type="hidden" name="updated" id="updated" value="yes">

        <p><input type="submit" name="Submit" value="Update Options" /></p>

        </form>

    </div>
HTML;

    echo $html;

}

function nexternal_display_display_menu() {

    if (!current_user_can('manage_options'))  {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    // setup error messages array to be populated with potential errors
    $errorMessages = array();

    // get all nexternal options from Wordpress
    $data = get_option('nexternal');
    //$activeKey = $data['activeKey'];
    //$accountName = $data['accountName'];
    //$userName = $data['userName'];
    //$password = $data['pw'];

    if (!isset($data['npcss'])) {
        $NPCSS = '';
        include "lib/np-variables.php";
        $data['npcss'] = $NPCSS; // use html as registered
    }
    if (!isset($data['defaultFieldOrder'])) {
        $data['defaultFieldOrder'] = 'image,name,shortDescription,price,originalPrice,rating,addToCart';
    }


    // copy default options into data and save it, only if the form was submitted
    if (isset($_POST['updated'])) {
        $data['defaultCarouselType'] = $_POST['nexternal_carouselType']?$_POST['nexternal_carouselType']:'plain';
        $data['defaultGridSizeRows'] = $_POST['nexternal_gridSizeRows'];
        $data['defaultDisplayProductImage'] = $_POST['nexternal_displayProductImage'];
        $data['defaultDisplayProductName'] = $_POST['nexternal_displayProductName'];
        $data['defaultGridSizeColumns'] = $_POST['nexternal_gridSizeColumns'];
        $data['defaultDisplayProductOriginalPrice'] = $_POST['nexternal_displayProductOriginalPrice'];
        $data['defaultDisplayProductPrice'] = $_POST['nexternal_displayProductPrice'];
        $data['defaultDisplayProductRating'] = $_POST['nexternal_displayProductRating'];
        $data['defaultDisplayProductShortDescription'] = $_POST['nexternal_displayProductShortDescription'];
        $data['defaultDisplayProductAddToCart'] = $_POST['nexternal_displayProductAddToCart'];
        $data['defaultProductsInView'] = $_POST['nexternal_productsInView'];
        $data['defaultStyle'] = $_POST['nexternal_defaultStyle'];
        $data['defaultFieldOrder'] = $_POST['nexternal_fieldOrder'];
        $data['cartID'] = $_POST['nexternal_cartID'];
        $data['npcss'] = $_POST['nexternal_CSS'];
        if($_POST['nexternal_revertCSS'] == 'Y') {
            $NPCSS = '';
            include dirname(__FILE__) . "/lib/np-variables.php";
            $data['npcss'] = $NPCSS; // use html as registered
        }
        $data['customLinkAttributes'] = $_POST['nexternal_customLinkAttributes'];
        $errorMessages[] = 'Updated Default Display Configuration.';
    }
    update_option('nexternal', $data);

    // load variables from data to display in HTML form
    $defaultGridSizeRows = $data['defaultGridSizeRows'];
    $defaultGridSizeColumns = $data['defaultGridSizeColumns'];
    $defaultProductsInView = $data['defaultProductsInView'];
    $customLinkAttributes = htmlspecialchars(stripslashes($data['customLinkAttributes']));

    //$defaultDisplayProductRatingChecked = nexternal_convertDataToChecked($data['defaultDisplayProductRating']);
    //$defaultDisplayProductPriceChecked = nexternal_convertDataToChecked($data['defaultDisplayProductPrice']);
    //$defaultDisplayProductOriginalPriceChecked = nexternal_convertDataToChecked($data['defaultDisplayProductOriginalPrice']);
    //$defaultDisplayProductImageChecked = nexternal_convertDataToChecked($data['defaultDisplayProductImage']);
    //$defaultDisplayProductNameChecked = nexternal_convertDataToChecked($data['defaultDisplayProductName']);
    //$defaultDisplayProductShortDescriptionChecked =  nexternal_convertDataToChecked($data['defaultDisplayProductShortDescription']);
    $defaultUseCarouselChecked =  ($data['defaultCarouselType'] == 'owl') ? ("CHECKED"):('');
    $cartID = $data['cartID'];

    $defaultCarouselTypeHorizontalSelected = ($data['defaultCarouselType'] == 'horizontal') ? ("SELECTED"):('');
    $defaultCarouselTypeVerticalSelected = ($data['defaultCarouselType'] == 'vertical') ? ("SELECTED"):('');
    $defaultCarouselTypeNoneSelected = ($data['defaultCarouselType'] == 'none') ? ("SELECTED"):('');
    $defaultCarouselTypeSingleSelected = ($data['defaultCarouselType'] == 'single') ? ("SELECTED"):('');

    // load available styles
    $styleOptions = "";
    $path = ABSPATH.'wp-content/plugins/nexternal/styles';
    if ($dh = opendir($path)) {
        while (($file = readdir($dh)) !== false) {
            if (nexternal_endsWith($file, '.css', false)) {
                if ($data['defaultStyle'] == $file) $selected = 'SELECTED';
                else $selected = '';
                $fileData = get_file_data($path . '/' . $file, array( 'Name' => 'Style Name'));
                $styleOptions .= "<option value='$file' $selected>" . $fileData['Name'] . " ($file)</option>";
            }
        }
        closedir($dh);
    }

    // generate error message div element based on $errorMessages
    $displayErrors = '';
    if (count($errorMessages) > 0) {
        $displayErrors = "<div class='nexternal-errors' style='width: 400px; margin: auto; background: #aaffaa; padding: 5px; font-weight: bold;'>";
        foreach ($errorMessages as $errorMessage) $displayErrors .= "$errorMessage<br>";
        $displayErrors .= "</div>";
    }


    $html = nexternal_display_menu();
    $html .= <<<HTML

    <style type="text/css">
      .nextFieldContainer { max-width:400px; padding:10px; }
      .nextFieldListing { padding:3px;margin:3px;border-radius:5px;border:1px solid #cccccc;background:#efefef; }
    </style>
    <script type="text/javascript">
      jQuery(function($) {
        $('.nextFieldContainer').sortable({
              cursor: 'move',
          update: function( ) { // we don't need the arguments ( event, ui )
            var nfc = $('.nextFieldContainer');
            //console.log(nfc.sortable('toArray',{'attribute':'data-fname'}).join(','));
            $('#nexternal_fieldOrder').val(nfc.sortable('toArray',{'attribute':'data-fname'}).join(','));
          }
        });
      });
    </script>

    <div class="wrap">
        <div id="icon-edit-pages" class="icon32"><br /></div>
        <h2>Nexternal Display Configuration</h2>

        $displayErrors

        <form method="post">

        <h2>Default Display Options</h2>

        <p><input type="checkbox" $defaultUseCarouselChecked name="nexternal_carouselType" value="owl"/> Display items in a carousel?</p>

<!--
        <p><label for="nexternal_carouselType">Carousel Type:</label>
        <select id="nexternal_carouselType" name="nexternal_carouselType" style="width: 150px;">
            <option value="none" $defaultCarouselTypeNoneSelected>No Carousel</option>
            <option value="single" $defaultCarouselTypeSingleSelected>Single Product</option>
            <option value="horizontal" $defaultCarouselTypeHorizontalSelected>Horizontal Carousel</option>
            <option value="vertical" $defaultCarouselTypeVerticalSelected>Vertical Carousel</option>
        </select>
        </p>
-->
        <h3>Default Fields:</h3>

        <p>Select which fields should be displayed when you include Nexternal products in your pages.</p>

      <input type="hidden" id="nexternal_fieldOrder" name="nexternal_fieldOrder" value="$data[defaultFieldOrder]">
      <div class="nextFieldContainer">

HTML;
    $flds = explode(',',$data['defaultFieldOrder']);
    foreach($flds as $fld) {

        $ischecked = nexternal_convertDataToChecked($data['defaultDisplayProduct'.ucfirst($fld)]);
        $fname = ucfirst(preg_replace('/([A-Z])/',' $1',$fld));
        $flbl = ucfirst($fld);
        $html .= "

        <p class=\"nextFieldListing\" data-fname=\"$fld\">
          <input type=\"checkbox\" id=\"nexternal_displayProduct$flbl\" name=\"nexternal_displayProduct$flbl\" $ischecked> <strong>$fname</strong>
        </p>
        ";
    }

    $html .= <<<HTML

      </div>
      <br style="clear:both;"/>

        <h3>Legacy Options</h3>
        <p>Options from older versions of the Nexternal plugin: <a href="#" onclick="jQuery('#legacy_options').toggle();return false;">Show / Hide</a></p>
        <div id="legacy_options" style="display:none;padding-left:30px;">

        <h3>Carousel Options</h3>
        <p><label for="productsInView">Number of Visible Products in Carousel:</label> <input type="text" id="nexternal_productsInView" name="nexternal_productsInView" style="width: 25px" value="$defaultProductsInView"></p>

        <h3>Grid Options</h3>
        <p><label for="gridSizeRows">Grid Size (Width, Height):</label> (
            <input type="text" id="nexternal_gridSizeColumns" name="nexternal_gridSizeColumns" style="width: 25px" value="$defaultGridSizeColumns">,
            <input type="text" id="nexternal_gridSizeRows" name="nexternal_gridSizeRows" style="width: 25px" value="$defaultGridSizeRows">
             )</p>

        <p>Custom Link Attributes: $customLinkAttributes
        <br>
        Change to: <input size="40" type="text" id="nexternal_customLinkAttributes" name="nexternal_customLinkAttributes" value=""/></p>

        <h2>Default Style</h2>

        <p>Add new styles to: wp-content/plugins/nexternal/styles</p>
        <p>Select Default Style:
        <select id='nexternal_defaultStyle' name='nexternal_defaultStyle'>
            <option value='none'>No Default</option>
            $styleOptions
        </select></p>


        </div>


        <h3>Advanced</h3>
        <p>Options for advanced users and web developers: <a href="#" onclick="jQuery('#advanced_options').toggle();return false;">Show / Hide</a></p>
        <div id="advanced_options" style="display:none;padding-left:30px;">
        <h3>Shopping Cart Display</h3>
        <p>To create a display on your site for users indicating their Nexternal shopping cart status, enter the css selector (e.g. #cartDisplay or ul.menu>li:nth-child(3), etc) for the element you would like to use for this display here.
        The contents of this element will be overwritten with the cart display.
        <input type="text" name="nexternal_cartID" value="$cartID"/>
        </p>
        <h3>Edit CSS</h3>
        <p>This CSS code controls the layout of your nexternal products on your pages.  You may edit and save the code here, or
        modify the CSS in your theme's style.css file.  If you ever want to return to default CSS, select the 'Revert to original' checkbox below.<br/>
        <textarea class="" name="nexternal_CSS" style="width:100%;height:250px;">$data[npcss]</textarea><br/>
        <input type="checkbox" name="nexternal_revertCSS" value="Y" onclick="if(this.checked) return confirm('This will revert to default CSS and any changes made will be lost.  Are you sure?');"> Revert to original
        </p>
        </div>

        <input type="hidden" name="updated" id="updated" value="yes">

        <p><input type="submit" name="Submit" value="Update Options" /></p>

        </form>

    </div>
HTML;

    echo $html;

}

function nexternal_display_instructions_menu() {
  $imgdir = plugins_url( 'img' , __FILE__ );
  $plugindir = plugins_url( '' , __FILE__ );
    error_log('running instructions menu...');
  $settings_link = '<a href="'.admin_url( 'admin.php?page=nexternal_menu').'">Nexternal Settings</a>';
    /** @noinspection HtmlUnknownTarget */
    $html = nexternal_display_menu();
    $html .= <<<HTML
    <div class="wrap">
    <h2>Nexternal Configuration Instructions</h2>
    <h3>Use these instructions to activate the XML Tools API and connect the Nexternal WordPress Plugin to your store. Instructions for <a href="#pluginusage">using the plugin</a> are below.</h3>
        <img class="screenshot" src="$imgdir/screen_settings1a.jpg" alt="Click on Settings" title="Click on Settings" />
        <p>The first step is to make certain that your Nexternal account has the privileges required to use the Nexternal XML Tools API.
        <br /><br />
        To do so, first log in to your Nexternal Store OMS and click on <b>Settings</b>.</p>
            <br clear="both" />
        <br /><hr class="divider" /><br />
        <p>Scroll all the way to the bottom of the Settings page and click on <b>Edit</b> next to XML Tools:</p>
        <img class="screenshot" src="$imgdir/screen_xml_edit.jpg" alt="Click on Edit" title="Click on Edit" />
            <br clear="both" />
        <br /><hr class="divider" /><br />
        <img class="screenshot" src="$imgdir/screen_memorandum.jpg" alt="Check the box to accept the XML Memorandum of Understanding" title="Check the box to accept the XML Memorandum of Understanding" />
        <p>Accept the XML Memorandum of Understanding.
        <br /><br />
        (You may have already done this.)</p>
            <br clear="both" />
        <p>You may be required to "pass an XML test"; the plugin will satisfy this requirement for you.</p>
            <br clear="both" />
        <br /><hr class="divider" /><br />
        <p>Assign XML Tools access to a user account. You can add access to your existing user account or you can create a new one.
        <br /><br />
        To create a new user account click on <b>Users</b>, and then click <b>New</b>:</p>
        <img class="screenshot" src="$imgdir/screen_users.jpg" alt="Click on Users and then click New" title="Click on Users and then click New" />
            <br clear="both" />
        <br /><hr class="divider" /><br />
        <p>Complete the form with all of the appropriate details such as username and password, etc.
        <br /><br />
        You must add XML Tools privileges with the "Access Level" dropdown and checkboxes as shown:</p>
        <img class="screenshot" src="$imgdir/screen_newuser.jpg" alt="Select XML Tools from the dropdown and add privileges with the checkboxes." title="Select XML Tools from the dropdown and add privileges with the checkboxes." />
            <br clear="both" />
        <br /><hr class="divider" /><br />
        <p>Now enter this user's credentials in the <a target="_blank" href="/wp-admin/admin.php?page=nexternal_menu">Nexternal Plugin Settings</a>.
        <br /><br />
        You must enter your store URL, your Nexternal account name (typically the last part of your store URL), and the username and password.
        <br /><br />
        For example, if you access your Nexternal OMS through the URL "https://nexternal.com/myexamplestore/", your account name is "<b>examplestore</b>".
        </p>
        <img class="screenshot" src="$imgdir/screen_addusers.jpg" alt="Enter this user's credentials in the plugin settings." title="Enter this user's credentials in the plugin settings." />
            <br clear="both" />
            <br /><hr class="divider" /><br />
        <p><h3 id="pluginusage">Using The Plugin:</h3></p>
        <p>After your credentials are accepted on the <a target="_blank" href='/wp-admin/admin.php?page=nexternal_menu'>plugin settings page</a>, you can use the plugin to show your products on your WordPress website.
        <br /><br />
        Add products to pages and posts from the visual editor tab in WordPress by using the button with the Nexternal logo.</p>
        <img class="screenshot" src="$imgdir/screen_editor.jpg" alt="Add products to pages and posts from the visual editor tab in WordPress by using the 'N' button and shortcode generator." title="Add products to pages and posts from the visual editor tab in WordPress by using the 'N' button and shortcode generator." />
            <br clear="both" />
        <br /><hr class="divider" /><br />
        <p>Begin by entering your product name in the top box. Highlight the product you wish to add and then click <b>Add</b>.</p>
        <img class="screenshot" src="$imgdir/screen_add_product.jpg" alt="Begin by entering your product name in the top box. Highlight the product you wish to add and then click Add." title="Begin by entering your product name in the top box. Highlight the product you wish to add and then click Add." />
                    <br clear="both" />
                    <br /><hr class="divider" /><br />
        <p>You can use this plugin to show a single product, multiple products, or multiple products in a carousel. If you want to show multiple products, repeat the previous step until your product list is complete. If you added them in the wrong order, you can <b>drag and drop</b> to reorder them.</p>
        <img class="screenshot" src="$imgdir/screen_drag_drop.jpg" alt="Drag and Drop to re-order items in your list." title="Drag and Drop to re-order items in your list." />
                    <br clear="both" />
                    <br /><hr class="divider" /><br />
        <p>Select the <b>Display Options</b> that match your desired output. The Carousel Display is available only on the New Layout and is responsive and touch/swipe friendly. The "Old Style" option is provided only for legacy support; we don't recomment using it at all. Old shortcodes you have created using previous versions of this software should be updated by you to the "New Layout" style as we cannot guarantee that any future versions of this plugin will support the older styles.</p>
        <img class="screenshot" src="$imgdir/screen_layout.jpg" alt="Select New Layout and Carousel Display." title="Select New Layout and Carousel Display." />
                    <br clear="both" />
                    <br /><hr class="divider" /><br />
        <p>Select the <b>Product Info</b> options and <b>drag and drop</b> them to the order you wish for them to be displayed. For example, if you want the product Name at the top, drag it above Image.</p>
        <img class="screenshot" src="$imgdir/screen_order.jpg" alt="Drag and Drop to re-order the product info attributes in your list." title="Drag and Drop to re-order the product info attributes in your list." />
                    <br clear="both" />
                    <br /><hr class="divider" /><br />
        <p>After checking the desired options, click <b>Insert</b> to generate the shortcode and insert it into your post.</p>
        <img class="screenshot" src="$imgdir/screen_insert.jpg" alt="Click Insert to generate the shortcode" title="Click Insert to generate the shortcode" />
            <br clear="both" />
</div>

HTML;
    echo $html;
}

/* Add a 'Settings' link in the plugins page */
function nexternal_settings_link($links) {
  $settings_link = '<a href="'.admin_url( 'admin.php?page=nexternal_menu').'">Settings</a>';
  $links[] = $settings_link;
  return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'nexternal_settings_link' );



/* Display a notice that can be dismissed */
add_action('admin_notices', 'nexternal_admin_notice');
function nexternal_admin_notice() {
    //global $current_user ;
    //$user_id = $current_user->ID;

    add_thickbox();

    $data = get_option('nexternal');
    $userName = $data['userName'];
    if(!empty($_POST['nexternal_username'])) {
        $userName = $_POST['nexternal_username'];
    }


    /* Check that the user hasn't already clicked to ignore the message */
    if ( !$userName) {
        echo '<div class="error"><p>';
        echo __('Nexternal has updated the XMLTools API and your credentials need to be re-authenticated via the updated method.  Please <a href="'.admin_url( 'admin.php?page=nexternal_instructions').'">review the instructions</a> and then <a href="'.admin_url( 'admin.php?page=nexternal_menu').'">re-link your account</a>.');
        echo "</p></div>";
    }
}
add_action('admin_init', 'nexternal_msg_ignore');
function nexternal_msg_ignore() {
    $data = get_option('nexternal');
    /* If user clicks to ignore the notice, add that to their user meta */
    if ( isset($_GET['nexternal_msg_ignore']) && '0' == $_GET['nexternal_msg_ignore'] ) {
        $data['nexternal_ignore_notice'] = 'true';
        update_option('nexternal', $data);
    }
}

?>