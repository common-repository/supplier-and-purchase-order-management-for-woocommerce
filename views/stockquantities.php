<?php
 
 function get_first_attribute_value( $variation_id ) {
    $product_variation = new WC_Product_Variation( $variation_id );
    $attributes = $product_variation->get_attributes();

    if ( ! empty( $attributes ) ) {
        foreach ( $attributes as $name => $value ) {
            return $value;
        }
    }

    return '';
}

    $product_data = array();
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1
    );
    $products = new WP_Query( $args );
    foreach ( $products->posts as $product ) {
        $product = wc_get_product( $product->ID );
        if ( $product->is_type( 'simple' ) ) {
            $stock_quantity = $product->get_stock_quantity();
            $product_tags = get_the_terms( $product->get_id(), 'product_tag' );
            $tags = '';
            if ( $product_tags ) {
                foreach ( $product_tags as $tag ) {
                    $tags .= $tag->name . ', ';
                }
                $tags = rtrim( $tags, ', ' );
            }
            $thumbnail_id = get_post_thumbnail_id( $product->get_id() );
            $thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
            $thumbnail = '<a href="'.$thumbnail_src[0].'" target="_blank">'.'<img src="' . $thumbnail_src[0] . '" alt="Product Thumbnail"  style="max-width:120px;"></a>';
 
            $product_data[] = array(
                'product_type' => 'Simple',
                'product_name' => $product->get_name(),
                'product_variation' => 'N/A',
                'stock_quantity' => $stock_quantity,
                'product_tags' => $tags,
                'product_thumbnail' => $thumbnail
            );
        }
 
        if ( $product->is_type( 'variable' ) ) {
            $variations = $product->get_available_variations();
            $attributes = $product->get_attributes();

            foreach ( $variations as $variation ) {
                $variation_product = new WC_Product_Variation( $variation['variation_id'] );
                $stock_quantity = $variation_product->get_stock_quantity();
                $product_tags = get_the_terms( $product->get_id(), 'product_tag' );
                $tags = '';
                if ( $product_tags ) {
                    foreach ( $product_tags as $tag ) {
                        $tags .= $tag->name . ', ';
                    }
                    $tags = rtrim( $tags, ', ' );
                }
                $thumbnail_id = get_post_thumbnail_id( $variation['variation_id'] );
                $thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
                if(is_array($thumbnail_src))
                { 
                    $thumbnail = '<a href="'.$thumbnail_src[0].'" target="_blank">'.'<img src="' . $thumbnail_src[0] . '" alt="Product Thumbnail" style="max-width:120px;"></a>';
                }
                else{
                    $thumbnail_id = get_post_thumbnail_id( $product->get_id() );
                    $thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
                    if(is_array($thumbnail_src))
                    { 
                    $thumbnail = '<a href="'.$thumbnail_src[0].'" target="_blank">'.'<img src="' . $thumbnail_src[0] . '" alt="Product Thumbnail" style="max-width:120px;"></a>';
                    }
                }

 
                $product_data[] = array(
                    'product_type' => 'Variable',
                    'product_name' => $product->get_name(),
                    //'product_variation' => $variation['attributes'],
                    'product_variation' => get_first_attribute_value( $variation['variation_id'] ),
                    'stock_quantity' => $stock_quantity,
                    'product_tags' => $tags,
                    'product_thumbnail' => $thumbnail
                );
            }
        }
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e( 'Stock Quantities', 'supplier-and-purchase-order-management-for-woocommerce' );?></h1>
        <input id="productfilterinput" style="width=33%;margin-top:1em;" placeholder="<?php _e( 'type to filter list', 'stock-quantities-woocommerce' );?>" class="form-control" onkeyup="spomfilterproducts();"/> 
        <a href="#" class="button-secondary" style="float:right;margin-left:20px;margin-top:12px;" onclick="spom_printINV();"><?php _e( 'Print / save Screen', 'supplier-and-purchase-order-management-for-woocommerce' );?></a><br/><br /><br />
        <table class="wp-list-table widefat fixed striped" id="producttable">
            <thead>
                <tr>
                    <th scope="col"><?php _e( 'Product Thumbnail', 'supplier-and-purchase-order-management-for-woocommerce' );?></th>
                    <th scope="col"><?php _e( 'Product Name', 'supplier-and-purchase-order-management-for-woocommerce' );?></th>
                    <th scope="col"><?php _e( 'Product Variation', 'supplier-and-purchase-order-management-for-woocommerce' );?></th>
                    <th scope="col"><?php _e( 'Quantity Inventory', 'supplier-and-purchase-order-management-for-woocommerce' );?></th>
                    <th scope="col"><?php _e( 'Product Type', 'supplier-and-purchase-order-management-for-woocommerce' );?></th>
                    <th scope="col"><?php _e( 'Product Tags', 'supplier-and-purchase-order-management-for-woocommerce' );?></th>

                </tr>
            </thead>
            <tbody>
                <?php
                foreach ( $product_data as $product ) {
                    echo '<tr class="productrow">';
                    echo '<td>' . $product['product_thumbnail'] . '</td>';
                    echo '<td>' . $product['product_name'] . '</td>';
                    echo '<td>' . $product['product_variation'] . '</td>';
                    echo '<td>' . $product['stock_quantity'] . '</td>';
                    echo '<td>' . $product['product_type'] . '</td>';
                    echo '<td>' . $product['product_tags'] . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>






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
    const allproducts = document.getElementsByClassName("productrow");

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

    function spom_printINV(){
    <?php
    add_filter( 'admin_footer', '__return_empty_string', 11 ); 
    add_filter( 'admin_footer_text', '__return_empty_string', 11 ); 
    add_filter( 'update_footer', '__return_empty_string', 11 );
    ?>

    var getpanel = document.getElementsByClassName("wrap")[0];

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


    <?php

