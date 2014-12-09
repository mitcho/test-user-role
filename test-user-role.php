<?php
/*
Plugin Name: Test User Role
Author: mitcho (Michael 芳貴 Erlewine)
Description: Quickly test other user roles from a super admin account, to see what other users experience.
Author URI: http://mitcho.com/
Donate link: http://tinyurl.com/donatetomitcho
License: GPLv2 or later
Version: 1.0
*/

add_action('admin_bar_menu', 'tus_menu', 11);

function tus_menu( $wp_admin_bar ) {
	if ( !tus_is_testable() )
		return;

	$user = wp_get_current_user();
// 	if ( isset($_REQUEST['test']) ) {
// 		var_dump($user);
// // 		exit;
// 	}

	$wp_admin_bar->add_node(array(
		'id' => 'tus-test',
		'title' => 'Test User Role',
		'href' => false,
		'parent' => 'my-account',
// 		'secondary' => true
	));
	
	global $wp_roles;
	if ( isset($_REQUEST['test']) ) {
		var_dump($wp_roles);
	}	
	
	$current_roles = $user->roles;
	foreach ( get_testable_roles() as $role => $details ) {
		$prefix = ( in_array($role, $current_roles) ) ? '✔' : '&nbsp;';
		$prefix = '<span style="min-width: 10px; display: inline-block;">' . $prefix . '</span> ';

		$wp_admin_bar->add_node(array(
			'id' => 'tus-test-' . $role,
			'title' => $prefix . translate_user_role( $details['name'] ),
			'href' => add_query_arg( 'test_user_role', $role ),
			'parent' => 'tus-test'
		));
	}
}

function get_testable_roles() {
	global $wp_roles;
	$all_roles = $wp_roles->roles;
	$testable_roles = apply_filters( 'testable_roles', $all_roles );
	return $testable_roles;
}

function tus_is_testable() {
	if ( is_super_admin() )
		return true;

	if ( !$user = wp_get_current_user() )
		return false;
	
	if ( isset( $user->_tus_original_roles ) )
		return true;

	return false;
}

add_action('init', 'tus_switch');
function tus_switch() {
	if ( !tus_is_testable() || !isset( $_GET['test_user_role'] ) )
		return;

	if ( !in_array( $_GET['test_user_role'], array_keys(get_testable_roles()) ) )
		return;

	if ( !$user = wp_get_current_user() )
		die('-1');

	add_user_meta( $user->ID, '_tus_original_roles', $user->roles, true );
	$user->set_role( $_GET['test_user_role'] );

	// todo: why is redirect not working properly on non-admin side?
	wp_redirect( remove_query_arg( 'test_user_role' ) );
}

add_action( 'clear_auth_cookie', 'tus_reset' );
function tus_reset( $user = false ) {
	if ( $user == false && !$user = wp_get_current_user() )
		die('-1');
	if ( !isset( $user->_tus_original_roles ) )
		return;

	$user->set_role( false );
	foreach ( $user->_tus_original_roles as $role ){
		$user->add_role($role);
	}
}

// make sure to reset on login too, because clear_auth_cookie could be overwritten.
add_action( 'wp_login', 'tus_login_reset', 10, 2 );
function tus_login_reset( $user_login, $user ) {
	tus_reset( $user );
}


