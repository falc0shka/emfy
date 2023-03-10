<?php

$x = 42;
$y = [20, 1];
$z = '';

function banki($main, $array, $result) {
    print_r($result.' \ ');
    for ($i=0; $i < count($array); $i++) { 
        if ($array[0] === $main) {
          $result .= (string) $array[0];
          return $result;
        }
        else if ($array[0] < $main) {
          $temp = banki($main - $array[0], array_slice($array,$i), $result . ((string) $array[0]));
          if (!$temp) {
              continue;
          }
          else return $temp;
        }
        else if ($array[0] > $main) {
            return false;
        }
            
    }
}

var_dump(banki($x,$y,$z));
