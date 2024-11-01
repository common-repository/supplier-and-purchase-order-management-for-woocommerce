<?php 

global $wpdb;
$registration_date = date("Y-m-d H:i:s", time());
$supplierTable = $wpdb->prefix . 'suppliers';
$msg = '';

$action = sanitize_text_field(isset($_GET['action']) ? trim($_GET['action']) : "");
$id = sanitize_text_field(isset($_GET['id']) ? intval($_GET['id']) : "");




if(isset($_POST['btnsubmit'])){
    
    $action = sanitize_text_field(isset($_GET['action']) ? trim($_GET['action']) : "");
    $id = sanitize_text_field(isset($_GET['id']) ? intval($_GET['id']) : "");

    if(!empty($action)){
        //update supplier
        $wpdb->update($supplierTable,array(
            "suppliername"=>sanitize_text_field($_POST['suppliername']),
            "email"=>sanitize_text_field($_POST['email']),
            "nombrecontacto"=>sanitize_text_field($_POST['nombrecontacto']),
            "telefono"=>sanitize_text_field($_POST['telefono']),
            "provincia"=>sanitize_text_field($_POST['provincia']),
            "direccion"=>sanitize_text_field($_POST['direccion']),
            "nota"=>sanitize_text_field($_POST['nota']),
            "lastupdate_at"=>sanitize_text_field($_POST['lastupdate_at']),
            "created_at"=>sanitize_text_field($_POST['created_at'])
        ), array(
           "id" => $id
        ));
        $msg = "<div class='updated update'>".__( 'supplier successfully updated', 'supplier-and-purchase-order-management-for-woocommerce' )."</div>";

    }
    else{
        //add new supplier
    $wpdb->insert($supplierTable,array(
        "suppliername"=>sanitize_text_field($_POST['suppliername']),
        "email"=>sanitize_text_field($_POST['email']),
        "nombrecontacto"=>sanitize_text_field($_POST['nombrecontacto']),
        "telefono"=>sanitize_text_field($_POST['telefono']),
        "provincia"=>sanitize_text_field($_POST['provincia']),
        "direccion"=>sanitize_text_field($_POST['direccion']),
        "nota"=>sanitize_text_field($_POST['nota']),
        "created_at"=>sanitize_text_field($_POST['created_at'])
    ));
    if($wpdb->insert_id > 0){
        $msg = "<div class='updated update'>".esc_html(__( 'supplier successfully saved', 'supplier-and-purchase-order-management-for-woocommerce' ))."</div>";

    }else{
        $msg = "<div style='color:red'>".esc_html(__( 'error - supplier information couldn\'t be stored', 'supplier-and-purchase-order-management-for-woocommerce' ))."</div>";
    }


    }

    

}

$row_details = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * from $supplierTable WHERE id = %d",$id
    ),ARRAY_A
);

?>

<p><?php echo wp_kses_post($msg); ?></p>
<a class="button button-primary" href="admin.php?page=supplier-plugin"><?php echo __('List of all Suppliers', 'supplier-and-purchase-order-management-for-woocommerce')?></a><span> </span><a class="button button-primary" href="admin.php?page=supplier-plugin-po-list"><?php echo __('List of all POs', 'supplier-and-purchase-order-management-for-woocommerce')?></a>
<h1><?php _e( 'Add Supplier', 'supplier-and-purchase-order-management-for-woocommerce' );?></h1>
<form action="<?php echo esc_html($_SERVER['PHP_SELF']) ?>?page=supplier-plugin-add<?php 
if(!empty($action)){
     echo '&action=edit&id='.$id ; 
     } ?>" method="post">
<input type="datetime-local" name="created_at" value="<?php echo esc_html(isset($row_details['created_at']) ? $row_details['created_at'] : date('Y-m-d\TH:i:s')); ?>" hidden/>
<input type="datetime-local" name="lastupdate_at" value="<?php echo esc_html(date('Y-m-d\TH:i:s')); ?>" hidden/>


<div class="form-group">

    <label><?php _e( 'Supplier name*', 'supplier-and-purchase-order-management-for-woocommerce' );?></label><br>
    <input type="text" name="suppliername" value="<?php echo esc_html(isset($row_details['suppliername']) ? $row_details['suppliername'] : ""); ?>" placeholder="<?php _e( 'Enter supplier name', 'supplier-and-purchase-order-management-for-woocommerce' );?>" class="form-control" style="width:50%" required/><br><br>
</div>

<div class="form-group">

    <label><?php _e( 'Email(s)*', 'supplier-and-purchase-order-management-for-woocommerce' );?></label><span>  <?php _e( 'several emails divided by a comma are allowed', 'supplier-and-purchase-order-management-for-woocommerce' );?></span><br>
    <input type="text" name="email" value="<?php echo esc_html(isset($row_details['email']) ? $row_details['email'] : ""); ?>" placeholder="<?php _e( 'Enter email(s)', 'supplier-and-purchase-order-management-for-woocommerce' );?>" class="form-control" style="width:50%" required/><br><br>
</div>

<div class="form-group">

    <label><?php _e( 'Name(s) of contact*', 'supplier-and-purchase-order-management-for-woocommerce' );?></label><br>
    <input type="text" name="nombrecontacto" value="<?php echo esc_html(isset($row_details['nombrecontacto']) ? $row_details['nombrecontacto'] : ""); ?>" placeholder="<?php _e( 'Enter contact name(s)', 'supplier-and-purchase-order-management-for-woocommerce' );?>" class="form-control" style="width:50%" required/><br><br>
</div>

<div class="form-group">

    <label><?php _e( 'Telephone number(s)', 'supplier-and-purchase-order-management-for-woocommerce' );?>*</label><br>
    <input type="text" name="telefono" value="<?php echo esc_html(isset($row_details['telefono']) ? $row_details['telefono'] : ""); ?>" placeholder="<?php _e( 'Enter telephone number(s)', 'supplier-and-purchase-order-management-for-woocommerce' );?>" class="form-control" style="width:50%" required/><br><br>
</div>

<div class="form-group">

    <label><?php _e( 'Province / State', 'supplier-and-purchase-order-management-for-woocommerce' );?>*</label><br>
    <input type="text" name="provincia" value="<?php echo esc_html(isset($row_details['provincia']) ? $row_details['provincia'] : ""); ?>" placeholder="<?php _e( 'Enter province / state', 'supplier-and-purchase-order-management-for-woocommerce' );?>" class="form-control" style="width:50%" required/><br><br>

</div>

<div class="form-group">

    <label><?php _e( 'Address', 'supplier-and-purchase-order-management-for-woocommerce' );?></label><br>
    <input type="text" name="direccion" placeholder="<?php esc_html(_e( 'Enter address', 'supplier-and-purchase-order-management-for-woocommerce' ));?>" class="form-control" style="width:50%" maxlength="500" value="<?php echo esc_html(isset($row_details['direccion']) ? $row_details['direccion'] : ""); ?>"/><br><br>
</div><br />

<div class="form-group">

    <label><?php _e( 'Note', 'supplier-and-purchase-order-management-for-woocommerce' );?></label><br>
    <textarea type="text" name="nota" placeholder="<?php esc_html(_e( 'Enter note', 'supplier-and-purchase-order-management-for-woocommerce' ));?>" class="form-control" rows="3" maxlength="2000" style="width:50%"><?php echo esc_html(isset($row_details['nota']) ? $row_details['nota'] : ""); ?></textarea><br><br>
</div>

<div class="form-group">
<br><br>
    <button type="submit" name="btnsubmit" class="btn btn-primary button-primary"> <?php esc_html(_e( 'Submit', 'supplier-and-purchase-order-management-for-woocommerce' )); ?> </button>
</div>


</form>


