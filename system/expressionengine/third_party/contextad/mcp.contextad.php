<?php

/*
=====================================================
 Context Ads
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2013 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: mcp.contextad.php
-----------------------------------------------------
 Purpose: Context banners management system for ExpressionEngine
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'contextad/config.php';

class Contextad_mcp {

    var $version = CONTEXTAD_ADDON_VERSION;
    
    var $settings = array();
    
    var $perpage = 25;
    
    var $multiselect_fetch_limit = 50;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 

        if (version_compare(APP_VER, '2.6.0', '>='))
        {
        	$this->EE->view->cp_page_title = lang('contextad_module_name');
        }
        else
        {
        	$this->EE->cp->set_variable('cp_page_title', lang('contextad_module_name'));
        }
    } 

    
    function index()
    {
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');

    	$vars = array();
    	$js = '';
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;

        $this->EE->db->select('banner_id, title');
        $this->EE->db->from('contextad_banners');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $query = $this->EE->db->get();
        $vars['total_count'] = $query->num_rows();
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
       	$date_format = ($date_fmt == 'us')?'%m/%d/%y %h:%i %a':'%Y-%m-%d %H:%i';
        
        $vars['table_headings'] = array(
                        lang('id'),
                        lang('title'),
                        lang('edit'),
                        //lang('stats')
        			);		
		   
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           	$vars['data'][$i]['id'] = $row['banner_id'];
           	$vars['data'][$i]['title'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=banner_edit'.AMP.'banner_id='.$row['banner_id']."\">".$row['title']."</a>";
           	$vars['data'][$i]['edit'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=banner_edit'.AMP.'banner_id='.$row['banner_id']."\" title=\"".lang('edit').' '.$row['title']."\"><img src=\"".$this->EE->cp->cp_theme_url."images/icon-edit.png\" alt=\"".lang('edit')."\"></a>";
           	//$vars['data'][$i]['stats'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=banner_edit'.AMP.'banner_id='.$row['banner_id']."\" title=\"".lang('edit').' '.$row['title']."\"><img src=\"".$this->EE->config->slash_item('theme_folder_url')."third_party/contextad/stats.png\" alt=\"".lang('edit')."\"></a>";
           	$i++;
 			
        }

        
        $this->EE->javascript->output($js);
        
        $this->EE->jquery->tablesorter('.mainTable', '{
			/*headers: {0: {sorter: false}, 7: {sorter: false}},*/
			widgets: ["zebra"]
		}');


        $this->EE->load->library('pagination');

        $base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=index';

        $p_config = $this->_p_config($base_url, $this->perpage, $vars['total_count']);

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
		
		$this->EE->cp->set_right_nav(array(
		            'add_banner' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=banner_edit')
		        );
        
    	return $this->EE->load->view('banners', $vars, TRUE);
	
    }   
    
    
    
    function banner_edit()
    {

		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
    	
    	$yesno = array(
                                    'y' => $this->EE->lang->line('yes'),
                                    'n' => $this->EE->lang->line('no')
     	);
    	
    	$js = '';
    	
		$theme_folder_url = trim($this->EE->config->item('theme_folder_url'), '/').'/third_party/contextad/';
        $this->EE->cp->add_to_foot('<link type="text/css" href="'.$theme_folder_url.'multiselect/ui.multiselect.css" rel="stylesheet" />');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'multiselect/plugins/localisation/jquery.localisation-min.js"></script>');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'multiselect/plugins/blockUI/jquery.blockUI.js"></script>');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'multiselect/ui.multiselect.js"></script>');
		
		$values = array();
       	$db_fields = $this->EE->db->list_fields('contextad_banners');
       	foreach ($db_fields as $id=>$field)
       	{
       		$values[$field] = '';
       	}
       	$values['site_id'] = $this->EE->config->item('site_id');
       	$values['weight'] = 1;
       	$values['link_target'] = '_self';
       	$values['enabled'] = 'y';
       	$values['banner_zones'] = array();
       	$values['banner_entries'] = array();
       	$values['banner_categories'] = array();
       	$values['banner_channels'] = array();
		
		if ($this->EE->input->get('banner_id')!==false)
		{
			$q = $this->EE->db->select()
					->from('contextad_banners')
					->where('banner_id', $this->EE->input->get('banner_id'))
					->where('site_id', $this->EE->config->item('site_id'))
					->get();
			if ($q->num_rows()==0)
			{
				show_error(lang('unauthorized_access'));
			}
			
			$row = $q->row_array();
			
			foreach ($values as $field_name=>$default_field_val)
			{
				if (isset($row["$field_name"]))
				{
					$values["$field_name"] = $row["$field_name"];
				}
			}
			
			$query = $this->EE->db->select('zone_id')
					->from('contextad_banner_zones')
					->where('banner_id', $this->EE->input->get('banner_id'))
					->get();
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$values['banner_zones'][$row['zone_id']] = $row['zone_id'];
				}
			}
			
			$query = $this->EE->db->select('entry_id')
					->from('contextad_banner_entries')
					->where('banner_id', $this->EE->input->get('banner_id'))
					->get();
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$values['banner_entries'][$row['entry_id']] = $row['entry_id'];
				}
			}
			
			$query = $this->EE->db->select('cat_id')
					->from('contextad_banner_categories')
					->where('banner_id', $this->EE->input->get('banner_id'))
					->get();
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$values['banner_categories'][$row['cat_id']] = $row['cat_id'];
				}
			}
			
			$query = $this->EE->db->select('channel_id')
					->from('contextad_banner_channels')
					->where('banner_id', $this->EE->input->get('banner_id'))
					->get();
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$values['banner_channels'][$row['channel_id']] = $row['channel_id'];
				}
			}
		}
		
		
		

		
		$banner_zones = array();
        $this->EE->db->select();
        $this->EE->db->from('contextad_zones');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $banner_zones[$row['zone_id']] = $row['zone_title'];
        }
        
        
        
        $banner_entries = array();
        $this->EE->db->select('entry_id, title');
        $this->EE->db->from('channel_titles');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        if ($this->EE->stats->statdata('total_entries') > $this->multiselect_fetch_limit)
        {
            $this->EE->db->limit($this->multiselect_fetch_limit);
        }
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $banner_entries[$row['entry_id']] = $row['title'];
        }        
        
        $banner_categories = array();
        $this->EE->db->select('cat_id, cat_name');
        $this->EE->db->from('categories');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $banner_categories[$row['cat_id']] = $row['cat_name'];
        }        
        
        $banner_channels = array();
        $this->EE->db->select('channel_id, channel_title');
        $this->EE->db->from('channels');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $banner_channels[$row['channel_id']] = $row['channel_title'];
        }        
        
        $js = '';

        $this->EE->cp->add_js_script('ui', 'datepicker'); 
        $this->EE->javascript->output('
			date_obj = new Date();
			date_obj_hours = date_obj.getHours();
			date_obj_mins = date_obj.getMinutes();

			if (date_obj_mins < 10) { date_obj_mins = "0" + date_obj_mins; }

			if (date_obj_hours > 11) {
				date_obj_hours = date_obj_hours - 12;
				date_obj_am_pm = " PM";
			} else {
				date_obj_am_pm = " AM";
			}

			date_obj_time = " \'"+date_obj_hours+":"+date_obj_mins+date_obj_am_pm+"\'";
		');
        $this->EE->javascript->output(' $("#show_start").datepicker({ dateFormat: $.datepicker.W3C + date_obj_time }); '); 
        $this->EE->javascript->output(' $("#show_end").datepicker({ dateFormat: $.datepicker.W3C + date_obj_time }); '); 

        
		$data['banner_data'] = array();
		$data['banner_data']['title'] = form_input('title', $values['title']).form_hidden('banner_id', $values['banner_id']).form_hidden('site_id', $values['site_id']);
		$data['banner_data']['enabled'] = form_dropdown('enabled', $yesno, $values['enabled']);
		
		$data['banner_data']['banner_img'] = form_input('banner_img', $values['banner_img'], 'style="width: 90%"').NBS.NBS.$this->_file_select();

		$data['banner_data']['banner_dimensions'] = form_input('banner_width', $values['banner_width'], 'style="width: 3em"').' x '.form_input('banner_height', $values['banner_height'], 'style="width: 3em"');
		$data['banner_data']['alt_text'] = form_input('alt_text', $values['alt_text']);
		$data['banner_data']['link_url'] = form_input('link_url', $values['link_url']);
		$data['banner_data']['link_target'] = form_dropdown('link_target', array('_self' => '_self', '_blank' => '_blank', '_top' => '_top'), $values['link_target']);
		$data['banner_data']['xtra_html'] = form_textarea('xtra_html', $values['xtra_html']);
		
		$data['banner_data']['limit_views'] = form_input('limit_views', ($values['limit_views']!=0)?$values['limit_views']:'');
		$data['banner_data']['limit_clicks'] = form_input('limit_clicks', ($values['limit_clicks']!=0)?$values['limit_clicks']:'');
		$data['banner_data']['show_start'] = form_input('show_start', ($values['show_start']!=0)?$values['show_start']:'', 'id="show_start"');
		$data['banner_data']['show_end'] = form_input('show_end', ($values['show_end']!=0)?$values['show_end']:'', 'id="show_end"');
		
		$data['banner_zones'] = form_multiselect('banner_zones[]', $banner_zones, $values['banner_zones'], 'id="banner_zones"');
		$js .= "
	            $('#banner_zones').multiselect({ droppable: 'none', sortable: 'none' });
	        ";
		$data['banner_entries'] = form_multiselect('banner_entries[]', $banner_entries, $values['banner_entries'], 'id="banner_entries"');
		$act = $this->EE->db->select('action_id')->from('actions')->where('class', 'Contextad')->where('method', 'find_members')->get();
        $remoteUrl = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id');
        $js .= "
        $('#banner_entries').multiselect({ droppable: 'none', sortable: 'none', remoteUrl: '$remoteUrl' });
        ";
  		$data['banner_categories'] = form_multiselect('banner_categories[]', $banner_categories, $values['banner_categories'], 'id="banner_categories"');
		$js .= "
	            $('#banner_categories').multiselect({ droppable: 'none', sortable: 'none' });
	        ";
  		$data['banner_channels'] = form_multiselect('banner_channels[]', $banner_channels, $values['banner_channels'], 'id="banner_channels"');
		$js .= "
	            $('#banner_channels').multiselect({ droppable: 'none', sortable: 'none' });
	        ";
		
        
        $js .= '
				var draft_target = "";

			$("<div id=\"this_delete_warning\">'.$this->EE->lang->line('banner_delete_warning').'</div>").dialog({
				autoOpen: false,
				resizable: false,
				title: "'.$this->EE->lang->line('confirm_deleting').'",
				modal: true,
				position: "center",
				minHeight: "0px", 
				buttons: {
					Cancel: function() {
					$(this).dialog("close");
					},
				"'.$this->EE->lang->line('delete_banner').'": function() {
					location=draft_target;
				}
				}});

			$(".this_delete_warning").click( function (){
				$("#this_delete_warning").dialog("open");
				draft_target = $(this).attr("href");
				$(".ui-dialog-buttonpane button:eq(2)").focus();	
				return false;
		});';
		
		$js .= "
            $(\".editAccordion\").css(\"borderTop\", $(\".editAccordion\").css(\"borderBottom\")); 
            $(\".editAccordion h3\").click(function() {
                if ($(this).hasClass(\"collapsed\")) { 
                    $(this).siblings().slideDown(\"fast\"); 
                    $(this).removeClass(\"collapsed\").parent().removeClass(\"collapsed\"); 
                } else { 
                    $(this).siblings().slideUp(\"fast\"); 
                    $(this).addClass(\"collapsed\").parent().addClass(\"collapsed\"); 
                }
            }); 
        ";

        $this->EE->javascript->output($js);
        
        $vars['data'] = $data;
        
    	return $this->EE->load->view('banner_edit', $vars, TRUE);
	
    }
    


    
    function save_banner()
    {
    	if (empty($_POST))
    	{
    		show_error($this->EE->lang->line('unauthorized_access'));
    	}
    	
    	if ($this->EE->input->post('site_id')!=$this->EE->config->item('site_id'))
    	{
    		show_error(lang('unauthorized_access'));
    	}   
    	
    	if (trim($this->EE->input->post('title'))=='')
    	{
    		show_error(lang('name_this_banner'));
    	}   	
    	
        
        $db_fields = $this->EE->db->list_fields('contextad_banners');
        $data = array();
        foreach ($db_fields as $id=>$field)
        {
        	if (!in_array($field, array('views','clicks')))
        	{
        		$data[$field] = $this->EE->input->post($field);
        	}
        }
      	
		if ($this->EE->input->post('banner_id')!='')
        {
			$banner_id = $this->EE->input->post('banner_id');
			
			$this->EE->db->where('banner_id', $banner_id);
            $this->EE->db->update('contextad_banners', $data);
            
            $this->EE->db->where('banner_id', $banner_id);
            $this->EE->db->delete('contextad_banner_zones');
            
            $this->EE->db->where('banner_id', $banner_id);
            $this->EE->db->delete('contextad_banner_entries');
            
            $this->EE->db->where('banner_id', $banner_id);
            $this->EE->db->delete('contextad_banner_categories');
            
            $this->EE->db->where('banner_id', $banner_id);
            $this->EE->db->delete('contextad_banner_channels');
        }
        else
        {
            $this->EE->db->insert('contextad_banners', $data);
            $banner_id = $this->EE->db->insert_id();
        }
        
        if (isset($_POST['banner_zones']) && !empty($_POST['banner_zones']))
        {
			foreach ($_POST['banner_zones'] as $key=>$val)
	    	{
	        	$data = array(
					'banner_id' => $banner_id,
					'zone_id'	=> $val
					);
				$this->EE->db->insert('contextad_banner_zones', $data);
	    	}
   		}
    	
	    if (isset($_POST['banner_entries']) && !empty($_POST['banner_entries']))
        {
			foreach ($_POST['banner_entries'] as $key=>$val)
	    	{
	        	$data = array(
					'banner_id' => $banner_id,
					'entry_id'	=> $val
					);
				$this->EE->db->insert('contextad_banner_entries', $data);
	    	}
   		}
	    	
	    if (isset($_POST['banner_categories']) && !empty($_POST['banner_categories']))
        {
			foreach ($_POST['banner_categories'] as $key=>$val)
	    	{
	        	$data = array(
					'banner_id' => $banner_id,
					'cat_id'	=> $val
					);
				$this->EE->db->insert('contextad_banner_categories', $data);
	    	}
   		}
	    
		if (isset($_POST['banner_channels']) && !empty($_POST['banner_channels']))
        {	
	    	foreach ($_POST['banner_channels'] as $key=>$val)
	    	{
	        	$data = array(
					'banner_id' => $banner_id,
					'channel_id'	=> $val
					);
				$this->EE->db->insert('contextad_banner_channels', $data);
	    	}
   		}
        
        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=index');
	
    }
    
    
    
    function delete_banner()
    {
		$success = false;
        if ($this->EE->input->get_post('banner_id')!='')
        {
            $this->EE->db->where('banner_id', $this->EE->input->get_post('banner_id'));
            $this->EE->db->delete('contextad_banner_zones');
            
            $this->EE->db->where('banner_id', $this->EE->input->get_post('banner_id'));
            $this->EE->db->delete('contextad_banner_entries');
            
            $this->EE->db->where('banner_id', $this->EE->input->get_post('banner_id'));
            $this->EE->db->delete('contextad_banner_categories');
            
            $this->EE->db->where('banner_id', $this->EE->input->get_post('banner_id'));
            $this->EE->db->delete('contextad_banner_channels');
            
            $this->EE->db->where('banner_id', $this->EE->input->get_post('banner_id'));
            $this->EE->db->delete('contextad_hits');
			
			$this->EE->db->where('banner_id', $this->EE->input->get_post('banner_id'));
            $this->EE->db->delete('contextad_banners');
            
            $success = $this->EE->db->affected_rows();
            
        }
        
        
        if ($success != false)
        {
            $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('success')); 
        }
        else
        {
            $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('error'));  
        }

        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=index');
        
        
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    function zones()
    {
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');

    	$vars = array();
    	$js = '';
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;

        $this->EE->db->select();
        $this->EE->db->from('contextad_zones');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $query = $this->EE->db->get();
        $vars['total_count'] = $query->num_rows();
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
       	$date_format = ($date_fmt == 'us')?'%m/%d/%y %h:%i %a':'%Y-%m-%d %H:%i';
        
        $vars['table_headings'] = array(
                        lang('id'),
                        lang('title'),
                        lang('edit'),
                        //lang('stats')
        			);		
		   
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           	$vars['data'][$i]['id'] = $row['zone_id'];
           	$vars['data'][$i]['title'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=zone_edit'.AMP.'zone_id='.$row['zone_id']."\">".$row['zone_title']."</a>";
           	$vars['data'][$i]['edit'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=zone_edit'.AMP.'zone_id='.$row['zone_id']."\" title=\"".lang('edit').' '.$row['zone_title']."\"><img src=\"".$this->EE->cp->cp_theme_url."images/icon-edit.png\" alt=\"".lang('edit')."\"></a>";
           //	$vars['data'][$i]['stats'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=stats'.AMP.'zone_id='.$row['zone_id']."\" title=\"".lang('stats_for').' '.$row['zone_title']."\"><img src=\"".$this->EE->config->slash_item('theme_folder_url')."third_party/contextad/stats.png\" alt=\"".lang('stats')."\"></a>";
           	$i++;
 			
        }

        
        $this->EE->javascript->output($js);
        
        $this->EE->jquery->tablesorter('.mainTable', '{
			/*headers: {0: {sorter: false}, 7: {sorter: false}},*/
			widgets: ["zebra"]
		}');


        $this->EE->load->library('pagination');

        $base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=zones';

        $p_config = $this->_p_config($base_url, $this->perpage, $vars['total_count']);

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
		
		$this->EE->cp->set_right_nav(array(
		            'add_zone' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=zone_edit')
		        );
        
    	return $this->EE->load->view('zones', $vars, TRUE);
	
    }   
    
    
    
    function zone_edit()
    {

		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
    	
    
    	$js = '';
    
		
		$values = array();
       	$db_fields = $this->EE->db->list_fields('contextad_zones');
       	foreach ($db_fields as $id=>$field)
       	{
       		$values[$field] = '';
       	}
       	$values['site_id'] = $this->EE->config->item('site_id');
		
		if ($this->EE->input->get('zone_id')!==false)
		{
			$q = $this->EE->db->select()
					->from('contextad_zones')
					->where('zone_id', $this->EE->input->get('zone_id'))
					->where('site_id', $this->EE->config->item('site_id'))
					->get();
			if ($q->num_rows()==0)
			{
				show_error(lang('unauthorized_access'));
			}
			
			foreach ($values as $field_name=>$default_field_val)
			{
				$values["$field_name"] = $q->row("$field_name");
			}
		}
		
		if ($values['zone_id']=='') 
		{
			$this->EE->cp->add_js_script('plugin', 'ee_url_title');

			$this->EE->javascript->output('
				$("#zone_title").bind("keyup keydown", function() {
					$(this).ee_url_title("#zone_name");
				});
			');
		}


        
		$data['data'] = array();
		$data['data']['zone_title'] = form_input('zone_title', $values['zone_title'], ' id="zone_title"').form_hidden('zone_id', $values['zone_id']).form_hidden('site_id', $values['site_id']);
		$data['data']['zone_name'] = form_input('zone_name', $values['zone_name'], ' id="zone_name"');
		$data['data']['xtra_html'] = form_textarea('xtra_html', $values['xtra_html']);

        
        $js .= '
				var draft_target = "";

			$("<div id=\"this_delete_warning\">'.$this->EE->lang->line('zone_delete_warning').'</div>").dialog({
				autoOpen: false,
				resizable: false,
				title: "'.$this->EE->lang->line('confirm_deleting').'",
				modal: true,
				position: "center",
				minHeight: "0px", 
				buttons: {
					Cancel: function() {
					$(this).dialog("close");
					},
				"'.$this->EE->lang->line('delete_zone').'": function() {
					location=draft_target;
				}
				}});

			$(".this_delete_warning").click( function (){
				$("#this_delete_warning").dialog("open");
				draft_target = $(this).attr("href");
				$(".ui-dialog-buttonpane button:eq(2)").focus();	
				return false;
		});';
		

        $this->EE->javascript->output($js);
        
    	return $this->EE->load->view('zone_edit', $data, TRUE);
	
    }
    


    
    function save_zone()
    {
    	if (empty($_POST))
    	{
    		show_error($this->EE->lang->line('unauthorized_access'));
    	}
    	
    	if ($this->EE->input->post('site_id')!=$this->EE->config->item('site_id'))
    	{
    		show_error(lang('unauthorized_access'));
    	}   
    	
    	if (trim($this->EE->input->post('zone_title'))=='' || trim($this->EE->input->post('zone_name'))=='')
    	{
    		show_error(lang('name_this_zone'));
    	}   	
    	
    	$this->EE->db->select('zone_id')
    				->from('contextad_zones')
    				->where('zone_title', $this->EE->input->post('zone_title'))
    				->where('zone_name', $this->EE->input->post('zone_name'));
    	if ($this->EE->input->post('zone_title')!='')
    	{
    		$this->EE->db->where('zone_id != ', $this->EE->input->post('zone_id'));
    	}
    	$check = $this->EE->db->get();
    	if ($check->num_rows()>0)
    	{
    		show_error(lang('zone_name_and_title_must_be_unique'));
    	}
    	
    	$data = array(
			'site_id'	=> $this->EE->input->post('site_id'),
			'zone_title'=> $this->EE->input->post('zone_title'),
    		'zone_name'	=> $this->EE->input->post('zone_name'),
    		'xtra_html'	=> $this->EE->input->post('xtra_html')
		);
        
      	
		if ($this->EE->input->post('zone_id')!='')
        {
			$this->EE->db->where('zone_id', $this->EE->input->post('zone_id'));
            $this->EE->db->update('contextad_zones', $data);
        }
        else
        {
            $this->EE->db->insert('contextad_zones', $data);
           
        }
        
  
        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=zones');
	
    }
    
    
    
    function delete_zone()
    {
		$success = false;
        if ($this->EE->input->get_post('zone_id')!='')
        {
            $this->EE->db->where('zone_id', $this->EE->input->get_post('zone_id'));
            $this->EE->db->delete('contextad_banner_zones');
			
			$this->EE->db->where('zone_id', $this->EE->input->get_post('zone_id'));
            $this->EE->db->delete('contextad_zones');
            
            $success = $this->EE->db->affected_rows();
            
        }
        
        
        if ($success != false)
        {
            $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('success')); 
        }
        else
        {
            $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('error'));  
        }

        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=contextad'.AMP.'method=zones');
        
        
    }
    
    
    
    
    
    
    
    
    
    
    
	
	 

    function stats()
    {
        $ext_settings = $this->EE->affiliate_plus_lib->_get_ext_settings();
        if (empty($ext_settings))
        {
        	$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=settings');
			return;
        }
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
       	$date_format = ($date_fmt == 'us')?'%m/%d/%y %h:%i %a':'%Y-%m-%d %H:%i';
       	$date_format_picker = ($date_fmt == 'us')?'mm/dd/y':'yy-mm-dd';

    	$vars = array();
        
        if ($this->EE->input->get_post('perpage')!==false)
        {
        	$this->perpage = $this->EE->input->get_post('perpage');	
        }
        $vars['selected']['perpage'] = $this->perpage;
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;
        
        $vars['selected']['member_id']=$this->EE->input->get_post('member_id');
        
        $vars['selected']['date_from']=($this->EE->input->get_post('date_from')!='')?$this->EE->input->get_post('date_from'):'';
        
        $vars['selected']['date_to']=($this->EE->input->get_post('date_to')!='')?$this->EE->input->get_post('date_to'):'';

        $this->EE->cp->add_js_script('ui', 'datepicker'); 
        $this->EE->javascript->output(' $("#date_from").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        $this->EE->javascript->output(' $("#date_to").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        
        $q = $this->EE->db->select('affiliate_commissions.member_id, screen_name')
        		->distinct()
        		->from('affiliate_commissions')
        		->join('members', 'affiliate_commissions.member_id=members.member_id', 'left')
        		->order_by('screen_name', 'asc')
        		->get();
   		$members_list = array('' => '');
   		foreach ($q->result_array() as $row)
   		{
   			$members_list[$row['member_id']] = $row['screen_name'];
   		}
   		$vars['member_select'] = form_dropdown('member_id', $members_list, $vars['selected']['member_id']);
        
        switch ($ext_settings['ecommerce_solution'])
        {
        	case 'simplecommerce':
        	case 'store':
        		$this->EE->db->select('affiliate_commissions.*, referrers.screen_name AS referrer_screen_name, referrals.screen_name AS referral_screen_name')
        			->from('affiliate_commissions')
					->join('members AS referrers', 'affiliate_commissions.member_id=referrers.member_id', 'left')
					->join('members AS referrals', 'affiliate_commissions.referral_id=referrals.member_id', 'left');
        		break;

			case 'cartthrob':
        	default:
        		$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
				$this->EE->load->model('cartthrob_settings_model');
				$cartthrob_config = $this->EE->cartthrob_settings_model->get_settings();
				$this->EE->load->remove_package_path(PATH_THIRD.'cartthrob/');
        		$this->EE->db->select('affiliate_commissions.*, referrers.screen_name AS referrer_screen_name, referrals.screen_name AS referral_screen_name, title AS order_title')
        			->from('affiliate_commissions')
					->join('members AS referrers', 'affiliate_commissions.member_id=referrers.member_id', 'left')
					->join('members AS referrals', 'affiliate_commissions.referral_id=referrals.member_id', 'left')
        			->join('channel_titles', 'affiliate_commissions.order_id=channel_titles.entry_id', 'left');
        		break;
        }
        
        $this->EE->db->where('affiliate_commissions.order_id > ', 0);
		
		if ($vars['selected']['member_id']!='' || $vars['selected']['date_from']!='' || $vars['selected']['date_to']!='')
		{
			//$this->EE->db->start_cache();
			if ($vars['selected']['member_id']!='')
			{
				$this->EE->db->where('affiliate_commissions.member_id', $vars['selected']['member_id']);
			}
			if ($vars['selected']['date_from']!='')
			{
				$this->EE->db->where('record_date >= ', $this->_string_to_timestamp($vars['selected']['date_from']));
			}
			if ($vars['selected']['date_to']!='')
			{
				$this->EE->db->where('record_date <= ', $this->_string_to_timestamp($vars['selected']['date_to']));
			}
			//$this->EE->db->stop_cache();
		}
		
		if ($this->perpage!=0)
		{
        	$this->EE->db->limit($this->perpage, $vars['selected']['rownum']);
 		}
 		
 		$this->EE->db->order_by('record_date', 'desc');
 		
 		//echo $this->EE->db->_compile_select();
 		

        $query = $this->EE->db->get();
        //$this->EE->db->_reset_select();
        
        $vars['table_headings'] = array(
                        lang('date'),
                        lang('affiliate'),
                        lang('order'),
                        lang('customer'),
                        lang('commission'),
                        ''
        			);		
		   
		
		   
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           $vars['data'][$i]['date'] = $this->EE->localize->decode_date($date_format, $row['record_date']);
           $vars['data'][$i]['affiliate'] = "<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['referrer_screen_name']."</a>";   
           switch ($ext_settings['ecommerce_solution'])
	        {
	        	case 'simplecommerce':
	        		$vars['data'][$i]['order'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_purchases'.AMP.'purchase_id='.$row['order_id']."\">".lang('order').NBS.$row['order_id']."</a>";   
	        		break;
	        	case 'store':
	        		$vars['data'][$i]['order'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=store'.AMP.'method=orders'.AMP.'order_id='.$row['order_id']."\">".lang('order').NBS.$row['order_id']."</a>";   
	        		break;
        		case 'cartthrob':
        		default:
					$vars['data'][$i]['order'] = "<a href=\"".BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'entry_id='.$row['order_id']."\">".$row['order_title']."</a>";   
					break;
			}
			$vars['data'][$i]['customer'] = ($row['referral_id']!=0)?"<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['referral_id']."\">".$row['referral_screen_name']."</a>":lang('guest');  
           $vars['data'][$i]['commission'] = $row['credits']; 
           $vars['data'][$i]['other1'] = '';    
           $i++;
 			
        }
        
        

		if (($vars['selected']['rownum']==0 && $this->perpage > $query->num_rows()) || $this->perpage==0)
		{
        	$vars['total_count'] = $query->num_rows();
 		}
 		else
 		{
 			
  			$this->EE->db->select("COUNT('*') AS count")
  				->from('affiliate_commissions');
			$this->EE->db->where('affiliate_commissions.order_id > ', 0);
  				
			if ($vars['selected']['member_id']!='' || $vars['selected']['date_from']!='' || $vars['selected']['date_to']!='')
			{
				if ($vars['selected']['member_id']!='')
				{
					$this->EE->db->where('affiliate_commissions.member_id', $vars['selected']['member_id']);
				}
				if ($vars['selected']['date_from']!='')
				{
					$this->EE->db->where('record_date >= ', $this->_string_to_timestamp($vars['selected']['date_from']));
				}
				if ($vars['selected']['date_to']!='')
				{
					$this->EE->db->where('record_date <= ', $this->_string_to_timestamp($vars['selected']['date_to']));
				}
			}
	        
	        $q = $this->EE->db->get();
	        
	        $vars['total_count'] = $q->row('count');
 		}
 		
 		//$this->EE->db->flush_cache();
 		
 		$this->EE->jquery->tablesorter('.mainTable', '{
			headers: {0: {sorter: false}, 7: {sorter: false}},
			widgets: ["zebra"]
		}');

        $this->EE->load->library('pagination');

        $base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=affiliate_plus'.AMP.'method=stats';
        $base_url .= AMP.'perpage='.$vars['selected']['perpage'];
        if ($vars['selected']['member_id']!='')
		{
        	$base_url .= AMP.'member_id='.$vars['selected']['member_id'];
 		}

        $p_config = $this->_p_config($base_url, $vars['selected']['perpage'], $vars['total_count']);

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
        
    	return $this->EE->load->view('stats', $vars, TRUE);
	
    }

    
  
    
    function _p_config($base_url, $per_page, $total_rows)
    {
        $p_config = array();
        $p_config['base_url'] = $base_url;
        $p_config['total_rows'] = $total_rows;
		$p_config['per_page'] = $per_page;
		$p_config['page_query_string'] = TRUE;
		$p_config['query_string_segment'] = 'rownum';
		$p_config['full_tag_open'] = '<p id="paginationLinks">';
		$p_config['full_tag_close'] = '</p>';
		$p_config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$p_config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$p_config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$p_config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';
        return $p_config;
    }
    
    
    
    
    function _file_select($field_name='')
	{
		if (version_compare(APP_VER, '2.2.0', '<'))
		{
			return;
		}
		
		$this->EE->lang->loadfile('fieldtypes');  
	        
        $this->EE->load->model('file_upload_preferences_model');
		
        if (version_compare(APP_VER, '2.4.0', '<'))
		{
			$upload_directories = $this->EE->file_upload_preferences_model->get_upload_preferences($this->EE->session->userdata('group_id'), '');
		}
		else
		{
			$upload_directories = $this->EE->file_upload_preferences_model->get_file_upload_preferences($this->EE->session->userdata('group_id'));
		}
		
		if (count($upload_directories) == 0) return '';
		
		foreach($upload_directories as $row)
		{
			$upload_dirs[$row['id']] = $row['name'];
		}
        
        if (count($upload_dirs) == 0) return '';
        
        $this->EE->load->library('filemanager');
		
		if (version_compare(APP_VER, '2.4.0', '<'))
		{
	        $this->EE->filemanager->filebrowser('C=content_publish&M=filemanager_actions');   
		}
		else
		{
			$this->EE->lang->loadfile('content');
			
			// Include dependencies
			$this->EE->cp->add_js_script(array(
				'plugin'    => array('scrollable', 'scrollable.navigator', 'ee_filebrowser', 'ee_fileuploader', 'tmpl', 'ee_table')
			));
			
			$this->EE->load->helper('html');
			
			$this->EE->javascript->set_global(array(
				'lang' => array(
					'resize_image'		=> $this->EE->lang->line('resize_image'),
					'or'				=> $this->EE->lang->line('or'),
					'return_to_publish'	=> $this->EE->lang->line('return_to_publish')
				),
				'filebrowser' => array(
					'endpoint_url'		=> 'C=content_publish&M=filemanager_actions',
					'window_title'		=> lang('file_manager'),
					'next'				=> anchor(
						'#', 
						img(
							$this->EE->cp->cp_theme_url . 'images/pagination_next_button.gif',
							array(
								'alt' => lang('next'),
								'width' => 13,
								'height' => 13
							)
						),
						array(
							'class' => 'next'
						)
					),
					'previous'			=> anchor(
						'#', 
						img(
							$this->EE->cp->cp_theme_url . 'images/pagination_prev_button.gif',
							array(
								'alt' => lang('previous'),
								'width' => 13,
								'height' => 13
							)
						),
						array(
							'class' => 'previous'
						)
					)
				),
				'fileuploader' => array(
					'window_title'		=> lang('file_upload'),
					'delete_url'		=> 'C=content_files&M=delete_files'
				)
			));
		}
	  
		$r = "<a href=\"#\" class=\"choose_file\"\ title=\"".$this->EE->lang->line('select_upload_image')."\"><img src=\"".$this->EE->cp->cp_theme_url."images/icon-create-upload-file.png\" alt=\"".$this->EE->lang->line('select_upload_image')."\"></a>";
        
        $r .= "<script type=\"text/javascript\">
        $(document).ready(function(){
        	var e = !1;
	$.ee_filebrowser();
	$(\"input[name=banner_img]\").each(function () {
		var a = $(this).closest(\"td\"),
			b = a.find(\".choose_file\"),
			e = 'image',
			f = 'all',
			e = {
				content_type: e,
				directory: f
			};
		$.ee_filebrowser.add_trigger(b, $(this).attr(\"name\"), e, c);
	});
    function c(a, d) {
		$(\"input[name=banner_img]\").val('{filedir_'+a.upload_location_id+'}'+a.file_name);
		//$(\"input[name=banner_width]\").val(a.file_width);
		//$(\"input[name=banner_height]\").val(a.file_height);
	}
});
</script>";
		return $r;
		
		
	}
    

    function _string_to_timestamp($human_string, $localized = TRUE)
    {
        if (version_compare(APP_VER, '2.6.0', '<'))
        {
            return $this->EE->localize->convert_human_date_to_gmt($human_string, $localized);
        }
        else
        {
            return $this->EE->localize->string_to_timestamp($human_string, $localized);
        }
    }
  

}
/* END */
?>