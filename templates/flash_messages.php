<?php if(!empty($messages)){ ?>
<div class="updated">
	<ul>
	<?php foreach($messages as $message){ ?>
	<li><?php echo esc_html($message); ?></li>
	<?php } ?>
	</ul>
</div>
<?php } ?>
