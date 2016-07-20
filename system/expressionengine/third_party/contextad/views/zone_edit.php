<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=save_zone');?>

<?php 

$this->load->view('tabs'); 

?> 

<h3><?=lang("edit_zone")?></h3> 
    
<?php 

$this->table->set_template($cp_pad_table_template);
foreach ($data as $key => $val)
{
	
		$this->table->add_row(
			array('data' => lang($key, $key), 'style' => 'width:50%;'), $val
		);
}
echo $this->table->generate();
$this->table->clear();

?>




<p><?=form_submit('submit', lang('save'), 'class="submit"')?></p>

<?php if ($this->input->post('zone_id')!=''):?>

<p>&nbsp;</p>

<p><a class="this_delete_warning" href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=delete_zone'.AMP.'zone_id='.$this->input->post('zone_id')?>"><?=lang('delete_zone')?></a> </p>

<?php endif;?>

<p>&nbsp;</p>

<?php
form_close();