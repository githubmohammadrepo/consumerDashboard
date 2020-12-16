<?php
  session_start();
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  require_once("connection.php");

  define('_JEXEC', 1);
  define('JPATH_BASE', realpath(dirname(__FILE__) . '/..'));

  require_once(JPATH_BASE . '/includes/defines.php');
  require_once(JPATH_BASE . '/includes/framework.php');
  $mainframe = JFactory::getApplication('site');
  $mainframe->initialise();


  $object = new stdClass();
  $customeObject  = new stdClass();
  class ChangeOrderStatus
  {
    private $hika_user_id;
    private $conn;
    public $last_id;
    public $row;
    public $storeOrders;
    public $singleAcceptAffecerRows = 0;
    public $allOrderAccepted = false;
    private $customerSessionId;
    private $storeOwnerSessionId = array();

    public function __construct($conn, $JFactory_getSession)
    {
      $this->conn = $conn;
      $this->session = $JFactory_getSession;
    }
    /**
     * accept type
     * goal: reject all record that has this order_id to buy_satus to "reject"
     */
    public function setAllOrderStatusToReject($order_id)
    {
      $statusComplete = false;
      try {
        // run your code here
        $sql = "UPDATE `pish_customer_vendor` SET `buy_status` ='reject' WHERE order_id = $order_id";
        $result = $this->conn->query($sql);
        if ($result) {
          $statusComplete = true;
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
     * accept type
     * goal: reject all record that has this order_id to buy_satus to "reject"
     */
    public function setOrderStatusToAccept($user_id, $order_id,&$object)
    {
      $statusComplete = 'notok';
      $object->value= $this->getIsAllOrderProductAccepted($order_id);
      if ($this->getIsAllOrderProductAccepted($order_id)) {
        $object->other1='allOrderProductAccepted';
        return 'other';
      } else {
        if ($this->isRejectedOrderForMe($user_id, $order_id)) {
          $object->other2 = 'rejectedForMe';
          return 'other';
        } else {
          /* Start transaction */
          mysqli_begin_transaction($this->conn);
          try {
            if ($this->setAllOrderStatusToReject($order_id)) {
              // run your code here
              $sql = "UPDATE pish_customer_vendor set pish_customer_vendor.buy_status = 'done' WHERE pish_customer_vendor.order_id =$order_id\n"
                . " AND  pish_customer_vendor.vendor_id = (SELECT pish_phocamaps_marker_store.id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id)";
             $object->result =$result = $this->conn->query($sql);
              if ($result) {
                if ( $count = $this->conn->affected_rows) {
                $object->resultText = 'isOk';

                  // start second update
                  $sql = "UPDATE pish_hikashop_order_product set pish_hikashop_order_product.vendor_id_accepted = (SELECT pish_phocamaps_marker_store.id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id) WHERE pish_hikashop_order_product.order_id =$order_id\n";
                  $object->result2 = $result = $this->conn->query($sql);
                  if ($result) {
                    $object->resultTest= 'order_productUpdated complete';
                    $object->count = $count = $this->conn->affected_rows;
                    if ($count > 0) {
                      /* If code reaches this point without errors then commit the data in the database */
                      mysqli_commit($this->conn);


                      $statusComplete = 'ok';
                    } else {
                      mysqli_rollback($this->conn);
                      $statusComplete = 'notok';
                    }
                    // end second update
                  } else {
                    $statusComplete = 'notok';
                  }
                } else {
                  $statusComplete = 'notok';
                }
              } else {
                $statusComplete = 'notok';
              }
            }
          } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($this->conn);
            //code to handle the exception
            return 'notok';
          }
        }
      }
      return $statusComplete;
    }

    /**
     * accept type
     * goal: acceptAll order that order_product was doned
     */
    public function setOrdersBelogsToProductTOAccept($user_id, $order_id)
    {
      $statusComplete = false;
      /* Start transaction */
      mysqli_begin_transaction($this->conn);
      try {
        if ($this->setAllOrderStatusToReject($order_id)) {
          // run your code here
          $sql = "UPDATE pish_customer_vendor SET pish_customer_vendor.buy_status = 'done' WHERE pish_customer_vendor.vendor_id IN (SELECT pish_hikashop_order_product.vendor_id_accepted FROM `pish_hikashop_order_product` WHERE pish_hikashop_order_product.order_id =$order_id GROUP BY pish_hikashop_order_product.vendor_id_accepted)";
          $result = $this->conn->query($sql);
          if ($result) {
            $count = $this->conn->affected_rows;
            if ($count > 0) {

              // start second update
              $sql = "UPDATE pish_customer_vendor SET buy_status = 'reject' WHERE buy_status = 'undone' AND order_id = $order_id";
              $result = $this->conn->query($sql);
              if ($result) {
                // $count = $this->conn->affected_rows;
                /* If code reaches this point without errors then commit the data in the database */
                mysqli_commit($this->conn);

                $statusComplete = true;

                // end second update
              } else {
                $statusComplete = false;
              }
            } else {
              $statusComplete = false;
            }
          } else {
            $statusComplete = false;
          }
        }
      } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($this->conn);
        //code to handle the exception
        return false;
      }

      return $statusComplete;
    }

    /**
     * is rejected this order for this user
     */
    public function isRejectedOrderForMe($user_id, $order_id)
    {
      $statusComplete = false;
      try {
        // run your code here
        $sql = "SELECT id FROM `pish_customer_vendor` WHERE buy_status = 'reject' and order_id = $order_id AND vendor_id = (\n"

          . "SELECT pish_phocamaps_marker_store.id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id\n"

          . ")";
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
          $row = $result->fetch_assoc();
          if (isset($row['id'])) {
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
      // echo $sql;
      return $statusComplete;
    }

    /**
     * accept type
     * goal: reject all record that has this order_id to buy_satus to "archive"
     */
    public function setOrderStatusToArchive($user_id, $order_id)
    {
      $statusComplete = false;

      try {
        // run your code here
        $sql = "UPDATE pish_customer_vendor set pish_customer_vendor.archive = '1' WHERE pish_customer_vendor.order_id =$order_id\n"

          . " AND  pish_customer_vendor.vendor_id = (SELECT pish_phocamaps_marker_store.id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id)";
        $result = $this->conn->query($sql);
        if ($result) {
          $statusComplete = true;
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
     * accept type
     * goal: reject one record that has this order_id and user_id"
     */
    public function setOrderStatusToReject($user_id, $order_id)
    {
      $statusComplete = false;

      try {
        // run your code here
        $sql = "UPDATE pish_customer_vendor set pish_customer_vendor.buy_status = 'reject' WHERE pish_customer_vendor.order_id =$order_id\n"

          . " AND  pish_customer_vendor.vendor_id = (SELECT pish_phocamaps_marker_store.id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id)";
        $result = $this->conn->query($sql);
        if ($result) {
          $statusComplete = true;
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
     * accept type
     * goal: set one order product to accept.
     */
    public function seOneOrderProductToAccept($order_id, $order_product_id, $user_id)
    {
      $statusComplete = false;
      //if all record was accepted?
      if ($this->getIsAllOrderProductAccepted($order_id)) {
        //all order_procut was accepted
        $statusComplete = 'other';
      } else {
        //all order_product does not accepted
        try {
          // run your code here
          $sql = "UPDATE `pish_hikashop_order_product`\n"

            . "SET pish_hikashop_order_product.vendor_id_accepted = (\n"

            . "    SELECT id\n"

            . "    FROM pish_phocamaps_marker_store\n"

            . "    WHERE pish_phocamaps_marker_store.user_id = $user_id\n"

            . "  )\n"

            . "WHERE pish_hikashop_order_product.order_product_id = $order_product_id\n"

            . "  AND IF( (pish_hikashop_order_product.vendor_id_accepted), false, true )";

          $result = $this->conn->query($sql);
          if ($result) {
            $statusComplete = true;
            $this->singleAcceptAffecerRows = $this->conn->affected_rows;
          } else {
            $statusComplete = false;
          }
        } catch (exception $e) {
          //code to handle the exception
          return false;
        }

        //if all record was accepted?
        $this->getIsAllOrderProductAccepted($order_id);
        if ($this->getIsAllOrderProductAccepted($order_id)) {
          //all order_procut was accepted
          $sac = $this->setOrdersBelogsToProductTOAccept($user_id, $order_id);
          if ($sac) {
            $this->allOrderAccepted = true;
          } else {
            $this->allOrderAccepted = false;
          }
        } else {
          //all order_product does not accepted
          $statusComplete = true;
        }
      }
      return $statusComplete;
    }
    /**
     * get fraction for acceptedAllrows/allRows that have same order_id
     * goal: return if all order_product table is accepted?
     */
    public function getIsAllOrderProductAccepted($order_id)
    {
      $statusComplete = false;
      try {
        // run your code here
        $sql = "SELECT acceptAll.accept/CountAllRecord.countAll as result from\n"

          . "(SELECT count(*) as countAll from pish_hikashop_order_product \n"

          . "\n"

          . "WHERE order_id = $order_id)as CountAllRecord,\n"

          . "(SELECT count(*) as accept from pish_hikashop_order_product \n"

          . "\n"

          . "WHERE order_id = $order_id AND pish_hikashop_order_product.vendor_id_accepted !=0)as acceptAll";

        $result = $this->conn->query($sql);
        if ($result) {
          // Associative array
          $row = $result->fetch_assoc();
          $dataResult = ($row['result']);
          if ($dataResult == 1) {
            return true;
          } else {
            return false;
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
     * get customerSessioonId
     */
    public function getCustomerSessionId($order_id)
    {
      $statusComplete = false;
      try {
        // run your code here
        $sql = "SELECT pish_session.session_id FROM `pish_session` \n"

          . "WHERE userid = (SELECT pish_hikashop_user.user_cms_id FROM `pish_hikashop_user`\n"

          . "WHERE pish_hikashop_user.user_id = (\n"

          . "SELECT pish_customer_vendor.customer_id FROM `pish_customer_vendor` WHERE pish_customer_vendor.order_id = $order_id LIMIT 1\n"

          . ") LIMIT 1) order by time desc limit 1";

        $result = $this->conn->query($sql);
        if ($result) {
          // Associative array
          $row = $result->fetch_assoc();
          $dataResult = ($row['session_id']);
          $rowcount = mysqli_num_rows($result);
          if ($rowcount && isset($dataResult)) {
            $this->customerSessionId = $dataResult;
            return true;
          } else {
            return false;
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
     * get storeOwnerSessioonId
     */
    public function getStoreOwnerSessionId($order_id)
    {
      $statusComplete = false;
      try {
        // run your code here
        $sql = "SELECT pish_session.session_id FROM pish_session WHERE pish_session.userid IN ( SELECT pish_phocamaps_marker_store.user_id FROM pish_phocamaps_marker_store WHERE id IN( SELECT pish_customer_vendor.vendor_id FROM pish_customer_vendor WHERE order_id = $order_id AND pish_customer_vendor.buy_status = 'done' ))";

        $result = $this->conn->query($sql);

        if ($result) {
          if (mysqli_num_rows($result) > 0) {
            // output data of each row
            $this->storeOwnerSessionId = array();
            while ($row = mysqli_fetch_assoc($result)) {
              $this->storeOwnerSessionId[] = $row;
            }
            return true;
          } else {
            return false;
          }
        } else {
          return false;
        }
      } catch (exception $e) {
        //code to handle the exception
        return false;
      }
      return $statusComplete;
    }

    //return result function accept one
    public function acceptOneResult(&$arrayData, $typeAction, $store, &$object, $order_id, $order_product_id, $user_id)
    {
      if ($typeAction == 'acceptOne') {
        $AllOrderProductToAccept = $store->seOneOrderProductToAccept($order_id, $order_product_id, $user_id);
        if ($AllOrderProductToAccept === true) {
          if ($store->singleAcceptAffecerRows > 0) {
            if ($store->allOrderAccepted) {
              $object->response = 'complete';
              if ($object->response == 'complete') {
                $object = null;
                $arrayData->response = 'ok';

                if ($this->getStoreOwnerSessionId($order_id)) {
                  $arrayData->storeSessionId = $this->storeOwnerSessionId;
                } else {
                  $arrayData->storeSessionId = $this->session->getId();
                }
                // set customerSessionId property to customer session id
                if ($this->getCustomerSessionId($order_id)) {
                  $arrayData->customerSessonId = $this->customerSessionId;
                } else {
                  $arrayData->customerSessonId = $this->customerSessionId;
                }
              }
            } else {
              $object->response = 'owned';
            }
          } else {
            $object->response = 'other';
          }
        } else if ($AllOrderProductToAccept == 'other') {
          $object->response = 'other';
        } else {
          $object->response = 'notok';
        }
      } else {
        $object->response = 'notok';
      }
    }

    //return result function accept All
    public function acceptAllResult(&$arrayData, $typeAction, $store, &$object, $order_id, $order_product_id, $user_id)
    {
      // set all record that have this order id to reject 
      $object = null;
      $arrayData->response = $store->setOrderStatusToAccept($user_id, $order_id,$arrayData);
      if ($arrayData->response == 'ok') {
        $arrayData->storeSessionId = $this->session->getId();
        // set customerSessionId property to customer session id
        if ($this->getCustomerSessionId($order_id)) {
          $arrayData->customerSessonId = $this->customerSessionId;
        } else {
          $arrayData->customerSessonId = $this->customerSessionId;
        }
      }
    }
    //return result function reject one
    public function rejectAllResult($typeAction, $store, &$object, $order_id, $order_product_id, $user_id)
    {
      if ($typeAction == 'rejectAll') {
        if ($store->setOrderStatusToReject($user_id, $order_id)) {
          $object->response = 'ok';
        }
      } else {
        $object->response = 'notok';
      }
    }

    //return result function archive all
    public function archiveAllResult($typeAction, $store, &$object, $order_id, $order_product_id, $user_id)
    {
      if ($typeAction == 'archive') {
        if ($store->setOrderStatusToArchive($user_id, $order_id)) {
          $object->response = 'ok';
        } else {
          $object->response = 'notok';
        }
      } else {
        $object->response = 'notok';
      }
    }


    /*
    # proposal section for working and adding all functionality needed for change or save informations
    */



    /**
     * save one proposal product
     * @method validateInputs(Array)
     * @return bool
     */
    private function saveOneProposalProduct(int $product_id, int $count, float $price, string $name, int $order_id, int $user_id): bool
    {
      //step 1 => get data
      $product_id = is_numeric($product_id) ? $product_id :  -1;
      $count = is_numeric($count) ? $count :  -1;
      $price = is_float($price) ? $price :  -1;
      $name = is_string($name) ? strip_tags(htmlspecialchars($name)) :  '';
      $order_id = is_numeric($order_id) ? $order_id :  -1;
      $user_id = is_numeric($user_id) ? $user_id :  -1;

      //validation
      $validation = $this->validateInputs([
        'int' => $product_id,
        'int' => $count,
        'int' => $price,
        'string' => $name,
        'int' => $order_id,
        'int' => $user_id
      ]);

      $status = false;
      if ($validation) {
        //step 2 => insert
        /* Start transaction */
        mysqli_begin_transaction($this->conn);
        try {
          if ($this->saveProposalProductStore($product_id, $count, $price, $name, $order_id, $user_id)) {
            if ($this->saveProposalProductOrder($product_id, $count, $price, (String)$name, $order_id, $user_id)) {
              if ($this->updateOrderTypeToProposal($order_id, $user_id)) {
                mysqli_commit($this->conn);
                $status = true;
              } else {
                mysqli_rollback($this->conn);
                $status = false;
              }
            } else {
              mysqli_rollback($this->conn);
              $status = false;
            }
          } else {
            mysqli_rollback($this->conn);
            $status = false;
          }
        } catch (mysqli_sql_exception $exception) {
          mysqli_rollback($this->conn);
          //code to handle the exception
          return false;
        }
      } else {
        return false;
      }

      //step3 =>  return result
      return $status;
    }

    /**
     * select if product for one store was saved in pish_store_product
     * check if two field product_id and store_id is not doublicate
     * @param int $product_id product id
     * @param int $user_id user_id
     * @return bool
     */
    private function productForStoreIsExist(int $product_id, int $user_id)
    {
      $statusComplete = false;
      try {
        // run your code here
        $sql = "select id from pish_product_store WHERE `product_id`=$product_id AND `store_id` =(SELECT pish_phocamaps_marker_store.id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id LIMIT 1)";

        $result = $this->conn->query($sql);
        if ($result) {
          // Associative array
          $count = $result->num_rows;
          if ($count >= 1) {
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
     * select if product for one store was saved in pish_store_product
     * check if two field product_id and store_id is not doublicate
     * @param int $product_id product id
     * @param int $order_id order_id
     * @return bool
     */
    private function productForProposalProductIsExist(int $product_id, int $order_id)
    {
      $statusComplete = false;
      try {
        // run your code here
        $sql = "SELECT proposal_order_product.order_product_id FROM proposal_order_product WHERE order_id =$order_id AND product_id = $product_id";


        $result = $this->conn->query($sql);
        if ($result) {
          // Associative array
          $count = $result->num_rows;
          if ($count >= 1) {
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
     * save one proposal product store
     * @method validateInputs(Array)
     * @return bool
     */
    private function saveProposalProductStore(int $product_id, int $count, float $price, string $name, int $order_id, int $user_id): bool
    {
      //step 1 => get data
      $product_id = is_numeric($product_id) ? $product_id :  -1;
      $count = is_numeric($count) ? $count :  -1;
      $price = is_float($price) ? $price :  -1;
      $name = is_string($name) ? strip_tags(htmlspecialchars($name)) :  '';
      $order_id = is_numeric($order_id) ? $order_id :  -1;
      $user_id = is_numeric($user_id) ? $user_id :  -1;

      //validation
      $validation = $this->validateInputs([
        'int' => $product_id,
        'int' => $count,
        'int' => $price,
        'string' => $name,
        'int' => $order_id,
        'int' => $user_id
      ]);

      $status = false;
      if ($validation) {

        //step 2 => insert
        /* Start transaction */
        try {
          $resultStatus = false;
          //step 1 => insert into pish_product_store
          // run your code here
          $sql = "INSERT INTO pish_product_store (`product_id`,`store_id`,`product_price`,`product_quantity`) VALUES($product_id,\n"

            . "(SELECT pish_phocamaps_marker_store.id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id LIMIT 1),$price,$count)";

          $result = $this->conn->query($sql);
          if ($result) {
            $count = $this->conn->affected_rows;
            if ($count > 0) {
              /* If code reaches this point without errors then commit the data in the database */
              $status = true;
            } else {
              $status = false;
            }
          }
        } catch (mysqli_sql_exception $exception) {
          //code to handle the exception
          return false;
        }
      } else {
        return false;
      }

      //step3 =>  return result
      return $status;
    }

    /**
     * update one proposal product store
     * @method validateInputs(Array)
     * @return bool
     */
    private function updateProposalProductStore(int $product_id, int $count, float $price, string $name, int $order_id, int $user_id): bool
    {
      //step 1 => get data
      $product_id = is_numeric($product_id) ? $product_id :  -1;
      $count = is_numeric($count) ? $count :  -1;
      $price = is_float($price) ? $price :  -1;
      $name = is_string($name) ? strip_tags(htmlspecialchars($name)) :  '';
      $order_id = is_numeric($order_id) ? $order_id :  -1;
      $user_id = is_numeric($user_id) ? $user_id :  -1;

      //validation
      $validation = $this->validateInputs([
        'int' => $product_id,
        'int' => $count,
        'int' => $price,
        'string' => $name,
        'int' => $order_id,
        'int' => $user_id
      ]);

      $status = false;
      if ($validation) {

        //step 2 => insert
        /* Start transaction */
        try {
          $resultStatus = false;
          //step 1 => insert into pish_product_store
          // run your code here
          $sql = "UPDATE pish_product_store SET `product_id`=$product_id,`store_id`=" .

            "(SELECT pish_phocamaps_marker_store.id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id LIMIT 1),`product_price`=$price,`product_quantity`=$count";

          $result = $this->conn->query($sql);
          if ($result) {

            $status = true;
          }
        } catch (mysqli_sql_exception $exception) {
          //code to handle the exception
          return false;
        }
      } else {
        return false;
      }

      //step3 =>  return result
      return $status;
    }


    /**
     * save one proposal product
     * @method validateInputs(Array)
     * @return bool
     */
    private function saveProposalProductOrder(int $product_id, int $count, float $price, string $name, int $order_id, int $user_id): bool
    {
      //step 1 => get data
      $product_id = is_numeric($product_id) ? $product_id :  -1;
      $count = is_numeric($count) ? $count :  -1;
      $price = is_float($price) ? $price :  -1;
      $name = is_string($name) ? strip_tags(htmlspecialchars($name)) :  '';
      $order_id = is_numeric($order_id) ? $order_id :  -1;
      $user_id = is_numeric($user_id) ? $user_id :  -1;

      //validation
      $validation = $this->validateInputs([
        'int' => $product_id,
        'int' => $count,
        'int' => $price,
        'string' => $name,
        'int' => $order_id,
        'int' => $user_id
      ]);

      $status = false;
      if ($validation) {

        //step 2 => insert
        /* Start transaction */
        try {
          $resultStatus = false;
          //step 1 => insert into pish_product_store
          // run your code here

          //step 2 => insert into proposal_order_product
          $sql ="INSERT INTO `proposal_order_product` (`order_id`, `product_id`, `order_product_quantity`, `order_product_name`, `order_product_code`, `order_product_price`, `order_product_tax`, `order_product_tax_info`, `order_product_options`, `order_product_option_parent_id`, `order_product_status`, `order_product_wishlist_id`, `order_product_wishlist_product_id`, `order_product_shipping_id`, `order_product_shipping_method`, `order_product_shipping_price`, `order_product_shipping_tax`, `order_product_shipping_params`, `order_product_parent_id`, `order_product_vendor_price`, `order_product_params`, `vendor_id_accepted`) " .
            " VALUES ($order_id, $product_id, $count, '".$name."', 'product_$product_id', $price, '0.00000', '', '', '0', '', '0', '0', '', '', '0.00000', '0.00000', NULL, '0', '0.00000', NULL, NULL)";
          $result = $this->conn->query($sql);
          if ($result) {
            $count = $this->conn->affected_rows;
            if ($count > 0) {
              /* If code reaches this point without errors then commit the data in the database */
              $status = true;
            } else {
              $status = false;
            }
            // end second update
          }
        } catch (mysqli_sql_exception $exception) {
          //code to handle the exception
          return false;
        }
      } else {
        return false;
      }

      //step3 =>  return result
      return $status;
    }


    /**
     * update one proposal product
     * @method validateInputs(Array)
     * @return bool
     */
    private function updateProposalProductOrder(int $product_id, int $count, float $price, string $name, int $order_id, int $user_id,$baseProductId): bool
    {
      //step 1 => get data
      $product_id = is_numeric($product_id) ? $product_id :  -1;
      $count = is_numeric($count) ? $count :  -1;
      $price = is_float($price) ? $price :  -1;
      $name = is_string($name) ? strip_tags(htmlspecialchars($name)) :  '';
      $order_id = is_numeric($order_id) ? $order_id :  -1;
      $user_id = is_numeric($user_id) ? $user_id :  -1;
      $baseProductId = is_numeric($baseProductId) ? $baseProductId : -1;

      //validation
      $validation = $this->validateInputs([
        'int' => $product_id,
        'int' => $count,
        'int' => $price,
        'string' => $name,
        'int' => $order_id,
        'int' => $user_id
      ]);

      $status = false;
      if ($validation) {

        //step 2 => insert
        /* Start transaction */
        try {
          $resultStatus = false;
          //step 1 => update pish_product_store
          // run your code here

          //step 2 => update proposal_order_product
          $sql = "UPDATE
          `proposal_order_product`
        SET
          `order_id` = '$order_id',
          `product_id` = '$product_id',
          `order_product_name` = '$name',
          `order_product_quantity` = '$count',
          `order_product_code` = 'product_$product_id',
          `order_product_price` = $price,
          `vendor_id_accepted` = (SELECT id FROM pish_phocamaps_marker_store WHERE user_id = $user_id) 

        WHERE
          `order_id` = $order_id
          AND `product_id` = $baseProductId";

          $result = $this->conn->query($sql);
          if ($result) {
            $status = true;

            // end second update
          }
        } catch (mysqli_sql_exception $exception) {
          //code to handle the exception
          return false;
        }
      } else {
        return false;
      }

      //step3 =>  return result
      return $status;
    }


    /**
     * if number is valid for proposal save information
     */
    private function validateInputs(array $values): bool
    {
      foreach ($values as $key => $value) {
        if ($key == 'int' && $value == -1) {
          return false;
          break;
        } else if ($key == 'string' && $value == '') {
          return false;
          break;
        } else {
        }
      }
      return true;
    }


    /**
     * update order type to proposal
     * @method validateInputs(Array)
     * @return bool
     */
    private function updateOrderTypeToProposal(int $order_id, int $user_id,&$object =null): bool
    {
      //step 1 => get data
      //validation
      $validation = $this->validateInputs([
        'int' => $order_id,
        'int' => $user_id
        ]);
        
        $status = false;
        if ($validation) {
          
          //step 2 => insert
          /* Start transaction */
          try {
            //step 1 => insert into pish_product_store
            // run your code here
            
            //step 2 => insert into proposal_order_product
            $sql = "UPDATE pish_customer_vendor SET buy_status = 'proposal' WHERE order_id = $order_id AND vendor_id = \n"
            
            . "(SELECT pish_phocamaps_marker_store.id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id)";
            
            $result = $this->conn->query($sql);
            if ($result) {           
            
            return true;
            // end second update
          } else {
            return false;
          }
        } catch (mysqli_sql_exception $exception) {
          return false;
        }
      } else {

        return false;
      }

      //step3 =>  return result
      return $status;
    }


    /**
     * doing transaction
     * @param bool $proposalProduct true insert, false update
     * @param bool $sotreProduct true insert ,false update
     * @return bool
     */
    private function transactionProcess($proposalProduct, $sotreProduct, int $product_id, int $count, float $price, string $name, int $order_id,int $baseProductId,int $user_id): bool
    {
      /* Start transaction */
      $status = array();
      mysqli_begin_transaction($this->conn);
      try {
        if ($proposalProduct == true) {
          //insert proposal product
          $this->saveProposalProductOrder($product_id, $count, $price, (String)$name, $order_id, $user_id) ? array_push($status, true) :  array_push($status, false);
          if ($sotreProduct == true) {
            //insert store product
            $this->saveProposalProductStore($product_id, $count, $price, $name, $order_id, $user_id) ? array_push($status, true) :  array_push($status, false);
          } else {
            //update sotre product
            $this->updateProposalProductStore($product_id, $count, $price, $name, $order_id,$user_id) ? array_push($status, true) :  array_push($status, false);

          }
        } else {
          //update proposal product
          $this->updateProposalProductOrder($product_id, $count, $price, $name, $order_id, $user_id,$baseProductId) ? array_push($status, true) :  array_push($status, false);
          if ($sotreProduct == true) {
            //insert store product
            $this->saveProposalProductStore($product_id, $count, $price, $name, $order_id, $user_id) ? array_push($status, true) :  array_push($status, false);
          } else {
            //update store product
            $this->updateProposalProductStore($product_id, $count, $price, $name, $order_id,$user_id) ? array_push($status, true) :  array_push($status, false);
          }
        }

        ($this->updateOrderTypeToProposal($order_id, $user_id)) ? array_push($status, true) : array_push($status, false);


        //update customer vendor order to proposal
        if (array_search(false, $status)) {
          mysqli_rollback($this->conn);
          return false;
        } else {
          mysqli_commit($this->conn);
          return true;
        }
      } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($this->conn);
        //code to handle the exception
        return false;
      }
    }
    /**
     * show result saveOrderOrderProposal
     */
    public function showResultSaveOneProposal($product_id, $count, $price, $name, $order_id, $user_id,$baseProductId, &$object)
    {
      $price = isset($price) ? $price : 1;
      $count = isset($count) ? $count : 1;
      //if order exist insert all order and if not exist do not any thing
      //select if products exist with product_id and order_id 
      $object->productExsit = $productExist = $this->proposalProductIsExistWithProductId($order_id, $user_id);
      if (!$productExist) {
        //if not exist insert all and update this one
        mysqli_begin_transaction($this->conn);
        try {
          if($object->insert =$this->insertAllProductOrderToProposalProducts($order_id,$user_id,$object)){
            //continue
            // $object->insertProductOrder='inserted';
            mysqli_commit($this->conn);
            $object->response = 'ok';
          }else{
            mysqli_rollback($this->conn);
            // $object->insertProductOrder = 'notInserted';
            return $object->response = 'notok';
          }
        } catch (mysqli_sql_exception $exception) {
          mysqli_rollback($this->conn);
          // $object->throwError = 'insertOrders';
          return $object->response = 'notok';
        }
      }

      //start transaction
      mysqli_begin_transaction($this->conn);
      try {
        //code...
        //update just this product
       $object->updateProposal = $update1 = $this->updateProposalProductOrder((int)$product_id, (int)$count, (float)$price, (string) $name, (int)$order_id, (int)$user_id,$baseProductId) ? true : false;
        //update
        $object->updateOrder = $updated2 = $this->updateOrderTypeToProposal($order_id, $user_id,$object) ? true : false;
        if ($update1 == false || $updated2 == false) {
          mysqli_rollback($this->conn);
          $object->response = 'notok';
        } else {

          //if every thing is ok insert into pish_store_product table if not exsit
          //insert into
          $objcet->status =$storeProductExist = $this->productForStoreIsExist($product_id, $user_id) ? true : false;
          if($storeProductExist){
            //update
           $updated = $this->updateProposalProductStore($product_id, $count, $price, $name, $order_id,$user_id) ? true : false;
            if($updated){
              mysqli_commit($this->conn);
              $object->response = 'ok';
            }else{
              mysqli_rollback($this->conn);
              $object->response= 'notok';
            }
          }else{
            //insert
            $storeSaved = $this->saveProposalProductStore($product_id, $count, $price, $name, $order_id, $user_id) ? true :  false;
            if($storeSaved){
              mysqli_commit($this->conn);
              $object->response = 'ok';
            }else{
              //update sotre product
              $object->response = 'notok';
            }
          }
          
        }
      } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($this->conn);
        $object->response = 'notok';
      }
      
    }
/**
     * show result saveOrderOrderProposal
     */
    public function oldshowResultSaveOneProposal($product_id, $count, $price, $name, $order_id, $user_id,$baseProductId, $object)
    {
      $price = isset($price) ? $price : 1;
      $count = isset($count) ? $count : 1;
      
      if ($product_id && $count && strlen($name) && $order_id && $user_id && $baseProductId) {
        //if product exist
       
        $proposalProductExist = $this->productForProposalProductIsExist($baseProductId, $order_id) ? true : false;

        if ($storeProductExist == false && $proposalProductExist == false) {
          $this->saveOneProposalProduct($product_id, $count, $price, $name, $order_id, $user_id) ?  $object->response = 'ok' : $object->response = 'notok';
        } else if ($storeProductExist == false) {
          $this->transactionProcess(!$proposalProductExist, !$storeProductExist, $product_id, $count, $price, $name, $order_id,$baseProductId,$user_id)  ?  $object->response = 'ok' : $object->response = 'notok';
        } else if ($proposalProductExist == false) {
          $this->transactionProcess(!$proposalProductExist, !$storeProductExist, $product_id, $count, $price, $name, $order_id,$baseProductId,$user_id)  ?  $object->response = 'ok' : $object->response = 'notok';
        } else {
          //do any thing because both of them is true and this mean nothing to insert
          return $object->response = 'notok';
        }
      } else {
        $object->error = 'data input error';
        $object->response = 'notok';
      }
    }

    /**
     * if product with this id is exist
     */
    private function proposalProductIsExistWithProductId($order_id, $user_id)
    {
      if (is_numeric($order_id)) {
        $sql = "SELECT IF(count(order_product_id),true,false) as result from proposal_order_product WHERE order_id = $order_id AND user_id = $user_id";
        $result = $this->conn->query($sql);
        if ($result) {
          if (mysqli_num_rows($result) > 0) {
            $row = $result->fetch_assoc();
            return $row['result'] ? true : false;
          } else {
            return false;
          }
        } else {
        }
      } else {
        return false;
      }
    }

    /**
     * if product with this id is exist
     */
    private function insertAllProductOrderToProposalProducts($order_id, $user_id,&$object)
    {

      mysqli_begin_transaction($this->conn);
      try {
        $sql = "SELECT * FROM pish_hikashop_order_product WHERE order_id=$order_id";
        // $result = $this->conn->query($sql);
        if (true) {
          /* fetch associative array */
          $counter = 0;
          if ($result = $this->conn->query(($sql))) {
            $rows = [];
            while ($row = $result->fetch_assoc()) {
              $count = mysqli_num_rows($result);
              array_push($rows,$row);
            }
            $object->data = $rows;
             for($i=0;$i<$count;$i++){
               $row =$rows[$i];
              // array_push($object->data[],$row);
              $order_product_id = $row['order_product_id'] ? : "''";
              $order_product_id = is_string($order_product_id) ? (strlen($order_product_id)>0 ? $order_product_id  : "''") :(is_float($order_product_id) ($order_product_id ) ? :( is_int($order_product_id) ? $order_product_id  : 0));
              $order_id =$row['order_id'] ? : "''";
              $order_id = is_string($order_id) ? (strlen($order_id)>0 ? $order_id  : "''") :(is_float($order_id) ($order_id ) ? :( is_int($order_id) ? $order_id  : 0));
              $product_id =$row['product_id'] ? : "''";
              $product_id = is_string($product_id) ? (strlen($product_id)>0 ? $product_id  : "''") :(is_float($product_id) ($product_id ) ? :( is_int($product_id) ? $product_id  : 0));
              $order_product_quantity =$row['order_product_quantity'] ? : "''";
              $order_product_quantity = is_string($order_product_quantity) ? (strlen($order_product_quantity)>0 ? $order_product_quantity  : "''") :(is_float($order_product_quantity) ($order_product_quantity ) ? :( is_int($order_product_quantity) ? $order_product_quantity  : 0));
              $order_product_name =$row['order_product_name'] ? : "''";
              $order_product_name = is_string("$order_product_name")  ? (strlen($order_product_name)>0 ? $order_product_name  : "''") :(is_float($order_product_name) ($order_product_name ) ? :( is_int($order_product_name) ? $order_product_name  : 0));
              $order_product_name =$row['order_product_name'] ? : "''";
              $order_product_name = is_string($order_product_name) ? (strlen($order_product_name)>0 ? $order_product_name  : "''") :(is_float($order_product_name) ($order_product_name ) ? :( is_int($order_product_name) ? $order_product_name  : 0));
              $order_product_code =$row['order_product_code'] ? : "''";
              $order_product_code = is_string($order_product_code) ? (strlen($order_product_code)>0 ? $order_product_code  : "''") :(is_float($order_product_code) ($order_product_code ) ? :( is_int($order_product_code) ? $order_product_code  : 0));
              $order_product_code =$row['order_product_code']  ? : "''";
              $order_product_code = is_string($order_product_code) ? (strlen($order_product_code)>0 ? $order_product_code  : "''") :(is_float($order_product_code) ($order_product_code ) ? :( is_int($order_product_code) ? $order_product_code  : 0));
              $order_product_price =$row['order_product_price'] ? : "''";
              $order_product_price = is_string($order_product_price) ? (strlen($order_product_price)>0 ? $order_product_price  : "''") :(is_float($order_product_price) ($order_product_price ) ? :( is_int($order_product_price) ? $order_product_price  : 0));
              $order_product_tax =$row['order_product_tax'] ? : "''";
              $order_product_tax = is_string($order_product_tax) ? (strlen($order_product_tax)>0 ? $order_product_tax  : "''") :(is_float($order_product_tax) ($order_product_tax ) ? :( is_int($order_product_tax) ? $order_product_tax  : 0));
              $order_product_tax_info =$row['order_product_tax_info'] ? : "''";
              $order_product_tax_info = is_string($order_product_tax_info) ? (strlen($order_product_tax_info)>0 ? $order_product_tax_info  : "''") :(is_float($order_product_tax_info) ($order_product_tax_info ) ? :( is_int($order_product_tax_info) ? $order_product_tax_info  : 0));
              $order_product_options =$row['order_product_options'] ? : "''";
              $order_product_options = is_string($order_product_options) ? (strlen($order_product_options)>0 ? $order_product_options  : "''") :(is_float($order_product_options) ($order_product_options ) ? :( is_int($order_product_options) ? $order_product_options  : 0));
              $order_product_option_parent_id =$row['order_product_option_parent_id'] ? : "''";
              $order_product_option_parent_id = is_string($order_product_option_parent_id) ? (strlen($order_product_option_parent_id)>0 ? $order_product_option_parent_id  : "''") :(is_float($order_product_option_parent_id) ($order_product_option_parent_id ) ? :( is_int($order_product_option_parent_id) ? $order_product_option_parent_id  : 0));
              $order_product_status =$row['order_product_status'] ? : "''";
              $order_product_status = is_string($order_product_status) ? (strlen($order_product_status)>0 ? $order_product_status  : "''") :(is_float($order_product_status) ($order_product_status ) ? :( is_int($order_product_status) ? $order_product_status  : 0));
              $order_product_wishlist_id =$row['order_product_wishlist_id'] ? : "''";
              $order_product_wishlist_id = is_string($order_product_wishlist_id) ? (strlen($order_product_wishlist_id)>0 ? $order_product_wishlist_id  : "''") :(is_float($order_product_wishlist_id) ($order_product_wishlist_id ) ? :( is_int($order_product_wishlist_id) ? $order_product_wishlist_id  : 0));
              $order_product_wishlist_product_id =$row['order_product_wishlist_product_id'] ? : "''";
              $order_product_wishlist_product_id = is_string($order_product_wishlist_product_id) ? (strlen($order_product_wishlist_product_id)>0 ? $order_product_wishlist_product_id  : "''") :(is_float($order_product_wishlist_product_id) ($order_product_wishlist_product_id ) ? :( is_int($order_product_wishlist_product_id) ? $order_product_wishlist_product_id  : 0));
              $order_product_shipping_id =$row['order_product_shipping_id'] ? : "''";
              $order_product_shipping_id = is_string($order_product_shipping_id) ? (strlen($order_product_shipping_id)>0 ? $order_product_shipping_id  : "''") :(is_float($order_product_shipping_id) ($order_product_shipping_id ) ? :( is_int($order_product_shipping_id) ? $order_product_shipping_id  : 0));
              $order_product_shipping_method =$row['order_product_shipping_method'] ? : "''";
              $order_product_shipping_method = is_string($order_product_shipping_method) ? (strlen($order_product_shipping_method)>0 ? $order_product_shipping_method  : "''") :(is_float($order_product_shipping_method) ($order_product_shipping_method ) ? :( is_int($order_product_shipping_method) ? $order_product_shipping_method  : 0));
              $order_product_shipping_price =$row['order_product_shipping_price'] ? : "''";
              $order_product_shipping_price = is_string($order_product_shipping_price) ? (strlen($order_product_shipping_price)>0 ? $order_product_shipping_price  : "''") :(is_float($order_product_shipping_price) ($order_product_shipping_price ) ? :( is_int($order_product_shipping_price) ? $order_product_shipping_price  : 0));
              $order_product_shipping_tax =$row['order_product_shipping_tax'] ? : "''";
              $order_product_shipping_tax = is_string($order_product_shipping_tax) ? (strlen($order_product_shipping_tax)>0 ? $order_product_shipping_tax  : "''") :(is_float($order_product_shipping_tax) ($order_product_shipping_tax ) ? :( is_int($order_product_shipping_tax) ? $order_product_shipping_tax  : 0));
              $order_product_shipping_params =$row['order_product_shipping_params'] ? : "''";
              $order_product_shipping_params = is_string($order_product_shipping_params) ? (strlen($order_product_shipping_params)>0 ? $order_product_shipping_params  : "''") :(is_float($order_product_shipping_params) ($order_product_shipping_params ) ? :( is_int($order_product_shipping_params) ? $order_product_shipping_params  : 0));
              $order_product_parent_id =$row['order_product_parent_id'] ? : "''";
              $order_product_parent_id = is_string($order_product_parent_id) ? (strlen($order_product_parent_id)>0 ? $order_product_parent_id  : "''") :(is_float($order_product_parent_id) ($order_product_parent_id ) ? :( is_int($order_product_parent_id) ? $order_product_parent_id  : 0));
              $order_product_vendor_price =$row['order_product_vendor_price'] ? : "''";
              $order_product_vendor_price = is_string($order_product_vendor_price) ? (strlen($order_product_vendor_price)>0 ? $order_product_vendor_price  : "''") :(is_float($order_product_vendor_price) ($order_product_vendor_price ) ? :( is_int($order_product_vendor_price) ? $order_product_vendor_price  : 0));
              $order_product_params =$row['order_product_params'] ? : "''";
              $order_product_params = is_string($order_product_params) ? (strlen($order_product_params)>0 ? $order_product_params  : "''") :(is_float($order_product_params) ($order_product_params ) ? :( is_int($order_product_params) ? $order_product_params  : 0));
              $vendor_id_accepted =$row['vendor_id_accepted'] ? : "''";
              $vendor_id_accepted = is_string($vendor_id_accepted) ? (strlen($vendor_id_accepted)>0 ? $vendor_id_accepted  : "''") :(is_float($vendor_id_accepted) ($vendor_id_accepted ) ? :( is_int($vendor_id_accepted) ? $vendor_id_accepted  : 0));
              

$sql =<<<Demo
    INSERT INTO `proposal_order_product`
  (`order_id`, `product_id`, `order_product_quantity`,
  `order_product_name`, `order_product_code`, `order_product_price`, 
  `order_product_tax`, `order_product_tax_info`, `order_product_options`, 
  `order_product_option_parent_id`, `order_product_status`, `order_product_wishlist_id`,
  `order_product_wishlist_product_id`, `order_product_shipping_id`,
  `order_product_shipping_method`, `order_product_shipping_price`, 
  `order_product_shipping_tax`, `order_product_shipping_params`,
  `order_product_parent_id`, `order_product_vendor_price`, `order_product_params`, `vendor_id_accepted`, `user_id`)
  VALUES ($order_id,$product_id,$order_product_quantity,
  \'$order_product_name\',\'$order_product_code\',$order_product_price,
  $order_product_tax,\'$order_product_tax_info\',$order_product_options,
  $order_product_option_parent_id,$order_product_status,$order_product_wishlist_id,
  $order_product_wishlist_product_id,$order_product_shipping_id,
  $order_product_shipping_method,$order_product_shipping_price,
  $order_product_shipping_tax,$order_product_shipping_params,
  $order_product_parent_id,$order_product_vendor_price,$order_product_params,$vendor_id_accepted,$user_id)
  Demo;
  $object->sql = stripslashes($sql);
            $result = $this->conn->query(stripslashes($sql)) ;          
            if ($result) {
              $counter++;
            } else {
              mysqli_rollback($this->conn);
              return false;
            }
          }


          if($counter == $count){
            mysqli_commit($this->conn);
            return true;
          }else{
            mysqli_rollback($this->conn);
          }
        } else {
          return false;
        }
        } else {
          return false;
        }
      } catch (mysqli_sql_exception $exception) {

        mysqli_rollback($this->conn);
        return false;
      }
    }


    /**
     * show result saveOrderOrderProposal
     */
    public function showResultSaveAllProposal($product_id, $count, $price, $name, $order_id, $user_id, &$object)
    {
      //select if products exist with product_id and order_id 
      $productExist = $this->proposalProductIsExistWithProductId($order_id, $user_id);
      if (!$productExist) {
        //if not exist insert all and update this one
        mysqli_begin_transaction($this->conn);
        try {
          if($this->insertAllProductOrderToProposalProducts($order_id,$user_id,$object)){
            //continue
            mysqli_commit($this->conn);
          }else{
            mysqli_rollback($this->conn);
            return $object->response = 'notok';
          }
        } catch (mysqli_sql_exception $exception) {
          mysqli_rollback($this->conn);
          return $object->response = 'notok';
        }
      }
      
      
      //start transaction
      mysqli_begin_transaction($this->conn);
      try {
        //code...
        //update just this product
        $update1 = $this->updateProposalProductOrder((int)$product_id, (int)$count, (float)$price, (string) $name, (int)$order_id, (int)$user_id,$product_id) ? true : false;
        //update
        $updated2 = $this->updateOrderTypeToProposal($order_id, $user_id,$object) ? true : false;
        if ($update1 == false || $updated2 == false) {
          mysqli_rollback($this->conn);
          $object->response = 'notok';
        } else {
          mysqli_commit($this->conn);
          $object->response = 'ok';
        }
      } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($this->conn);
        $object->response = 'notok';
      }
    }

    /**
     * sent poposal store Orders to consumer
     * change poposal status to 2 => this mean poposal order is sent to consumer
     */

     public function sentStoreProposal(int $order_id,int $user_id,&$object){
       mysqli_begin_transaction($this->conn);
      try {
        // run your code here
        $sql = "UPDATE `pish_customer_vendor` SET `pish_customer_vendor`.`proposal_completed` =2 WHERE order_id = $order_id and pish_customer_vendor.vendor_id = (SELECT id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id)";
        $result = $this->conn->query($sql);
        $count = $this->conn->affected_rows;
        if ($result) {
          if($count>0){
          //start
          $sql2 = "UPDATE `proposal_order_product` SET `proposal_order_product`.`vendor_id_accepted` = (SELECT id FROM pish_phocamaps_marker_store WHERE pish_phocamaps_marker_store.user_id = $user_id) WHERE order_id = $order_id and `proposal_order_product`.user_id =$user_id";
          $result = $this->conn->query($sql2);
          if($result){
            mysqli_commit($this->conn);

            //get session store Ownere and consumer session ids
            $object->response = 'ok';
            $object->storeSessionId = $this->session->getId();
            // set customerSessionId property to customer session id
            if ($this->getCustomerSessionId($order_id)) {
              $object->customerSessonId =$this->customerSessionId;
            } else {
              $object->customerSessonId =$this->customerSessionId;
            }
            
          }else{
            $object->response == 'notok';
            mysqli_rollback($this->conn);
          }//end
            
          }else{
            $object->response == 'notok';
            mysqli_rollback($this->conn);
          }
        } else {
          $object->response == 'notok';
          mysqli_rollback($this->conn);
        }
      } catch (exception $e) {
        //code to handle the exception
        $object->response == 'notok';
        mysqli_rollback($this->conn);
      }
     }

  }

  //global Array
  $arrayData = array();
  //   using class
  $json = file_get_contents('php://input');
  $post = json_decode($json, true);
  $user_id = $post['user_id'];
  $order_id = null;
  if (array_key_exists('order_id', $post)) {
    $order_id = $post['order_id'];
  }
  $typeAction = $post['typeAction']; //is "accept" or "reject"
  $order_product_id = null;
  if (array_key_exists('order_product_id', $post)) {
    $order_product_id = $post['order_product_id'];
  }

  if ($post && count($post) && $user_id && $typeAction) {

    $object = new stdClass();
    $store = new ChangeOrderStatus($conn, JFactory::getSession());

    if ($order_id) {
      if ($typeAction == 'acceptAll') {
        $store->acceptAllResult($customeObject, $typeAction, $store, $object, $order_id, $order_product_id, $user_id);
      } else if ($typeAction == 'rejectAll') {

        $store->rejectAllResult($typeAction, $store, $object, $order_id, $order_product_id, $user_id);
      } else if ($typeAction == 'archive') {
        $store->archiveAllResult($typeAction, $store, $object, $order_id, $order_product_id, $user_id);
      } else if ($typeAction == 'saveOneProposal') {
        $product_id = $post['product_id'];
        $count = $post['count'];
        $price = $post['price'];
        $name = $post['name'];
        $order_id = $post['order_id'];
        $user_id = $post['user_id'];
        $baseProductId = $post['baseProductId'];
        $store->showResultSaveOneProposal($product_id, $count, $price, $name, $order_id, $user_id,$baseProductId ,$object);
      } else if ($typeAction == 'saveَAllProposal') {
        $product_id = $post['product_id'];
        $count = $post['count'];
        $price = $post['price'];
        $name = $post['name'];
        $order_id = $post['order_id'];
        $user_id = $post['user_id'];

        $store->showResultSaveAllProposal($product_id, $count, $price, $name, $order_id, $user_id, $object);
      } else if ($typeAction == 'sentStoreProposal') {
        $order_id = $post['order_id'];
        $user_id = $post['user_id'];
        $object->status = 'hi';
        $store->sentStoreProposal($order_id, $user_id, $object);
      } else if ($typeAction == 'acceptOne') {
        if ($order_product_id) {
          $store->acceptOneResult($customeObject, $typeAction, $store, $object, $order_id, $order_product_id, $user_id);
        } else {
          $object->response = 'notok';
        }
      } else {
        $object->response = 'notok';
      }
    } else {
      $object->response = 'notok';
    }
  } else {
    $object->response = 'notok';
  }


  jsonEncodeOutput($object, $customeObject);

  /**
   * customer jsonEncodedOutput
   */
  function jsonEncodeOutput($normalObject = null, $customeObject = null)
  {
    if (isset($normalObject)) {
      // echo json_encode([$normalObject->response], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      echo json_encode([$normalObject], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else if (isset($customeObject)) {
      echo json_encode([$customeObject], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
      // echo json_encode([$normalObject->response], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      echo json_encode([$normalObject], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
  }


  ?>