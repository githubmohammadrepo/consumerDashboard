<?php
use Joomla\CMS\Factory;
$document = Factory::getDocument();
// above 2 lines are equivalent to the older form: $document = JFactory::getDocument();
$document->addStyleSheet('/templates/dataTable-jquery/manStyle.css'); 
$document->addStyleSheet('/templates/%d9%be%d9%86%d9%84%20%da%a9%d8%a7%d8%b1%d8%a8%d8%b1/styles.css');
$document->addScript('/templates/dataTable-jquery/mainScript.js');


    // step 1 => sent curl user_id ->cms_user_id
$error;
// step 2 => show contents
function getStoreOrders($user_id, &$error)
{
  //default we have no error
  $error = false;
  //sent message to stores
  //get all sessionIds belongs to vendor own user_id
  $url = 'http://hypertester.ir/serverHypernetShowUnion/StoreOwnerOrders.php';
  // start get card info
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_id' => $user_id]));
  // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);
  if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    $error = true;
    curl_close($ch);
    return;
  }
  curl_close($ch);
  $content = json_decode($result);
  return $content;
}
$user_id = JFactory::getUser()->id;
$result = getStoreOrders($user_id, $error);
if (!$error) {
  if ($result[0]->response == 'notok') {
    echo "<h4 style='color:red'>هیچ رکوردی پیدا نشد.</h4>";
  } else {
    if (count($result[1])) {
?>
      <div class="alert none nofication alert-danger blue text-center" role="alert">
        <p id="alertText">سفارش مورد نظر شما قبلا توسط فروشگاه دیگر پذیرفته شده است.</p>
        <span class="close-alert btn btn-danger" onclick="removeAlert(true,null)">X</span>
      </div>
      <table id="storeOrders" class="w-100 table table-warning table-bordered table-hover">
        <!-- table caption -->
        <caption>جدول سفارشات مشتری</caption>
        <thead>
          <tr>
            <th scope="col">شماره سفارش</th>
            <th scope="col">نام محصول</th>
            <th scope="col">تعداد محصول</th>
            <th scope="col">عملیات سفارش</th>
            <th scope="col">بایگانی</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $status = -1;
          foreach ($result[1] as $key => $value) {
            if ($status != $value->order_id) {
              if ($status != -1) {
          ?>
                <tr class="list-group-item-light">
                  <td colspan="5"></td>
                </tr>
                <?php
              }
              $status = $value->order_id;
              echo '<tr id="order' . $value->order_id . '" class="orderHeader" onClick="toggleRowInfos(this,event)">';
              echo '<td> ' . ($key + 1) . '</td>'; //1
              echo '<td> ' . $status . ' key: ' . $key . '</td>'; //2
              echo '<td> ' . $status . ' key: ' . $key . '</td>'; //3
              switch ($inner = [$value->buy_status,$value->proposal_completed]) {
                case $inner[0]=='undone': {
                    echo "<td style='color:red' id='statusField" . $value->order_id . "'>"; //4
                ?>
                    <button class="btn btn-default btn-success" id="success<?php echo $value->order_id; ?>" onclick="acceptAllOrder(<?php echo $user_id; ?>,this,event)" data-orderId="<?php echo $value->order_id; ?>">پذیرفتن</button>
                    <button class="btn btn-default btn-danger" id="reject<?php echo $value->order_id; ?>" onclick="rejectAllOrder(<?php echo $user_id; ?>,this,event)" data-orderId="<?php echo $value->order_id; ?>">رد کردن</button>
                    <?php echo "</td>"; ?>
                <?php
                  }
                  break;
                case $inner[0]=='proposal' && $inner[1]==0: {
                    echo "<td style='color:green'>پیشنهاد ارسال شد</td>";
                  }
                  break;
                  case $inner[0]=='proposal' && $inner[1]==1: {
                    echo "<td style='color:green'>پیشنهاد پذیرفته شد</td>";
                  }
                  break;
                  case $inner[0]=='proposal' && $inner[1]==-1: {
                    echo "<td style='color:green'>پیشنهاد رد شد</td>";
                  }
                  break;
                case $inner[0]=='done': {
                    echo "<td style='color:green'>انجام شده</td>";
                  }
                  break;
                case $inner[0]=='reject': {
                    echo "<td style='color:red'>رد شد</td>";
                  }
                  break;
              }
              if ($value->archive) {
                //hvae value 1
                echo "<td style='color:red'>"; //5
                echo 'بایگانی شد';
                echo "</td>";
              } else {
                //does not have value or have null value
                echo "<td style='color:red'>"; //5 
                ?>
                <button class="btn btn-default btn-warning" onclick="archiveOrder(<?php echo $user_id; ?>,this,event)" data-orderId="<?php echo $value->order_id; ?>">بایگانی کردن</button>
              <?php echo "</td>";
              }
              echo '</tr>';
              // show header;
              echo "<tr  class='order" . $value->order_id . " none transition'>";
              echo '<th scope="col">کد سفارش</th>';
              echo '<th scope="col">نام محصول</th>';
              echo '<th scope="col">تعداد محصول</th>';
              echo '<th scope="col">قیمت محصول</th>';
              echo '<th scope="col">وضعیت سفارش</th>';
              echo '</tr>';
            }
            echo "<tr class='order" . $value->order_id . " baseProductId" . $value->product_id . "  none transition'>";
            echo "<th scope='row'>$value->order_id</th>";
            echo "<td id='name$value->product_id'>$value->order_product_name</td>";
            echo "<td id='count$value->product_id'>$value->order_product_quantity</td>";
            echo "<td id='price$value->product_id'>" . ($value->order_product_quantity * $value->order_product_price * $value->order_product_tax) . "</td>";
            if ($value->vendor_id_accepted == null && $value->proposal_completed==0 && $value->buy_status=='undone') {
              echo "<td style='color:red' class='status" . $value->order_id . "'>";
              ?>
              <button class="btn btn-default" id="<?php echo  'successOne'.$value->product_id ?>" onclick="acceptOneOrder(<?php echo $user_id; ?>,this,event,<?php echo $value->order_product_id; ?>)" style="background:green;color:white;" data-orderId="<?php echo $value->order_id; ?>">قبول</button>
              <!-- <button class="btn btn-default" onclick="rejectOrder(<?php echo $user_id; ?>,this,event)" style="background:red;color:white;" data-orderId="<?php echo $value->order_id; ?>">رد</button> -->
              <!-- Trigger the modal with a button -->
              <!-- <button type="button" class="btn btn-warning btn" data-toggle="modal" data-target="#myModal">Open Modal</button> -->
              <button type="button" class="btn btn-warning btn" onclick="clickModal(<?php echo $user_id; ?>,<?php echo $value->order_id; ?>,<?php echo $value->product_id; ?>,<?php echo $value->order_product_id; ?>)">تغییر محصول</button>
             <?php
              echo "</td>";
            } elseif (($value->vendor_id_accepted == $result[0]->store_vendor_id) && $value->proposal_completed==0 && $value->buy_status=='done') { //end if order_product_id
              echo "<td style='color:white'>انجام شده</td>";
            } elseif( $value->proposal_completed==0 && $value->buy_status=='reject') { //end if order_product_id
              echo "<td style='color:red'> رد شد</td>";
            } elseif( $value->proposal_completed==0 && $value->buy_status=='proposal') { //end if order_product_id
              echo "<td style='color:red'> پیشنهاد ارسال شد</td>";
            }elseif( $value->proposal_completed==1 && $value->buy_status=='proposal') { //end if order_product_id
              echo "<td style='color:red'> پیشنهاد پذیرفته شد</td>";
            }elseif( $value->proposal_completed==-1 && $value->buy_status=='proposal') { //end if order_product_id
              echo "<td style='color:red'> پیشنهاد رد شد</td>";
            }else { //end if order_product_id
              echo "<td style='color:red'> رد شد</td>";
            }
            echo "</tr>";
          }
          ?>
        </tbody>
      </table>
<?php
    }
  }
} else {
  echo "<h4 style='color:red'>خطا در  اینترنت شما.</h4>";
}
?>


<!-- add all scripts -->
<?php
  $document->addScript('http://hypertester.ir/templates/%d9%be%d9%86%d9%84%20%da%a9%d8%a7%d8%b1%d8%a8%d8%b1/script.js',['defer']);
?>