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
            $sql = "SELECT `user_id` FROM pish_hikashop_user WHERE user_cms_id=$user_id LIMIT 1";
            $sql = mysqli_real_escape_string($this->conn, $sql);
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
     *  archive all orders belogs to user where order id is that one she/he want to archive
     */
    public function setOrdersToArchive($user_id, $order_id)
    {
        $user_id = $this->getInput($user_id);
        $order_id = $this->getInput($order_id);
        $statusComplete = false;

        try {
            // run your code here
            $sql = "UPDATE pish_customer_vendor SET pish_customer_vendor.customer_archived = 1 WHERE pish_customer_vendor.customer_id = (SELECT pish_hikashop_user.user_id FROM pish_hikashop_user WHERE pish_hikashop_user.user_cms_id=$user_id) AND pish_customer_vendor.order_id = $order_id";
            $sql = mysqli_real_escape_string($this->conn, $sql);
            $result = $this->conn->query($sql);
            if ($result) {
                $rowcount = mysqli_affected_rows($this->conn);
                if ($rowcount) {
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
     *  archive all orders belogs to user where order id is that one she/he want to archive
     */
    public function setOrdersToReject($user_id, $order_id,$vendor_id)
    {
       $order_id;
       $user_id = $this->getInput($user_id);
       $order_id = $this->getInput($order_id);
       $vendor_id = $this->getInput($vendor_id);
       $statusComplete = false;

        try {
            // run your code here
             $sql = "UPDATE pish_customer_vendor SET pish_customer_vendor.proposal_completed = -1 WHERE pish_customer_vendor.customer_id = (SELECT pish_hikashop_user.user_id FROM pish_hikashop_user WHERE pish_hikashop_user.user_cms_id=$user_id) AND pish_customer_vendor.order_id = $order_id  AND pish_customer_vendor.vendor_id = $vendor_id";
            $sql = mysqli_real_escape_string($this->conn, $sql);
            $result = $this->conn->query($sql);
            if ($result) {
                $rowcount = mysqli_affected_rows($this->conn);
                if ($rowcount) {
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
     *  accept all orders belogs to user where order id is that one she/he want to archive
     */
    public function setOrdersToAccept($user_id, $order_id, $vendor_id)
    {
        $user_id = $this->getInput($user_id);
        $order_id = $this->getInput($order_id);
        $vendor_id = $this->getInput($vendor_id);
        $statusComplete = false;
        /* Start transaction */
        $this->conn->begin_transaction();
        try {
            // run your code here
            $sql = "UPDATE pish_customer_vendor SET pish_customer_vendor.buy_status = 'done',"

            . "pish_customer_vendor.proposal_completed=1 WHERE pish_customer_vendor.customer_id = (SELECT pish_hikashop_user.user_id "
        
            . "FROM pish_hikashop_user WHERE pish_hikashop_user.user_cms_id=$user_id) AND pish_customer_vendor.order_id = "
        
            . "$order_id AND pish_customer_vendor.vendor_id = $vendor_id";
           $sql = stripcslashes (mysqli_real_escape_string($this->conn, $sql));
            $result = $this->conn->query($sql);
            if ($result) {
                $rowcount = mysqli_affected_rows($this->conn);
                if ($rowcount) {
                    $statusComplete = false;
                    $sql = "UPDATE pish_hikashop_order_product SET pish_hikashop_order_product.vendor_id_accepted = $vendor_id WHERE order_id=$order_id";
                    $sql = mysqli_real_escape_string($this->conn, $sql);
                    $result = $this->conn->query($sql);
                    if ($result) {
                        $rowcount = mysqli_affected_rows($this->conn);
                        if ($rowcount) {
                            $statusComplete = true;
                            $this->conn->commit();
                        } else {
                            $statusComplete = false;
                            $this->conn->rollback();
                        }
                    } else {
                        $statusComplete = false;
                        $this->conn->rollback();
                    }
                } else {
                    $this->conn->rollback();
                    $statusComplete = false;
                }
            } else {
                $statusComplete = false;
                $this->conn->rollback();
            }
        } catch (exception $e) {
            //code to handle the exception
            $this->conn->rollback();
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
$type = $post['type'];
$vendor_id = $post['vendor_id'];
$order_id = $post['order_id'];

$object = new stdClass();
$store = new CustomerOrders($conn);

if ($post && count($post) && $user_id) {

    if ($type == 'rejectOrder') {
        if ($store->setOrdersToReject($user_id, $order_id,$vendor_id)) {
            $object->response = 'ok';
        } else {
            $object->response = 'notok';
        }
    } elseif ($type == 'acceptOrder') {
        if ($store->setOrdersToAccept($user_id, $order_id, $vendor_id)) {
            $object->response = 'ok';
        } else {
            $object->response = 'notok';
        }
    } elseif ($type == 'archiveOrder') {
        if ($store->setOrdersToArchive($user_id, $order_id)) {
            $object->response = 'ok';
        } else {
            $object->response = 'notok';
        }
    } else {
        $object->response = 'notok';
    }
} else {
    $object->response = 'notok';
}

// show result output
$store->showResponse($object);
