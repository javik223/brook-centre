<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=quicksave');?>

<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);

foreach ($settings as $key => $val)
{
	$options = '';
	foreach($val as $option)
	{
		$options .= $option.NBS.NBS.NBS;
	}

	$this->table->add_row(lang($key, $key), $options);
}

echo $this->table->generate();

?>

<?php if(isset($site_name)): ?>
<p class="notice">These settings will apply to <?=$site_name;?> only.</p>
<?php endif; ?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>

<?=form_close();?>