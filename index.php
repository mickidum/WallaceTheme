
<?php
	$wal_app_state = Wallace::get_initial_state('home', null);
	get_header();
?>


    <body>

    	<wallace>
			<?php echo $wal_twig->render('home.html', array(
				'posts' => $wal_app_state['posts'], 
				'siteData' => $wal_app_state['site_data'],
				'siteMenus' => $wal_app_state['site_menus']
				)); 

			?>
    	</wallace>

    	<script>
			var walInitialState = <?php echo json_encode($wal_app_state); ?>;
			console.log('state --- ');
			console.log(walInitialState);
			walInitialState.selectedPostId = -1;

		</script>

<?php get_footer(); ?>


	