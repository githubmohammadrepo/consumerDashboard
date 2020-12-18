<?php

use Joomla\CMS\Factory;

$document = Factory::getDocument();
// above 2 lines are equivalent to the older form: $document = JFactory::getDocument();
//add style css description
$style  = "
.searchMe{
  display: flex;
  flex-direction: row;
  justify-content: center
}

.searchContainer{
  display:block;
  width:50%;
  position: absolute;
  z-index: 1024;
  bottom:60%
}
.searChInput{
  text-align:center;
  font-size:2rem;
  font-weight: bold;
}

.input-group-search{
    padding: 20px 22px;
    text-shadow: 1px 1px 0.5px black;
    text-align: center;
    font-size: 2rem;
    direction: rtl;
}

.input-group-addon {
  border-radius: 50px;
  background-color: #00B1FF;
  color: white;
  border: 1px solid white;
  transition: border 0.2s ease;
  padding-left: 20px;
  padding-right: 20px;
}
.input-group-addon:hover {
  border: 1px solid #00B1FF;
  cursor: pointer;
}
";
$document->addStyleDeclaration($style);

// add javscript descripton
$script = <<<Demo
var counter =0;
  var myVar = setInterval(insertSearchInput, 300);
  function insertSearchInput(){
    if (jQuery(".searchMe")[0]){
      
      stopInsertSearchInput()

    }else if(counter>20){
      stopInsertSearchInput()
    }else{
    // Do something if class exists
    counter++;
      jQuery('.ls-layers').append(`<!-- start me -->
      <div class="searchMe" >
      <div class="searchContainer">
      <div class="input-group input-group-search" >
      <input type="text" class="form-control searching searChInput" style=" padding:18px 22px !important" aria-label="Amount (to the nearest dollar)">
      <span class="input-group-addon">
      <i class="fa fa-search" aria-hidden="true"></i>
      
      </span>
      </div>
      </div>
      
      </div>
      <!-- end me -->`);
    }

  }

function stopInsertSearchInput() {
  clearInterval(myVar);
}
Demo;
$document->addScriptDeclaration($script);
// 
?>
<script defer>
 let element = document.querySelector('#offlajn-ajax-search299')
 let bodyRect = document.body.getBoundingClientRect();
 let elemRect = element.getBoundingClientRect();
 let offset   = elemRect.top - bodyRect.top;
 let right   = elemRect.right - bodyRect.right;
  alert('Element is ' + right + ' vertical pixels from <body>');
  element.style.position  = 'absolute'
  element.style.top = `${-offset+60}px`
  element.style.right = `30%`

  element.parentElement.zIndex = 1024
  for(let i=0;i<10;i++){
    element = element.parentElement;
    element.style.zIndex = 1024;
  }
</script>