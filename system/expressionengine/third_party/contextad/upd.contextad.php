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
 File: upd.contextad.php
-----------------------------------------------------
 Purpose: Context banners management system for ExpressionEngine
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'contextad/config.php';

class Contextad_upd {

    var $version = CONTEXTAD_ADDON_VERSION;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
    } 
    
    function install() { 

		$this->EE->load->dbforge(); 

        $data = array( 'module_name' => 'Contextad' , 'module_version' => $this->version, 'has_cp_backend' => 'y', 'has_publish_fields' => 'n'); 
        $this->EE->db->insert('modules', $data); 
        
        $data = array( 'class' => 'Contextad' , 'method' => 'find_entries' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Contextad' , 'method' => 'register_hit' ); 
        $this->EE->db->insert('actions', $data); 
        
        //exp_contextad_zones
        $fields = array(
			'zone_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			'site_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'zone_name'			=> array('type' => 'VARCHAR',	'constraint'=> 100,	'default' => ''),
			'zone_title'		=> array('type' => 'VARCHAR',	'constraint'=> 250,	'default' => ''),
			'xtra_html'			=> array('type' => 'TEXT',		'default' => ''),
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('zone_id', TRUE);
		$this->EE->dbforge->add_key('site_id');
		$this->EE->dbforge->add_key('zone_name');
		$this->EE->dbforge->create_table('contextad_zones', TRUE);
        
        //exp_contextad_banners
        $fields = array(
			'banner_id'					=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			
			'site_id'					=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			
			'title'						=> array('type' => 'VARCHAR',	'constraint'=> 150,	'default' => ''), 
			
			'weight'					=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 1),
			
			'link_url'					=> array('type' => 'VARCHAR',	'constraint'=> 255,	'default' => ''), 
			'link_target'				=> array('type' => 'VARCHAR',	'constraint'=> 20,	'default' => '_self'), 
			
			'banner_img'				=> array('type' => 'VARCHAR',	'constraint'=> 255,	'default' => ''), 
			
			'banner_width'				=> array('type' => 'VARCHAR',	'constraint'=> 50,	'default' => ''), 
			'banner_height'				=> array('type' => 'VARCHAR',	'constraint'=> 50,	'default' => ''), 

			'alt_text'					=> array('type' => 'VARCHAR',	'constraint'=> 255,	'default' => ''), 
			
			'xtra_html'					=> array('type' => 'TEXT',		'default' => ''),

			'limit_views'				=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'limit_clicks'				=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'show_start'				=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'show_end'					=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			
			'views'						=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'clicks'						=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			
			'enabled'					=> array('type' => 'CHAR',		'constraint'=> 1,	'default' => 'y'),
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('banner_id', TRUE);
		$this->EE->dbforge->add_key('site_id');
		$this->EE->dbforge->create_table('contextad_banners', TRUE);
		
		//exp_contextad_banner_zones
        $fields = array(
			'banner_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'zone_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('banner_id');
		$this->EE->dbforge->add_key('zone_id');
		$this->EE->dbforge->create_table('contextad_banner_zones', TRUE);
		
		//exp_contextad_banner_entries
        $fields = array(
			'banner_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'entry_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('banner_id');
		$this->EE->dbforge->add_key('entry_id');
		$this->EE->dbforge->create_table('contextad_banner_entries', TRUE);
		
		//exp_contextad_banner_categories
        $fields = array(
			'banner_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'cat_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('banner_id');
		$this->EE->dbforge->add_key('cat_id');
		$this->EE->dbforge->create_table('contextad_banner_categories', TRUE);
		
		//exp_contextad_banner_channels
        $fields = array(
			'banner_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'channel_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('banner_id');
		$this->EE->dbforge->add_key('channel_id');
		$this->EE->dbforge->create_table('contextad_banner_channels', TRUE);
		
        
        //exp_contextad_hits
        $fields = array(
			'hit_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			'site_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'banner_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'zone_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'member_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'ip_address'		=> array('type' => 'VARCHAR',	'constraint'=> 45,	'default' => ''),
			'hit_date'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('hit_id', TRUE);
		$this->EE->dbforge->add_key('site_id');
		$this->EE->dbforge->add_key('banner_id');
		$this->EE->dbforge->add_key('zone_id');
		$this->EE->dbforge->add_key('member_id');
		$this->EE->dbforge->create_table('contextad_hits', TRUE);
		
        
        return TRUE; 
        
    } 
    
    
    function uninstall() { 

        $this->EE->load->dbforge(); 
		
		$this->EE->db->select('module_id'); 
        $query = $this->EE->db->get_where('modules', array('module_name' => 'Contextad')); 
        
        $this->EE->db->where('module_id', $query->row('module_id')); 
        $this->EE->db->delete('module_member_groups'); 
        
        $this->EE->db->where('module_name', 'Contextad'); 
        $this->EE->db->delete('modules'); 
        
        $this->EE->db->where('class', 'Contextad'); 
        $this->EE->db->delete('actions'); 
        
        $this->EE->dbforge->drop_table('contextad_hits');
        $this->EE->dbforge->drop_table('contextad_channels');
        $this->EE->dbforge->drop_table('contextad_categories');
        $this->EE->dbforge->drop_table('contextad_entries');
        $this->EE->dbforge->drop_table('contextad');
        $this->EE->dbforge->drop_table('contextad_zones');

        return TRUE; 
    } 
    
    function update($current='') 
	{ 
		
		return TRUE; 
    } 
	

}
/* END */
?>