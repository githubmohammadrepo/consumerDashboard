<?php
include "connection.php";
$json = file_get_contents('php://input');
$post = json_decode($json, true);
$user_id = $post["user_id"];
$orders = $post["orders"];
$object = new stdClass();
$dev_array = array();
$get_array = array();

function sendGCM($message, $id) {
  // echo $message;
    $url = 'https://fcm.googleapis.com/fcm/send';

            $title = 'نرم افزار فیشاپینگ';

    $fields = array (
            'registration_ids' => array (
                    $id
            ),
            'data' => array (
                    'body' => $message,
                    'title' => $title,
                    'sound' => 'default',
                    'icon'=> 'icon'
                    
            )
    );
    $fields = json_encode ( $fields );

    $headers = array (
            'Authorization: key=' . "AAAAn9Av9qU:APA91bEZZmcEPvZkhURX4fv5DPUsyZ44g_NIEPYtEilxdc0UkHTjiPQMaCjeBToy5NGXx9J_dn7HCWqgF8vymFmK8wfz6hfoWtDx4Fu8aQEVK_9qZQ9ST0s2YLIim9AAitkPUqro9NOX",
            'Content-Type: application/json'
    );

    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_POST, true );
    curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );

    $result = curl_exec ( $ch );
    curl_close ( $ch );
}


$sql = "SELECT * FROM pish_hikashop_user WHERE user_cms_id=$user_id LIMIT 1";
$result1 = $conn->query($sql);
if ($result1->num_rows > 0) {
    $row1 = $result1->fetch_assoc();
    $hika_user_id = $row1['user_id'];
	
	foreach($orders as $order) {
	   $vendor_id = $order["vendor_id"];
       $products = $order["products"];
       $time = time();
       $sql2 = "SELECT * FROM pish_hikashop_order ORDER BY order_id DESC LIMIT 1";
       $result2 = $conn->query($sql2);
	   if ($result2->num_rows > 0) {
            $row2 = $result2->fetch_assoc();
            $last_id = $row2['order_id'];
        } else {
            $last_id = 0;
        }
            $last_id = $last_id + 1;
            
		foreach($vendor_id as $vendor) {
             $time = time();
             $VID = $vendor["id"];
		 
		$sql3= "INSERT INTO pish_hikashop_order (order_user_id, order_status, order_id, order_created, order_modified, order_vendor_id)
        VALUES($hika_user_id, 'created', $last_id, $time, $time, $VID)";


        
		$sql4= "SELECT token from pish_users_token
				inner JOIN pish_phocamaps_marker_store ON pish_users_token.id = pish_phocamaps_marker_store.user_id
				WHERE pish_phocamaps_marker_store.id=$VID";
		$result4 = $conn->query($sql4);
			if ($result4->num_rows > 0) {
				$row4 = $result4->fetch_assoc();
				$token = $row4['token'];
			} 	
			
		foreach($products as $product) {
                $product_id = $product["product_id"];
				$cart_product_quantity = $product["quantity"];
                $product_name = $product["product_name"];
                $product_price = $product["product_price"];
                $product_code = 'product_' . $product["product_id"];
                $sql5 = "INSERT INTO pish_hikashop_order_product (order_id, product_id, order_product_quantity, order_product_name, order_product_code,
				order_product_price) VALUES ($last_id, $product_id, $cart_product_quantity, '$product_name', '$product_code', $product_price)";
				$result5 = $conn->query($sql5);
           }
		   $sql6 = "UPDATE pish_hikashop_order SET seen = 1 WHERE order_id = $last_id";
        $result6 = $conn->query($sql6);
		  // $last_id++;
		   
		

$sql7 = "SELECT OwnerName FROM pish_phocamaps_marker_store where user_id = '$user_id'";
       $result7 = $conn->query($sql7);
	   if ($result7->num_rows > 0) {
	      // output data of each row
    for ($i = 0; $i < $result7-> num_rows; $i++)
    {
        $get_array[$i] = $result7->fetch_assoc();
    }
}

   /* $dev_array[0]['order_id'] = $last_id;
    $dev_array[1]['customer_user_id'] = $user_id;
    $dev_array[2]['OwnerName'] = $get_array[0]['OwnerName'];*/

  $dev_array[] = array (
            'order_id' => $last_id,
                    'customer_user_id' => $user_id,
                    'OwnerName' => $get_array[0]['OwnerName'],
                    'group'=>'store'
    );


		$jsonporder = json_encode($dev_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
// 		echo $jsonporder."<br>";
		sendGCM($jsonporder,$token);

	if ($conn->query($sql3) === TRUE) {
			    
             $object->response = 'ok';
        } else {
             $object->response = 'notok';
        }
        
        $jsonEncode = json_encode($object, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
		}	
		    echo $jsonEncode;
	}
}	
