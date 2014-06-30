<?php
/**
 * Plugin Name: Cr Receive Emaile Commentators
 * Plugin URI: https://github.com/WP-Panda/cr-receive-email-commentators
 * Description: Плагин предназначен для сбора адресов электронной почты авторов комментариев, и экспорта их в CSV файл. В целях безопасности, и сохранения конфедициальности, рекомендуется активировать плагин только когда надо получить данные, после чего его желательно отключить. При деактивации плагин удаляет сфоримрованные им файлы."
 * Version: 1.0.0
 * Author: Максим (WP_Panda) Попов
 * Author URI: https://www.fl.ru/users/creative_world/
 * License: A "Slug" license name e.g. GPL2
 */

$cr_csv_receive_foler_dir = $_SERVER['DOCUMENT_ROOT'] .'/wp-content/emails_csv_files/';
function cr_receive_email_commentators_activate() {

    $cr_csv_receive_foler_dir = $_SERVER['DOCUMENT_ROOT'] .'/wp-content/emails_csv_files/';
	if ( !file_exists( $cr_csv_receive_foler_dir ) ) mkdir( $cr_csv_receive_foler_dir );
}
register_activation_hook( __FILE__, 'cr_receive_email_commentators_activate' );

function cr_receive_email_commentators_deactivate() {

    global $cr_csv_receive_foler_dir;
    if (file_exists( $cr_csv_receive_foler_dir )) cr_receive_remove_dir( $cr_csv_receive_foler_dir );

}
register_deactivation_hook( __FILE__, 'cr_receive_email_commentators_deactivate' );



	class options_page {
		function __construct() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}

		function admin_menu () {
			add_submenu_page( 'tools.php',__( "Cr E-mailes импорт", "wp_panda" ),__( "Cr E-mailes импорт", "wp_panda" ),'manage_options','cr-receive-email-commentators-submenu-page', array( $this, 'cr_receive_email_commentators_settings_page' ) );
		}
		
		function  cr_receive_email_commentators_settings_page () { ?>
			<div class="wrap">
				<h2><i class="dashicons dashicons-id" style="margin-right: 1em; margin-bottom: 1em; font-size: 1.5em; color: rgb(30, 140, 190);"></i><?php _e('Cr E-mailes импорт','wp_panda'); ?></h2>
				<span class="button button-primary email-parse"><?php _e('Получить адреса',' wp_panda'); ?></span>
				<div class="email-append"><?php cr_receive_laterst_file(); ?></div>
			</div>

		<?php }
	}

	new options_page;

	function cr_receive_email_action_javascript() {
	  $ajax_nonce = wp_create_nonce( "cr-receive-special-string" );
	?>

	<script>
	jQuery(document).ready(function($) {
		$('.email-parse').click(function() {

			var data = {
			action: 'cr_receive_email_action',
			security: '<?php echo $ajax_nonce; ?>',
			};

			$.post(ajaxurl, data, function(response) {
				$('.email-append').html(response);
			});
		});	
	});
	</script>

	<?php
	}

	add_action( 'admin_footer', 'cr_receive_email_action_javascript' );
	 
	function cr_receive_email_action_callback() {
		global $wpdb;
		global $cr_csv_receive_foler_dir;

		check_ajax_referer( 'cr-receive-special-string', 'security' );

		$emails = $wpdb->get_results("SELECT DISTINCT comment_author_email FROM $wpdb->comments");

		$out_emails= array();
		foreach ( $emails as $key ) {
	 		if(!empty( $key->comment_author_email ) && filter_var( $key->comment_author_email, FILTER_VALIDATE_EMAIL)) {
	  			$out_emails[][] = $key->comment_author_email;
	  		}
	 	}
        
	 	$file_name = 'email_lists'. date("m_d_y__G_i_s") . '.csv';
	 	$foler_name_path = $cr_csv_receive_foler_dir . $file_name;
	 	$file_too_write = fopen( $foler_name_path, 'w');
	 	$n = 0;
	 	foreach ( $out_emails as $key ) {
	  			fputcsv($file_too_write, $key);
	  		$n++;
	 	}

		fclose($file_too_write);
		echo '<h3>' . __( "Импорт завершен, получено Адресов -  ","wp_panda") . $n .'</h3>';
		echo '<a href="' . get_home_url() . '/wp-content/emails_csv_files/' . $file_name .'">' . __( "Файл с результатами импорта можно скачать по этой ссылке","wp_panda") . '</a>' ;
		die();
	}

	add_action( 'wp_ajax_cr_receive_email_action', 'cr_receive_email_action_callback' );

	function cr_receive_remove_dir( $path ) {
	   if ( $content_del_cat = glob( $path.'/*') ) {
	      	foreach ( $content_del_cat as $object ) {
	        	if ( is_dir( $object ) ) {
	            	cr_receive_remove_dir( $object );
	        	} else {
	           		unlink( $object );
	            }
	        }
	    }
		rmdir( $path );
	}

	function cr_receive_laterst_file(){
		global $cr_csv_receive_foler_dir;
		if ( is_dir( $cr_csv_receive_foler_dir ) ) {
        	if ( $dh = opendir( $cr_csv_receive_foler_dir ) ) {
            	while ( ( $file = readdir( $dh ) ) !== false ) {

	                $time_sec = time();
	                $time_file[] = filemtime( $cr_csv_receive_foler_dir . $file );
	                $files[] = $file;
            	}

	            if( count($time_file)-1 === 1 )  {
	            	_e( 'Сформированных файлов нет', 'wp_admin' );
						return;
				} else {
					$last_index = count($time_file)-1;
					echo '<a href="' .  get_home_url() . '/wp-content/emails_csv_files/' . $files[$last_index] . '">' . __('Скачать последний сформированный файл','wp_panda') . '</a>';
				}
                
            }
            closedir($dh);
    	}
    }
