<?php 

global $wpdb;
$registration_date = date("Y-m-d H:i:s", time());
$supplierTable = $wpdb->prefix . 'suppliers';
$poTable = $wpdb->prefix . 'posuppliers';
$poProductsupplierTable = $wpdb->prefix . 'poproductsuppliers';

$msg = '';

$poid = sanitize_text_field(isset($_GET['id']) ? intval($_GET['id']) : "");

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


$store_address     = WC()->countries->get_base_address();
$store_address_2   = WC()->countries->get_base_address_2();
$store_city        = WC()->countries->get_base_city();
$store_postcode    = WC()->countries->get_base_postcode();
$store_state       = WC()->countries->get_base_state();


?>

<script language=javascript>
function spom_printPO(){
    <?php
    add_filter( 'admin_footer', '__return_empty_string', 11 ); 
    add_filter( 'admin_footer_text', '__return_empty_string', 11 ); 
    add_filter( 'update_footer', '__return_empty_string', 11 );
    ?>

    var getpanel = document.getElementById("wrapper");

    var jHtmlObject = jQuery(getpanel);
    jHtmlObject.find("#wp-auth-check-wrap").remove();
    jHtmlObject.find("#wpfooter").remove();
    jHtmlObject.find('script').remove();
    var newHtml = jHtmlObject.html();


    spom_renderMePO(jQuery('<div/>').append(jQuery(newHtml).clone()).html());

}

function spom_renderMePO(data) {

     var browser = (function (agent) {
        switch (true) {
            case agent.indexOf("edge") > -1: return "edge";
            case agent.indexOf("edg/") > -1: return "chromium based edge (dev or canary)"; // Match also / to avoid matching for the older Edge
            case agent.indexOf("opr") > -1 && !!window.opr: return "opera";
            case agent.indexOf("chrome") > -1 && !!window.chrome: return "chrome";
            case agent.indexOf("trident") > -1: return "ie";
            case agent.indexOf("firefox") > -1: return "firefox";
            case agent.indexOf("safari") > -1: return "safari";
            default: return "other";
        }
    })(window.navigator.userAgent.toLowerCase());

    // alert(browser);

    var mywindow = window.open('', 'invoice-box', 'height=1000,width=1000');
    mywindow.document.write('<html><head>');
    mywindow.document.write('<style>#tablepoproducts tr td:nth-child(1), #tablepoproducts th:nth-child(1) {display: none;} #tablepoproducts td {padding:10px} .hidden{display:none}</style>');
    mywindow.document.write('</head><body >');
    mywindow.document.write(data);
    mywindow.document.write('</body></html>');

    if(browser.includes('chrome')){
        console.log(browser);

        myWindow.focus();

        myWindow.onload = function() { 
        myWindow.print(); 
        setTimeout(() => {
            // Without the timeout, the window seems to close immediately.
            mywindow.close();    	
            }, 250);
        };

    }
    else{
        console.log(browser);
        setTimeout(function () {
        
        mywindow.print();
        mywindow.close();
        }, 1000)
    }

}


</script>




<style>

#tablepoproducts tr td:nth-child(1), #tablepoproducts th:nth-child(1) {display: none;}
#outer_wrapper > tbody > tr > td:first-child {display: none;}

</style>


<div><h1 style="float:left">PO-00<?php echo esc_html($poid) ?></h1> <a href="#" class="button-secondary" style="float:left;margin-left:20px;margin-top:12px;" onclick="spom_printPO();"><?php _e( 'Print / save Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' );?></a><p style="padding-top:1em"> **<?php _e( 'A new window will open as well as a dialog to print/save. On some browsers the dialog might not open, meaning you can either press the buttons control+p (on mac command+p) to open the print/save as pdf dialog or right-click and choose print.', 'supplier-and-purchase-order-management-for-woocommerce' );?></p><br/></div>
<div id="toprint">
<?php 
$email_heading = "";
echo wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );?>

 <div style="background-color:Lavender;padding-left: 15px;padding-right: 15px;padding-top: 15px;">
    <h1>PO-00<?php echo esc_html($poid) ?></h1>
    <div style="float:right;font-weight: bold;"><?php $date = esc_html(strtotime($porow_details['created_at']));echo esc_html(date('d/M/Y', $date));?></div>
    <div><?php echo get_bloginfo( 'name', 'display' )?></div>
    <div><?php echo esc_html($store_address )?></div>
    <div><?php echo esc_html($store_address_2)?></div>
    <div><?php echo esc_html($store_city) .' '. esc_html($store_postcode) ?></div>
    <div><?php echo esc_html($store_state) ?></div>
    <hr />
 </div>

  <div style="background-color:FloralWhite;padding-left: 15px;padding-right: 15px;padding-top: 1px;padding-bottom: 15px;">

  <h2><?php echo esc_html($supplierrow_details['suppliername']) ?></h2>
  <div><?php echo esc_html($supplierrow_details['nombrecontacto']) ?></div>
  <div><?php echo esc_html($supplierrow_details['email']) ?></div>
  <div><?php echo esc_html($supplierrow_details['telefono']) ?></div>
  <div><?php echo esc_html($supplierrow_details['direccion']) ?></div>
  <div><?php echo esc_html($supplierrow_details['provincia']) ?></div>
  <hr />
    
        <table id="tablepoproducts" class="wp-list-table widefat fixed striped table-view-list posts" cellspacing="0" style="padding:5px;padding-left:5px;padding-right:5px;margin-top:10px">
        <thead>
        <tr>
            <?php if($porow_details['postatus'] == "recibido" ){
              
                echo '<th class="manage-column column-cb check-column" scope="col" style="max-width:2em;"><b>ID</b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col" style="max-width:40px"><b></b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col" style="min-width:150px"><b>'.__( 'Product', 'supplier-and-purchase-order-management-for-woocommerce' ).'</b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col" ><b>'.__( 'Quantity', 'supplier-and-purchase-order-management-for-woocommerce' ).'</b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col" ><b>'.__( 'Quantity received', 'supplier-and-purchase-order-management-for-woocommerce' ).'</b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col"><b>'.__( 'Price PU', 'supplier-and-purchase-order-management-for-woocommerce' ).'</b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col">'.__( 'Tax PU', 'supplier-and-purchase-order-management-for-woocommerce' ).'</b></th>'; 

            }
            else{
                echo '<th class="manage-column column-cb check-column" scope="col" style="max-width:2em;"><b>ID</b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col" style="max-width:100px"><b></b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col" style="min-width:150px"><b>'.__( 'Product', 'supplier-and-purchase-order-management-for-woocommerce' ).'</b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col" ><b>'.__( 'Quantity', 'supplier-and-purchase-order-management-for-woocommerce' ).'</b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col"><b>'.__( 'Price PU', 'supplier-and-purchase-order-management-for-woocommerce' ).'</b></th>';
                echo '<th class="manage-column column-cb check-column" scope="col"><b>'.__( 'Tax PU', 'supplier-and-purchase-order-management-for-woocommerce' ).'</b></th>';
            }
    
            ?>
        
        </tr>
        </thead>
        <tbody>

        <?php   
        $totaltax = 0.00;
        $totalprices = 0.00;
        $totalcost = 0.00;

        if(count($allproducts) > 0){
            foreach($allproducts as $product){

                if($porow_details['postatus'] == "recibido" ){
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
                echo '<td class="column-columnname">'.esc_html($product['productid']).'</td>';
                if($porow_details['postatus'] == "recibido" ){
                    echo '<td class="column-columnname"><img style="max-width:50px" src="data:image/jpeg;base64,'.wp_kses_post($product['productimg']).'" />'.'</td>';
                    }
                    else{
                    echo '<td class="column-columnname"><img style="max-width:100px" src="data:image/jpeg;base64,'.wp_kses_post($product['productimg']).'" />'.'</td>';
                    }
                echo '<td class="column-columnname">'.esc_html($product['productname']).'</td>';
                echo '<td class="column-columnname">'.esc_html($product['quantityordered']).'</td>';
                if($porow_details['postatus'] == "recibido" ){
                    echo '<td class="column-columnname">'.esc_html($product['quantityreceived']).'</td>';
                }
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
  


