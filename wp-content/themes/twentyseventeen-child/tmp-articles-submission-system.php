<?php
/*
 * Template Name: Articles submission system
 */
wp_head();

if( is_user_logged_in() ) {
  $user = wp_get_current_user();

  $roles = (array) $user->roles;
  $disabled = '';
  if( in_array('avs_writer', $roles) || in_array('shop_manager', $roles) || in_array('administrator', $roles) ){
    // allow page render
    $disabled = 'disabled="disabled"';
  }else{
    wp_redirect( wp_login_url( get_permalink() ) );
    exit();
  }
} else {
  wp_redirect( wp_login_url( get_permalink() ) );
  exit();
}
?>

<div id="avs-listings" class="avs-listings-wrap">
  <header class="page-header avs-header w-100">
    <div class="align-left">
      <p>
        <img width="50" height="50" src="<?php echo WP_THEME_URI . '/avs/assets/images/logo.png'; ?>" />
        <span><?php the_title();?></span>
        <p>
          <select onChange="top.location.href=this.options[this.selectedIndex].value;">
            <?php
            $users = get_users('role=avs_writer');
            $writer = isset($_REQUEST['writer']) ? $_REQUEST['writer'] : '';
            echo '<option value="'.get_the_permalink().'">--Select--</option>';

            foreach ($users as $key => $value) {
              echo '<option value="'.get_the_permalink().'?writer='.$value->ID.'" '.($writer == $value->ID ? ' selected="selected"' : "").'>'.avs_get_display_name($value->ID).'</option>';
            }
            ?>
          </select>
        </p>
      </p>

    </div>
    <div class="align-right">
      <p>Logged In As: <?php echo avs_get_display_name(); ?></p>
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
  
  <script type="text/html" id="tmpl-avs-listings">
    <# _.each( data, function( item, index ){ #>
      <tr class="new">
        <td>&nbsp&nbsp{{{index+1}}}</td>
        <td>{{{item[0]}}}</td>
        <td>
          <button type="button" class="uploadbtn"  data-index="{{{index}}}">Upload</button>
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

  <?php $data = avs_get_google_sheet_data();?>

  <script type="text/javascript">
    (function ($) {
      $(document).ready(function () {
        var tmpl_avs_listings = wp.template('avs-listings');
        var listings = JSON.parse(<?php echo json_encode($data['listings']); ?>);
        console.log(listings);
        for (i = 0; i < listings.length; i++) {

          if( listings[i][6] == 'Pending' || listings[i][6] == 'Validation Problems' ) { 
            //Do Nothing
          }else{

            listings.splice(i);

          }
        }

        $('#avs-listings table tbody').html(tmpl_avs_listings(listings));

        /**
         * Handle upload button click
         */
        var row_data = [];
        $(document).on('click', '.uploadbtn', function(){
          var data_index = $(this).data('index');
          row_data = listings[data_index];
          $('#avs-fileupload').trigger('click');
        });

        /**
         * File uploader
         */
        $('#avs-fileupload').fileupload({
          url: '<?php echo WP_SITE_URL; ?>/wp-content/uploads/server/',
          dataType: 'json',
          add: function (e, data) { // https://goo.gl/yKUxaw
            var ext = data.originalFiles[0]['name'].split('.').pop();

            if (ext == 'docx' || ext == 'doc') {
              data.submit();
            } else {
              $('.progress').css('display', 'none');
              $('.bPopup-body').html('<p>Only doc or docx files are allowed</p>');

              $('.bPopup-dialog').bPopup({
                closeClass:'bPopup-close',
                follow: [false, false],
                transition: 'slideDown'
              });
            }
          },
          done: function (e, data) {
            $.each(data.result.files, function (index, file) {
              $('#files').html('<p>' + file.name + '</p>');
              console.log(data.result.files);
              $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                cache: false,
                data: {
                  action: 'avs_process_document',
                  file_name: file.name,
                  article_data: JSON.stringify(row_data)
                },
                success : function( response ) {
                  var doc_upload_status  = '';
                  // alert(response);
                   // console.log(response);
                  if (response == 'null') {
                    doc_upload_status = '<p>Article document uploaded successfully</p>';
                  }else{
                    $.each(JSON.parse(response), function (key, val) {
                      doc_upload_status = doc_upload_status + '<p>'+val+'</p>';
                    });
                  }

                  $('.bPopup-body').html(doc_upload_status);

                  $('.bPopup-dialog').bPopup({
                    closeClass:'bPopup-close',
                    follow: [false, false],
                    transition: 'slideDown',
                   onOpen: function() {
                        if (response == 'null' && response) {
                        $('tbody tr:first-child').slideUp('slow');
                        $('#bPopup-message').text('Document uploaded');

                      }
                    },
                    onClose: function() {
                      if (response == 'null' && response) {
                        $('tbody tr:first-child').slideUp('slow');
                        $('#bPopup-message').text('Document uploaded');

                      }
                    }
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

<div class="avs-fileupload-status">
  <div id="files" class="files"></div>
  <div id="progress" class="progress" >
    <div class="progress-bar progress-bar-light progress-bar-striped "></div>
  </div>
</div>

<div class="bPopup-dialog">
  <div class="bPopup-content">
    <div class="bPopup-header">
      <h2 id="bPopup-message">Error Report</h2><hr> 
    </div>
    <div class="bPopup-body"></div>
    <div class="bPopup-footer">
      <hr><button type="button" class="bPopup-close">Ok</button>
    </div>
  </div>
</div>



<?php wp_footer();?>