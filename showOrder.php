<?php
use Joomla\CMS\Factory;
$document = Factory::getDocument();
// Add styles
$style = <<<Demo
  .none{
    display:none;
  }
  .custome-nav-bg li a{
    color:white;
  }
  .custome-nav-bg li:hover a{
    color:black;
  }
  .custome-nav-bg li{
    background-color:cadetblue  !important;
  }
Demo;
$document->addStyleDeclaration($style);
// above 2 lines are equivalent to the older form: $document = JFactory::getDocument();

// step 2 => show contents

function showOrdersForConsumers($user_id, &$error)
{
  //default we have no error
  $error = false;
  //sent message to stores
  //get all sessionIds belongs to vendor own user_id
  $url = 'http://hypertester.ir/serverHypernetShowUnion/getConsumerOrders.php';
  // start get card info
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_id' => $user_id,"type"=> "getAllOrders"]));
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
  $content = json_decode($result, JSON_UNESCAPED_UNICODE);
  return $content;
}



// step 1 => sent curl user_id ->cms_user_id
$user_id = JFactory::getUser()->id;
$error=false;
$result = showOrdersForConsumers($user_id, $error);



if (!$error) {
  if ($result[0]['response'] == 'notok') {
    echo "<h4 style='color:red'>هیچ رکوردی پیدا نشد.</h4>";
  } else {
    if (count($result[0]['data'])) {
?>
      <div class="alert none nofication alert-danger blue text-center" role="alert">
        <p id="alertText">سفارش مورد نظر شما قبلا توسط فروشگاه دیگر پذیرفته شده است.</p>
        <span class="close-alert btn btn-danger" onclick="removeAlert(true,null)">X</span>
      </div>

        <!-- show navs for category output -->
        <ul class="nav custome-nav-bg nav-pills nav nav-tabs nav-justified">
          <li role="presentation" class="active" ><a href="#">Home</a></li>
          <li role="presentation" class=""><a href="#">Profile</a></li>
          <li role="presentation" class=""><a href="#">Profile</a></li>
          <li role="presentation" class=""><a href="#">Profile</a></li>
          <li role="presentation" class=""><a href="#">Messages</a></li>
        </ul>
      
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
        <tbody class="text-light">
          <?php
          $status = -1;
          //get order
          foreach ($result[0]['data'] as $key => $value) {
            echo "<tr id='order".($key+1)."' class='orderHeader' onclick='toggleOrder(".'"order'.($key+1).'"'.")' style='color:white;background-color:#34568B;'>";
              echo "<td>".($key+1)."</td>";
              echo "<td>one</td>";
              echo "<td>".count($valueTwo)."</td>";
              
              //get order status
            foreach ($value as $keyOne => $valueOne) {
                echo "<td>$keyOne</td>";
                echo "<td>four</td>";
              echo "</tr>";
              //get vendor id
              foreach ($valueOne as $keyTwo => $valueTwo) {
                echo "<tr  class='order".($key+1)." orderHeader none' id='store".($keyTwo).'id'.($key+1)."' onclick='toggleStore(".'"store'.($keyTwo).'id'.($key+1).'"'.")' style='color:white;background-color:#1c53a5'>";
                  echo "<td>".($keyTwo)."</td>";
                  echo "<td>فروشگاه $keyTwo</td>";
                  echo "<td>".count($valueTwo)."</td>";
                  echo "<td>$keyOne</td>";
                  echo "<td>four</td>";
                echo "</tr>";
                //show one product order
                foreach ($valueTwo as $keyThree => $valueThree) {
                  echo "<tr  class='store".($keyTwo).'id'.($key+1)." orderHeader none' id='product".$valueThree['product_id']."' style='color:white;background-color:#554884 !important'>";
                  echo "<td>".$valueThree['id']."</td>";
                  echo "<td>".$valueThree['order_product_name']."</td>";
                  echo "<td>".$valueThree['order_product_quantity']."</td>";
                  echo "<td>عملیات سفارش</td>";
                  echo "<td>باگیانی</td>";
                echo "</tr>";
                  
                }
              }
            }
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

$script =<<<Script
  function toggle(elementId){
    let element = document.querySelectorAll('table tbody tr.'+elementId);
    if(element.length){
      element.forEach(function(el){
        if(el.classList.contains('none')){
        //remove none
        el.classList.remove('none')
      }else {
        //remove none
        el.classList.add('none')
      }
      })
    }else{
      if(element.classList.contains('none')){
        //remove none
        element.classList.remove('none')
      }else{
        //add none
        element.classList.add('none')
      }
  }
  }

  function toggleStore(storeId){
    toggle(storeId)
  }

  function toggleOrder(orderId){
    toggle(orderId)
  }
Script;
$document->addScriptDeclaration($script);
?>