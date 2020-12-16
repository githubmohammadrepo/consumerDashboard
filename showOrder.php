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
    color:white;
    background-color:#0d2d7a !important;
  }
  .custome-nav-bg li:lightblue a{
    color:white;
    background-color:lightblue !important;
  }
  .custome-nav-bg li:lightblue a{
    color:white;
    background-color:#0d2d7a !important;
  }
  .custome-nav-bg li{
    background-color:cadetblue  !important;
  }

  ul li#bg-click.myli a{
    background-color:#0d2d7a !important;
    color:white !important;
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
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_id' => $user_id, "type" => "getAllOrders"]));
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
$error = false;
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
        <li role="presentation" onclick="filterShowOrders(event,this,'new')" class="myli"><a>سفارش جدید</a></li>
        <li role="presentation" onclick="filterShowOrders(event,this,'done')" class="myli"><a href="#">انجام شده ها</a></li>
        <li role="presentation" onclick="filterShowOrders(event,this,'all')" class="myli"><a href="#">همه</a></li>
        <li role="presentation" onclick="filterShowOrders(event,this,'archive')" class="myli"><a href="#"> بایگانی ها</a></li>
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
          //get order
          foreach ($result[0]['data'] as $key => $value) {
            echo "<tr id='order" . ($key + 1) . "' class='orderHeader " . array_keys($value)[0] . "' onclick='toggleOrder(" . '"order' . ($key + 1) . '"' . ")' style='color:white;background-color:#34568B;'>";
            echo "<td>" . ($key + 1) . "</td>";
            echo "<td>one</td>";
            echo "<td>" . count($valueTwo) . "</td>";

            //get order status
            foreach ($value as $keyOne => $valueOne) {
              echo "<td>$keyOne</td>";
              echo "<td>four</td>";
              echo "</tr>";
              //get vendor id
              foreach ($valueOne as $keyTwo => $valueTwo) {
                echo "<tr  class='order" . ($key + 1) . " orderHeader none' id='store" . ($keyTwo) . 'id' . ($key + 1) . "' onclick='toggleStore(" . '"store' . ($keyTwo) . 'id' . ($key + 1) . '"' . ")' style='color:white;background-color:#1c53a5'>";
                echo "<td>" . ($keyTwo) . "</td>";
                echo "<td>فروشگاه $keyTwo</td>";
                echo "<td>" . count($valueTwo) . "</td>";
                echo "<td>$keyOne</td>";
                echo "<td>four</td>";
                echo "</tr>";
                //show one product order
                foreach ($valueTwo as $keyThree => $valueThree) {
                  echo "<tr  class='store" . ($keyTwo) . 'id' . ($key + 1) . " orderHeader none' id='product" . $valueThree['product_id'] . "' style='color:white;background-color:#554884 !important'>";
                  echo "<td>" . $valueThree['id'] . "</td>";
                  echo "<td>" . $valueThree['order_product_name'] . "</td>";
                  echo "<td>" . $valueThree['order_product_quantity'] . "</td>";
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

$script = <<<Script
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

<script>
  function filterShowOrders(event, button, type) {
    event.preventDefault();
    //remove class in all li
    let el = document.querySelectorAll('.myli');
    el.forEach(function(myEl) {
      myEl.id = ""
    })
    //add color
    button.id = 'bg-click';
    if (type == 'new') {
      newOrders(type);
    } else if (type == 'all') {
      allOrders(type);
    } else if (type == 'done') {
      doneOrders(type);
    } else if (type == 'archive') {
      archiveOrders(type);
    } else {
      //do nothing
    }
  }

  // new orders
  function newOrders(type) {
    hide('.done')
    show('.proposal')

  }
  // all orders
  function allOrders(type) {
    show('.done')
    show('.proposal')
  }

  //done orders
  function doneOrders(type) {
    hide('.proposal')
    show('.done')
  }
  //archvie orders
  function archiveOrders(type) {
    //hide all other rows
    hide('.done')
    hide('.proposal')

    //get all archive records
    getAllArchiveOrders();

  }

  //hide elements by className
  function hide(className) {
    let allTrs = document.querySelectorAll(className);
    allTrs.forEach(function(el) {
      if (el.classList.contains('none')) {

      } else {
        el.classList.add('none')
      }
    })
  }

  //show elements by class name
  function show(className) {
    let allTrs = document.querySelectorAll(className);
    allTrs.forEach(function(el) {
      if (el.classList.contains('none')) {
        el.classList.remove('none')

      }
    })
  }

  //get all archive order records
  function getAllArchiveOrders() {
    var data = {
      user_id: <?php echo JFactory::getUser()->id; ?>,
      type: "getAllArchived"
    }
    // sent ajax request
    jQuery.ajax({
      url: "http://hypertester.ir/serverHypernetShowUnion/getConsumerOrders.php",
      method: "POST",
      data: JSON.stringify(data),
      dataType: "json",
      contentType: "application/json",
      success: function(data) {
        console.log(data)
        if (data[0].response == 'ok') {

          //get order
          let insertRows = '';
          let valueTwoCup = 0;
          data[0].data.forEach(function(value, key) {
            let orderId = 'order' + (key + 1) +fastHashParams(randomChar());
            insertRows += "<tr id='" +orderId+
              "' class='orderHeader " + Object.keys(value)[0] +
              "' onclick='toggleOrder("+'"'+orderId+'"'+")'"
               +"style='color:white;background-color:#34568B;'>";
            insertRows += "<td>" + (key + 1) +
              "</td>";
            insertRows += "<td>one</td>";
            insertRows += "<td>" + (valueTwoCup) +
              "</td>";

            //get order status

            Object.keys(value).forEach(function(valueOne, keyOne) {
              insertRows += "<td>keyOne</td>";
              insertRows += "<td>four</td>";
              insertRows += "</tr>";
              //get vendor id

              appendArchivedOrders(insertRows);
              insertRows = ''
              Object.keys(value[valueOne]).forEach(function(valueTwo, keyTwo) {
              let storeId = 'store' + (key + 1) +fastHashParams(randomChar());

                valueTwoCup = valueTwo.length
                insertRows += "<tr  class='" + orderId +
                  " orderHeader none' id='" +storeId+"'"+
                  "' onclick='toggleStore("+'"'+storeId+'"'+")'"+
                  " style='color:white;background-color:#1c53a5'>";
                insertRows += "<td>" + (keyTwo) +
                  "</td>";
                insertRows += "<td>فروشگاه keyTwo</td>";
                insertRows += "<td>" + (valueTwo.length) +
                  "</td>";
                insertRows += "<td>keyOne</td>";
                insertRows += "<td>four</td>";
                insertRows += "</tr>";
                //show one product order
                appendArchivedOrders(insertRows);
                insertRows = ''
                Object.keys(value[valueOne]).forEach(function(valueThree, keyThree) {
                  insertRows += "<tr  class='"+storeId+
                    " orderHeader none' id='product" + valueThree['product_id'] +
                    "' style='color:white;background-color:#554884 !important'>";
                  insertRows += "<td>" + valueThree['id'] +
                    "</td>";
                  insertRows += "<td>" + valueThree['order_product_name'] +
                    "</td>";
                  insertRows += "<td>" + valueThree['order_product_quantity'] +
                    "</td>";
                  insertRows += "<td>عملیات سفارش</td>";
                  insertRows += "<td>باگیانی</td>";
                  insertRows += "</tr>";
                  appendArchivedOrders(insertRows);
                  insertRows = ''
                })
              })
            })
          })
        } else {
          console.log('no')
          console.log(data)
        }
      },
      error: function(xhr) {
        console.log('error', xhr);
        notificationDisplay(tdsClassName, 'خطا در اینترنت', 'red', 'white')
      }
    })
  }


  //appendData to table
  function appendArchivedOrders(insertRows) {
    jQuery('#storeOrders').append(insertRows)
  }

  /**
 * Generates a hash from params passed in
 * @returns {string} hash based on params
 */
function fastHashParams() {
    var args = Array.prototype.slice.call(arguments).join('|');
    var hash = 0;
    if (args.length == 0) {
        return hash;
    }
    for (var i = 0; i < args.length; i++) {
        var char = args.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    let ransomStrStart =Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 15)
    let ransomStrEnd =Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 15)
    hash =String(hash);
    return String(ransomStrStart+hash+ransomStrEnd)
}

function randomChar(){
  return Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 15)+'mwfji'+Math.random()*100+
  Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 15)+'mwfji';
}
</script>