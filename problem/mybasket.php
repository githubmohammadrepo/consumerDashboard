{source}

<?php

session_start();

error_reporting(E_ALL);

ini_set('error_reporting', E_ALL);

ini_set('display_errors', 1);

// end headers

?>

<?php

class Index

{

  public $vendor_ids = array();

  public $user_id; //like 963

  public $userIdArray = array();

  public $user_id_json_encoded; //is json_encoded array like ['user_id'=>$user_id]

  public $cardSaved;

  public $foundStore;

  public $error = false;

  public $vendor_user_ids = array();

  //array of geolocation current user

  public $post = [

    'lat' => 0,

    'lng' => 0

  ];

  public function __construct($user_id)

  {

    $this->userIdArray  = $user_id['user_id'];

    $this->user_id = $user_id;

    $this->user_id_json_encoded = json_encode(['user_id' => $this->userIdArray]);
  }

  /**

   * get current user location by json_encoded current user_id

   */

  public function getCurrentUserLocation()

  {

    //default we have no error

    $this->error = false;

    $userLocationUrl = 'http://hypertester.ir/serverHypernetShowUnion/getUserLocation.php';

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $userLocationUrl);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->user_id_json_encoded);

    // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {

      $error_msg = curl_error($ch);

      $this->error = true;

      curl_close($ch);

      return true;
    }

    curl_close($ch);

    // echo $result;

    $status = true;

    $userLocationResult = (json_decode($result, true));

    foreach ($userLocationResult as $key => $value) {

      if (isset($value['status']) && $value['status'] == 0) {

        //error userlocation does not found

        $status  = false;

        break;
      } else {

        $this->post['lat'] = $value['latitude'];

        $this->post['lng'] = $value['longitude'];

        $this->post['city'] = $value['city'];

        $this->post['province'] = $value['province'];
      }
    }

    return $status;
  }

  /**

   * select nearest 20 shop by current user geolocation 

   * and user_id

   */

  public function selectNearestShop()

  {

    //default we have no error

    $this->error = false;

    $url = "http://hypertester.ir/serverHypernetShowUnion/SelectNearestShop.php";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POST, 1);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->post));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);

    if (curl_errno($ch)) {

      $error_msg = curl_error($ch);

      $this->error = true;

      curl_close($ch);

      return;
    }

    curl_close($ch);

    $contents = json_decode($output, true);

    $this->foundStore = -1;

    // end select nearest shop

    if ($contents && count($contents) > 0) {

      if ($contents[0]['id'] == "notok") {
        $this->foundStore = -1;

        return [];
      } else {

        $ids = [];

        for ($j = 0; $j < count($contents); $j++) {

          $ids[] = [

            'id' => $contents[$j]['id'],

            'user_id' => $contents[$j]['user_id'],

          ];
        }

        $this->vendor_ids = $ids;

        $this->foundStore = 1;

        $card = [

          // 'user_id' => json_decode($this->user_id)->user_id,

          'user_id' => $this->userIdArray,

          'orders' => [[

            'vendor_id' => $ids,

            // 'products' => $products,

          ]],

        ];

        return $card;
      }
    } else {

      $this->foundStore = -1;
      var_dump($contents);


      return [];
    }
  }

  /**

   * sent current order to 20 store and return reponse

   * and message contain all products to sent by chat

   */

  public function sentUserCartTo20Store($card)

  {

    //default we have no error

    $this->error = false;

    $url = "http://hypertester.ir/serverHypernetShowUnion/Sendto20StoreAndNotify.php";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POST, 1);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($card));

    // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['sentTo20Store'=>true]));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);

    if (curl_errno($ch)) {

      $error_msg = curl_error($ch);

      $this->error = true;

      curl_close($ch);

      return;
    }

    curl_close($ch);

    $contents = json_decode($output);

    // if($contents instanceof stdClass

    return $contents;
  }

  /**

   * get session id users in jomla

   */

  public function getSessionIdUsers()

  {

    //default we have no error

    $this->error = false;

    //sent message to stores

    foreach ($this->vendor_ids as $key => $value) {

      if ($value['user_id'] && is_numeric($value['user_id'])) {

        array_push($this->vendor_user_ids, $value['user_id']);
      }
    }

    //get all sessionIds belongs to vendor own user_id

    $url = 'http://hypertester.ir/serverHypernetShowUnion/getJchatSessionIdsByVendorOwnerIds.php';

    // start get card info

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($vendorOwnerSessionIds = ['vedorOwnerIds' => $this->vendor_user_ids]));

    // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {

      $error_msg = curl_error($ch);

      $this->error = true;

      curl_close($ch);

      return;
    }

    curl_close($ch);

    $content = json_decode($result);

    return $content;
  }

  /**

   * get current user location by json_encoded current user_id

   */

  public function getLastOrderIdByCurl()

  {

    //default we have no error

    $this->error = false;

    $userLocationUrl = 'http://hypertester.ir/serverHypernetShowUnion/getLastOrderId.php';

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $userLocationUrl);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->user_id_json_encoded);

    // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {

      $error_msg = curl_error($ch);

      $this->error = true;

      curl_close($ch);

      return null;
    }

    curl_close($ch);

    // echo $result;

    $decodeResult = (json_decode($result, true));

    return $decodeResult;
  }
}

// end class Index

/**

 * using class

 */

$user_id = (JFactory::getUser())->id;

// curl to get last order_id

// end curl to get last order_id

// $user_id = 963;
$user_id = ['user_id' => $user_id];

//create init from class

$index = new Index($user_id);

$liveLastOrderId = null;

$session = JFactory::getSession();

$last_order_id = $index->getLastOrderIdByCurl();

if ($last_order_id == null) {
  
  //returned any thing

  $session->set('last_order_id', null);
  
  echo '<h2>session is not set</h2>';
} else {

  //some thing returned

  if ($last_order_id['response'] == 'notok') {

    //error get data from serve

    $session->set('last_order_id', null);

    echo 'last_order_id return null';
  } else {

    //data was returned
    $liveLastOrderId = $last_order_id['response'];

    if ($session->has('last_order_id') && ($liveLastOrderId == $session->get('last_order_id'))) {

      echo '<h3>سبد خرید شما قبلا به فروشگاه ها ی نزدیک فرستاده شده است</h3>';
    } else {

      /**

       * complete opareation or main operation

       */

      $index->cardSaved = false;

      $index->foundStore = 0;

      //  if (isset($_POST) && isset($_POST["lat"]) && isset($_POST["lng"])) {

      if ($user_id['user_id']) {

?>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.js"></script>

        <!-- end show html codes -->

        <?php

        if ($index->getCurrentUserLocation()) {

          //user location does returned   

          if ($index->error) {

            //get user location is not returned

            echo '<h3>Error => user location is not found</h3>';
          } else {

            //get user location was successfull

            /**

             *  end get lat and lng location current user. **completed**

             * start select nearest shop

             * 

             * error possliblity

             * ----------------

             * one => any thing error is set

             * two => empty array fail curl

             * thre => complete array with values

             */

            $card = $index->selectNearestShop();

            if ($index->error) {

              //fail select nearest 20 shops

              echo '<h3>Error => nearer store is not found</h3>';
            } else {

              //get nearest shop is successful

              if ($card && count($card)) {

                //found some nearest shop successfully

                $contents = $index->sentUserCartTo20Store($card);

                //set order_id to session

                if ($index->error) {

                  //error curl get correct data

                  echo '<h3>error => cant find any user information from server</h3>';
                } else {

                  //curl get data successfull

                  if ($contents[0]->response == 'notok') {

                    echo " <h3>محصولات شما قبلا به فروشگاه های نزدیک فرستاده شده است.</h3>";

                    //show error

                  } else {

                    echo '<hr >';

                    $index->cardSaved = true;

                    $vendorOwnerIdSessionIds = $index->getSessionIdUsers();

                    if (count($vendorOwnerIdSessionIds)) {

        ?>

                      <script>
                        //setInterval(display, 6000);

                        let jsonLiveSite = "http://hypertester.ir/index.php?option=com_jchat&format=json"

                        var postObject;

                        <?php

                        // $btns = "<button style='color:white;background-color: green;'>قبول</button>" .

                        //   " <button style='color:white;background-color: orange;'>صحبت کردن</button>" .

                        //   " <button style='color:white;background-color: red;'>رد</button>";

                        // 

                        ?>

                        <?php foreach ($index->vendor_user_ids as $key => $storeOwnUser_id) { ?>

                          postObject = {

                            "message": "<?php echo "<a href='http://hypertester.ir/index.php/%D9%86%D8%A7%D8%AD%DB%8C%D9%87-%DA%A9%D8%A7%D8%B1%D8%A8%D8%B1%DB%8C'>مشاهده سفارش جدید</a>"; ?>",

                            "task": "stream.saveEntity",

                            <?php

                            foreach ($vendorOwnerIdSessionIds as $key => $value) {

                              if ($value->userid == $storeOwnUser_id) {

                            ?> "to": "<?php echo $value->session_id; ?>", //error - solved => ownUser sessionId

                            <?php

                                break;
                              }
                            }

                            ?> "tologged": "<?php echo $storeOwnUser_id; ?>"

                          };

                          $.post(jsonLiveSite, postObject, function(response) {

                            console.log(response);

                            postObject = null;

                          });

                        <?php } ?>

                        // console.log('hi mohammad', '');

                        // console.log('jqEvent' + jqEvent);

                        // console.log('this' + this);

                        // console.log('ae:' + ae);

                        // console.log('complete object:' + completeObject);
                      </script>

      <?php

                    } else {

                      echo '<h3>اطلاعات شما به هیچ فروشگاهی ارسال نشد.</h3>';
                    }
                  }

                  ///////////////////////////////////////

                }
              } else {

                //does not find any near shop

                echo '<h3>warning => cant find any nearer store</h3>';
              } //end close this else

            }
          }
        } else { // end error user location

          echo '<h3>موقعیت مکانی کاربر پیدا نشد.</h3>';
        }
      } //end if isset post data

      ?>

      <?php

      // show successful message 

      // for send message and orders

      if ($index->cardSaved) {

      ?>

        <div style="text-align: center; background-color: #eee; padding: 10px; margin-bottom: 10px; color: green; font-size: 16px; font-weight: bold;">

          <p>سبد خرید با موفقیت برای فروشگاه های نزدیک شما ارسال شد. </p>

        </div>

      <?php

        $session->set('last_order_id', $last_order_id['response']);

        //header( "refresh:15;url=http://hypertester.ir/" );

      }

      if (true) {

      ?>

        <?php

        if ($index->foundStore == -1) {
          echo 'working';die(' - "exit page"');

        ?>

          <div style="text-align: center; background-color: #eee; padding: 10px; margin-bottom: 10px; color: red; font-size: 16px; font-weight: bold;">

            <p>فروشگاهی در نزدیکی شما پیدا نشد.</p>

          </div>

        <?php

        }

        ?>

      <?php

      } else {

      ?>

        <div>

          <p style="width: 100%; background-color: #eee; padding: 10px; text-align: center;">

            سبد خرید خالی می باشد.

          </p>

        </div>

<?php

      }
    }
  }
}

?>

{/source}