<?php 
global $wpdb;
$poTable = $wpdb->prefix . 'posuppliers';
$msg = "";

$allpos = $wpdb->get_results(

    $wpdb->prepare(
                "SELECT * from $poTable ORDER BY created_at DESC",""
    ),ARRAY_A
);


if(count($allpos) > 0){

    ?>
    <script>

var globalTimeout = null;  

function spomfilterproducts(){
    if(globalTimeout != null) clearTimeout(globalTimeout);  
    globalTimeout =setTimeout(SearchFunc,1000);  
    }

    function SearchFunc(){  

    globalTimeout = null;  
    
    var tosearchfor = document.getElementById('productfilterinput').value;

    //get all products
    const allproducts = document.getElementsByClassName("supplierrow");

    if(tosearchfor.trim() == ""){

        Array.from(allproducts).forEach((product) => {

            jQuery(product).show();
        });

    }
    else{

        Array.from(allproducts).forEach((product) => {
            if(!product.innerHTML.toLowerCase().includes(tosearchfor.toLowerCase())){
                jQuery(product).hide();
            }
            else{
                jQuery(product).show();
            }
        });

    }
    
    }

    function showmoretext(divid){

      
        var divtoexpand = document.getElementById("notetext" + divid);
        var textnota = divtoexpand.innerHTML;
        if(textnota.trim() == ""){
            alert("La OC no contiene una nota");
            return;
        }

        divtoexpand.style.height = "auto";

        divtoexpand.onclick = function() { divtoexpand.style.height = "40px"; };

        

    }
    </script>
    <style>
        .notaexcerpt{
            /* width:100px; */
            height:40px;
            display:block;
            /* border:1px solid red; */
            /* padding:10px; */
            overflow:hidden;
        }
    </style>
    
    <p style="color:red"><?php echo wp_kses_post($msg); ?></p>
    <a class="button button-primary" href="admin.php?page=supplier-plugin"><?php echo __('List of all Suppliers', 'supplier-and-purchase-order-management-for-woocommerce')?></a>
    <h1><?php _e( 'All Purchase Orders', 'supplier-and-purchase-order-management-for-woocommerce' );?></h1>
    <input id="productfilterinput" style="width=33%;margin-top:1em;" placeholder="<?php _e( 'type to filter list', 'supplier-and-purchase-order-management-for-woocommerce' );?>" class="form-control" onkeyup="spomfilterproducts();"/> <br/><br /><br />
    <table class="wp-list-table widefat fixed striped table-view-list posts" cellspacing="0" style="padding:20px;padding-left:50px;padding-right:50px">
    <thead>
    <tr>
        <th class="manage-column column-cb check-column" scope="col"><b>ID</b></th>
        <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Supplier', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
        <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Status', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
        <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Note', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
        <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Date created', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
    </tr>
    </thead>
    <tbody>
<?php    
$count = 1;
    foreach($allpos as $index => $singlepo){
        $supplierTable = $wpdb->prefix . 'suppliers';
        $row_details = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * from $supplierTable WHERE id = %d",$singlepo['supplieridfk']
            ),ARRAY_A
);

?>

<tr class="supplierrow">
    <td class="column-columnname"><a target="_blank" href="admin.php?page=supplier-plugin-poview&id=<?php echo esc_html($singlepo['id']) ?>"><?php echo 'PO-00'.esc_html($singlepo['id']) ?></a></td>
    <td class="column-columnname"><?php echo esc_html($row_details['suppliername']) ?></td>

    <?php 
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
    ?>

    
    <?php 
    if(strlen($singlepo['nota']) > 10 ){
        echo '<td class="column-columnname"><div class="notaexcerpt" id="notetext'.esc_html($singlepo['id']).'">'.esc_html($singlepo['nota']).'</div><a id="more" onclick="showmoretext('.esc_html($singlepo['id']).');">'.__( 'show more', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a></td>';
        }
        else{
            echo '<td class="column-columnname"><div id="notetext'.esc_html($singlepo['id']).'">'.esc_html($singlepo['nota']).'</div></td>';
        }
    ?>
    <td class="column-columnname"><?php echo esc_html($singlepo['created_at']) ?></td>
</tr>
<?php

    }
    ?>
     </tbody>
    </table>




    <?php
}
else{
    echo 'All your POs will be shown here. Aún no hay órdenes de compra en el sistema';
}


?>