<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab<?php if (in_array($this->input->get('method'), array('', 'index', 'banner_edit'))) echo ' current';?>"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=index'?>"><?=lang('banners')?></a>  </li> 
<li class="content_tab<?php if (in_array($this->input->get('method'), array('zones', 'zone_edit'))) echo ' current';?>"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=zones'?>"><?=lang('zones')?></a>  </li> 

</ul> 
<div class="clear_left shun"></div>