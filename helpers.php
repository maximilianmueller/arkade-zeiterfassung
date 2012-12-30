<?php

/*
enumerates the entries of an array.
if $sep is given, it is used as a separator
if $lastSep is given, it is used to separate the last element
if $lookup is given, it is used to lookup the text to append for each element
*/
function enumerate ($array, $sep, $lastSep, $lookup)
  {
  $result = "";
  $size = count($array);
  for ($i=0; $i<$size; $i++)
    {
    $val = $array[$i];
    $val = $lookup ? $lookup[$val] : $val;
    if ($lastSep && $i == $size - 1)
      $val = $lastSep . $val;
    else if ($sep && $i > 0)
      $val = $sep . $val;
    $result .= $val;
    }
  return $result;
  }

?>