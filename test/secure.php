<?php
$result = htmlspecialchars(strip_tags(1889));
if(preg_match('/<>;:\$^/', $result)){
  echo 'yes';
}else{
  echo 'no';
}