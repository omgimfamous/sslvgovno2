<?php use bff\db\Dynprops;

$blocksType = $extra['blocksType'];
$prefix = $aData['prefix'];

foreach($aData['dynprops'] as $d)
{
    if(empty($d['value'])) continue;

    switch($blocksType)
    {
        case 'div': {
            echo '<div style="margin-right:5px;"><span class="field-title">'.$d['title'].':&nbsp;</span>';
        } break;
        case 'table': {
            echo '<tr><td class="row1 field-title">'.$d['title'].':</td><td>';
        } break;
    }

    switch($d['type'])
    {
        case Dynprops::typeRadioGroup:
        {
            $value = (isset($d['value'])? $d['value'] : $d['default_value']);
            foreach($d['multi'] as $dm) {
               if($value == $dm['value']) {
                   echo $dm['name'];
                   break;
               }
            }
        }break;
        case Dynprops::typeRadioYesNo:
        {
            $value = (isset($d['value'])? $d['value'] : $d['default_value']);
            echo ($value == 2 ? $this->langText['yes'] : ($value == 1 ? $this->langText['no'] : ''));
        }break;
        case Dynprops::typeCheckboxGroup:
        {
            $value = ( isset($d['value']) && $d['value'] ? explode(';', $d['value']) : explode(';', $d['default_value']) );
            $cbGroup = array();
            foreach($d['multi'] as $dm) {
               if(in_array($dm['value'], $value))
                   $cbGroup[] = $dm['name'];
            }
            echo join(', ', $cbGroup);
        }break;
        case Dynprops::typeCheckbox:
        {
            echo ( (isset($d['value'])? $d['value'] : $d['default_value']) ? $this->langText['yes']:$this->langText['no']);
        }break;
        case Dynprops::typeSelect:
        {
            $value = (isset($d['value'])? $d['value'] : $d['default_value']);
            if($d['parent'])
            {
                // parent values
                foreach($d['multi'] as $dm) {
                    if($value == $dm['value']){
                        echo $dm['name']; break;
                    }
                }

                // child values
                if( ! empty($value) && isset($aData['children'][$d['id']])) {
                   $dmv = current( $aData['children'][$d['id']] );
                   foreach($dmv['multi'] as $dm) {
                       if($dm['value'] == $dmv['value']) {
                           echo '<div><b>'.$d['child_title'].'</b>:&nbsp;'.$dm['name'].'</div>'; break;
                       }
                   }
                }
            } else {
                foreach($d['multi'] as $dm) {
                    if($value == $dm['value'] && $value!=0){
                        echo $dm['name']; break;
                    }
                }
            }
        }break;
        case Dynprops::typeInputText:
        case Dynprops::typeTextarea:
        case Dynprops::typeNumber:
        case Dynprops::typeRange:
        {
            echo (isset($d['value'])? $d['value'] : '');
        }break;
    }

    switch($blocksType)
    {
        case 'div': {
            echo '</div>';
        } break;
        case 'table': {
            echo '</tr>';
        } break;
    }
}