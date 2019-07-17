<?php if ( !empty($messages) ) { ?>
<div class="updated">
	<ul>
	<?php foreach ( $messages as $layout_optimizer_message ) { ?>
	<li><?php echo esc_html($layout_optimizer_message); ?></li>
	<?php } ?>
	</ul>
</div>
<?php } ?>
