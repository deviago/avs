<?php
/*
 * Template Name: Articles submission system
 */
wp_head();
?>

<div id="avs-listings" class="avs-listings-wrap">
  <header class="page-header avs-header w-100">
    <div class="align-left">
      <p>
        <img width="50" height="50" src="<?php echo WP_THEME_URI . '/avs/assets/images/logo.png'; ?>" />
        <span><?php the_title();?></span>
      </p>
    </div>

    <div class="align-right">
      <p>Loged In As: <?php echo avs_get_display_name(); ?></p>
    </div>

    <input id="avs-fileupload" type="file" name="files[]" >
  </header>

  <div class="avs-list-tbl-wrap">
    <table class="widefat fixed striped">
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

  <div class="avs-fileupload-status w-100">
    <div id="files" class="files"></div>
    <div id="progress" class="progress" >
      <div class="progress-bar progress-bar-light progress-bar-striped "></div>
    </div>
    <div id="progress-error" style="color: red;"></div>
  </div>

  <?php $avs_current_user = avs_get_display_name(); ?>

  <script type="text/html" id="tmpl-avs-listings">
    <# _.each( data, function( item, index ){  #>
      <# if(item[1] == '<?php echo $avs_current_user; ?>') { #>
        <tr>
          <td>&nbsp&nbsp{{{index+1}}}</td>
          <td>{{{item[1]}}}</td>
          <td>
            <button type="button" class="btn btn-primary"  onclick="jQuery('#avs-fileupload').click();">Upload</button>
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
      <# } #>
    <# }) #>
  </script>

  <?php $data = avs_get_google_sheet_data();?>

	<script type="text/javascript">
		(function ($) {
		  $(document).ready(function () {
		    var tmpl_avs_listings = wp.template('avs-listings');

		    // Quick search in large JSON data. Reference link - http://fusejs.io
		    var options = { keys: ['6'] };
		    var fuse = new Fuse(JSON.parse(<?php echo json_encode($data['listings']); ?>), options);
		    var filterd_data = fuse.search('Pending, Validated');

        $('#avs-listings table tbody').html(tmpl_avs_listings(filterd_data));

        $('#avs-fileupload').fileupload({
          url: '<?php echo WP_SITE_URL; ?>/wp-content/uploads/articles-docs/',
          dataType: 'json',
          add: function (e, data) { // https://goo.gl/yKUxaw
            var ext = data.originalFiles[0]['name'].split('.').pop();
            if (ext == 'docx' || ext == 'doc') {
              $('#progress-error').html('');
              data.submit();
            } else {
              $('#files').html(''); $('#progress .progress-bar').css('width', '0px');
              $('#progress-error').html('*Only doc or docx files are allowed');
            }
          },
          done: function (e, data) {
            $.each(data.result.files, function (index, file) {
              $('#files').html('<p>' + file.name + '</p>');

              $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                cache: false,
                data: {
                  action: 'avs_process_document',
                  file_name: file.name,
                },
                success : function( response ) {
                  $.each(JSON.parse(response), function (key, val) {
                    $('.avs-fileupload-status').append('<p>'+val+'</p>');
                  });
                }
              });
            });
          },
          progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .progress-bar').css('width', progress + '%');
          }
        });
		  });
		}(jQuery));
	</script>
</div>

<?php wp_footer();?>