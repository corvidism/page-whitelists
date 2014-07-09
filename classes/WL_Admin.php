<?php

/**
 * creates settings page
 */
class WL_Admin {
	private $settings;
	private $data;
	
	function __construct(&$data,&$settings) {
		$this->settings = $settings;
		$this->data = $data;
	}
	
	
	public function add_menus() {
		//$this->data->create_whitelist("mango");
		//$this->data->create_whitelist("apple");
		//$this->data->create_whitelist("grape");
		$this->data->create_whitelist("radish");
		$this->data->create_whitelist("millenium");
		$this->data->create_whitelist("junior");
		
		$apple = $this->data->get_whitelist_by('name','apple');
		//$grape = $this->data->get_whitelist_by('name','grape');
		
		//$apple->add_page(289);
		//$apple->add_page(300);
		//$apple->add_user(3);
		
		//$grape->add_page(19);
		//$grape->add_role('editor');
		
		
		add_menu_page( 
			$this->settings->get_plugin_title(), //label of the sidebar link
			$this->settings->get_plugin_title(), //title of the main options page
			'manage_options',
			'wl_lists', //the slug of the options page
			array($this,'render_lists_page')  
		);
		
		/*
		add_submenu_page( 
					'wl_lists', 
					'Roles',
					'Roles',
					'manage_options', 
					'$wl_roles', 
					array($this,'render_roles_page')
					);*/
		
		add_submenu_page( 
			'wl_lists', 
			'Settings',
			'Settings',
			'manage_options', 
			'$wl_settings', 
			array($this,'render_settings_page')
			);
		
	}
	
	public function enqueue_scripts($hook) {
		WL_Dev::log("trying to enqueue scripts");
		$screen = get_current_screen(); 
		if($screen->id != 'toplevel_page_wl_lists') {
			WL_Dev::log("not on the right page");
			return;
		}
		$script_path = $this->settings->get_template_url(). 'js/wl_lists.js';
		wp_enqueue_script('wl_lists_js', $script_path, array('jquery'),'1.0.0',true);
	}
	
	public function register_ajax() {
		WL_Dev::log('registering ajax');
		add_action('wp_ajax_wl_delete', array($this,'ajax_delete'));
		add_action('wp_ajax_wl_load', array($this,'ajax_load'));
	}
	
 	public function render_settings_page() {
		require_once $this->settings->get_template_path()."settings_page.php";
		//whitelists as strict?
		//how to combine wlists
		//...???
	}
	
	public function render_roles_page() {
		require_once $this->settings->get_template_path()."roles_page.php";
		//load existing roles into a table
		//Create New...
			//a table of permissions, possibly with explanations?
			//assign whitelist
		//Edit
			//table of permissions
			//assigned whitelists - add, remove
		//Delete
		
		//should have similar look and feel as the rest of WP
		
	}
	
	public function render_lists_page() {
		
		$lists = $this->data->get_all_whitelists(); //returns all lists from the database as WL_List objects
		require_once $this->settings->get_template_path()."lists_page.php";

		//load existing whitelists
		//Create New...
			//add pages from a list (checkboxes?)
			//(possibly - add categories, tags)
			//assign to Roles
			//(assign to users -----> on Users page should be an option to add whitelist, too!)
		//Edit
			//assigned to Roles - add, remove
			//(assigned to Users - add, remove)
	}
	
	public function ajax_delete() {
		$id = $_POST['id'];
		$result = $this->data->delete_whitelist($id);
		if ($result[0]) {
			$reply = 'success';
		} else {
			$reply = $result[1];
		}
		die($reply);
	}
	
	public function ajax_load() {
		/*
		header('Cache-Control: no-cache, must-revalidate'); //don't cache
				$year_exp = date('Y')+5;  
				$date_exp = date('D, n M ').$year_exp.date(' H:i:s e');
				header('Expires: '.$date_exp);
				header('Content-type: application/json');*/
		
		
		//FIRST STEP: build a "fresh" data array
		$data = array();
		
		$data['pages'] = array();
		$query = new WP_Query('post_type=page');
		while ($query->have_posts()) {
			$query->the_post();
			$data['pages'][] = array(
				'title'=> $query->post->post_title,
				'id' => $query->post->ID,
				'assigned'=>false
			);
		} //TODO add page hierarchy (parent/child; automatically mark children in editor)
		wp_reset_postdata();
		
		$data['users'] = array();
		$query_args = array(
			'orderby'=>'ID'
		);
		$user_query = new WP_User_Query($query_args);
		$users = $user_query->results;
		foreach($users as $user) {
			$data['users'][] = array(
				'login' => $user->user_login,
				'id' => $user->ID,
				'assigned' => false
			); 
		}
		
		$all_roles = get_editable_roles();
		//which roles shouldn't be assignable? "admin", "superadmin"?
		$data['roles'] = array();
		foreach ($all_roles as $rolename=>$roledata) {
			$data['roles'][$rolename] = false;
		}
		
		if (isset($_POST['id'])) {
			$id = $_POST['id'];
			$list = $this->data->get_whitelist_by('id',$id);
			$assigned_pages = $list->get_page_ids();
			foreach ($data['pages'] as $key=>$page) {
				if (in_array($page['id'],$assigned_pages)) {
					$data['pages'][$key]['assigned']=true;
				}
			} 
			$assigned_users = $list->get_user_logins();
			foreach ($data['users'] as $key=>$user) {
				if (in_array($user['login'],$assigned_users)) {
					$data['users'][$key]['assigned']=true;
				}
			}
			 
			$assigned_roles = $list->get_role_names();
			foreach ($data['roles'] as $key=>$role) {
				if (in_array($key,$assigned_roles)) {
					$data['roles'][$key] = true;
				}
			}  
			//add data			
		} else {
			$id = 0;
		}
		
		die(json_encode($data)); 		
	}
	
	public function ajax_save() {
		//create new whitelist from ajaxed data, or update an existing whitelist
		//needed: hidden field with id
		
	}

}