<?php


$order =
Array(
  'done'=>
    Array( 
      Array( //justo one vendor
        // {order one}
        // {order two}
        // {order three}
      )
    ),
  'proposal'=>
    Array(
      Array( //proposal vendor one
        //  {order one}
        //  {order two}
        //  {order three}
      ),
      Array( //proposal vendor two
        //  {order one}
        //  {order two}
        //  {order three}
      ),
    )
);

$name=Array();
$name ['done']=[];
$name['done']['12']=['original'];
array_push($name['done']['12'],'hi');

 print_r($name);