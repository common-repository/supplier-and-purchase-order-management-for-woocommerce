<?php 

global $wpdb;
$registration_date = date("Y-m-d H:i:s", time());
$supplierTable = $wpdb->prefix . 'suppliers';
$poTable = $wpdb->prefix . 'posuppliers';
$poProductsupplierTable = $wpdb->prefix . 'poproductsuppliers';
$msg = '';

$action = sanitize_text_field(isset($_GET['action']) ? trim($_GET['action']) : "");
$poid = sanitize_text_field(isset($_GET['poid']) ? intval($_GET['poid']) : "");

$quantitiesreceivedrecorded= "";



if(!empty($action)){
    if($action == "receivepo"){

        //get full po data
        $po_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * from $poTable WHERE id = %d",$poid
            ),ARRAY_A
        );

        //get all products 
        $allpoproducts = $wpdb->get_results(

            $wpdb->prepare(
                        "SELECT * from $poProductsupplierTable WHERE poidfk = %d",$poid
            ),ARRAY_A
        );

        //get supplier data
        
        $row_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * from $supplierTable WHERE id = %d",$po_details['supplieridfk'] 
            ),ARRAY_A
        );      


    }
    if($action == "savereceivedquantities"){

        $productsarray = wp_kses_post($_COOKIE['prodsarrayforpo']);
        $productsarray = str_replace('\"','"',$productsarray);      
        $productsarraydecoded = json_decode($productsarray, true);

        foreach($productsarraydecoded as $productdata) {
            if($productdata['quantityreceived'] > 0 && $productdata['quantityreceived'] != ""){

                //update products
                $wpdb->update($poProductsupplierTable,array(
                    "quantityreceived"=>$productdata['quantityreceived']
                ), array(
                "poidfk" => $poid,
                "productid" => $productdata['id']
                ));
                $msg = "<div class='updated update'>".__( 'Received product quantities registered successfully', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";
                $quantitiesreceivedrecorded = "yes";

            }


            //get full po data
            $po_details = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * from $poTable WHERE id = %d",$poid
                ),ARRAY_A
            );
    
            //get all products 
            $allpoproducts = $wpdb->get_results(
    
                $wpdb->prepare(
                            "SELECT * from $poProductsupplierTable WHERE poidfk = %d",$poid
                ),ARRAY_A
            );
    
            //get supplier data
            
            $row_details = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * from $supplierTable WHERE id = %d",$po_details['supplieridfk'] 
                ),ARRAY_A
            );   


        unset ($_COOKIE['prodsarrayforpo']);

        }

    }

}



echo '<p>'.wp_kses_post($msg).'</p>';
echo "<h1>".__( 'Receive Delivery of Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' )." PO-00".esc_html($poid)." ".__( 'for', 'supplier-and-purchase-order-management-for-woocommerce' )." ".esc_html($row_details['suppliername']).'</h1>';
echo "<h3>".__( 'Please enter the quantities received', 'supplier-and-purchase-order-management-for-woocommerce' )."</h3>";

if($quantitiesreceivedrecorded == "yes"){
    echo '<a href="/wp-admin/admin.php?page=supplier-plugin-perfil&id='.esc_html($row_details['id']).'" class="button-primary">'.__( 'Return to supplier profile', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/><br/>';
}
else{
    echo '<a href="#" class="button-primary btncreatePO" onclick="receivePO();">'.__( 'Save quantities received', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/><br/>';
}
echo '<textarea rows="3" class="form-group" style="min-width:50%" id="ponota" disabled maxlength=1000 placeholder="'.__( 'Note', 'supplier-and-purchase-order-management-for-woocommerce' ).':">'.esc_html($po_details['nota']).'</textarea><br/>';

?>

<script>

function receivePO(){
    
    //deactivate btns
    var btns = document.getElementsByClassName("btncreatePO");
    Array.from(btns).forEach((btn) => {
        btn.style.display = "none";
    });
   
    //get all table rows tablecreatepo
    const alltablerows = document.getElementById("tablecreatepo").getElementsByTagName('tbody')[0].rows;
    var arrayallproducts = [];

    Array.from(alltablerows).forEach((tablerow) => {
   
        //alert(product.outerHTML);
        //save as ID::NAME::URL::MINCANTIDAD:::PRICE:::TAX|||
        var cantidad = tablerow.getElementsByClassName("quantityreceived")[0].value;
        if(!isNaN(cantidad) && cantidad > 0){
            //alert(cantidad);
            var id = tablerow.getElementsByClassName("prid")[0].innerHTML;
            var tax = tablerow.getElementsByClassName("quantityreceived")[0].value;
            var name = tablerow.getElementsByClassName("productname")[0].innerHTML;

            var poarray = {"id":id,"name":name,"quantityreceived":cantidad};
    
            arrayallproducts.push(poarray);
        }
    });

    if(arrayallproducts.length > 0){
        var expires;
            var days = 1;
            if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
             expires = "; expires=" + date.toGMTString();
             }
             else {
            expires = "";
            }
            document.cookie = escape('prodsarrayforpo') + "=" + JSON.stringify(arrayallproducts) + expires + "; path=/";

            //alert(arrayallproducts);
            window.location = 'admin.php?page=supplier-plugin-receivepo&action=savereceivedquantities&poid=' + <?php echo esc_html($poid)?>;
    }
    else{
        alert("<?php _e( 'There are no registered products', 'supplier-and-purchase-order-management-for-woocommerce' );?>");
        Array.from(btns).forEach((btn) => {
        btn.style.display = "block";
        btn.style.width = "150px";
        });

    }   

   
}

</script>
    
<table id="tablecreatepo" class="wp-list-table widefat fixed striped table-view-list posts" cellspacing="0" style="padding:20px;padding-left:50px;padding-right:50px">
<thead>
<tr>
    <th class="manage-column column-cb check-column" scope="col" style="max-width:0.8em"><b>ID</b></th>
    <th class="manage-column column-cb check-column" scope="col" style="max-width:1em"><b><?php _e( 'Image', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <th class="manage-column column-cb check-column" scope="col" style="max-width:1em"><b>SKU</b></th>
    <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Product', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <th class="manage-column column-cb check-column" scope="col" style="max-width:1em"><b><?php _e( 'Quantity', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Quantity received', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Price', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Tax', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
</tr>
</thead>
<tbody>

<?php    
if(count($allpoproducts) > 0){
            
    foreach($allpoproducts as $singleproduct){

        $productid = $singleproduct['productid'];
        $isVariation = "";
        $sku = "";
        $imgurl = "";
        $product = "";

        
        try{
            $product = wc_get_product($productid);
            $isVariation = $product->get_type(); //variable or simple
            $sku = $product->get_sku();
            $imgurl = get_the_post_thumbnail_url( $productid, 'full' );
        }
        catch(Exception $e){}

        echo '<tr class="productrow">';
        echo '<td class="column-columnname prid">'.esc_html($productid).'</td>';
        echo '<td class="column-columnname"><a target="_blank" href="'.esc_html($imgurl).'" style="margin-right:10px;margin-left:10px"><img class="prodimg" width="40px" src="data:image/jpeg;base64,'.esc_html($singleproduct['productimg']).'"/></a></td>';
        echo '<td class="column-columnname sku">'.esc_html($sku).'</td>';
        echo '<td class="column-columnname productname">'.esc_html($singleproduct['productname']).'</td>';
        echo '<td class="column-columnname"><input type="number" min="0" disabled class="quantity" value="'.esc_html($singleproduct['quantityordered']).'"/></td>';
        echo '<td class="column-columnname"><input type="number" min="0" class="quantityreceived" placeholder="'.__( 'Quantity received', 'supplier-and-purchase-order-management-for-woocommerce' ).'" value="'.esc_html($singleproduct['quantityreceived']).'"/></td>';
        echo '<td class="column-columnname"><input type="number" min="0" disabled step="any" class="price" value="'.esc_html($singleproduct['price']).'"/></td>';
        echo '<td class="column-columnname"><input type="number" min="0" disabled step="any" class="tax" value="'.esc_html($singleproduct['tax']).'"/></td>';
        echo '</tr>';


    }
  
}
?>
</tbody>
</table>
<?php
if($quantitiesreceivedrecorded == "yes"){
    echo '<a href="/wp-admin/admin.php?page=supplier-plugin-perfil&id='.esc_html($row_details['id']).'" class="button-primary">'.__( 'Return to supplier profile', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/><br/>';
}
else{
    echo '<br/><a href="#" class="button-primary btncreatePO" onclick="receivePO();">'.__( 'Save quantities received', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/>';
}
?>
