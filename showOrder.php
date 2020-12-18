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

  tr th {
    text-align: center;
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
        <li role="presentation" onclick="filterShowOrders(event,this,'new')" class="myli" id="bg-click"><a>سفارش جدید</a></li>
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
            <th scope="col">تعداد پیشنهاد</th>
            <th scope="col">تعداد محصول</th>
            <th scope="col">عملیات سفارش</th>
            <th scope="col">بایگانی</th>
          </tr>
        </thead>
        <tbody class="text-light">
          <?php
          //get order

          foreach ($result[0]['data'] as $key => $value) {
            $order_id_finded = (array_values((array_values($value)[0]))[0])[0]['order_id'];
            echo "<tr id='order" . ($key + 1) . "' class='orderHeader orderId" . $order_id_finded . " " . array_keys($value)[0] . "' onclick='toggleOrder(" . '"order' . ($key + 1) . '"' . ")' style='color:white;background-color:#34568B;'>";
            echo "<td>" . ($key + 1) . "</td>";
            echo "<td>" . count(array_values($value)[0]) . "</td>";
            echo "<td>" . count(array_values((array_values($value)[0]))[0]) . "</td>";

            //get order status
            foreach ($value as $keyOne => $valueOne) {
              if ($keyOne == 'done') {
                echo "<td>انجام شده</td>";
              } else if ($keyOne == 'proposal') {
                echo "<td>پیشنهاد شده</td>";
              } else {
                echo "<td>خطا</td>";
              }
              echo "<td><button type='button' class='archiveOrder btn btn-warning btn-sm'  onclick='setArchiveOrder(this,event," . $order_id_finded . ")'>بایگانی</button></td>";
              echo "</tr>";
              //get vendor id
              echo "<tr  class='order" . ($key + 1) . " orderId" . $order_id_finded . " child-" . array_keys($value)[0] . " none' style='color:white;background-color:#1C53A5 !important'>
                <th scope='col'>کد فروشگاه</th>
                <th scope='col'>نام فروشگاه</th>
                <th scope='col'>تعداد محصول</th>
                <th scope='col'>عملیات سفارش</th>
                <th scope='col'>قیمت کل </th>
              </tr>";
              foreach ($valueOne as $keyTwo => $valueTwo) {
                echo "<tr  class='order" . ($key + 1) . " orderHeader orderId" . $order_id_finded . " child-" . array_keys($value)[0] . " none' id='store" . ($keyTwo) . 'id' . ($key + 1) . "' onclick='toggleStore(" . '"store' . ($keyTwo) . 'id' . ($key + 1) . '"' . ")' style='color:white;background-color:#1c53a5'>";
                echo "<td>" . ($keyTwo) . "</td>";
                echo "<td>فروشگاه $keyTwo</td>";
                echo "<td>" . count($valueTwo) . "</td>";
                if ($keyOne == 'done') {
                  echo "<td>انجام شده</td>";
                } elseif ($valueOne[$keyTwo][0]['proposal_completed'] == -1) {
                  echo "<td>رد شد</td>";
                } else if ($keyOne == 'proposal') {
                  echo '<td>';
                  echo "<button type='button' class='acceptOrder btn btn-success btn-sm'  onclick='setAcceptOrder(this,event," . $order_id_finded . "," . $keyTwo . ")'>قبول</button>";
                  echo "<button type='button' class='rejectOrder btn btn-danger btn-sm'  onclick='setRejectOrder(this,event," . $order_id_finded . "," . $keyTwo . ")'>رد</button>";
                  echo '</td>';
                } else {
                  echo "<td>خطا</td>";
                }
                echo "<td>" . (array_sum(array_column(array_values($valueTwo), 'order_product_price'))) . "</td>";
                echo "</tr>";
                //show one product order

                foreach ($valueTwo as $keyThree => $valueThree) {
                  if ($keyThree == 0) {
                    echo "<tr  class='store" . ($keyTwo) . 'id' . ($key + 1) . " orderId" . $order_id_finded . " child-" . array_keys($value)[0] . " none' style='color:white;background-color:#554884 !important'>
                      <th scope='col'>کد محصول</th>
                      <th scope='col'>نام محصول</th>
                      <th scope='col'>تعداد محصول</th>
                      <th scope='col'>قیمت محصول</th>
                      <th scope='col'>صحبت کردن</th>
                    </tr>";
                  }

                  echo "<tr  class='store" . ($keyTwo) . 'id' . ($key + 1) . " orderHeader orderId" . $order_id_finded . " child-" . array_keys($value)[0] . " none' id='product" . $valueThree['product_id'] . "' style='color:white;background-color:#554884 !important'>";
                  echo "<td>" . $valueThree['id'] . "</td>";
                  echo "<td>" . $valueThree['order_product_name'] . "</td>";
                  echo "<td>" . $valueThree['order_product_quantity'] . "</td>";
                  echo "<td> " . $valueThree['order_product_price'] . " </td>";
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
  var buyStatusGlobal = '';

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
    hideAll();
    // hide('.done')
    // hide('.archive')
    show('.proposal')

  }

  //default show new orders 
  newOrders();
  // all orders
  function allOrders(type) {
    hideAll();
    show('.done')
    show('.proposal')
    // hide('.archive')
  }

  //done orders
  function doneOrders(type) {
    hideAll();
    // hide('.proposal')
    // hide('.archive')
    show('.done')
  }
  //archvie orders
  function archiveOrders(type) {
    hideAll()
    //hide all other rows
    // hide('.done')
    // hide('.proposal')
    show('.archive')

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
      //hide cild-ClassName too
      hide('.child-' + className.replace(".", ""))
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

  // hide all order types
  function hideAll() {
    hide('.done')
    hide('.proposal')
    hide('.archive')
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
        if (data[0].response == 'ok') {
          //remove all row before
          jQuery('.archive').remove();
          //get order
          let insertRows = '';
          let valueTwoCup = 0;
          data[0].data.forEach(function(value, key) {
            var orderId = 'order' + (key + 1) + fastHashParams(randomChar());
            insertRows += "<tr id='" + orderId +
              "' class='orderHeader archive none" + Object.keys(value)[0] +
              "' onclick='toggleOrder(" + '"' + orderId + '"' + ")'" +
              "style='color:white;background-color:#34568B;'>";
            insertRows += "<td>" + (key + 1) +
              "</td>";
            insertRows += "<td>"+Object.keys(value[Object.keys(value)]).length+"</td>";
            
            //get order status
            
            Object.keys(value).forEach(function(valueOne, keyOne) {
              
              insertRows += "<td>" + value[valueOne][Object.keys(value[valueOne])[0]].length +  "</td>";
              insertRows += "<td>باگیانی شد</td>";
              insertRows += "<td>بایگانی شده</td>";
              insertRows += "</tr>";
              //get vendor id

              appendArchivedOrders(insertRows);
              insertRows = ''
              Object.keys(value[valueOne]).forEach(function(valueTwo, keyTwo) {
                let storeId = 'store' + (key + 1) + fastHashParams(randomChar());
                if(keyTwo==0){
                  // insert header section start
                  insertRows += "<tr  class='" + orderId +
                    "  archive none' id='" + storeId + "'" +
                    "" +
                    " style='color:white;background-color:#1c53a5'>";
                    insertRows += "<th class='col'>کد فروشگاه</th>";
                    insertRows += "<th class='col'>نام فروشگاه</th>";
                    insertRows += "<th class='col'>تعداد محصول</th>";
                    insertRows += "<th class='col'>عملیات سفارش</th>";
                    insertRows += "<th class='col'>قیمت کل</th>";
                  insertRows += "</tr>";
                }
                // insert header section end
                let prices = value[valueOne][valueTwo];
                let newPrice = prices.map(function(v,index){
                  return ((parseFloat(v.order_product_price))+(parseFloat(v.order_product_tax)* parseFloat(v.order_product_price)* parseFloat(v.order_product_quantity)))
                }).reduce(function(sum,cvalue){
                  return sum + cvalue;
                });
                valueTwoCup = valueTwo.length
                insertRows += "<tr  class='" + orderId +
                  " orderHeader archive none' id='" + storeId + "'" +
                  "' onclick='toggleStore(" + '"' + storeId + '"' + ")'" +
                  " style='color:white;background-color:#1c53a5'>";
                insertRows += "<td>" + valueTwo + "</td>";
                insertRows += "<td>"+ value[valueOne][valueTwo][0]['ShopName'] +"</td>";
                insertRows += "<td>" + value[valueOne][valueTwo].length +  "</td>";
                if (valueOne == 'done') {
                  insertRows+= "<td>انجام شده</td>";
                } else if (value[valueOne][valueTwo][0]['proposal_completed'] == -1) {
                  insertRows+= "<td>رد شد</td>";
                } else if (valueOne == 'proposal') {
                  insertRows += '<td>';
                  insertRows += "<button type='button' class='acceptOrder btn btn-success btn-sm'  onclick='setAcceptOrder(this,event," + (value[valueOne][valueTwo][0]['order_id']) + "," + valueTwo + ")'>قبول</button>";
                  insertRows += "<button type='button' class='rejectOrder btn btn-danger btn-sm'  onclick='setRejectOrder(this,event," + (value[valueOne][valueTwo][0]['order_id']) + "," + valueTwo + ")'>رد</button>";
                  insertRows += '</td>';
                } else {
                  insertRows += "<td>خطا</td>";
                }
                // insertRows += "<td>keyOne</td>";//must be show with some condition
                insertRows += "<td>"+newPrice+"</td>";
                insertRows += "</tr>";
                //show one product order
                appendArchivedOrders(insertRows);
                insertRows = ''

                  value[valueOne][valueTwo].forEach(function(data, index) {
                    if(index ==0){
                      insertRows += "<tr  class='" + storeId +
                      "  archive none' id='product" + data.product_id +
                      "' style='color:white;background-color:#554884 !important'>";
                      insertRows += "<th scope='col'>کد محصول</th>";
                      insertRows += "<th scope='col'>نام محصول</th>";
                      insertRows += "<th scope='col'>تعداد محصول</th>";
                      insertRows += "<th scope='col'>قیمت محصول</th>";
                      insertRows += "<th scope='col'>تخفیف</th>";
                      insertRows += "</tr>";

                    }
                    insertRows += "<tr  class='" + storeId +
                      " orderHeader archive none' id='product" + data.product_id +
                      "' style='color:white;background-color:#554884 !important'>";
                    insertRows += "<td>" + data.id + "</td>";
                    insertRows += "<td>" + data.order_product_name + "</td>";
                    insertRows += "<td>" + data.order_product_quantity + "</td>";
                    insertRows += "<td>" + parseFloat(data.order_product_price) + "</td>";
                    insertRows += "<td>" + parseFloat(data.order_product_tax) + "</td>";
                    insertRows += "</tr>";
                    appendArchivedOrders(insertRows);
                    insertRows = ''
                  })
              })
            })
          })
        } else {
        }
      },
      error: function(xhr) {
        console.log('error', xhr);

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
    let ransomStrStart = Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 15)
    let ransomStrEnd = Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 15)
    hash = String(hash);
    return String(ransomStrStart + hash + ransomStrEnd)
  }

  function randomChar() {
    return Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 15) + 'mwfji' + Math.random() * 100 +
      Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 15) + 'mwfji';
  }


  /**set archive order */
  function setArchiveOrder(button, event, order_id) {
    var data = {
      user_id: <?php echo JFactory::getUser()->id; ?>,
      type: "archiveOrder",
      order_id: order_id
    }
    // sent ajax request
    jQuery.ajax({
      url: "http://hypertester.ir/serverHypernetShowUnion/changeConsumerOrderStatus.php",
      method: "POST",
      data: JSON.stringify(data),
      dataType: "json",
      contentType: "application/json",
      success: function(data) {
        if (data[0].response == 'ok') {
          //remove all row before
          jQuery('.orderId' + order_id).remove();
          //get order

        } else {
        }
      },
      error: function(xhr) {
        console.log('error', xhr);

      }
    })
  }

  
  /**set archive order */
  function setUnArchiveOrder(button, event, order_id,user_id) {
    var data = {
      user_id: user_id,
      type: "unarchiveOrder",
      order_id: order_id
    }
    // sent ajax request
    jQuery.ajax({
      url: "http://hypertester.ir/serverHypernetShowUnion/changeConsumerOrderStatus.php",
      method: "POST",
      data: JSON.stringify(data),
      dataType: "json",
      contentType: "application/json",
      success: function(data) {
        if (data[0].response == 'ok') {
          //remove all row before
          jQuery(button).parent().remove();
          //get order

        } else {
        }
      },
      error: function(xhr) {
        console.log('error', xhr);

      }
    })
  }

  /**set accept order */
  function setAcceptOrder(button, event, order_id, vendor_id) {
    var data = {
      user_id: <?php echo JFactory::getUser()->id; ?>,
      type: "acceptOrder",
      order_id: order_id,
      vendor_id: vendor_id
    }
    // sent ajax request
    jQuery.ajax({
      url: "http://hypertester.ir/serverHypernetShowUnion/changeConsumerOrderStatus.php",
      method: "POST",
      data: JSON.stringify(data),
      dataType: "json",
      contentType: "application/json",
      success: function(data) {
        if (data[0].response == 'ok') {
          //remove all row before
          if (data[0].customerSessonId && data[0].storeSessionId) {
            let jsonLiveSite = "http://hypertester.ir/index.php?option=com_jchat&format=json";

            let text = "<a href='http://hypertester.ir/%D9%86%D8%A7%D8%AD%DB%8C%D9%87-%DA%A9%D8%A7%D8%B1%D8%A8%D8%B1%DB%8C'>خریدار مورد نظر شما سفارش را پذیرفت</a>"

            // sent ajax request
            let postObject = {
              "message": '' + text + '',
              "task": "stream.saveEntity",
              "to": '' + data[0].storeSessionId[0].session_id.toString() + '', //error
              "tologged": '' + data[0].customerSessonId.toString() + ''
            };
            jQuery.post(jsonLiveSite, postObject, function(response) {
              postObject = null;

            }).done(function(data) {
              if (data && data.storing && data.storing.details.id) {
                removeAlert(false, 'پیام شما به خریدار ارسال شد', true)
              }
            }).fail(function(error) {
              removeAlert(false, 'خریدار مورد نظر شما آنلاین نیست')
              console.log(error)
            })

          } else {
            removeAlert(false, 'خریدار مورد نظر شما آنلاین نیست');
          }
          jQuery(button).parent().html('انجام شد')
          //get order

        } else {
        }
      },
      error: function(xhr) {
        console.log('error', xhr);

      }
    })
  }

  /**set reject order */
  function setRejectOrder(button, event, order_id, vendor_id) {
    var data = {
      user_id: <?php echo JFactory::getUser()->id; ?>,
      type: "rejectOrder",
      order_id: order_id,
      vendor_id: vendor_id
    }
    // sent ajax request
    jQuery.ajax({
      url: "http://hypertester.ir/serverHypernetShowUnion/changeConsumerOrderStatus.php",
      method: "POST",
      data: JSON.stringify(data),
      dataType: "json",
      contentType: "application/json",
      success: function(data) {
        if (data[0].response == 'ok') {
          //remove all row before
          if (data[0].customerSessonId && data[0].storeSessionId) {
            let jsonLiveSite = "http://hypertester.ir/index.php?option=com_jchat&format=json";

            let text = "<a href='http://hypertester.ir/%D9%86%D8%A7%D8%AD%DB%8C%D9%87-%DA%A9%D8%A7%D8%B1%D8%A8%D8%B1%DB%8C'>خریدار مورد نظر شما سفارش را رد کرد</a>"

            // sent ajax request
            let postObject = {
              "message": '' + text + '',
              "task": "stream.saveEntity",
              "to": '' + data[0].storeSessionId[0].session_id.toString() + '', //error
              "tologged": '' + data[0].customerSessonId.toString() + ''
            };
            jQuery.post(jsonLiveSite, postObject, function(response) {
              postObject = null;

            }).done(function(data) {
              if (data && data.storing && data.storing.details.id) {
                removeAlert(false, 'پیام شما به خریدار ارسال شد', true)
              }
            }).fail(function(error) {
              removeAlert(false, 'خریدار مورد نظر شما آنلاین نیست')
              console.log(error)
            })

          } else {
            removeAlert(false, 'خریدار مورد نظر شما آنلاین نیست');
          }
          jQuery(button).parent().html('رد شد')
          //get order

        } else {
        }
      },
      error: function(xhr) {
        console.log('error', xhr);

      }
    })
  }

  // send sms 
  function smsentSmsToCustomers(data, text = null) {
    let jsonLiveSite = "http://hypertester.ir/index.php?option=com_jchat&format=json";
    if (text == null) {
      text = "<a href='http://hypertester.ir/%D9%86%D8%A7%D8%AD%DB%8C%D9%87-%DA%A9%D8%A7%D8%B1%D8%A8%D8%B1%DB%8C'>سلام خریدار گرامی پیشنهاد خود را چک کنید</a>"
    } else {
      text = "<a href='http://hypertester.ir/%D9%86%D8%A7%D8%AD%DB%8C%D9%87-%DA%A9%D8%A7%D8%B1%D8%A8%D8%B1%DB%8C'>" + text + "</a>"
    }
    // sent ajax request
    postObject = {
      "message": '' + text + '',
      "task": "stream.saveEntity",
      "to": '' + data[0].storeSessionId.toString() + '', //error - solved => ownUser sessionId
      "tologged": '' + data[0].customerSessonId.toString() + ''
    };

    jQuery.post(jsonLiveSite, postObject, function(response) {
      postObject = null;

    }).done(function(data) {
      if (data && data.storing && data.storing.details.id) {
        removeAlert(false, 'پیام شما به خریدار ارسال شد', true)
      }
    }).fail(function(error) {
      removeAlert(false, 'خریدار مورد نظر شما آنلاین نیست')
      console.log(error)
    })

  }


  // show alert notication
  /**
   * remove alert notification
   */
  function removeAlert(isClicked = false, text = null, customeClass = null) {
    let textElement = document.querySelector('#alertText');
    if (customeClass != null) {
      textElement.parentElement.classList.remove('alert-danger')
      textElement.parentElement.classList.add('alert-success')
      textElement.style.color = 'black';
    }
    if (text != null) {
      textElement.innerHTML = text.toString()
    } else {
      textElement.innerHTML = 'سفارش مورد نظر شما قبلا توسط فروشگاه دیگر پذیرفته شده است.';
    }
    let element = document.querySelector('.close-alert');
    element = element.parentElement;
    if (isClicked) {
      element.classList.remove('display')
      element.classList.add('none')
    } else {
      if (element.classList.contains('display') == false) {
        element.classList.add('display')
        element.classList.remove('none')
      }
    }
  }
</script>