<?php

use bff\db\Dynprops;

$bFull = ! empty($attr['name']);

switch($type)
{
    # Выпадающий список
    case Dynprops::typeSelect:
    {
        if($bFull) {
            echo '<select'.HTML::attributes($attr).'>';
        }

        $mValue = (isset($value) ? $value : 0);

        if( ! empty($default_value)) {
            echo '<option value="0">'.$default_value.'</option>';
        }
        foreach($multi as $dm) {
           echo '<option value="'.$dm['value'].'" '.($mValue == $dm['value'] ? 'selected="selected"' : '').'>'.$dm['name'].'</option>';
        }

        if($bFull) {
            echo '</select>';
        }
    } break;
    case Dynprops::typeRange:
    {
        if($bFull) {
            echo '<select'.HTML::attributes($attr).'>';
        }
        if( ! empty($default_value)) {
            echo '<option value="0">'.$default_value.'</option>';
        }
        $value = (isset($value) && $value ? $value : $default_value);
        if( isset($start) && isset($end) ) {
            if($start <= $end) {
                for($i = $start; $i <= $end;$i += $step) {
                   echo '<option value="'.$i.'" '.($value == $i ? 'selected="selected"' : '').'>'.$i.'</option>';
                }
            } else {
                for($i = $start; $i >= $end;$i -= $step) {
                   echo '<option value="'.$i.'" '.($value == $i ? 'selected="selected"' : '').'>'.$i.'</option>';
                }
            }
        }
        if($bFull) {
            echo '</select>';
        }
    } break;
}