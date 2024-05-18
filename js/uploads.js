jQuery(document).ready(function($) {
  var uploadButton = $('#upload'),
    tusFile = $('#tus-file');
  $('.file-input').on('change', function(e) {
    var name = e.target.value.split('\\').reverse()[0];
    var extension = name.substr(name.length - 4);
    if (name && extension == '.iso') {
      uploadButton.attr('disabled', false);
    } else {
      uploadButton.attr('disabled', true);
    }
  });
  uploadButton.on('click', function(e) {
    var formData = new FormData,
      fileMeta = tusFile[0].files[0],
      fileSize = fileMeta.size,
      bytesUploaded = 0;
    formData.append('tus_file', fileMeta);
    formData.append('useriso_fname', $('#useriso_fname').val());
    formData.append('useriso_type', $('#useriso_type').val());
    formData.append('useriso_who', $('#user').val());
    tusFile.attr('disabled', true);
    $('#useriso_fname').attr('disabled', true);
    $('#useriso_type').attr('disabled', true);
    $('[id^=useriso_delete]').attr('disabled', true);
    uploadButton.attr('disabled', true).text('Calculating...');
    initiateUpload(formData, fileMeta, function() {
      upload(formData, fileSize, function(data) {
        bytesUploaded = data;
        renderProgressBar(bytesUploaded, fileSize);
      }, function(uploadKey) {
        cleanUp();
        listUploadedFiles(fileMeta, uploadKey);
      });
    });
  });
  $('[id^=useriso_delete]').click(function(e) {
    e.preventDefault();
    $(this).prop("disabled", true);
    var data = {
      id: $(this).attr('role')
    };
    socket.emit('UserISODeleteReq', data);
  });
  socket.on('UserISODeleteRes', function(res) {
    window.location.reload();
  });
});

function initiateUpload(formData, fileMeta, cb) {
  $.ajax({
    type: 'POST',
    url: 'verify.php',
    data: formData,
    dataType: 'json',
    processData: false,
    contentType: false,
    success: function(response) {
      if('error' === response.status) {
        $('#error').html(response.error).fadeIn(200);
        cleanUp();
        return;
      }
      renderProgressBar(response.bytes_uploaded, fileMeta.size);
      if('uploaded' === response.status) {
        cleanUp();
        listUploadedFiles(fileMeta, response.upload_key)
      }else if('error' !== response.status) {
        cb();
      }
    },
    error: function(error) {
      $('#error').fadeIn(200);
    }
  });
}

function upload(formData, fileSize, cb, onComplete) {
  $('#upload').text('Uploading...');
  $.ajax({
    type: 'POST',
    url: 'upload.php',
    data: formData,
    dataType: 'json',
    processData: false,
    contentType: false,
    success: function(response) {
      if ('error' === response.status) {
        $('#error').html(response.error).fadeIn(200);
        cleanUp();
        return;
      }
      var bytesUploaded = response.bytes_uploaded;
      cb(bytesUploaded);
      if (bytesUploaded < fileSize) {
        upload(formData, fileSize, cb, onComplete);
      }else{
        onComplete(response.upload_key);
      }
    },
    error: function(error) {
      $('#error').fadeIn(200);
    }
  });
}

var cleanUp = function() {
  $('.progress').hide(100, function() {
    $('.progress-bar')
      .attr('style', 'width: 0%')
      .attr('aria-valuenow', '0');
  });
};

var listUploadedFiles = function(fileMeta, uploadKey) {
  var completedUploads = $('div.completed-uploads');
  completedUploads.find('p.info').remove();
  $('#upload').text('Done!');
  completedUploads.append(
    '<div class="panel panel-success"><div class="panel-body">Upload successful! Refresh the page to view ISO status.</div></div>'
  );
};

var renderProgressBar = function(bytesUploaded, fileSize) {
  var percent = (bytesUploaded / fileSize * 100).toFixed(2);
  $('.progress-bar')
    .attr('style', 'width: ' + percent + '%')
    .attr('aria-valuenow', percent)
    .find('span')
    .html(percent + '%');
  $('.progress').show();
}
