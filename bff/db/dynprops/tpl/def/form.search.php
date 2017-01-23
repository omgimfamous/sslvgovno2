<?php

use bff\db\Dynprops;

$blocksType = $extra['blocksType'];
$prefix = $aData['prefix'];
$prefixChild = 'dc';

$f = $this->input->getpost('d', TYPE_ARRAY);

$sChildUrl = $this->adminLink($this->act_action);
$sChildUrlAct = 'child-form';
$sChildInputIdPrefix = 'bffdp';
$sChildTemplate = 'form.child';
$sChildTemplatePath = $this->module_dir_tpl;

foreach($aData['dynprops'] as $d)
{
    $ID = $d['id'];
    $name = $prefix.'['.$d['data_field'].']';
    $title = $d['title'];
    $blockClass = 'dynprop-owner-'.$d[$this->ownerColumn].' dynprop-id-'.$ID;
    if(isset($f[ $d['data_field'] ])) {
        $d['value'] = $f[ $d['data_field'] ];
    } else {
        $d['value'] = false;
    }

    switch($blocksType)
    {
        case 'inline': {
            echo '<div class="left '.$blockClass.'" style="margin-right:5px;"><span class="field-title">'.$title.':&nbsp;</span>';
        } break;
        case 'table': {
            echo '<tr class="'.$blockClass.'"><td class="row1 field-title">'.$title.':</td><td>';
        } break;
    }

    switch($d['type'])
    {
        case Dynprops::typeRadioGroup:
        case Dynprops::typeCheckboxGroup:
        {
            $values = (isset($d['value']) && $d['value'] ? $d['value'] : array());
            foreach($d['multi'] as $k=>$dm) {
                echo '<label class="checkbox inline"><input type="checkbox" name="'.$name.'['.$k.']" '.(in_array($dm['value'], $values)?'checked="checked"':'').' value="'.$dm['value'].'" /> &nbsp;'.$dm['name'].'</label><br />';
            }
        }break;
        case Dynprops::typeRadioYesNo:
        {
            $value = (isset($d['value'])? $d['value'] : 0);
              ?>
                <label class="radio inline"><input type="radio" name="<?= $name ?>" value="0" <?= (empty($value)?'checked="checked"':'') ?> /><?= $this->langText['all'] ?></label>&nbsp;
                <label class="radio inline"><input type="radio" name="<?= $name ?>" value="2" <?= ($value == 2?'checked="checked"':'') ?> /><?= $this->langText['yes'] ?></label>&nbsp;
                <label class="radio inline"><input type="radio" name="<?= $name ?>" value="1" <?= ($value == 1?'checked="checked"':'') ?> /><?= $this->langText['no'] ?></label>
              <?php
        }break;
        case Dynprops::typeCheckbox:
        {
            $value = (isset($d['value'])? $d['value'] : 0);
            ?>
                <label class="checkbox inline"><input type="hidden" name="<?= $name ?>" value="0" /><input type="checkbox" name="<?= $name ?>" value="1" <?= ($value?'checked="checked"':'') ?> /><?= $this->langText['yes'] ?></label>
            <?php
        }break;
        case Dynprops::typeSelect:
        {
            $value = (isset($d['value'])? $d['value'] : 0);
            if($d['parent'])
            {
                ?>
                  <div class="left" style="margin-right: 10px;">
                      <select<?= ' name="'.$name.'" onchange="bffDynpropsParents.select('.$ID.', this.value, \''.$prefixChild.'\');"' ?>>
                      <?php foreach($d['multi'] as $dm) {
                           echo '<option value="'.$dm['value'].'" '.($dm['value'] == $value ? 'selected="selected"' : '').'>'.$dm['name'].'</option>';
                        } ?>
                      </select>
                  </div>
                  <div class="left field-title" style="<?php if(empty($value)){ ?>display: none;<?php } ?>">
                    <?= $d['child_title'] ?>:
                    <span id="<?= $sChildInputIdPrefix.$ID; ?>_child">
                    <?php
                       if( ! empty($value) && isset($aData['children'][$ID])) {
                           echo $this->formChild($aData['children'][$ID], array('name'=>$prefixChild), true, $sChildTemplate, $sChildTemplatePath);
                       }
                    ?>
                    </span>
                  </div>
                  <div class="clear"></div>
                <?php
            } else {
                ?><select name="<?= $name ?>"><?php
                    foreach($d['multi'] as $dm) {
                        echo '<option value="'.$dm['value'].'" '.($value == $dm['value'] ? 'selected="selected"' : '').'>'.$dm['name'].'</option>';
                    }
                ?></select><?php
            }
        }break;
        case Dynprops::typeNumber:
        {
            $this->input->clean_array($d['value'], array(
                'f' => TYPE_UNUM,
                't' => TYPE_UNUM,
            ));

            echo '<input type="text" name="'.$name.'[f]" style="width: 50px;" value="'.$d['value']['f'].'" />';
            echo '&nbsp;-&nbsp;';
            echo '<input type="text" name="'.$name.'[t]" style="width: 50px;" value="'.$d['value']['t'].'" />';

            if($d['parent'] && isset($aData['children'][$ID])) {
                echo $this->formChild($aData['children'][$ID], array('name'=>$prefixChild), true, $sChildTemplate, $sChildTemplatePath);
            }
        }break;
        case Dynprops::typeRange:
        {
            $this->input->clean_array($d['value'], array(
                'f' => TYPE_UNUM,
                't' => TYPE_UNUM,
            ));

            $values = range($d['start'], $d['end'], $d['step']);

            $sOptionsFrom = '';
            $sOptionsTo = '';
            foreach($values as $i) {
               $sOptionsFrom .= '<option value="'.$i.'" '.($d['value']['f'] == $i ? 'selected="selected"' : '').'>'.$i.'</option>';
               $sOptionsTo   .= '<option value="'.$i.'" '.($d['value']['t'] == $i ? 'selected="selected"' : '').'>'.$i.'</option>';
            }
            ?>
            <?= '<select name="'. $name .'[f]" style="width:61px;"><option value="0">-</option>'. $sOptionsFrom .'</select>'; ?>
            &nbsp;&nbsp;-&nbsp;&nbsp;
            <?= '<select name="'. $name .'[t]" style="width:61px;"><option value="0">-</option>'. $sOptionsTo .'</select>'; ?>
            <?php
            if($d['parent'] && isset($aData['children'][$ID])) {
                echo $this->formChild($aData['children'][$ID], array('name'=>$prefixChild), true, $sChildTemplate, $sChildTemplatePath);
            }

        }break;
    }

    switch($blocksType)
    {
        case 'inline': {
            echo '</div>';
        } break;
        case 'table': {
            echo '</tr>';
        } break;
    }
}

?>
<script type="text/javascript">
var bffDynpropsParents = (function(){
    var cache = {}, prefixID = '<?= $sChildInputIdPrefix ?>';

    function view(data, id){
        var $inp = $('#'+prefixID+id+'_child').html( ( data.form ? data.form : '')).parent();
        if(data.form!='') {
            $inp.show();
        } else {
            $inp.hide();
        }
    }

    return {
        select: function(id, val, prefix)
        {
            var key = id+'-'+val;
            if(!intval(val)) cache[key] = {form:''};
            if(cache.hasOwnProperty(key)) {
                view( cache[key], id );
            } else {
                bff.ajax('<?= $sChildUrl; ?>', {act:'<?= $sChildUrlAct ?>', dp_id: id, dp_value:val, name_prefix: prefix, search:true}, function(data){
                    if(data) {
                        view( (cache[key] = data), id );
                    }
                });
            }
        }
    }
}());
</script>