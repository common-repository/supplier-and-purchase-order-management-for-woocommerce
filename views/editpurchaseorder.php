<?php 

global $wpdb;
$registration_date = date("Y-m-d H:i:s", time());
$supplierTable = $wpdb->prefix . 'suppliers';
$poTable = $wpdb->prefix . 'posuppliers';
$poProductsupplierTable = $wpdb->prefix . 'poproductsuppliers';
$msg = '';

$action = sanitize_text_field(isset($_GET['action']) ? trim($_GET['action']) : "");
$id = sanitize_text_field(isset($_GET['id']) ? intval($_GET['id']) : "");
$poid = sanitize_text_field(isset($_GET['poid']) ? intval($_GET['poid']) : "");
$pochangessaved = "no";



if(!empty($action)){
    if($action == "saveeditedpurchaseorder"){

        $productsarray = sanitize_text_field($_POST['editedproductdata']);
        $productsarray = str_replace('\"','"',$productsarray);;      
        $productsarraydecoded = json_decode($productsarray, true);
             
        //delete existing products
        $allsavedproducts = $wpdb->get_results(

            $wpdb->prepare(
                        "SELECT * from $poProductsupplierTable WHERE poidfk = %d",$poid
            ),ARRAY_A
        );
        //delete product one by one
        foreach ($allsavedproducts as $producttodelete) {
            $wpdb->delete($poProductsupplierTable,array(
                "id" => $producttodelete['id']
            ));
        }

        //add edited products
        foreach($productsarraydecoded as $productdata) {
            if($productdata['quantity'] > 0 && $productdata['quantity'] != ""){


                $image = NULL;
                
                //make image small and then save to db
                if(!empty($productdata['imgurl']) && $productdata['imgurl'] != ""){
                  
                    $image = spom_scaleImageFileToBlob($productdata['imgurl']);
                    $base64 = base64_encode($image);

                }
                              
                $wpdb->insert($poProductsupplierTable,array(
                    "poidfk"=>$poid,
                    "productid"=>$productdata['id'],
                    "productname"=>$productdata['name'],
                    "quantityordered"=>$productdata['quantity'],
                    "price"=>number_format((double)$productdata['price'], 2, '.', ''),
                    "tax"=>number_format((double)$productdata['tax'], 2, '.', ''),
                    "productimg"=>addslashes($base64),
                    "created_at"=>$registration_date
                ));
                if($wpdb->insert_id > 0){
                    $msg = "<div class='updated update'>".__( 'PO saved successfully', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";


                }else{
             
                    $msg = "<div style='color:red'>".__( 'ERROR - products could not be saved', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";
                }


            }
        }

        //update po in table
        $wpdb->update($poTable,array(
            "nota"=>$productsarraydecoded[0]['nota'],
            "lastupdate_at"=>$registration_date
        ), array(
           "id" => $poid
        ));



        //get full po data
        $po_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * from $poTable WHERE id = %d",$poid
            ),ARRAY_A
        );


        //get supplier data
        $row_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * from $supplierTable WHERE id = %d",$po_details['supplieridfk'] 
            ),ARRAY_A
        );

           

        $pochangessaved = "yes";
        
        
            unset ($_COOKIE['prodsarrayforpo']);


    }
    if($action == "edit"){
        //get po data
        $po_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * from $poTable WHERE id = %d",$poid
            ),ARRAY_A
        );

        //get supplier data
        $row_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * from $supplierTable WHERE id = %d",$po_details['supplieridfk'] 
            ),ARRAY_A
        );

    }
}



function spom_scaleImageFileToBlob($file) {

    $source_pic = $file;
    $max_width = 100;
    $max_height = 100;

    list($width, $height, $image_type) = getimagesize($file);

    switch ($image_type)
    {
        case 1: $src = imagecreatefromgif($file); break;
        case 2: $src = imagecreatefromjpeg($file);  break;
        case 3: $src = imagecreatefrompng($file); break;
        default: return '';  break;
    }

    $x_ratio = $max_width / $width;
    $y_ratio = $max_height / $height;

    if( ($width <= $max_width) && ($height <= $max_height) ){
        $tn_width = $width;
        $tn_height = $height;
        }elseif (($x_ratio * $height) < $max_height){
            $tn_height = ceil($x_ratio * $height);
            $tn_width = $max_width;
        }else{
            $tn_width = ceil($y_ratio * $width);
            $tn_height = $max_height;
    }

    $tmp = imagecreatetruecolor($tn_width,$tn_height);

    /* Check if this image is PNG or GIF to preserve its transparency */
    if(($image_type == 1) OR ($image_type==3))
    {
        imagealphablending($tmp, false);
        imagesavealpha($tmp,true);
        $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
        imagefilledrectangle($tmp, 0, 0, $tn_width, $tn_height, $transparent);
    }
    imagecopyresampled($tmp,$src,0,0,0,0,$tn_width, $tn_height,$width,$height);

    /*
     * imageXXX() has only two options, save as a file, or send to the browser.
     * It does not provide you the oppurtunity to manipulate the final GIF/JPG/PNG file stream
     * So I start the output buffering, use imageXXX() to output the data stream to the browser,
     * get the contents of the stream, and use clean to silently discard the buffered contents.
     */
    ob_start();

    switch ($image_type)
    {
        case 1: imagegif($tmp); break;
        case 2: imagejpeg($tmp, NULL, 80);  break; // best quality
        case 3: imagepng($tmp, NULL, 9); break; // 1 is FASTEST but produces larger files, 9 provides the best compression (smallest files) but takes a long time to compress
        default: echo ''; break;
    }

    $final_image = ob_get_contents();

    ob_end_clean();

    return $final_image;
}



echo '<p>'.wp_kses_post($msg).'</p>';
echo "<h1>".__( 'Edit Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' )." --> ".esc_html($row_details['suppliername']).'</h1>';
echo "<h3>".__( 'Please enter the data below. The fields price and tax are optional. Products without quantity will not be part of the PO.', 'supplier-and-purchase-order-management-for-woocommerce' )."</h3>";

if($pochangessaved == "yes"){
    echo '<a href="/wp-admin/admin.php?page=supplier-plugin-perfil&id='.esc_html($row_details['id']).'" class="button-primary">'.__( 'Go back to supplier profile', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/><br/>';
}
else{
    echo '<a href="#" class="button-primary btncreatePO" onclick="spom_actualizarPO();">'.__( 'Save changes to Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/><br/>';
}


echo '<textarea rows="3" class="form-group" style="min-width:50%" id="ponota" maxlength=1000 placeholder="'.__( 'Here you can enter a note that you would like to go with your PO', 'supplier-and-purchase-order-management-for-woocommerce' ).'">'.esc_html($po_details['nota']).'</textarea><br/>';
echo '<a href="#" class="button-secondary" style="margin-right:10px;margin-top:10px" onclick="spom_showAllProducts();">'.__( 'Show all products of this supplier', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><a href="#" class="button-secondary" style="margin-right:10px;margin-top:10px" onclick="spom_hideEmptyProducts();">'.__( 'Hide all products of this supplier without quantity', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a>';



?>
<form id="productdatasubmitform" method="post" name="form" action="admin.php?page=supplier-plugin-editpo&action=saveeditedpurchaseorder&poid=<?php echo esc_html($poid)?>" hidden>
        <input type="text" id="productdatainput" name="editedproductdata">
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

                echo '<tr class="productrow">';
                echo '<td class="column-columnname prid">'.esc_html($productid).'</td>';
                echo '<td class="column-columnname"><a target="_blank" href="'.esc_html($productdata[2]).'" style="margin-right:10px;margin-left:10px"><img class="prodimg" width="40px" src="'.esc_html($productdata[2]).'"/></a></td>';
                echo '<td class="column-columnname sku">'.esc_html($sku).'</td>';
                echo '<td class="column-columnname productname">'.esc_html(urldecode($productdata[1])).'</td>';

                //get product data
                $singleproducts = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * from $poProductsupplierTable WHERE poidfk = %d AND productid = %d",$poid,$productid
                    ),ARRAY_A
                );

                echo '<td class="column-columnname"><input type="number" min="0" class="quantity" value="'.esc_html(@$singleproducts['quantityordered']).'"/></td>';
                echo '<td class="column-columnname"><input type="number" min="0" step="any" class="price" value="'.esc_html(@$singleproducts['price']).'"/></td>';
                echo '<td class="column-columnname"><input type="number" min="0" step="any" class="tax" value="'.esc_html(@$singleproducts['tax']).'"/></td>';
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
                            // $xpath = new DOMXPath(@DOMDocument::loadHTML($html));
                            $doc = new DOMDocument();
                            $doc->loadHTML($html);
                            $xpath = new DOMXPath($doc);
                            $img_src = $xpath->evaluate("string(//img/@src)");

                            echo '<tr class="productrow">';
                            echo '<td class="column-columnname prid">'.esc_html($productid).'</td>';
                            echo '<td class="column-columnname"><a target="_blank" href="'.esc_html($img_src).'" style="margin-right:10px;margin-left:10px"><img class="prodimg" width="40px" src="'.esc_html($img_src).'"/></a></td>';
                            echo '<td class="column-columnname sku">'.esc_html($product->get_sku()).'</td>';
                            echo '<td class="column-columnname productname">'.esc_html($product->get_name()).'</td>';
                            //get product data
                            $singleproducts = $wpdb->get_row(
                                $wpdb->prepare(
                                    "SELECT * from $poProductsupplierTable WHERE poidfk = %d AND productid = %d",$poid,$productid
                                ),ARRAY_A
                            );

                            echo '<td class="column-columnname"><input type="number" min="0" class="quantity" value="'.esc_html(@$singleproducts['quantityordered']).'"/></td>';
                            echo '<td class="column-columnname"><input type="number" min="0" step="any" class="price" value="'.esc_html(@$singleproducts['price']).'"/></td>';
                            echo '<td class="column-columnname"><input type="number" min="0" step="any" class="tax" value="'.esc_html(@$singleproducts['tax']).'"/></td>';
                            echo '</tr>';

                        }
                    }
                    catch(Exception $e){}
                }
                
            }

        }
    }


    ?>
    </tbody>
   </table>
<script>

 const allproducts = document.getElementsByClassName("productrow");

 jQuery(document).ready(function() {
    //hide all not ordered products
    const allproducts = document.getElementsByClassName("productrow");
        
        Array.from(allproducts).forEach((product) => {
            var quantityfield = product.getElementsByClassName("quantity")[0];
            if(quantityfield.value == 0 || quantityfield.value === "" || quantityfield.value.trim() === ""){
                jQuery(product).hide();
            }

        });
    
});

function spom_showAllProducts(){
        
        Array.from(allproducts).forEach((product) => {
     
                jQuery(product).show();

        });
    

        }

function spom_hideEmptyProducts(){
            Array.from(allproducts).forEach((product) => {
                    var quantityfield = product.getElementsByClassName("quantity")[0];
                    if(quantityfield.value == 0 || quantityfield.value === "" || quantityfield.value.trim() === ""){
                        jQuery(product).hide();
                    }

            });
}



function spom_actualizarPO(){
    
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
if($pochangessaved == "yes"){
    echo '<a href="/wp-admin/admin.php?page=supplier-plugin-perfil&id='.esc_html($row_details['id']).'" class="button-primary">'.__( 'Go back to supplier profile', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/><br/>';
}
else{
    echo '<br><a href="#" class="button-primary btncreatePO" onclick="spom_actualizarPO();">'.__( 'Save changes to Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a><br/>';
}


?>

