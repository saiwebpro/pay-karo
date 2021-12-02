<?php

function download_swr_order_list() {

    global $wpdb;
    $table_transaction = $wpdb->prefix . 'swr_transaction'; // do not forget about tables prefix
    $table_address = $wpdb->prefix . 'swr_shipping_address'; // do not forget about tables prefix

    ob_end_clean();
    $sql = $wpdb->get_results( "SELECT rt.* , rs.* FROM $table_transaction as rt, $table_address as rs WHERE rs.id = rt.shipping_id " );

    if ( ! $sql ) {
        die( 'Invalid query: ' . mysql_error() );
    }

    // Get The Field Name
    $output = 'Name' . ',';
    $output .= 'Email' . ',';
    $output .= 'Mobile' . ',';
    $output .= 'Delivered To' . ',';
    $output .= 'Pincode' . ',';
    $output .= 'Product Name' . ',';
    $output .= 'Amount' . ',';
    $output .= 'Shipping Amount' . ',';
    $output .= 'currency' . ',';
    $output .= 'Payment Status' . ',';
    $output .= 'Payment Type' . ',';
    $output .= 'Description' . ',';
    $output .= 'Payment ID' . ',';
    $output .= 'Created at' . ',';
    $output .= 'Shipping' . ',';
    $output .="";

    // Get Records from the table

    foreach ($sql as $row) {

        $output .="\n";
        $output .='"' . $row->first_name . ' ' . $row->last_name. '",';
        $output .='"' . $row->email . '",';
        $output .='"' . $row->mobile . '",';
        $output .='"' . $row->address . ', ' . $row->landmark . ', ' . $row->city . ', ' . $row->state . ', ' . $row->country. '",';
        $output .='"' . $row->pincode . '",';
        $output .='"' . $row->product_name . '",';
        $output .='"' . $row->total_amount . '",';
        $output .='"' . $row->shipping_fare . '",';
        $output .='"' . $row->currency . '",';
        $output .='"' . $row->payment_status . '",';
        $output .='"' . $row->payment_type . '",';
        $output .='"' . $row->description . '",';
        $output .='"' . $row->payment_id . '",';
        $output .='"' . $row->created_at . '",';
        $output .='"' . $row->shipping . '",';
       
    }
    $output .="\n";
    // Download the file

    $filename = "OrderDetail";
    header("Content-type: application/vnd.ms-excel");
    header("Content-disposition: csv" . date("Y-m-d") . ".csv");
    header("Content-disposition: filename=" . $filename . ".csv");

    echo $output;
    exit;
}
