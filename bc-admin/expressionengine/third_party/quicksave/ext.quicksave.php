<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD . 'quicksave/config.php');

class Quicksave_ext {

    var $settings        = array();

    var $name            = QS_NAME;
    var $version         = QS_VERSION;
    var $settings_exist  = 'y';
    var $docs_url        = QS_DOCS;


    // -------------------------------
    //   Constructor - Extensions use this for settings
    // -------------------------------

    function Quicksave_ext($settings='')
    {
		$this->EE =& get_instance();

		$this->active_site	= $this->EE->config->item('site_id');

		if(is_array($settings) AND array_key_exists($this->active_site, $settings))
		{
			$this->settings = $settings[$this->active_site];
		}
		else
		{
			$this->settings = array();
		}
    }

	// --------------------------------
	//  Activate Extension
	// --------------------------------

	function activate_extension()
	{
	    global $DB;

	    $this->EE->db->query($this->EE->db->insert_string('exp_extensions',
	                                  array(
	                                        'extension_id' => '',
	                                        'class'        => __CLASS__,
	                                        'method'       => "modify_redirect",
	                                        'hook'         => "entry_submission_redirect",
	                                        'settings'     => "",
	                                        'priority'     => 50,
	                                        'version'      => $this->version,
	                                        'enabled'      => "y"
	                                      )
	                                 )
	              );

	    $this->EE->db->query($this->EE->db->insert_string('exp_extensions',
	                                  array(
	                                        'extension_id' => '',
	                                        'class'        => __CLASS__,
	                                        'method'       => "add_buttons",
	                                        'hook'         => "cp_js_end",
	                                        'settings'     => "",
	                                        'priority'     => 50,
	                                        'version'      => $this->version,
	                                        'enabled'      => "y"
	                                      )
	                                 )
	              );

	}

	// --------------------------------
	//  Update Extension
	// --------------------------------

	function update_extension($current='')
	{
	    if ($current == '' OR $current == $this->version)
	    {
	        return FALSE;
	    }

	    // Add cp_js_end hook
	    if ($current < '1.2')
	    {
	    	$qs_settings = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE class = '".__CLASS__."' LIMIT 1");

		    $this->EE->db->query($this->EE->db->insert_string('exp_extensions',
											array(
												'extension_id' => '',
												'class'        => __CLASS__,
												'method'       => "add_buttons",
												'hook'         => "cp_js_end",
												'settings'     => $qs_settings->row('settings'),
												'priority'     => 50,
												'version'      => $this->version,
												'enabled'      => "y"
											)
										)
									);
	    }

	    // Convert old settings to site-specific settings (they'll be applied to site #1 or the active site if #1 doesn't exist)
	    if($current < '1.4')
	    {
	    	$site_data = $this->EE->db->get_where('exp_sites', array('site_id' => 1));

			$site = ($site_data->num_rows() === 1) ? 1 : $this->EE->config->item('site_id');

	    	$qs_settings = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE class = '".__CLASS__."' LIMIT 1");

	    	if($qs_settings->num_rows() > 0)
	    	{
   				$old_settings = unserialize($qs_settings->row('settings'));
   				$new_settings = serialize( array($site => $old_settings) );

   				$this->EE->db->where('class', __CLASS__)->update('exp_extensions', array('settings' => $new_settings));
	    	}
	    }

	    $this->EE->db->query("UPDATE exp_extensions
	                SET version = '".$this->EE->db->escape_str($this->version)."'
	                WHERE class = '".__CLASS__."'");
	}

	// --------------------------------
	//  Disable Extension
	// --------------------------------

	function disable_extension()
	{
		$this->EE->db->query("DELETE FROM exp_extensions WHERE class = '".__CLASS__."'");
	}

	// --------------------------------
	//  Modify the Default Redirect
	// --------------------------------

	function modify_redirect($entry_id, $meta, $data, $cp_call, $orig_loc)
	{
		// Deal with any pesky SAEFs
		if(!$cp_call)
		{
			return $orig_loc;
		}

		// Setup redirects

		// Save and Preview (aka default EE behaviour)
		if($this->EE->input->post('qs_save_preview'))
		{
			return BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$meta['channel_id'].AMP.'entry_id='.$entry_id.AMP.'U=new';
		}

		// Save and Close (aka main Edit page)
		elseif($this->EE->input->post('qs_save_close'))
		{
			if(@$this->settings['save_close_dest'] == 'structure_home')
			{
				return BASE.AMP.'C=addons_modules&M=show_module_cp&module=structure';
			}
			else
			{
				return BASE.AMP.'C=content_edit';
			}
		}

		// Default behaviour (aka Quick Save)
		else
		{
			return BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$meta['channel_id'].AMP.'entry_id='.$entry_id;
		}


	}

	// --------------------------------
	//  Add the QuickSave JS to the CP
	// --------------------------------

	function add_buttons()
	{
		// Are the extra buttons enabled?
		$docready = "\t";

		if(@$this->settings['save_preview'] == 'yes')
		{
			$docready .= 'qs.save_and_preview(); ';
		}

		if(@$this->settings['save_close'] == 'yes')
		{
			$docready .= 'qs.save_and_close();';
		}

		if(@$this->settings['hide_revision_button'] == 'yes')
		{
			$docready .= 'qs.hide_revision_button();';
		}

		if(@$this->settings['clone_buttons'] == 'yes')
		{
			$docready .= 'qs.clone_buttons();';
		}

		$this->EE->lang->loadfile('quicksave');

		$save_close_btn = $this->EE->lang->line('save_close_btn');
		$save_preview_btn = $this->EE->lang->line('save_preview_btn');

		// Load the QuickSave JS
		$r = <<<EOF
(function($){ vaya_quicksave=function(){ var qs=this;$(document).ready(function(){ $("#publish_submit_buttons #submit_button").attr("value","Save"); {$docready} });qs.save_and_close=function(){ $("#publish_submit_buttons").append('<li><input type="submit" class="submit" name="qs_save_close" id="save_close" value="{$save_close_btn}" style="margin-left:10px" /></li>') };qs.save_and_preview=function(){ $("#publish_submit_buttons").append('<li><input type="submit" class="submit" name="qs_save_preview" id="save_close" value="{$save_preview_btn}" style="margin-left:10px" /></li>') };qs.hide_revision_button=function(){ $("#revision_button").hide() };qs.clone_buttons=function(){ $("#publish_submit_buttons").clone().prependTo("#holder").wrap('<div class="publish_field" style="padding-top: 0; padding-bottom: 0">').css("margin-top","0") };$("#showToolbarLink a").click(function(){ setTimeout(function(){ if($("#submit_button").hasClass("disabled_field"))$("#publish_submit_buttons .submit").attr("disabled",true).addClass("disabled_field");else $("#publish_submit_buttons .submit").removeAttr("disabled").removeClass("disabled_field") }, 50) }) } })(jQuery); new vaya_quicksave();
EOF;

		return $this->EE->extensions->last_call . $r;
	}

	// --------------------------------
	//  Legacy function from the CI hook days
	// --------------------------------
	// If users leave the CI hook call in place and this function is removed a fatal
	// error will occur. Best leave it here to be safe.

	function cp_tweaks()
	{
		return;
	}

	// --------------------------------
	//  Settings form
	// --------------------------------
	function settings_form($current)
	{
		$vars = array();

		$this->EE->load->helper('form');
		$this->EE->load->library('table');

		$yes_no_array = array('value' => 'no', 'options' =>  array('yes', 'no'));

		// Defaults
		$defaults							= array();
		$defaults['save_preview']			= $yes_no_array;
		$defaults['save_close']				= $yes_no_array;
		$defaults['hide_revision_button']	= $yes_no_array;
		$defaults['clone_buttons']			= $yes_no_array;

		// Is Structure installed? If so, let folks decide whether to go there after using 'save and close'
		$query = $this->EE->db->get_where('exp_modules', array('module_name' => 'Structure'));
		if($query->num_rows() === 1)
		{
			$defaults['save_close_dest']	= array('value' => 'edit_entries', 'options' =>  array('edit_entries', 'structure_home'));
		}

		// Do settings exist for this site?
		if(array_key_exists($this->active_site, $current))
		{
			foreach($current[$this->active_site] as $name => $value)
			{
				if(array_key_exists($name, $defaults))
				{
					$defaults[$name]['value'] = $value;
				}
			}
		}

		// Setup fields
		$settings = array();

		foreach($defaults as $name => $data)
		{
			$options = array();
			foreach($data['options'] as $option)
			{
				$options[] =	form_radio(array(
									'name'		=> $name,
									'id'		=> $name.'_'.$option,
									'value'		=> $option,
									'checked' 	=> ($data['value'] == $option) ? 'checked' : ''
									)
								)
								.NBS.form_label($this->EE->lang->line($option), $name.'_'.$option);
			}

			$settings[$name] = $options;
		}

		// Setup user info note ('these settings apply to $sitename') if MSM is active
		if ($this->EE->config->item('multiple_sites_enabled') == 'y')
		{
			$vars['site_name'] = $this->EE->config->item('site_name');
		}

		$vars['settings'] = $settings;

		return $this->EE->load->view('settings', $vars, TRUE);
	}

	// --------------------------------
	//  Save settings
	// --------------------------------
	function save_settings()
	{
		unset($_POST['submit']);

		$settings = array();

		// Get existing settings so we don't overwrite those for other sites
		$query = $this->EE->db->get_where('exp_extensions', array('class' => __CLASS__), 1);

		if($query->num_rows() > 0 AND $query->row('settings') != '')
		{
			$settings = unserialize($query->row('settings'));
		}

		foreach($_POST as $name => $value)
		{
			$settings[$this->active_site][$name] = $value;
		}

		$this->EE->db->where('class', __CLASS__)->update('extensions', array('settings' => serialize($settings)));

		$this->EE->session->set_flashdata(
			'message_success',
			$this->EE->lang->line('preferences_updated')
		);
	}

}
// END CLASS

/* End of file ext.quicksave.php */
/* Location: ./system/expressionengine/third_party/quicksave/ext.quicksave.php */