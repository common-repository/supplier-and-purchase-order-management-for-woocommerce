<?php 

global $wpdb;
$registration_date = date("Y-m-d H:i:s", time());
$supplierTable = $wpdb->prefix . 'suppliers';
$poTable = $wpdb->prefix . 'posuppliers';
$poProductsupplierTable = $wpdb->prefix . 'poproductsuppliers';
$action = sanitize_text_field(isset($_GET['action']) ? trim($_GET['action']) : "");
$poid = sanitize_text_field(isset($_GET['poid']) ? intval($_GET['poid']) : "");

$porow_details = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * from $poTable WHERE id = %d",$poid
    ),ARRAY_A
);

$supplierrow_details = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * from $supplierTable WHERE id = %d",$porow_details['supplieridfk']
    ),ARRAY_A
);

$allproducts = $wpdb->get_results(

    $wpdb->prepare(
                "SELECT * from $poProductsupplierTable WHERE poidfk = %d",$poid
    ),ARRAY_A
);
$msg = '';


if(!empty($action)){
    if($action == "dosendconf"){


        $htmlconftobesent = stripslashes($_POST['confdata']);
    
        //get emails
        $allemailsforpo = $supplierrow_details['email'];
        $emailrecipients = $supplierrow_details['email'];
        $searchForValue = ',';
        $subject = __( 'Delivery confirmation of Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' ).' PO-00'.esc_html($porow_details['id']);
        $message = $htmlconftobesent;

        if( strpos($allemailsforpo, $searchForValue) !== false ) {
            //commas found, maybe several email addresses
            $emailrecipients = "";
            $emailarray = explode(",",$allemailsforpo);
            foreach ($emailarray as $emailaddress) {
                $emailaddress = trim($emailaddress);
                if(filter_var($emailaddress, FILTER_VALIDATE_EMAIL)){
                    $emailrecipients = $emailrecipients.$emailaddress.', ';
                    $msg = '<div class="updated update">'.__( 'Email(s) successfully sent to', 'supplier-and-purchase-order-management-for-woocommerce' ).' '.$emailrecipients.'</div>';

                    }
                    else{
                    $msg = '<div style="color:red">'.__( 'One or more emails do not have the correct format', 'supplier-and-purchase-order-management-for-woocommerce' ).'</div>';
                }
            }

            spom_sendEmail($emailrecipients,$subject,$message);
            $wpdb->update($poTable,array(
                "confirmaciononreceivedsent"=>1
            ), array(
               "id" => $poid
            ));



        }
        else{
            //no commas en email, let's check if email correct format
            $emailrecipients = trim($emailrecipients);
            if(filter_var($emailrecipients, FILTER_VALIDATE_EMAIL)){
                // echo “Great! The Email Format is Valid! <br>”;
                spom_sendEmail($emailrecipients,$subject,$message);
                $msg = '<div class="updated update">'.__( 'Email(s) successfully sent to', 'supplier-and-purchase-order-management-for-woocommerce' ).' '.$emailrecipients.'</div>';
                
                $wpdb->update($poTable,array(
                    "confirmaciononreceivedsent"=>1
                ), array(
                   "id" => $poid
                ));


                }
                else{
                // echo “Sorry! Invalid Email Format! <br>”;
                $msg = '<div style="color:red">'.__( 'One or more emails do not have the correct format', 'supplier-and-purchase-order-management-for-woocommerce' ).'</div>';
            }


        }

        //send emails to store owner and shop managers
        $shopManager = get_users( 'role=shop_manager' );
        $siteAdmin = get_users( 'role=Administrator' );


        foreach ( $shopManager as $user ) 
        {
            spom_sendEmail($user->user_email,$subject,$message);
        }

        foreach ( $siteAdmin as $user2 ) 
        {
            spom_sendEmail($user2->user_email,$subject,$message);
        }


        
    }

}

function spom_sendEmail($recipient,$subject,$message){
    // Define a constant to use with html emails
    if (!defined('HTML_EMAIL_HEADERS')) define('HTML_EMAIL_HEADERS', array('Content-Type: text/html; charset=UTF-8'));

    // define("HTML_EMAIL_HEADERS", array('Content-Type: text/html; charset=UTF-8'));

  // Get woocommerce mailer from instance
  $mailer = WC()->mailer();

  // Create new WC_Email instance
  $wc_email = new WC_Email;
  
  if($mailer->send( $recipient, $subject, $message, HTML_EMAIL_HEADERS )){
    } 
  else{
    //echo $mailer->error()->message();
  }

 
}




$store_address     = WC()->countries->get_base_address();
$store_address_2   = WC()->countries->get_base_address_2();
$store_city        = WC()->countries->get_base_city();
$store_postcode    = WC()->countries->get_base_postcode();
$store_state       = WC()->countries->get_base_state();


?>

<script language=javascript>


var isworking = 1;

function send_spom_confPO(){

    if(isworking == 1){
        isworking++;
        <?php
        add_filter( 'admin_footer', '__return_empty_string', 11 ); 
        add_filter( 'admin_footer_text', '__return_empty_string', 11 ); 
        add_filter( 'update_footer', '__return_empty_string', 11 );
        ?>

        var linkbutton = document.getElementById("sendpobutton").textContent = "<?php _e( 'Please wait', 'supplier-and-purchase-order-management-for-woocommerce' );?>...";

        var getpanel = document.getElementById("toprint").outerHTML;
        
        var jHtmlObject = jQuery(getpanel);
        jHtmlObject.find("#wp-auth-check-wrap").remove();
        jHtmlObject.find("#wpfooter").remove();
        jHtmlObject.find('script').remove();
        var newHtml = jHtmlObject.html();
        

        var input = document.getElementById("confdata");
        input.value = newHtml.replace(/>\s+</g,'><');

        var form = document.getElementById("confirmationdatasubmitform");
        form.submit();
    }

}
</script>




<style>

.hidden{display: none;} 
#outer_wrapper > tbody > tr > td:first-child {display: none;}

</style>


<p><?php echo wp_kses_post($msg);?></p>

<div><h1 style="float:left"><?php _e( 'Send Confirmation to Supplier', 'supplier-and-purchase-order-management-for-woocommerce' );?> || PO-00<?php echo esc_html($poid) ?></h1> <a href="#" onclick="send_spom_confPO();" id="sendpobutton" class="button-secondary" style="float:left;margin-left:20px;margin-top:12px;"><?php _e( 'Send confirmation emails to the contacts below', 'supplier-and-purchase-order-management-for-woocommerce' );?></a><br/></div>
<div style="clear:both;"><h3> <?php echo esc_html($supplierrow_details['suppliername'])?></h3></div><div><?php _e( 'Email(s)/ Contacts', 'supplier-and-purchase-order-management-for-woocommerce' );?>: <?php echo esc_html($supplierrow_details['email'])?></div>
<form id="confirmationdatasubmitform" method="post" name="form" action="admin.php?page=supplier-plugin-sendconf&action=dosendconf&poid=<?php echo esc_html($poid)?>" hidden>
        <input type="text" id="confdata" name="confdata">
        <input type="submit" value="Submit">
</form>
<div id="toprint">
<?php echo wc_get_template('emails/email-header.php', array( 'email_heading' => @$email_heading ));?>

 <div style="background-color:Lavender;padding-left: 15px;padding-right: 15px;padding-top: 15px;">
    <h1>PO-00<?php echo esc_html($poid) ?></h1>
    <div style="float:right;font-weight: bold;"><?php $date = strtotime($porow_details['created_at']);echo esc_html(date('d/M/Y', $date));?></div>
    <div><?php echo get_bloginfo('name', 'display')?></div>
    <div><?php echo esc_html($store_address) ?></div>
    <div><?php echo esc_html($store_address_2) ?></div>
    <div><?php echo esc_html($store_city .' '. $store_postcode)?></div>
    <div><?php echo esc_html($store_state)?></div>
    <hr />
 </div>

  <div style="background-color:FloralWhite;padding-left: 15px;padding-right: 15px;padding-top: 1px;padding-bottom: 15px;">

  <h2><?php echo esc_html($supplierrow_details['suppliername'])?></h2>
  <div><?php echo esc_html($supplierrow_details['nombrecontacto'])?></div>
  <div><?php echo esc_html($supplierrow_details['email'])?></div>
  <div><?php echo esc_html($supplierrow_details['telefono']) ?></div>
  <div><?php echo esc_html($supplierrow_details['direccion']) ?></div>
  <div><?php echo esc_html($supplierrow_details['provincia'])?></div>
  <hr />
    
        <table id="tablepoproducts" class="wp-list-table widefat fixed striped table-view-list posts" cellspacing="0" style="padding:5px;padding-left:5px;padding-right:5px;margin-top:10px;">
        <thead>
        <tr>
            <th class="manage-column column-cb check-column" scope="col" style="max-width:40px"><b></b></th>
            <th class="manage-column column-cb check-column" scope="col" style="min-width:150px"><b><?php _e( 'Product', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
            <th class="manage-column column-cb check-column" scope="col" ><b><?php _e( 'Quantity', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
            <th class="manage-column column-cb check-column" scope="col" ><b><?php _e( 'Quantity received', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
            <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Price PU', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
            <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Tax PU', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
        </tr>
        </thead>
        <tbody>

        <?php   
        $totaltax = 0.00;
        $totalprices = 0.00;
        $totalcost = 0.00;

        if(count($allproducts) > 0){
            foreach($allproducts as $product){

                if($product['quantityreceived'] > $product['quantityordered'] || $product['quantityreceived'] < $product['quantityordered']){
                    if($product['price'] > 0){
                        $totalprices = $totalprices + ($product['quantityreceived'] * $product['price']);
                    }
                    if($product['tax'] > 0){
                        $totaltax = $totaltax + ($product['quantityreceived'] * $product['tax']);
                    }

                }
                else{
                    if($product['price'] > 0){
                        $totalprices = $totalprices + ($product['quantityordered'] * $product['price']);
                    }
                    if($product['tax'] > 0){
                        $totaltax = $totaltax + ($product['quantityordered'] * $product['tax']);
                    }
                }
               
                echo '<tr>';
                echo '<td class="column-columnname"><img style="max-width:50px" src="data:image/jpeg;base64,'.wp_kses_post($product['productimg']).'" />'.'</td>';

                echo '<td class="column-columnname">'.esc_html($product['productname']).'</td>';
                echo '<td class="column-columnname">'.esc_html($product['quantityordered']).'</td>';
                echo '<td class="column-columnname">'.esc_html($product['quantityreceived']).'</td>';
                echo '<td class="column-columnname">'.esc_html($product['price']).'</td>';
                echo '<td class="column-columnname">'.esc_html($product['tax']).'</td>';
                echo '</tr>';

            }
        
        }
      
            ?>
        
            </tbody>
        </table>
        <hr />
        <?php echo '<div style="float:right;text-decoration-line: underline;text-decoration-style: solid;">'.__( 'Tax', 'supplier-and-purchase-order-management-for-woocommerce' ).': '.esc_html($totaltax).'</div><br/>'?>
        <?php $totalcost=$totaltax+$totalprices; echo '<div style="float:right;font-weight:bold;text-decoration-line: underline;text-decoration-style: double;">'.__( 'Total', 'supplier-and-purchase-order-management-for-woocommerce' ).': '.esc_html($totalcost).'</div><br />'?>
        <hr />
        <div id="nota" style="margin-top:1em;"><?php _e( 'Note', 'supplier-and-purchase-order-management-for-woocommerce' );?>: <?php echo esc_html($porow_details['nota']) ?></div>
 </div>
 </div>
  


