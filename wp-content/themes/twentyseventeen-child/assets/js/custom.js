(function ($) {
  $(document).ready(function () {
    var tmpl_avs_listings = wp.template('avs-listings');
    $.ajax({
      url: 'https://sunmax.ourgoogle.in/clients/kosta-hristov/avs/google-api-php-client/index.php',
      type: 'GET',
      success: function (response, textStatus, jQxhr) {
        if (response.error === false) {
          // Quick search in large JSON data. Reference link - http://fusejs.io
          var options1 = { keys: ['6'] };
          var fuse1 = new Fuse(JSON.parse(response.listings), options1);
          var filterd_data1 = fuse1.search('Pending, Validated');
          console.log(filterd_data1);

          setTimeout(function () {
            var options2 = { keys: ['1'], findAllMatches: true };
            var fuse2 = new Fuse(filterd_data1, options2);
            var filterd_data2 = fuse2.search(ajax_object.user_display_name);
            console.log(filterd_data2);

            $('#avs-listings table tbody').html(tmpl_avs_listings(filterd_data2));
          }, 2500);
        } else {
          alert(response.error);
          window.location = response.authUrl;
        }
      }
    })
    var myfile = "";


    // Change this to the location of your server-side upload handler:
    $('#fileupload').fileupload({
      url: 'https://sunmax.ourgoogle.in/clients/kosta-hristov/avs/wp-content/uploads/avs/server/',
      dataType: 'json',
      add: function (e, data) { // https://goo.gl/yKUxaw      
        var ext = data.originalFiles[0]['name'].split('.').pop();
        if (ext == "docx" || ext == "doc") {
          data.submit();
          $("#progress-error").html("");
        } else {
          $('#progress .progress-bar').css('width', '0px');
          $('#files').html('');
          $("#progress-error").html("*Only doc or docx files are allowed");

        }
      },
      done: function (e, data) {
        $.each(data.result.files, function (index, file) {
          $('#files').html('<p>' + file.name + '</p>');
        });
      },
      progressall: function (e, data) {
        var progress = parseInt(data.loaded / data.total * 100, 10);
        $('#progress .progress-bar').css(
          'width',
          progress + '%'
        );
      }
    }).prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled');

    $('.uploadbtn').click(function (e) {
      e.preventDefault();
      $('#fileupload').trigger('click');
    });
  });
}(jQuery));