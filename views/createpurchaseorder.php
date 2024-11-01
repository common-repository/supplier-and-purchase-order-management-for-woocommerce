<?php 

global $wpdb;
$registration_date = date("Y-m-d H:i:s", time());
$supplierTable = $wpdb->prefix . 'suppliers';
$poTable = $wpdb->prefix . 'posuppliers';
$msg = '';


$id = sanitize_text_field(isset($_GET['id']) ? intval($_GET['id']) : "");


    $row_details = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * from $supplierTable WHERE id = %d",$id
        ),ARRAY_A
    );



echo "<h1>".__( 'Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' )." --> ".esc_html($row_details['suppliername']).'</h1>';
echo "<h3>".__( 'Please enter the data below. The fields price and tax are optional. Products without quantity will not be part of the PO.', 'supplier-and-purchase-order-management-for-woocommerce' )."</h3>";


echo '<a href="#" class="button-primary btncreatePO" onclick="spom_createPO();">'.__( 'Create Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/><br/>';
echo '<textarea rows="3" class="form-group" style="min-width:50%" id="ponota" maxlength=1000 placeholder="'.__( 'Here you can enter a note that you would like to go with your PO', 'supplier-and-purchase-order-management-for-woocommerce' ).'"></textarea><br/>';


?>
    
<p style="color:red"><?php echo wp_kses_post($msg); ?></p>
<form id="productdatasubmitform" method="post" name="form" action="admin.php?page=supplier-plugin-perfil&action=savepurchaseorder&id=<?php echo esc_html($id)?>" hidden>
        <input type="text" id="productdatainput" name="productdata">
        <input type="submit" value="Submit">
</form>
<table id="tablecreatepo" class="wp-list-table widefat fixed striped table-view-list posts" cellspacing="0" style="padding:20px;padding-left:50px;padding-right:50px">
<thead>
<tr>
    <th class="manage-column column-cb check-column" scope="col" style="max-width:1em"><b>ID</b></th>
    <th class="manage-column column-cb check-column" scope="col" style="max-width:1em"><b><?php _e( 'Image', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <th class="manage-column column-cb check-column" scope="col"><b>SKU</b></th>
    <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Product', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Quantity', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Price', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Tax', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    <!-- <th class="manage-column column-cb check-column" scope="col"></th> -->
</tr>
</thead>
<tbody>

<?php    

if(trim($row_details['productids']) != ""){
        
    $productsarray = explode("|||",$row_details['productids']);
    if(count($productsarray) > 0){
        foreach($productsarray as $singleproduct){

            $productdata = explode(":::", $singleproduct);
            $productid = str_replace(",","",$productdata[0]);
            $product = wc_get_product($productid);
            $isVariation = "";
            $sku = "";

            if(!empty($product)){
                try{
                    $isVariation = $product->get_type(); //variable or simple
                    $sku = $product->get_sku();
                }
                catch(Exception $e){}
            }
            
            //if product has variations --> get variations
            if($isVariation == "simple"){

                echo '<tr>';
                echo '<td class="column-columnname prid">'.esc_html($productid).'</td>';
                echo '<td class="column-columnname"><a target="_blank" href="'.esc_html($productdata[2]).'" style="margin-right:10px;margin-left:10px"><img class="prodimg" width="40px" src="'.esc_html($productdata[2]).'"/></a></td>';
                echo '<td class="column-columnname sku">'.esc_html($sku).'</td>';
                echo '<td class="column-columnname productname">'.esc_html(urldecode($productdata[1])).'</td>';
                echo '<td class="column-columnname"><input type="number" min="0" class="quantity" value="'.esc_html($productdata[3]).'"/></td>';
                echo '<td class="column-columnname"><input type="number" min="0" step="any" class="price" value="'.esc_html($productdata[4]).'"/></td>';
                echo '<td class="column-columnname"><input type="number" min="0" step="any" class="tax" value="'.esc_html($productdata[5]).'"/></td>';
                // echo '<td class="column-columnname"></td>';
                echo '</tr>';

            }
            else{
                //it's variation
                if(!empty($product)){
                    try{
                        $current_products = $product->get_children();
                        //print_r($current_products);
                        foreach ($current_products as $productid) {
                            $product = wc_get_product($productid);
                            $html = $product->get_image();
                            //$xpath = new DOMXPath(@DOMDocument::loadHTML($html));
                            $doc = new DOMDocument();
                            $doc->loadHTML($html);
                            $xpath = new DOMXPath($doc);
                            $img_src = $xpath->evaluate("string(//img/@src)");

                            echo '<tr>';
                            echo '<td class="column-columnname prid">'.esc_html($productid).'</td>';
                            echo '<td class="column-columnname"><a target="_blank" href="'.esc_html($img_src).'" style="margin-right:10px;margin-left:10px"><img class="prodimg" width="40px" src="'.esc_html($img_src).'"/></a></td>';
                            echo '<td class="column-columnname sku">'.esc_html($product->get_sku()).'</td>';
                            echo '<td class="column-columnname productname">'.esc_html($product->get_name()).'</td>';
                            echo '<td class="column-columnname"><input type="number" min="0" class="quantity" value="'.esc_html($productdata[3]).'"/></td>';
                            echo '<td class="column-columnname"><input type="number" min="0" step="any" class="price" value="'.esc_html($productdata[4]).'"/></td>';
                            echo '<td class="column-columnname"><input type="number" min="0" step="any" class="tax" value="'.esc_html($productdata[5]).'"/></td>';
                            // echo '<td class="column-columnname"></td>';
                            echo '</tr>';

                        }
                    }
                    catch(Exception $e){}
                }
                
            }

           
            //save as ID::NAME::URL::MINCANTIDAD:::PRICE:::TAX|||
            // echo urldecode("$productdata[0]");
            // echo '<a target="_blank" href="'.$productdata[2].'" style="margin-right:10px;margin-left:10px"><img width="40px" src="'.$productdata[2].'"/></a>';
            // echo urldecode("$productdata[1]");
            
            //echo "<br>";
        }
    }


    ?>
    </tbody>
   </table>
<script>
function spom_createPO(){
    
    //deactivate btns
    var btns = document.getElementsByClassName("btncreatePO");
    Array.from(btns).forEach((btn) => {
        btn.style.display = "none";
    });
   
    //get all table rows tablecreatepo
    const alltablerows = document.getElementById("tablecreatepo").getElementsByTagName('tbody')[0].rows;
    var arrayallproducts = [];
    var notatext = document.getElementById("ponota").value;
    var firstnota = 1;

    Array.from(alltablerows).forEach((tablerow) => {
   
        //alert(product.outerHTML);
        //save as ID::NAME::URL::MINCANTIDAD:::PRICE:::TAX|||
        var cantidad = tablerow.getElementsByClassName("quantity")[0].value;
        if(!isNaN(cantidad) && cantidad > 0){
            //alert(cantidad);
            var id = tablerow.getElementsByClassName("prid")[0].innerHTML;
            var name = tablerow.getElementsByClassName("productname")[0].innerText.trim();
            var imgurl = tablerow.getElementsByClassName("prodimg")[0].src;
            var price = tablerow.getElementsByClassName("price")[0].value;
            var tax = tablerow.getElementsByClassName("tax")[0].value;

            if(firstnota == 1){
            var poarray = {"id":id,"name":name,"imgurl":imgurl,"quantity":cantidad,"price":price,"tax":tax, "nota":notatext};
            firstnota = 2;
            }
            else{
                var poarray = {"id":id,"name":name,"imgurl":imgurl,"quantity":cantidad,"price":price,"tax":tax, "nota":""};
            }
    
            arrayallproducts.push(poarray);
        }
    });

    if(arrayallproducts.length > 0){
        var input = document.getElementById("productdatainput");
        // input.value = getpanel.replace(/>\s+</g,'><');
        input.value = JSON.stringify(arrayallproducts);
        var form = document.getElementById("productdatasubmitform");
        form.submit();
    }
    else{
        alert("No hay productos elegidos");
        for (var i = 0; i < btns.length; i++) {
        btns[i].style.display = "block";
        }
    }   

   
}

</script>



   <?php
}
echo '<br><a href="#" class="button-primary btncreatePO" onclick="spom_createPO();">'.__( 'Create Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/>';


?>

