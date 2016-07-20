<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=save_banner', array('id'=>'contextad_form'));?>

<?php 

$this->load->view('tabs'); 

foreach ($data as $key=>$arr)
{

?> 

<div class="editAccordion open"> 
<h3><?=lang("$key")?></h3> 
    
<?php 
if ($key=='banner_data')
{
$this->table->set_template($cp_pad_table_template);
foreach ($arr as $key => $val)
{
	
		$this->table->add_row(
			array('data' => lang($key, $key), 'style' => 'width:50%;'), $val
		);
}
echo $this->table->generate();
$this->table->clear();
}
else
{
	$this->table->set_template($cp_pad_table_template);
	$this->table->add_row(
			array($arr)
	);
	//echo '<div style="width: 100%" class="padTable">'.$arr.'</div>';
	echo $this->table->generate();
	$this->table->clear();
}
?>

</div>
<?php
}
?>


<p><?=form_submit('submit', lang('save'), 'class="submit"')?></p>

<?php if ($this->input->post('banner_id')!=''):?>

<p>&nbsp;</p>

<p><a class="this_delete_warning" href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=delete_banner'.AMP.'banner_id='.$this->input->post('banner_id')?>"><?=lang('delete_banner')?></a> </p>

<?php endif;?>

<p>&nbsp;</p>

<?php
form_close();
?>

<style type="text/css">
#contextad_form .ui-multiselect .search {height: 16px; padding: 0;}
</style>