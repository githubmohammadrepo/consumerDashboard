<?php

/**
 * get all consumer order history
 */
require_once("connection.php");

$object = new stdClass();

class CustomerOrders
{
    public $hikashop_userId;
    private $conn;
    public $last_id;
    public $row;
    public $storeOrders;
    public $allOrders = array();
    public function __construct($conn)
    {
        $this->conn = $conn;

        // set hikashop user_id

        //get order infos
    }
    /**
     * get hikashop user id
     */
    public function getHikashopUserId($user_id)
    {
        $user_id = $this->getInput($user_id);
        $statusComplete = false;

        try {
            // run your code here
            $this->row = $sql = "SELECT `user_id` FROM pish_hikashop_user WHERE user_cms_id=$user_id LIMIT 1";

            $result = $this->conn->query($sql);
            if ($result) {
                $rowcount = $result->num_rows;
                if ($rowcount > 0) {

                    $row = $result->fetch_assoc();
                    $this->hikashop_userId = $row['id'];
                    $statusComplete = true;
                } else {
                    $statusComplete = false;
                }
            } else {
                $statusComplete = false;
            }
        } catch (exception $e) {
            //code to handle the exception
            return false;
        }
        return $statusComplete;
    }

    /**
     * get all store orders unArchived
     */

    public function getStoreOrders($user_id)
    {
        $user_id = $this->getInput($user_id);
        $statusComplete = false;

        try {
            // run your code here
            $sql = "SELECT  pish_customer_vendor.id 
           ,pish_customer_vendor.customer_id 
           ,pish_customer_vendor.vendor_id 
           ,pish_customer_vendor.order_id AS customer_order_id 
           ,pish_customer_vendor.buy_status 
           ,pish_customer_vendor.archive 
           ,pish_customer_vendor.proposal_completed 
           ,pish_customer_vendor.order_id AS custome_order_id 
           ,pish_hikashop_order_product.*
    FROM `pish_customer_vendor`
    INNER JOIN pish_hikashop_order_product ON pish_customer_vendor.order_id = pish_hikashop_order_product.order_id
    AND pish_customer_vendor.vendor_id = pish_hikashop_order_product.vendor_id_accepted
    WHERE pish_customer_vendor.buy_status = 'done' 
    AND pish_customer_vendor.customer_archived IS NULL
    AND pish_customer_vendor.customer_id = ( 
    SELECT  `user_id`
    FROM pish_hikashop_user
    WHERE user_cms_id=$user_id 
    LIMIT 1)
    
    UNION
    
    SELECT  pish_customer_vendor.id 
           ,pish_customer_vendor.customer_id 
           ,pish_customer_vendor.vendor_id 
           ,pish_customer_vendor.order_id AS customer_order_id 
           ,pish_customer_vendor.buy_status 
           ,pish_customer_vendor.archive 
           ,pish_customer_vendor.proposal_completed 
           ,pish_customer_vendor.order_id AS custome_order_id 
           ,proposal_order_product.*
    FROM `pish_customer_vendor`
    INNER JOIN proposal_order_product
    ON pish_customer_vendor.order_id = proposal_order_product.order_id
    AND pish_customer_vendor.vendor_id = proposal_order_product.vendor_id_accepted
    WHERE pish_customer_vendor.buy_status = 'proposal' 
    AND pish_customer_vendor.customer_archived IS NULL
    AND pish_customer_vendor.customer_id = ( 
    SELECT  `user_id`
    FROM pish_hikashop_user
    WHERE user_cms_id=$user_id 
    LIMIT 1)";
            // . "AND pish_customer_vendor.customer_id = $this->hikashop_userId";
            $result = $this->conn->query($sql);
            if ($result) {
                $rowcount = $result->num_rows;
                if ($rowcount > 0) {
                    $order_id = -1;
                    $vendor_id_accepted = -1;
                    $order_array = array();
                    $fake = -1;

                    for ($i = 0; $i < $result->num_rows; $i++) {
                        $row = $result->fetch_assoc();
                        if ($order_id == $row['order_id']) {

                            $order_array[$row['buy_status']][$row['vendor_id_accepted']][] = $row;
                        } else {

                            if ($fake != -1) {
                                array_push($this->allOrders, $order_array);
                                $order_array = [];
                            }
                            $order_id = $row['order_id'];
                            $fake = $order_id;

                            $vendor_id_accepted = $row['vendor_id_accepted'];
                            $order_array[$row['buy_status']] = [];
                            $order_array[$row['buy_status']][$vendor_id_accepted][] = $row;
                        }
                    }
                    array_push($this->allOrders, $order_array);

                    $statusComplete = true;
                } else {
                    $statusComplete = false;
                }
            } else {
                $statusComplete = false;
            }
        } catch (exception $e) {
            //code to handle the exception
            return false;
        }
        return $statusComplete;
    }



    /**
     * get all store orders unArchived
     */

    public function getStoreOrdersArchived($user_id,$start=0)
    {
        $user_id = $this->getInput($user_id);
        $start = $this->getInput($start);
        $statusComplete = false;

        try {
            // run your code here
            $sql = "(
                SELECT NewTable.*,
                  pish_phocamaps_marker_store.ShopName
                FROM (
                  SELECT * FROM
                    (SELECT pish_customer_vendor.id,
                      pish_customer_vendor.customer_id,
                      pish_customer_vendor.vendor_id,
                      pish_customer_vendor.order_id AS customer_order_id,
                      pish_customer_vendor.buy_status,
                      pish_customer_vendor.archive,
                      pish_customer_vendor.proposal_completed,
                      pish_customer_vendor.customer_archived,
                      pish_customer_vendor.order_id AS custome_order_id
                    FROM `pish_customer_vendor` order by pish_customer_vendor.order_id limit $start,40) AS customer_vendor
                      INNER JOIN pish_hikashop_order_product ON customer_vendor.customer_order_id = pish_hikashop_order_product.order_id
                      AND customer_vendor.vendor_id = pish_hikashop_order_product.vendor_id_accepted
                    WHERE customer_vendor.buy_status = 'done'
                      AND customer_vendor.customer_archived IS NOT NULL
                      AND customer_vendor.customer_id = (
                        SELECT `user_id`
                        FROM pish_hikashop_user
                        WHERE user_cms_id = $user_id
                        LIMIT 1
                      ) 
                  ) as NewTable
                  INNER JOIN pish_phocamaps_marker_store ON NewTable.vendor_id = pish_phocamaps_marker_store.id
              )
              UNION
              (
                SELECT NewTable.*,
                  pish_phocamaps_marker_store.ShopName
                FROM (
                    SELECT * FROM
                    (SELECT pish_customer_vendor.id,
                      pish_customer_vendor.customer_id,
                      pish_customer_vendor.vendor_id,
                      pish_customer_vendor.order_id AS customer_order_id,
                      pish_customer_vendor.buy_status,
                      pish_customer_vendor.archive,
                      pish_customer_vendor.proposal_completed,
                      pish_customer_vendor.order_id AS custome_order_id,
                      pish_customer_vendor.customer_archived
                    FROM `pish_customer_vendor` order by pish_customer_vendor.order_id limit $start,40) AS customer_vendor
                      INNER JOIN proposal_order_product ON customer_vendor.customer_order_id = proposal_order_product.order_id
                      AND customer_vendor.vendor_id = proposal_order_product.vendor_id_accepted
                    WHERE customer_vendor.buy_status = 'proposal'
                      AND customer_vendor.customer_archived IS NOT NULL
                      AND customer_vendor.customer_id = (
                        SELECT `user_id`
                        FROM pish_hikashop_user
                        WHERE user_cms_id = $user_id
                        LIMIT 1
                      )
                  ) AS NewTable
                  INNER JOIN pish_phocamaps_marker_store ON NewTable.vendor_id = pish_phocamaps_marker_store.id
              )";
            // . "AND pish_customer_vendor.customer_id = $this->hikashop_userId";
            $result = $this->conn->query($sql);
            if ($result) {
                $rowcount = $result->num_rows;
                if ($rowcount >=0) {
                    $order_id = -1;
                    $vendor_id_accepted = -1;
                    $order_array = array();
                    $fake = -1;

                    for ($i = 0; $i < $result->num_rows; $i++) {
                        $row = $result->fetch_assoc();
                        if ($order_id == $row['order_id']) {

                            $order_array[$row['buy_status']][$row['vendor_id_accepted']][] = $row;
                        } else {

                            if ($fake != -1) {
                                array_push($this->allOrders, $order_array);
                                $order_array = [];
                            }
                            $order_id = $row['order_id'];
                            $fake = $order_id;

                            $vendor_id_accepted = $row['vendor_id_accepted'];
                            $order_array[$row['buy_status']] = [];
                            $order_array[$row['buy_status']][$vendor_id_accepted][] = $row;
                        }
                    }
                    array_push($this->allOrders, $order_array);

                    $statusComplete = true;
                } else {
                    $statusComplete = false;
                }
            } else {
                $statusComplete = false;
            }
        } catch (exception $e) {
            //code to handle the exception
            return false;
        }
        return $statusComplete;
    }
    /**
     * fail response
     */
    public function showResponse(&$object, $data = null)
    {
        echo json_encode([$object, $data], JSON_UNESCAPED_UNICODE);
    }

    /**
     * get input and ake sure it secure
     */
    private function getInput($input)
    {
        $result = htmlspecialchars(strip_tags($input));
        if (preg_match('/<>;:\$^/', $result)) {
            return;
        } else {
            return $result;
        }
    }
}

//   using class
$json = file_get_contents('php://input');
$post = json_decode($json, true);
$user_id = $post['user_id'];
$start = $post['start'];
$type = $post['type'];

$object = new stdClass();
$store = new CustomerOrders($conn);

if ($post && count($post) && $user_id) {

    if ($type == 'getAllOrders') {
        if ($store->getStoreOrders($user_id)) {
            $object->response = 'ok';
            $object->data = $store->allOrders;
        } else {
            $object->response = 'fournotok';
        }
    } elseif ($type == 'getAllArchived') {
        if ($store->getStoreOrdersArchived($user_id,$start)) {
            $object->response = 'ok';
            $object->data = $store->allOrders;
        } else {
            $object->response = 'fournotok';
        }
    } else {
        $object->response = 'twonotok';
    }
} else {
    $object->response = 'onenotok';
}

// show result output
$store->showResponse($object);
