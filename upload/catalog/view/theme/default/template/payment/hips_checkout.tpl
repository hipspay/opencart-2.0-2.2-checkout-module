<?php echo $header; ?>
<div class="container">

  <div class="row">
	  
<!-- <div class="col-sm-12">
		  <p><?php echo $heading_title; ?></p>
	</div>	 -->

<?php 
	if (isset($section_left) && $section_left=='no') {
?>
		<div id="content" class="col-sm-4">
		 <?php echo $column_left; ?>
		</div>
<?php }?>		 
		<div id="content" <?php if (isset($section_left) && $section_left=='yes') {?> class="col-sm-12" <?php }else{?>class="col-sm-8"<?php }?>>
			<?php echo $column_main; ?>
		</div>
     
	</div>
	
</div>

<?php echo $footer; ?>