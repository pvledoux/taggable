<?php if (!defined("BASEPATH")) die("No direct script access allowed");
/**
 * Taggable
 *
 * A powerful, easy to use folksonomy
 * engine for ExpressionEngine 2.0.
 *
 * @author Jamie Rumbelow <http://jamierumbelow.net>
 * @copyright Copyright (c)2010 Jamie Rumbelow
 * @license http://getsparkplugs.com/taggable/docs#license
 * @version 1.2.1
 **/

require_once PATH_THIRD."taggable/config.php";

class Taggable_upd {
	public $version = TAGGABLE_VERSION;
	private $ee;
	
	public function __construct() {
		$this->ee =& get_instance();
		$this->ee->load->dbforge();
	}
	
	public function install() {
		// exp_modules
		$module = array(
			'module_name' 			=> 'Taggable',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'y',
			'has_publish_fields' 	=> 'n'
		);
		
		$this->ee->db->insert('modules', $module);
		
		// exp_taggable_tags
		$tags = array(
			'id' 			=> array('type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'name'			=> array('type' => 'VARCHAR', 'constraint' => 100),
			'description'	=> array('type' => 'TEXT'),
			'site_id'		=> array('type' => 'INT', 'default' => $this->ee->config->item('site_id'))
		);
		
		$this->ee->dbforge->add_field($tags);
		$this->ee->dbforge->add_key('id', TRUE);
		$this->ee->dbforge->create_table('taggable_tags');
		
		// exp_taggable_tags_entries
		$tags_entries = array(
			'tag_id' 	=> array('type' => 'INT'),
			'entry_id'	=> array('type' => 'INT'),
			'template'  => array('type' => 'VARCHAR', 'constraint' => 250, 'default' => 'tags')
		);
		
		$this->ee->dbforge->add_field($tags_entries);
		$this->ee->dbforge->add_key('tag_id');
		$this->ee->dbforge->add_key('entry_id');
		$this->ee->dbforge->create_table('taggable_tags_entries');
		
		// exp_taggable_preferences
		$taggable_preferences = array(
			'id'			=> array('type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'site_id'		=> array('type' => 'INT', 'default' => '1'),
			'preference'	=> array('type' => 'VARCHAR', 'constraint' => 50),
			'type'			=> array('type' => 'VARCHAR', 'constraint' => 10),
			'value'			=> array('type' => 'TEXT')
		);
		
		$this->ee->dbforge->add_field($taggable_preferences);
		$this->ee->dbforge->add_key('id', TRUE);
		$this->ee->dbforge->create_table('taggable_preferences');
		
		// Insert default preference values
		$this->ee->config->load('default_preferences', TRUE);
		
		foreach ($this->ee->config->item('default_preferences') as $key => $value) {			
			$this->ee->db->set('preference', $key)
						 ->set('value', $value['value'])
						 ->set('type', $value['type'])
						 ->set('site_id', $this->ee->config->item('site_id'))
						 ->insert('taggable_preferences');
		}
		
		// We're done!
		return TRUE;
	}
	
	public function uninstall() {
		// Goodbye!
		$this->ee->dbforge->drop_table('taggable_tags');
		$this->ee->dbforge->drop_table('taggable_tags_entries');
		$this->ee->dbforge->drop_table('taggable_preferences');
		$this->ee->db->where('module_name', 'Taggable')->delete('modules');
		
		// We're done
		return TRUE;
	}
	
	public function update($version = '') {
		// Update from 1.0 to 1.1:
		//   - Add the site ID to the tags table, set the current ID as the default
		//   - Get rid of the 'search_tags' extension hook
		if ($version < 1.1) {			
			$this->ee->dbforge->add_column('tags', array('site_id' => array('type' => 'INT')));
			$this->ee->db->set('site_id', $this->ee->config->item('site_id'))->update('tags');
			$this->ee->db->where('class', 'Taggable_ext')->where('method', 'search_tags')->delete('extensions');
		}
		
		// Update from 1.1 to 1.2:
		//   - Add the template column to tags_entries
		//   - Get rid of the publish tab
		//   - Get rid of the 'parse_tags_tag' hook
		//   - Add the site ID to the preferences table
		if ($version < 1.2) {
			$this->ee->dbforge->add_column('tags_entries', array('template' => array('type' => 'VARCHAR', 'constraint' => 250, 'default' => 'UPGRADE')));
			$this->ee->db->set('has_publish_fields', 'n')->where('module_name', 'Taggable')->update('modules');
			$this->ee->db->where('preference_key', 'enable_autotagging')->where('preference_key', 'alchemy_api_key')->delete('taggable_preferences');
			$this->ee->db->where('method', 'parse_tags_tag')->where('class', 'Taggable_ext')->delete('exp_extensions');
			$this->ee->dbforge->add_column('taggable_preferences', array('site_id' => array('type' => 'INT', 'default' => '1')));
		}
		
		// Update from 1.2 to 1.3:
		//   - Rename tables so they're all prefixed with 'taggable_'
		//   - Drop table prefix from columns
		//	 - Get rid of the extension
		if ($version < 1.3) {
			$this->ee->dbforge->rename_table('tags', 'taggable_tags');
			$this->ee->dbforge->rename_table('tags_entries', 'taggable_tags_entries');
			$this->ee->db->where('class', 'Taggable_ext')->delete('exp_extensions');
			
			// @todo Add renaming of all the columns to upgrade
		}

		return TRUE;
	}
}