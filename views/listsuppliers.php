<?php 
global $wpdb;
$supplierTable = $wpdb->prefix . 'suppliers';
$poProductsupplierTable = $wpdb->prefix . 'poproductsuppliers';
$poTable = $wpdb->prefix . 'posuppliers';

$action = sanitize_text_field(isset($_GET['action']) ? trim($_GET['action']) : "");
$id = sanitize_text_field(isset($_GET['id']) ? intval($_GET['id']) : "");
$msg = "";

if(!empty($action) && $action=="deletesupplier"){

    $row_exists = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * from $supplierTable WHERE id = %d",$id
        ),ARRAY_A
    );

if(count($row_exists)>0){

    //get all POs for this supplier
    $allsavedPOs = $wpdb->get_results(

        $wpdb->prepare(
                    "SELECT * from $poTable WHERE supplieridfk = %d",$id
        ),ARRAY_A
    );

    //for each PO delete the products
    foreach ($allsavedPOs as $potodeleteproductsof) {
        //get products for this PO
        $allsavedproducts = $wpdb->get_results(

            $wpdb->prepare(
                        "SELECT * from $poProductsupplierTable WHERE poidfk = %d",$potodeleteproductsof['id']
            ),ARRAY_A
        );
        foreach ($allsavedproducts as $producttodelete) {
            $wpdb->delete($poProductsupplierTable,array(
                "id" => $producttodelete['id']
            ));
        }

    }

    //now delete POs as all have no products right now
    foreach ($allsavedPOs as $potodelete) {
        $wpdb->delete($poTable,array(
            "id" => $potodelete['id']
        ));

    }

    //now finally delete provider/supplier
    $wpdb->delete($supplierTable,array(
        "id" => $id
    ));

    $msg = "<div class='updated update'>".__( 'supplier successfully updated', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";

}
else{

    $msg = "<div style='color:red'>".__( 'supplier could not be found', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";


}

}




$allsuppliers = $wpdb->get_results(

    $wpdb->prepare(
                "SELECT * from $supplierTable ORDER BY suppliername",""
    ),ARRAY_A
);


if(count($allsuppliers) > 0){

    ?>
    <script>
var globalTimeout = null;  

function spom_filterproducts(){
    if(globalTimeout != null) clearTimeout(globalTimeout);  
    globalTimeout =setTimeout(spom_SearchFunc,1000);  
    }

    function spom_SearchFunc(){  

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
    </script>
    
    <p><?php echo wp_kses_post($msg); ?></p>
    <a class="button button-primary" href="admin.php?page=supplier-plugin-add"><?php echo __('Add Suppliers', 'supplier-and-purchase-order-management-for-woocommerce')?></a>

    <h1><?php _e( 'Suppliers', 'supplier-and-purchase-order-management-for-woocommerce' );?></h1>
    <input id="productfilterinput" style="width=33%;margin-top:1em;" placeholder="<?php _e( 'filter list', 'supplier-and-purchase-order-management-for-woocommerce' );?>" class="form-control" onkeyup="spom_filterproducts();"/> <br/><br /><br />
    <table class="wp-list-table widefat fixed striped table-view-list posts" cellspacing="0" style="padding:20px;padding-left:50px;padding-right:50px">
    <thead>
    <tr>
        <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Supplier', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
        <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Email(s)', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
        <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Name(s) of contact', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
        <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Telephone', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
        <th class="manage-column column-cb check-column" scope="col"><b><?php _e( 'Province / state', 'supplier-and-purchase-order-management-for-woocommerce' );?></b></th>
        <th class="manage-column column-cb check-column" scope="col"><b></b></th>
    </tr>
    </thead>
    <tbody>
<?php    
$count = 1;
    foreach($allsuppliers as $index => $supplier){
?>

<tr class="supplierrow">
    <td class="column-columnname"><a href="admin.php?page=supplier-plugin-perfil&id=<?php echo esc_html($supplier['id']);?>"><?php echo esc_html($supplier['suppliername']) ?></a></td>
    <td class="column-columnname"><?php echo esc_html($supplier['email']) ?></td>
    <td class="column-columnname"><?php echo esc_html($supplier['nombrecontacto']) ?></td>
    <td class="column-columnname"><?php echo esc_html($supplier['telefono']) ?></td>
    <td class="column-columnname"><?php echo esc_html($supplier['provincia']) ?></td>
    <td class="column-columnname"><a href="admin.php?page=supplier-plugin-add&action=edit&id=<?php echo esc_html($supplier['id']);?>"><?php _e( 'edit supplier', 'supplier-and-purchase-order-management-for-woocommerce' );?></a></td>
</tr>
<?php

    }
    ?>
     </tbody>
    </table>




    <?php
}
else{
    echo '<a class="button-primary" href="admin.php?page=supplier-plugin-add" style="margin:2em">'.__( 'Please add your first supplier. All your suppliers will show here.', 'supplier-and-purchase-order-management-for-woocommerce' ).'</a>';
}


?>