<?php
function clean($str)
{
    $str = str_replace(" ", " ", $str);
    $str = preg_replace("/\s+/", " ", $str);
    $str = trim($str);
    return $str;
}
