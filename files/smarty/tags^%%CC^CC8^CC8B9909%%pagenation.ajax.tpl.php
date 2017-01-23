<?php /* Smarty version 2.6.7, created on 2017-01-15 21:43:27
         compiled from pagenation.ajax.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'count', 'pagenation.ajax.tpl', 2, false),array('modifier', 'default', 'pagenation.ajax.tpl', 4, false),)), $this); ?>
<div class="pagination pagination-centered">
    <?php if (count($this->_tpl_vars['pagenation']) > 1): ?>
        <ul>
            <?php if (((is_array($_tmp=@$this->_tpl_vars['pgsPrev'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, ''))): ?><li><a href="#" onclick="<?php echo $this->_tpl_vars['pgsPrev']; ?>
; return false;">&larr;</a></li><?php endif; ?>
            <?php $this->_foreach['pagenation'] = array('total' => count($_from = (array)$this->_tpl_vars['pagenation']), 'iteration' => 0);
if ($this->_foreach['pagenation']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['k'] => $this->_tpl_vars['v']):
        $this->_foreach['pagenation']['iteration']++;
?>
                <li <?php if ($this->_tpl_vars['v']['active']): ?>class="active"<?php endif; ?>>
                    <a href="#" onclick="<?php echo $this->_tpl_vars['v']['link']; ?>
; return false;"><?php echo $this->_tpl_vars['v']['page']; ?>
</a>
                </li>
            <?php endforeach; endif; unset($_from); ?>
            <?php if (((is_array($_tmp=@$this->_tpl_vars['pgsNext'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, ''))): ?><li><a href="#" onclick="<?php echo $this->_tpl_vars['pgsNext']; ?>
; return false;">&rarr;</a></li><?php endif; ?>
        </ul>
    <?php endif; ?>
</div>