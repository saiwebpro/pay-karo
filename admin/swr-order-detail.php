<?php
if ( ! class_exists ( 'WP_List_Table' ) ) {
    require_once ( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class SWR_Order_List_Table extends WP_List_Table {

    function __construct() {
        
        global $status, $page;

        parent::__construct ( array (
            'singular' => 'orderlist', //singular name of the listed records
            'plural' => 'orderlist', //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ) );
        
    }

    function column_default ( $item, $column_name ) {
        
        date_default_timezone_set ( 'Asia/Calcutta' );
        
        switch ( $column_name ) {
            case 'pincode' :
            case 'payment_id' :
            case 'total_amount' :
            case 'product_name' :
                return $item[ $column_name ];
            case 'created_at' :
                return $item['created_at'];
            case 'name' :
                return $item['first_name'] . " " . $item['last_name'];
            case 'address' :
                return $item['address'] . ", " . $item['landmark'] . ", " . $item['city'] . ", " . $item['state'] . ", " . $item['country'];
            case 'shipping' :
                return '<p class="' . $item['payment_id'] . '">'
                    . '<select class="action" id="shipping_' . $item['payment_id'] . '">'
                        . '<option value="pending"' . ( ( $item['shipping'] == 'pending' ) ? 'selected="selected"' : "") . '> Pending </option>'
                        . '<option value="delivered"' . ( ( $item['shipping'] == 'delivered' ) ? 'selected="selected"' : "") . '> Delivered </option>'
                    . '</select> </p>';
            default:
                return print_r ( $item , true );
        }
        
    }

    function column_title( $item ) {

        //Build row actions
        $actions = array(
            'edit' => sprintf ( '<a href="?page=%s&action=%s&orderlist=%s"> Edit </a>', $_REQUEST['page'], 'edit', $item['trans_id']),
            'delete' => sprintf ( '<a href="?page=%s&action=%s&orderlist=%s"> Delete </a>', $_REQUEST['page'], 'delete', $item['trans_id'] ),
        );

        //Return the title contents
        return sprintf ( '%1$s <span style="color:silver"> (trans_id:%2$s) </span> %3$s',
                $item['name'],
                $item['trans_id'],
                $this->row_actions ( $actions )
        );
        
    }
    
    function column_cb( $item ){
        
        return sprintf (
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args [ 'singular' ],  //Let's simply repurpose the table's singular label ("movie")
            $item['trans_id']   //The value of the checkbox should be the record's id
        );
        
    }

    function get_columns() {
        
        $columns = array (
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'name' => 'Name',
            'address' => 'Delivered To',
            'pincode' => 'Pincode',
            'product_name' => 'Product Name',
            'payment_id' => 'Payment ID',
            'total_amount' => 'Amount',
            'created_at' => 'Created at',
            'shipping' => 'Shipping'
        );
        
        return $columns;
        
    }

    function get_sortable_columns() {
        
        $sortable_columns = array (
            'name' => array ( 'first_name', false) , array ( 'last_name', false),
            'total_amount' => array ( 'total_amount', false ), //true means it's already sorted
            'payment_id' => array ( 'payment_id', false ),
            'created_at' => array ( 'created_at', false ),
            'pincode' => array( 'pincode', false )
        );
        
        return $sortable_columns;
        
    }

    function get_bulk_actions() {
        
        $actions = array (
            'delete' => 'Delete'
        );
        
        return $actions;
        
    }

    function process_bulk_action() {
        
        global $wpdb;
        $table_transaction = $wpdb->prefix . 'swr_transaction';
        if ( 'delete' === $this->current_action() ) {
            $ids = isset ( $_REQUEST['orderlist'] ) ? $_REQUEST['orderlist'] : array();
            $id = implode ( ',', $ids );
            if ( !empty ( $id ) ) {
                $wpdb->query ( "DELETE FROM $table_transaction WHERE trans_id IN ( $id ) " );
            }
        }
        
    }

    function prepare_items() {
        
        global $wpdb;
        $table_transaction = $wpdb->prefix . 'swr_transaction'; // do not forget about tables prefix
        $table_address = $wpdb->prefix . 'swr_shipping_address';

        $per_page = 5; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);

        // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var ( "SELECT COUNT(trans_id) FROM $table_transaction " );

        // prepare query params, as usual current page, order by and order direction
        $do_search = '';
        $order_id = ( isset ( $_REQUEST['search_product_name'] ) ) ? sanitize_text_field ( $_REQUEST['search_product_name'] ) : false;
        $name = ( isset ( $_REQUEST['search_name'] ) ) ? sanitize_text_field ( $_REQUEST['search_name'] ) : false;
        $date = ( isset ( $_REQUEST['search_date'] ) ) ? sanitize_text_field ( $_REQUEST['search_date'] ) : false;
        $payment_id = ( isset ( $_REQUEST['search_payment_id'] ) ) ? sanitize_text_field ( $_REQUEST['search_payment_id'] ) : false;
        $pincode = ( isset ( $_REQUEST['search_pincode'] ) ) ? sanitize_text_field ( $_REQUEST['search_pincode'] ) : false;

        $do_search = ( $order_id ) ? $wpdb->prepare ( " AND rt.product_name = '%s' ", $order_id ) : '';
        $do_search .= ( $name ) ? $wpdb->prepare ( " AND rs.first_name = '%s' ", $name ) : '';
        $do_search .= ( $date ) ? $wpdb->prepare ( " AND DATE(rt.created_at) = '%s' ", $date ) : '';
        $do_search .= ( $payment_id ) ? $wpdb->prepare ( " AND rt.payment_id = '%s' ", $payment_id ) : '';
        $do_search .= ( $pincode ) ? $wpdb->prepare ( " AND rs.pincode = '%s' ", $pincode ) : '';

        $paged = isset ( $_REQUEST['paged']) ? max ( 0, intval ( $_REQUEST['paged'] ) - 1) : 0;
        $offset = $paged * $per_page;
        $orderby = ( isset ( $_REQUEST['orderby'] ) && in_array ( $_REQUEST['orderby'], array_keys ( $this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'rt.created_at';
        $order = ( isset ( $_REQUEST['order'] ) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        $this->items = $wpdb->get_results ( $wpdb->prepare ( "SELECT rt.payment_id, rt.trans_id, rt.total_amount, rt.product_name, rt.created_at, rt.shipping, rs.first_name, rs.last_name, rs.pincode, rs.address, rs.city, rs.state, rs.country, rs.landmark, rs.mobile FROM $table_transaction as rt, $table_address as rs WHERE rt.shipping_id = rs.id $do_search ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A );

        // [REQUIRED] configure pagination
        $this->set_pagination_args ( array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil ( $total_items / $per_page) // calculate pages count
        ) );
    }

}

function swr_add_menu_items() {
    
    add_menu_page ( 'Pay Karo Orders', 
            'Pay Karo Orders', 
            'activate_plugins', 
            'swr_order_list', 
            'swr_list_page'
            );
    
}
add_action ( 'admin_menu', 'swr_add_menu_items' ) ;

function swr_list_page() {

    //Create an instance of our package class...
    $testListTable = new SWR_Order_List_Table();
    //Fetch, prepare, sort, and filter our data...

    $testListTable->prepare_items();
    ?>

    <div class="wrap">

        <div id="icon-users" class="icon32"> <br/> </div>
        
        <h2> <?php _e ( 'Order Details ' ); ?> </h2>
        
        <?php
        $product_name = ( isset ( $_REQUEST['search_product_name'] ) ) ? $_REQUEST['search_product_name'] : false;
        $search_name = ( isset ( $_REQUEST['search_name']) ) ? $_REQUEST['search_name'] : false;
        $search_date = ( isset ( $_REQUEST['search_date']) ) ? $_REQUEST['search_date'] : false;
        $search_payment_id = ( isset ( $_REQUEST['search_payment_id']) ) ? $_REQUEST['search_payment_id'] : false;
        $search_pincode = ( isset ( $_REQUEST['search_pincode']) ) ? $_REQUEST['search_pincode'] : false;
        
        echo '<a href="' . admin_url() . 'admin.php?page=download_swr_order_list&product_name='.$product_name.'&name='.$search_name.'&date='.$search_date.'&payment_id='.$search_payment_id.'&pincode='.$search_pincode.' "> Download Report</a>';
        ?>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <script type="text/javascript">
            jQuery( document ).ready( function() {
                
                jQuery( ' .action ' ).change ( function() {
                    var p_id = jQuery ( this ).parent().attr( 'class' );
                    var shipping_status = jQuery ( '#shipping_' + p_id ).val();

                    jQuery.ajax ( {
                        type: 'POST',
                        url: '<?php echo admin_url ( "admin-ajax.php" ); ?>',
                        data: {
                            action: 'swr_order_action_status',
                            shipping_status: shipping_status,
                            payment_id: p_id
                        },
                        error: function ( jqXHR, textStatus, errorThrown ) {
                            console.error("error occured");
                        },
                        success: function ( data ) {
                            if ( data != '') {
                                alert ( 'Status Changed' );
                            }
                        }
                    });
                });
                
                ( function( $ ) {
                    $( '.complete_datepicker_razorpay' ).datepicker ( {
                        dateFormat: 'yy-mm-dd'
                    } );
                }(jQuery) );

            });
        </script>

        <form id="orderlist-filter" method="get">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <input type="text" name="search_product_name" id="search_order_id" value="<?php echo $product_name; ?>" placeholder="Product Name"/>
            <input type="text" name="search_name" id="search_name" value="<?php echo $search_name; ?>" placeholder="Name"/>
            <input type="text" name="search_date" class="complete_datepicker_razorpay" id="search_date" value="<?php echo $search_date; ?>" placeholder="Date"/>
            <input type="text" name="search_payment_id" id="search_payment_id" value="<?php echo $search_payment_id; ?>" placeholder="Payment ID"/>
            <input type="text" name="search_pincode" id="search_pincode" value="<?php echo $search_pincode; ?>" placeholder="Pincode"/>

            <input type="submit" name="search-submit" id="search-submit" class="button" value="Search">
            <?php $testListTable->display() ?>
        </form>

    </div>
    <?php
}