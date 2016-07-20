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
 File: mod.contextad.php
-----------------------------------------------------
 Purpose: Context banners management system for ExpressionEngine
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}


class Contextad {

    var $return_data	= ''; 	
    
    var $settings = array();

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function __construct()
    {        
    	$this->EE =& get_instance(); 

		$this->EE->lang->loadfile('contextad');  
    }
    /* END */
    
    
    function find_entries()
    { 
        $out = '';
        $str = urldecode($this->EE->input->get_post('q'));
        if (strlen($str)<3)
        {
            exit();
        }
		$this->EE->db->select('entry_id, title');
        $this->EE->db->from('channel_titles');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $this->EE->db->where('title LIKE "%'.$str.'%"');
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $out .= $row['entry_id']."=".$row['title']."\n";
        }
   		echo trim($out);
     	exit();
    }
    


    
    function show()
    {
		if ($this->EE->TMPL->fetch_param('zone')=='' && $this->EE->TMPL->fetch_param('zone_id')=='') 
		{
			return $this->EE->TMPL->no_results();
		}
		
		$noentry = false;
		$nocat = false;
		$nochannel = false;
		
		/*$this->EE->db->select('contextad_banners.*')
				->from('contextad_banners')
				->join('contextad_banner_zones', 'contextad_banners.banner_id=contextad_banner_zones.banner_id', 'left');*/
		$this->EE->db->select('contextad_banner_zones.zone_id, contextad_banner_zones.banner_id')
				->from('contextad_banner_zones');
		if ($this->EE->TMPL->fetch_param('zone_id')!='') 
		{
			$this->EE->db->where('zone_id', $this->EE->TMPL->fetch_param('zone_id'));
		}
		else
		{
			$this->EE->db->join('contextad_zones', 'contextad_zones.zone_id=contextad_banner_zones.zone_id', 'left');
			$this->EE->db->where('zone_name', $this->EE->TMPL->fetch_param('zone'));
		}
		if ($this->EE->TMPL->fetch_param('entry_id')=='' && $this->EE->TMPL->fetch_param('url_title')=='') 
		{
			$noentry = true;
			$this->EE->db->where('NOT EXISTS (SELECT null FROM exp_contextad_banner_entries WHERE exp_contextad_banner_entries.banner_id=exp_contextad_banner_zones.banner_id)', NULL, false);
			if ($this->EE->TMPL->fetch_param('category_id')=='' && $this->EE->TMPL->fetch_param('category')=='') 
			{
				$nocat = true;
				$this->EE->db->where('NOT EXISTS (SELECT null FROM exp_contextad_banner_categories WHERE exp_contextad_banner_categories.banner_id=exp_contextad_banner_zones.banner_id)', NULL, false);
			}
			if ($this->EE->TMPL->fetch_param('channel_id')=='' && $this->EE->TMPL->fetch_param('channel')=='') 
			{
				$nochannel = true;
				$this->EE->db->where('NOT EXISTS (SELECT null FROM exp_contextad_banner_channels WHERE exp_contextad_banner_channels.banner_id=exp_contextad_banner_zones.banner_id)', NULL, false);
			}
		}
		//echo $this->EE->db->_compile_select();
		$query = $this->EE->db->get();
		//echo $this->EE->db->last_query();
		if ($query->num_rows()==0)
		{
			return $this->EE->TMPL->no_results();
		}
		
		$zone_banners = array();
		foreach ($query->result_array() as $row)
		{
			$zone_banners[$row['banner_id']] = $row['banner_id'];
			if (!isset($zone_id)) $zone_id = $row['zone_id'];
		}
		//no entry id
		if ($noentry && $nocat && $nochannel) 
		{
			$this->_show_banner($zone_banners, $zone_id);
			return;
		}
		else
		{
			if ($this->EE->TMPL->fetch_param('entry_id')!='')
			{
				$entry_id = $this->EE->TMPL->fetch_param('entry_id');
			}
			else if ($this->EE->TMPL->fetch_param('url_title')!='')
			{
				$entry_id_q = $this->EE->db->select('entry_id, channel_id')
								->from('channel_titles')
								->where('url_title', $this->EE->TMPL->fetch_param('url_title'))
								->where('site_id', $this->EE->config->item('site_id'))
								->get();
				if ($entry_id_q->num_rows() > 0)
				{
					$entry_id = $entry_id_q->row('entry_id');
					$channel_id = $entry_id_q->row('channel_id');
				}
				
			}
			
			//for entry
			if (isset($entry_id))
			{
				$query = $this->EE->db->select('banner_id')
					->from('contextad_banner_entries')
					->where('entry_id', $entry_id)
					->where_in('banner_id', $zone_banners)
					->get();
				if ($query->num_rows() > 0)
				{
					$entry_banners = array();
					foreach ($query->result_array() as $row)
					{
						$entry_banners[$row['banner_id']] = $row['banner_id'];
					}
					
					$banner_displayed = $this->_show_banner($entry_banners, $zone_id);
					
					if ($banner_displayed!=false) return $banner_displayed;
				}
			}

			//for category
			if (isset($entry_id) || $nocat == false)
			{
				$this->EE->db->select('banner_id');
				$this->EE->db->from('contextad_banner_categories');
				
				if (isset($entry_id))
				{
					$this->EE->db->join('category_posts', 'contextad_banner_categories.cat_id=category_posts.cat_id', 'left');
					$this->EE->db->where('category_posts.entry_id', $entry_id);
				}
				else
				{
					if ($this->EE->TMPL->fetch_param('category_id')!='')
					{
						$this->EE->db->where('contextad_banner_categories.cat_id', $this->EE->TMPL->fetch_param('category_id'));
					}
					else
					{
						$this->EE->db->join('categories', 'contextad_banner_categories.cat_id=categories.cat_id', 'left');
						$this->EE->db->where('categories.cat_url_title', $this->EE->TMPL->fetch_param('category'));
					}
				}
				
				$this->EE->db->where_in('banner_id', $zone_banners);
				
				$query = $this->EE->db->get();
				if ($query->num_rows() > 0)
				{
					$category_banners = array();
					foreach ($query->result_array() as $row)
					{
						$category_banners[$row['banner_id']] = $row['banner_id'];
					}
					
					$banner_displayed = $this->_show_banner($category_banners, $zone_id);
					
					if ($banner_displayed!=false) return $banner_displayed;
				}
			}
			
			
			//for channel
			if (isset($entry_id) || $nochannel == false)
			{
				if (!isset($channel_id))
				{
					if (isset($entry_id))
					{
						$channel_id_q = $this->EE->db->select('channel_id')
										->from('channel_titles')
										->where('entry_id', $entry_id)
										->where('site_id', $this->EE->config->item('site_id'))
										->get();
						if ($q->num_rows() > 0)
						{
							$channel_id = $channel_id_q->row('channel_id');
						}
						
					}
					else if ($this->EE->TMPL->fetch_param('channel_id')!='')
					{
						$channel_id = $this->EE->TMPL->fetch_param('channel_id');
					}
					else
					{
						$channel_id_q = $this->EE->db->select('channel_id')
										->from('channels')
										->where('channel_name', $this->EE->TMPL->fetch_param('channel_id'))
										->where('site_id', $this->EE->config->item('site_id'))
										->get();
						if ($channel_id_q->num_rows() > 0)
						{
							$channel_id = $channel_id_q->row('channel_id');
						}
					}
				}
				
				if (isset($channel_id))
				{
					$query = $this->EE->db->select('banner_id')
						->from('contextad_banner_channels')
						->where('channel_id', $channel_id)
						->where_in('banner_id', $zone_banners)
						->get();
					if ($query->num_rows() > 0)
					{
						$channel_banners = array();
						foreach ($query->result_array() as $row)
						{
							$channel_banners[$row['banner_id']] = $row['banner_id'];
						}
						
						$banner_displayed = $this->_show_banner($channel_banners, $zone_id);
						
						if ($banner_displayed!=false) return $banner_displayed;
					}
				}
			}
			
				//$this->_show_banner(array $banners);
		}
		
		//if we got here, still no banners are shown
		//get banners that are not tied anywhere (considered site-wide)
		$this->EE->db->select('banner_id')
				->from('contextad_banner_zones')
				->where_in('banner_id', $zone_banners);
		$this->EE->db->where('NOT EXISTS (SELECT null FROM exp_contextad_banner_entries WHERE exp_contextad_banner_entries.banner_id=exp_contextad_banner_zones.banner_id)', NULL, false);
		$this->EE->db->where('NOT EXISTS (SELECT null FROM exp_contextad_banner_categories WHERE exp_contextad_banner_categories.banner_id=exp_contextad_banner_zones.banner_id)', NULL, false);
		$this->EE->db->where('NOT EXISTS (SELECT null FROM exp_contextad_banner_channels WHERE exp_contextad_banner_channels.banner_id=exp_contextad_banner_zones.banner_id)', NULL, false);
			
		$query = $this->EE->db->get();
		if ($query->num_rows()==0)
		{
			return $this->EE->TMPL->no_results();
		}
		
		$site_banners = array();
		foreach ($query->result_array() as $row)
		{
			$site_banners[$row['banner_id']] = $row['banner_id'];
		}
		
		$banner_displayed =  $this->_show_banner($site_banners, $zone_id);
		
		if ($banner_displayed!=false) 
		{
			return $banner_displayed;
		}
		else
		{
			return $this->EE->TMPL->no_results();
		}
		
    }
    
    
    
    function _show_banner($banners_array, $zone_id)
    {

		$query = $this->EE->db->select()
    			->from('contextad_banners')
    			->where_in('banner_id', $banners_array)
    			->where('enabled', 'y')
    			->get();
		if ($query->num_rows()==0)
		{
			return false;
		}
		
		$banners = array();
		$i = 0;
		
		foreach ($query->result_array() as $row)
		{
			if ($row['limit_views']!=0 && $row['views']>=$row['limit_views'])
			{
				continue;
			}
			if ($row['limit_clicks']!=0 && $row['clicks']>=$row['limit_clicks'])
			{
				continue;
			}
			if ($row['show_start']!=0 && $row['show_start']>$this->EE->localize->now)
			{
				continue;
			}
			if ($row['show_end']!=0 && $row['show_end']<$this->EE->localize->now)
			{
				continue;
			}

			if ($row['weight']<1) $row['weight']=1;
			for ($x=0; $x<$row['weight']; $x++)
			{
				$banners[$i] = $row;
				$i++;
			}
			
		}
		
		if (count($banners)==0) return false;
		
		//get random banner
		shuffle($banners);
		$i = array_rand($banners);
		$banner = $banners[$i];
		
		//show the banner
		$act = $this->EE->db->select('action_id')
						->from('exp_actions')
						->where('class', 'Contextad')
						->where('method', 'register_hit')
						->get();
        $link = rtrim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id').'&zone_id='.$zone_id.'&banner_id='.$banner['banner_id'];
        
        $this->EE->load->helper('string');
        
        if ($banner['banner_img']!='')
        {
        	if (strpos($banner['banner_img'], '{filedir_') !== FALSE)
			{
        		$this->EE->load->library('file_field');
				$banner['banner_url'] = $this->EE->file_field->parse_string($banner['banner_img']);
       		}
        	$text = '<img src="'.$banner['banner_url'].'" alt="'.strip_quotes($banner['alt_text']).'"';
			if ($banner['banner_width']!='') $text .= ' width="'.$banner['banner_width'].'"';
			if ($banner['banner_height']!='') $text .= ' height="'.$banner['banner_height'].'"';
			$text .= ' />';
        }
        else
        {
        	$text = $banner['alt_text'];
        }
		
		if ($banner['link_target']=='') $banner['link_target'] = '_self';
		
		$zone_q = $this->EE->db->select('xtra_html')
				->from('contextad_zones')
				->where('zone_id', $zone_id)
				->get();
		
		$out = '<a href="'.$link.'"  class="contextad contextad_zone_'.$zone_id.'" target="'.$banner['link_target'].'">'.$text.'</a>'.$banner['xtra_html'].$zone_q->row('xtra_html');
		
		//update shows
		$data = array('views' =>'views+1');
		$this->EE->db->where('banner_id', $row['banner_id']);
		$this->EE->db->update('contextad_banners', $data);

		return $out;
    }
    
    
    

    
    function _redirect($url='')
    {
    	if ($url=='')
    	{
    		$url = $this->EE->functions->fetch_site_index(1);
    	}
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		exit;
    }

    
    function register_hit()
    {
    	if ($this->EE->input->get('banner_id')=='') return false;
    	
    	$query = $this->EE->db->select('link_url')
    				->from('contextad_banners')
    				->where('banner_id', $this->EE->input->get('banner_id'))
    				->get();
		
		if ($query->num_rows()==0) return false;
		
		$banner = $query->row_array();
		
		if ($banner['link_url']!='')
        {
		    if (strpos($banner['link_url'], "http://")!==FALSE || strpos($banner['link_url'], "https://")!==FALSE)
		    {
		        $link = $banner['link_url'];
		    }
		    else
		    {
		        $this->EE->load->library('template');
        		$this->EE->TMPL = $this->EE->template;
        		if (strpos($banner['link_url'], "{path=")!==FALSE)
        		{
        			$link = $this->EE->TMPL->parse_globals($banner['link_url']);
        		}
        		else
        		{
        			$link = $this->EE->functions->create_url($this->EE->TMPL->parse_globals($banner['link_url']));
        		}
		    }
        }
        else
        {
        	$link = $this->EE->functions->fetch_site_index(true);
        }
        
        $data = array(
			'site_id'			=> $this->EE->config->item('site_id'),
			'banner_id'			=> $this->EE->input->get('banner_id'),
			'zone_id'			=> $this->EE->input->get('zone_id'),
			'member_id'			=> $this->EE->session->userdata('member_id'),
			'ip_address'		=> $this->EE->input->ip_address(),
			'hit_date'			=> $this->EE->localize->now
		);
		$this->EE->db->insert('contextad_hits', $data);
        
        $data = array('clicks' =>'clicks+1');
		$this->EE->db->where('banner_id', $this->EE->input->get('banner_id'));
		$this->EE->db->update('contextad_banners', $data);
        
        $this->_redirect($link);
		
		
	}
	
	
	


}
/* END */
?>