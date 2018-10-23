<?php
/* 
 * Template Name: Articles submission system
 */

get_header();

if(!is_user_logged_in()) {
  wp_redirect( wp_login_url( get_permalink() ) );
}
?>
<div class="wrapper">

	 <input id="fileupload" type="file" name="files[]" >
 
   
		<header class="page-header avs-header">
		
		<div class="align-left">
			<p>
				<img width="50" height="50" src="<?php echo get_template_directory_uri() . '/wp-logo.png'; ?>" />
				<span><?php the_title(); ?></span>
			</p>
		</div>
		<div class="align-right">
			<p>Loged In As: <?php echo avs_get_display_name(); ?></p>
		</div>
	</header>
	<div id="avs-listings" class="avs-listings-wrap"> 
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Sr. No</th>
					<th>Owner</th>
					<th>Upload</th>	
					<th>UID</th>
					<th>Date Received</th>
					<th>Status</th>
					<th>Type</th>
					<th>Main Keyword</th>
					<th>Main URL</th>
					<th>To Be In Title</th>
					<th>Length</th>
					<th>Image</th>
					<th>Count</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td align="center" colspan="13">Loading ...</td>  
				</tr>
			</tbody>
		</table>

	</div>

	<script type="text/html" id="tmpl-avs-listings">
		<# _.each( data, function( item, index ){  #>
			<tr>
				<td>&nbsp&nbsp{{{index+1}}}</td>
				<td>{{{item[1]}}}</td>
				<td>
          <button type="button" class="btn btn-primary"  onclick="jQuery('#fileupload').click();">Upload</button>
     		</td>
				<td>{{{item[2]}}}</td>
				<td>{{{item[4]}}}</td>
				<td>{{{item[6]}}}</td>
				<td>{{{item[7]}}}</td>
				<td>{{{item[9]}}}</td>
				<td>{{{item[10]}}}</td>
				<td>{{{item[11]}}}</td>
				<td>{{{item[12]}}}</td>
				<td>{{{item[13]}}}</td>
				<td>NA</td>
			</tr>
		<# }) #>
	</script>
  
    <div id="files" class="files"></div>
   	<div id="progress" class="progress" >
        <div class="progress-bar progress-bar-light progress-bar-striped "></div>
  </div>
    <span id="progress-error" style="color: red;"></span>
  </div>
</div>   
      
<?php get_footer();