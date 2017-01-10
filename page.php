
<?php
	$wal_app_state = Wallace::get_initial_state('page', get_the_ID());
	get_header();
?>

    <body>
    	<wallace>

			<?php 
				echo $wal_twig->render('page.html', array(
				'post' => $wal_app_state['pages'][0],
				'siteData' => $wal_app_state['site_data'],
				'siteMenus' => $wal_app_state['site_menus']
				)); 
			?>
    	</wallace>

    	<script>
			var walInitialState = <?php echo json_encode($wal_app_state); ?>;
			// console.log(walInitialState);
			walInitialState.selectedPostId = <?php echo get_the_ID(); ?>;
		</script>

<?php get_footer(); ?>


	