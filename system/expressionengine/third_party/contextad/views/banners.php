<?php 

$this->load->view('tabs'); 

if ($total_count == 0) {
	
	?>
	<div class="tableFooter">
		<p class="notice"><?=lang('no_records')?></p>
		<p><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=banner_edit'?>"><?=lang('add_banner')?></a></p>
	</div>
<?php 

}
else
{

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('id'), 'style' => 'width:10%;'),
    array('data' => lang('title'), 'style' => 'width:70%;'),
    array('data' => lang('edit'), 'style' => 'width:10%;')
    //array('data' => lang('stats'), 'style' => 'width:10%;')
);


foreach ($data as $item)
{
	$this->table->add_row($item['id'], $item['title'] , $item['edit']);//, $item['stats']);
}

echo $this->table->generate();


$this->table->clear();

}
?>