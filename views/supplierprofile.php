<?php 

global $wpdb;
$registration_date = date("Y-m-d H:i:s", time());
$supplierTable = $wpdb->prefix . 'suppliers';
$poProductsupplierTable = $wpdb->prefix . 'poproductsuppliers';
$poTable = $wpdb->prefix . 'posuppliers';
$msg = '';

$action = sanitize_text_field(isset($_GET['action']) ? trim($_GET['action']) : "");
$id = sanitize_text_field(isset($_GET['id']) ? intval($_GET['id']) : "");
$poid = sanitize_text_field(isset($_GET['poid']) ? intval($_GET['poid']) : "");
$newpostatus = sanitize_text_field(isset($_GET['newpostatus']) ? trim($_GET['newpostatus']) : "");

$row_details = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * from $supplierTable WHERE id = %d",$id
    ),ARRAY_A
);

$allpurchaseorders = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * from $poTable WHERE supplieridfk = %d ORDER BY created_at DESC",$id
    ),ARRAY_A
);


if(!empty($action)){
    //check what action
    if($action == "changepostatus"){

        if($newpostatus != ""){
            //update po status to
            if($newpostatus == "listapararecibir"){$newpostatus = "lista para recibir";}
            
            //get po and update status and date
            $wpdb->update($poTable,array(
                "postatus"=>$newpostatus,
                "lastupdate_at"=>$registration_date
            ), array(
               "id" => $poid
            ));

            $podetails = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * from $poTable WHERE id = %d",$poid
                ),ARRAY_A
            );
    
            $row_details = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * from $supplierTable WHERE id = %d",$podetails['supplieridfk']
                ),ARRAY_A
            );

            $allpurchaseorders = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * from $poTable WHERE supplieridfk = %d ORDER BY created_at DESC",$podetails['supplieridfk']
                ),ARRAY_A
            );
            
    
            $msg = "<div class='updated update'>".__( 'PO status successfully updated', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";

        }
    }
    if($action == "saveproducts"){

        $productsarray = wp_kses_post($_POST['productdata']);

        // echo $productsarray;

        $wpdb->update($supplierTable,array(
            "productids"=>$productsarray
        ), array(
           "id" => $id
        ));

        $row_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * from $supplierTable WHERE id = %d",$id
            ),ARRAY_A
        );

        $msg = "<div class='updated update'>".__( 'supplier successfully updated', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";


    }
    if($action == "deletepo"){
          
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

        //get po details
        $wpdb->delete($poTable,array(
            "id" => $poid
        ));


        $allpurchaseorders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from $poTable WHERE supplieridfk = %d ORDER BY created_at DESC",$id
            ),ARRAY_A
        );


        $msg = "<div class='updated update'>".__( 'PO successfully deleted', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";

    }
    if($action == "savepurchaseorder"){
        
        $productsarray = wp_kses_post($_POST['productdata']);

        //check if product data empty
        if(!empty($productsarray)){
            $productsarray = str_replace('\"','"',$productsarray);;      
            $productsarraydecoded = json_decode($productsarray, true);
             
            //create po in table
            $poTable = $wpdb->prefix . 'posuppliers';
            $wpdb->insert($poTable,array(
                "supplieridfk"=>$id,
                "postatus"=>"borrador",
                "nota"=>$productsarraydecoded[0]['nota'],
                "created_at"=>$registration_date
            ));
            if($wpdb->insert_id > 0){
                $msg = "<div class='updated update'>".__( 'PO successfully created', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";
    
                $poid = $wpdb->insert_id;
    
                //create each product in po product table
                $poProductsupplierTable = $wpdb->prefix . 'poproductsuppliers';
                
                foreach($productsarraydecoded as $productdata) {
                    if($productdata['quantity'] > 0){
    
    
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
                        
                            $msg = "<div class='updated update'>".__( 'product successfully saved', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";
                            $msg = "<div class='updated update'>".__( 'PO successfully created', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";
    
                        }else{
                            $msg = "<div style='color:red'>".__( 'ERROR - product could not be saved', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";
    
                        }
    
    
                    }
                }
    
    
    
            }
            else{
                $msg = "<div style='color:red'>".__( 'ERROR - PO could not be created', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";
    
            }
        }
        
        

    
        $allpurchaseorders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from $poTable WHERE supplieridfk = %d ORDER BY created_at DESC",$id
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

?>

<script language=javascript>
function spom_confirmDeleteSuppl()
{
var res;
res = confirm('<?php _e( 'Are you sure that you want to delete the supplier and all his purchase orders?', 'supplier-and-purchase-order-management-for-woocommerce' );?>');
if (res==false)
 return false;
 
res = confirm('<?php _e( 'Are you absolutely sure you wanto to delete the supplier and all his PO? This action can NOT be reversed.', 'supplier-and-purchase-order-management-for-woocommerce' );?>');
if (res==false)
 return false;
return true;
}

var globalTimeout = null;  

function spom_filterproducts(){
  if(globalTimeout != null) clearTimeout(globalTimeout);  
  globalTimeout =setTimeout(spom_SearchFunc,1000);  
}

function spom_SearchFunc(){  

  globalTimeout = null;  
  
  var tosearchfor = document.getElementById('productfilterinput').value;

  //get all products
   const allproducts = document.getElementsByClassName("productrow");

  if(tosearchfor.trim() == ""){

    Array.from(allproducts).forEach((product) => {

        product.style.display = "block"; 
    });

  }
  else{

    Array.from(allproducts).forEach((product) => {
        var linkwithtexttobesearched = product.querySelector(".productrow a");
        linkwithtexttobesearched = linkwithtexttobesearched.text;

        console.log(tosearchfor.toLowerCase());
       
        if(linkwithtexttobesearched.toLowerCase().indexOf(tosearchfor.toLowerCase()) !== -1){
            product.style.display = "block";
            product.previousElementSibling.style.display = "block";
            product.nextElementSibling.style.display = "block";
        }
        else{
            product.style.display = "none";
            product.previousElementSibling.style.display = "none";
            product.nextElementSibling.style.display = "none";

        }
    });

  }
   
}

function spom_saveProviderProducts(){
    //get all products
  const allproducts = document.getElementsByClassName("productrow");

        var arrayOfProdsToSend = [];

        Array.from(allproducts).forEach((product) => {

            var checkbox = product.getElementsByClassName("largerCheckbox")[0];
            if(checkbox.checked){

                String.fromHtmlEntities = function(string) {
                    return (string+"").replace(/&#\d+;/gm,function(s) {
                        return String.fromCharCode(s.match(/\d+/gm)[0]);
                    })
                };

                //alert(product.outerHTML);
                //save as ID::NAME::URL::MINCANTIDAD:::PRICE:::TAX|||
                var id = checkbox.value;
                var name = product.getElementsByClassName("imagelink")[0].text.trim();
                var imgurl = product.getElementsByClassName("primage")[0].src;
                var mincantidad = product.getElementsByClassName("minorderquantity")[0].value;
                var price = product.getElementsByClassName("priceperproduct")[0].value;
                var tax = product.getElementsByClassName("tax")[0].value;
                
                //alert(id + " " + name + " " + imgurl + " " +mincantidad + " " + price + " " +tax + " ");
                var datatopush = id + ":::" + encodeURI(name) + ":::" + imgurl + ":::" +mincantidad + ":::" + price + ":::" +tax + "|||";
                arrayOfProdsToSend.push(datatopush);
            }

        });


        var input = document.getElementById("productdatainput");
        input.value = arrayOfProdsToSend;
        var form = document.getElementById("productdatasubmitform");
        form.submit();
        
}

</script>

<script>

jQuery(document).ready(function() {
    // jQuery code goes here
    //getallcheckboxes
    var allcheckboxes = document.getElementsByClassName("largerCheckbox");

    //mark existing products
        //as ID::NAME::URL::MINCANTIDAD:::PRICE:::TAX|||
    var associatedproducts = decodeURI(<?php echo '"'.esc_html($row_details['productids']).'"' ?>); 
    var allproducts = associatedproducts.split("|||");

        allproducts.forEach(function(product){

            var productdetail = product.split(":::");

            var productid = productdetail[0].replace(",","").trim();
            var mincantidad = productdetail[3];
            var price = productdetail[4];
            var tax = productdetail[5];

            for (let checkbox of allcheckboxes) {
    
                    if(checkbox.value == productid){
                        checkbox.checked = true;

                        if(mincantidad != ""){
                            checkbox.parentNode.getElementsByClassName("minorderquantity")[0].value = mincantidad;
                        }
                        if(price != ""){
                            checkbox.parentNode.getElementsByClassName("priceperproduct")[0].value = price;
                        }
                        if(mincantidad != ""){
                            checkbox.parentNode.getElementsByClassName("tax")[0].value = tax;
                        }

                        break; 
                    }


            }       


        });


});


function spom_showmoretext(divid){

      
    var divtoexpand = document.getElementById("notetext" + divid);
    var textnota = divtoexpand.innerHTML;
    if(textnota.trim() == ""){
        return;
    }

    divtoexpand.style.height = "auto";

    divtoexpand.onclick = function() { divtoexpand.style.height = "40px"; };

}

function spom_changePOstatus(selectObject){

    var status = selectObject.value;  
    var poid = selectObject.className;
       
    if(status == ""){return;}
    else if(status == "recibido"){
        let confirmAction = confirm('<?php _e( 'Before changing the status to received, please make sure you have entered the quantities received in your PO. For that change the status to \"ready to receive\" and follow the link \"receive delivery\"', 'supplier-and-purchase-order-management-for-woocommerce' );?>');
        if (confirmAction) {
          //alert("Action successfully executed");
          spom_doChangeStatus(status);
        } else {
          //alert("Action canceled");
          return;
        }
    }
    else if(status == "listapararecibir"){
        let confirmAction = confirm('<?php _e( 'Make sure you have received an order confirmation from the supplier before setting this status.', 'supplier-and-purchase-order-management-for-woocommerce' );?>');
        if (confirmAction) {
          //alert("Action successfully executed");
          spom_doChangeStatus(status);
        } else {
          //alert("Action canceled");
          return;
        }
    }
    else if(status == "cancelado"){
        let confirmAction = confirm('<?php _e( 'Cancelled Purchase Orders can not be edited. Are you sure you want to cancel this PO?', 'supplier-and-purchase-order-management-for-woocommerce' );?>');
        if (confirmAction) {
          //alert("Action successfully executed");
          spom_doChangeStatus(status);
        } else {
          //alert("Action canceled");
          return;
        }
    }
    else{
        spom_doChangeStatus(status);
    }

    function spom_doChangeStatus(status){
        window.location = 'admin.php?page=supplier-plugin-perfil&action=changepostatus&newpostatus=' + status + '&poid=' + poid;
    }


}

var allmarked = "no";

function spom_toggleAllCheckmarks(){
    if(allmarked == "no"){
        jQuery(':checkbox').filter(':visible').each(function () {    this.checked = true; });
        allmarked = "yes";
    }
    else{
        jQuery(':checkbox').filter(':visible').each(function () {    this.checked = false; });
        allmarked = "no";
    }


}


</script>
<style>
        input{
            margin-left:10px;
        }
        .primage{
            margin-left:20px;
            margin-bottom:-10px;
        }
        .imagelink{
            margin-left:20px;
        }
        input.largerCheckbox {
        transform : scale(1.6);
        margin-left:0 !important;
        }

        .notaexcerpt{
            /* width:100px; */
            height:40px;
            display:block;
            /* border:1px solid red; */
            /* padding:10px; */
            overflow:hidden;
        }
        
</style>
<p><?php echo wp_kses_post($msg); ?></p>
<a class="button button-primary" href="admin.php?page=supplier-plugin"><?php echo __('List of all Suppliers', 'supplier-and-purchase-order-management-for-woocommerce')?></a><span> </span><a class="button button-primary" href="admin.php?page=supplier-plugin-po-list"><?php echo __('List of all POs', 'supplier-and-purchase-order-management-for-woocommerce')?></a>

<div><h1 style="float:left"><?php _e( 'Supplier Profile', 'supplier-and-purchase-order-management-for-woocommerce' );?> || <?php echo esc_html($row_details['suppliername']) ?></h1> <a href="admin.php?page=supplier-plugin&id=<?php echo $row_details['id'];?>&action=deletesupplier" style="float:right;margin-left:20px;margin-top:12px;" onclick="return spom_confirmDeleteSuppl();"><?php _e( 'delete supplier', 'supplier-and-purchase-order-management-for-woocommerce' );?></a><br/></div>
<br/><br/>
<form id="productdatasubmitform" method="post" name="form" action="admin.php?page=supplier-plugin-perfil&action=saveproducts&id=<?php echo esc_html($id)?>" hidden>
        <input type="text" id="productdatainput" name="productdata">
        <input type="submit" value="Submit">
</form>
<code><b><?php _e( 'Contact(s):', 'supplier-and-purchase-order-management-for-woocommerce' );?></b> <?php echo esc_html($row_details['nombrecontacto']) ?> <b>Email(s):</b> <?php echo esc_html($row_details['email']) ?> <b><?php _e( 'Telephone(s):', 'supplier-and-purchase-order-management-for-woocommerce' );?></b> <?php echo $row_details['telefono'] ?> <b><?php _e( 'Note:', 'supplier-and-purchase-order-management-for-woocommerce' );?></b> <?php echo $row_details['nota'] ?></code>
 <div style="background-color:Lavender;padding: 20px; margin-top:2em">
 <a href="#" class="button-secondary" onclick="jQuery('#productslistshow').toggle()"><?php _e( 'Manage associated products', 'supplier-and-purchase-order-management-for-woocommerce' );?></a><br/>
 <h2><?php _e( 'Associated Products', 'supplier-and-purchase-order-management-for-woocommerce' );?></h2>
 <div id="associatedproducts">

 <!--get associated product data-->
    <?php
    if(trim($row_details['productids']) != ""){
        
        $productsarray = explode("|||",$row_details['productids']);
        
        foreach($productsarray as $singleproduct){

            $productdata = explode(":::", $singleproduct);
            
                //save as ID::NAME::URL::MINCANTIDAD:::PRICE:::TAX|||
               
                echo '<a target="_blank" href="'.esc_html(@$productdata[2]).'" style="margin-right:10px;margin-left:10px"><img width="40px" src="'.esc_html(@$productdata[2]).'"/></a>';
                echo esc_html(urldecode(@"$productdata[1]"));
              
            
        }
   


    } 
    ?>
 </div>

 <div id="productslistshow" style="display:none"> <div id="searchfilter"></div><br />
 <hr/>
    <input id="productfilterinput" style="width=33%" placeholder="<?php _e( 'type to filter list', 'supplier-and-purchase-order-management-for-woocommerce' );?>" class="form-control" onkeyup="spom_filterproducts();"/> <a href="#" class="button-primary" onclick="spom_saveProviderProducts();"><?php _e( 'Save changes', 'supplier-and-purchase-order-management-for-woocommerce' );?></a><a href="#" class="button-secondary" onclick="spom_toggleAllCheckmarks();"><?php _e( 'mark/unmark all visible products', 'supplier-and-purchase-order-management-for-woocommerce' );?></a><br/><br /><br />
    <code><?php _e( 'Mark all products that you want to associate with this supplier. The fields of minimum quantity, price and tax are optional.', 'supplier-and-purchase-order-management-for-woocommerce' );?></code>
    <hr/>
    <?php
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1
    );
    
    $loop = new WP_Query( $args );
    
    while ( $loop->have_posts() ) : $loop->the_post();
        global $product;
                $html = woocommerce_get_product_thumbnail();
                //$xpath = new DOMXPath(@DOMDocument::loadHTML($html));
                //$img_src = $xpath->evaluate("string(//img/@src)");
                    $doc = new DOMDocument();
                    $doc->loadHTML($html);
                    $xpath = new DOMXPath($doc);
                    $img_src = $xpath->evaluate("string(//img/@src)"); # "/images/image.jpg"

        echo '<br /><div class=productrow><input type="checkbox" class="largerCheckbox" name="checkboxchosenproduct" value="'.esc_html($product->get_id()).'"/><a href="'.get_permalink().'" class="imagelink" target="_blank"><img src="'.esc_html($img_src).'" class="primage" style="max-width:60px"/>    '.get_the_title().'</a> '.__( 'Type: ', 'supplier-and-purchase-order-management-for-woocommerce' ).esc_html($product->get_type()).' <input type="number" class="minorderquantity" min="0" placeholder="'.__( 'min. order quantity', 'supplier-and-purchase-order-management-for-woocommerce' ).'"/> <input type="number" step="any" min="0" class="priceperproduct" placeholder="'.__( 'price, i.e. 5.26', 'supplier-and-purchase-order-management-for-woocommerce' ).'"/> <input type="number" step="any" min="0" class="tax" placeholder="'.__( 'amount tax, i.e 0.23', 'supplier-and-purchase-order-management-for-woocommerce' ).'"/></div><br />';
    endwhile;
    
    wp_reset_query();
    ?>
 </div>


 </div><br/><br/>

  <div style="background-color:FloralWhite;padding: 20px;">
  <a href="admin.php?page=supplier-plugin-createpo&id=<?php echo esc_html($row_details['id']);?>" class="button-primary"><?php _e( 'New Purchase Order', 'supplier-and-purchase-order-management-for-woocommerce' );?></a><br/>
  <h2><?php _e( 'Purchase Orders', 'supplier-and-purchase-order-management-for-woocommerce' );?></h2>

        <table id="tablecreatepo" class="wp-list-table widefat fixed striped table-view-list posts" cellspacing="0" style="padding:20px;padding-left:50px;padding-right:50px">
        <thead>
        <tr>
            <th class="manage-column column-cb check-column" scope="col" style="max-width:2em"><b>ID</b></th>
            <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Status', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
            <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Date', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
            <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Note', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
            <th class="manage-column column-cb check-column" scope="col"><b></b></th>
        </tr>
        </thead>
        <tbody>

        <?php   
         if(count($allpurchaseorders) > 0){
            foreach($allpurchaseorders as $singlepo){
                echo '<tr>';
                echo '<td class="column-columnname poid"><a target="_blank" href="admin.php?page=supplier-plugin-poview&id='.esc_html($singlepo['id']).'">PO-00'.esc_html($singlepo['id']).'</a></td>';
                if($singlepo['postatus'] == "recibido"){
                    echo '<td class="column-columnname">'.__( 'received', 'supplier-and-purchase-order-management-for-woocommerce' ).'</td>';
                }
                else if($singlepo['postatus'] == "borrador"){
                    echo '<td class="column-columnname">'.__( 'draft', 'supplier-and-purchase-order-management-for-woocommerce' ).'</td>';

                }
                else if($singlepo['postatus'] == "cancelado"){
                    echo '<td class="column-columnname">'.__( 'cancelled', 'supplier-and-purchase-order-management-for-woocommerce' ).'</td>';

                }
                else if($singlepo['postatus'] == "lista para recibir"){
                    echo '<td class="column-columnname">'.__( 'ready to receive', 'supplier-and-purchase-order-management-for-woocommerce' ).'</td>';

                }
                
                echo '<td class="column-columnname">'.esc_html($singlepo['created_at']).'</td>';
                
                if(strlen($singlepo['nota']) > 10 ){
                echo '<td class="column-columnname"><div class="notaexcerpt" id="notetext'.esc_html($singlepo['id']).'">'.esc_html($singlepo['nota']).'</div><a id="more" onclick="spom_showmoretext('.esc_html($singlepo['id']).');">'.__( 'show more', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a></td>';
                }
                else{
                    echo '<td class="column-columnname"><div id="notetext'.esc_html($singlepo['id']).'">'.esc_html($singlepo['nota']).'</div></td>';
                }


                if($singlepo['postatus'] == "recibido"){
                    if($singlepo['confirmaciononreceivedsent'] != 1){
                    echo '<td class="column-columnname"><a href="admin.php?page=supplier-plugin-sendconf&poid='.esc_html($singlepo['id']).'">'.__( 'send confirmation', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a>'.
                    '</td>';
                    }
                    else{
                            echo '<td class="column-columnname"><a href="admin.php?page=supplier-plugin-sendconf&poid='.esc_html($singlepo['id']).'">'.__( 'send confirmation again', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a>'.
                            '</td>';
                    }
                }
                elseif($singlepo['postatus'] == "lista para recibir"){
                    echo '<td class="column-columnname">'.
                    '<select id="statuspo" class="'.esc_html($singlepo['id']).'" onchange="spom_changePOstatus(this)"><option value="">'.__( 'change status', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option><option value="borrador">'.__( 'draft', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option><option value="listapararecibir">'.__( 'ready to receive', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option><option value="recibido">'.__( 'received', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option><option value="cancelado">'.__( 'cancelled', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option></select>'.
                    ' || <a href="admin.php?page=supplier-plugin-receivepo&action=receivepo&poid='.esc_html($singlepo['id']).'">'.__( 'receive delivery', 'supplier-and-purchase-order-management-for-woocommerce' ).' </a></td>';
                }
                elseif($singlepo['postatus'] == "cancelado"){
                    echo '<td class="column-columnname">'.
                    '</td>';
                }
                else{
                    echo '<td class="column-columnname"><a href="admin.php?page=supplier-plugin-editpo&action=edit&poid='.esc_html($singlepo['id']).'">'.__( 'edit PO', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a>'.' || '.
                    '<select id="statuspo" class="'.esc_html($singlepo['id']).'" onchange="spom_changePOstatus(this)"><option value="">'.__( 'change status', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option><option value="borrador">'.__( 'draft', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option><option value="listapararecibir">'.__( 'ready to receive', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option><option value="recibido">'.__( 'received', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option><option value="cancelado">'.__( 'cancelled', 'supplier-and-purchase-order-management-for-woocommerce' ).'</option></select>'.
                    ' || <a onclick="return confirm(\''.__( 'Are you 100% you want to delete the PO?', 'supplier-and-purchase-order-management-for-woocommerce' ).'\')" href="admin.php?page=supplier-plugin-perfil&action=deletepo&poid='.esc_html($singlepo['id']).'&id='.esc_html($singlepo['supplieridfk']).'"> '.__( 'delete PO', 'supplier-and-purchase-order-management-for-woocommerce' ).' </a></td>';
                }
                echo '</tr>';
            }
        }
            ?>
        
            </tbody>
        </table>

 </div>

  





