var ivoleImporter;

jQuery(document).ready(function() {
    var max_file_size = _wpPluploadSettings.defaults.filters.max_file_size;

    ivoleImporter = {
        progress_id: null,

        init: function() {
            jQuery('#ivole-import-cancel').on('click', function(event) {
                event.preventDefault();
                ivoleImporter.cancel_import();
            });

            if (window.localStorage) {
                var import_data = localStorage.getItem('ivole_import_data');
                if (import_data) {
                    try {
                        import_data = JSON.parse(import_data);
                    } catch (error) {}

                    if (typeof import_data === 'object') {
                        ivoleImporter.progress_id = import_data.progress_id;
                        ivoleImporter.check_progress();
                        ivoleImporter.begin_import(import_data);
                    }
                }
            }

            ivoleImporter.uploader = new plupload.Uploader({
                browse_button: document.getElementById('ivole-select-button'),
                container: document.getElementById('ivole-upload-container'),

                url: ajaxurl,
                multi_selection: false,
                multipart_params: {
                    _wpnonce: _wpPluploadSettings.defaults.multipart_params._wpnonce,
                    action: 'ivole_import_upload_csv'
                },

                filters : {
                    max_file_size : max_file_size,
                    mime_types: [
                        {
                            title : "CSV files",
                            extensions : "csv"
                        }
                    ]
                }
            });

            ivoleImporter.uploader.bind('postinit', function(up) {
                jQuery('#ivole-upload-button').on('click', function(event) {
                    event.preventDefault();
                    ivoleImporter.uploader.start();
                    return false;
                });

                jQuery('#ivole-upload-button').prop('disabled', true);
            });

            ivoleImporter.uploader.init();

            ivoleImporter.uploader.bind('QueueChanged', function(up) {
                ivoleImporter.set_status('none', '');

                // Limit the file queue to a single file
                if (up.files.length > 1) {
                    var length = up.files.length;
                    var to_remove = [];
                    for (var i = 0; i < length - 1; i++) {
                        to_remove.push(up.files[i].id);
                    }

                    for (var g = 0; g < to_remove.length; g++) {
                        up.removeFile(to_remove[g]);
                    }
                }

                // Render the list of files, for our purposes it should only display a single file
                var $file_list = jQuery('#ivole-import-filelist');
                $file_list.html('');
                plupload.each(up.files, function(file) {
                    $file_list.append('<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ')</div>');
                });

                // If there are files in the queue, upload button is enabled, else disabled
                if (up.files.length > 0) {
                    jQuery('#ivole-upload-button').prop('disabled', false);
                } else {
                    $file_list.html(ivoleImporterStrings.filelist_empty);
                    jQuery('#ivole-upload-button').prop('disabled', true);
                }
            });

            ivoleImporter.uploader.bind('UploadProgress', function(up, file) {
                ivoleImporter.set_status('notice', ivoleImporterStrings.uploading.replace('%s', file.percent));
            });

            ivoleImporter.uploader.bind('UploadFile', function(up, file) {
                jQuery('#ivole-select-button').prop('disabled', true);
            });

            ivoleImporter.uploader.bind('FileUploaded', function(up, file, response) {
                var success = true,
                    error = pluploadL10n.default_error;

                try {
                    response = JSON.parse( response.response );
                } catch ( e ) {
                    success = false;
                }

                if ( ! _.isObject( response ) || _.isUndefined( response.success ) ) {
                    success = false;
                } else if ( ! response.success ) {
                    if (_.isObject(response.data) && response.data.message) {
                        error = response.data.message;
                    }
                    success = false;
                }

                up.refresh();
                up.removeFile(file.id);

                if (!success) {
                    ivoleImporter.set_status('error', error);
                    jQuery('#ivole-select-button').prop('disabled', false);
                    return;
                }

                ivoleImporter.progress_id = response.data.progress_id;

                if (window.localStorage) {
                    localStorage.setItem('ivole_import_data', JSON.stringify(response.data));
                }

                ivoleImporter.begin_import(response.data);
            });

            ivoleImporter.uploader.bind('Error', function(up, err) {
                var error_text;
                switch (err.code) {
                    case -600:
                        error_text = pluploadL10n.file_exceeds_size_limit.replace('%s', err.file.name);
                        break;
                    default:
                        error_text = pluploadL10n.default_error;
                }

                ivoleImporter.set_status('error', error_text);
                jQuery('#ivole-select-button').prop('disabled', false);
            });
        },

        set_status: function(status, text) {
            var $status = jQuery('#ivole-import-status');
            $status.html(text);
            $status.removeClass('status-error status-notice');

            switch (status) {
                case 'none':
                    $status.html('');
                    $status.hide();
                    return;
                case 'error':
                    $status.addClass('status-error');
                    break;
                case 'notice':
                    $status.addClass('status-notice');
                    break;
            }

            $status.show();
        },

        begin_import: function(import_job) {
            jQuery('#ivole-import-upload-steps').remove();
            jQuery('#ivole-import-text').html(ivoleImporterStrings.importing.replace('%s', '0').replace('%s', import_job.num_rows));
            jQuery('#ivole-import-progress').show();

            ivoleImporter.__progress_check_interval = setInterval(function() {
                ivoleImporter.check_progress();
            }, 1000);
        },

        import_completed: function(data) {
            clearInterval(ivoleImporter.__progress_check_interval);

            if (window.localStorage) {
                localStorage.removeItem('ivole_import_data');
            }

            var start_date = new Date(data.started * 1000);
            var end_date   = new Date(data.finished * 1000);
            var delta      = end_date.getSeconds() - start_date.getSeconds();

            jQuery('#ivole-import-result-started').html(ivoleImporterStrings.result_started.replace('%s', start_date.toLocaleDateString() + ' ' + start_date.toLocaleTimeString()));
            jQuery('#ivole-import-result-finished').html(ivoleImporterStrings.result_finished.replace('%s', end_date.toLocaleDateString() + ' ' + end_date.toLocaleTimeString()));
            jQuery('#ivole-import-result-imported').html(ivoleImporterStrings.result_imported.replace('%d', data.reviews.imported));
            jQuery('#ivole-import-result-skipped').html(ivoleImporterStrings.result_skipped.replace('%d', data.reviews.skipped));
            jQuery('#ivole-import-result-errors').html(ivoleImporterStrings.result_errors.replace('%d', data.reviews.errors));
            
            if (data.reviews.error_list && data.reviews.error_list.length > 0) {
                jQuery('#ivole-import-result-details').show().append('<div>' + data.reviews.error_list.join('<br>') + '</div>');
            }

            if (data.reviews.duplicate_list && data.reviews.duplicate_list.length > 0) {
                jQuery('#ivole-import-result-details').show().append('<div>' + data.reviews.duplicate_list.join('<br>') + '</div>');
            }

            setTimeout(function() {
                jQuery('#ivole-import-progress').hide();
                jQuery('#ivole-import-results').show();
            }, 1000);
        },

        import_failed: function() {
            clearInterval(ivoleImporter.__progress_check_interval);

            if (window.localStorage) {
                localStorage.removeItem('ivole_import_data');
            }

            jQuery('#ivole-import-result-status').html(ivoleImporterStrings.upload_failed);

            jQuery('#ivole-import-progress').hide();
            jQuery('#ivole-import-results').show();
        },

        import_cancelled: function(data) {
            jQuery('#ivole-import-result-status').html(ivoleImporterStrings.upload_cancelled);
            ivoleImporter.import_completed(data);
        },

        check_progress: function() {
            jQuery.post(
                ajaxurl,
                {
                    action: 'ivole_check_import_progress',
                    progress_id: ivoleImporter.progress_id
                }
            ).done(function(response) {
                if (response.status) {
                    if (response.status === 'importing') {
                        var processed = response.reviews.imported + response.reviews.skipped + response.reviews.errors;
                        var percentage = Math.floor((processed / response.reviews.total) * 100);
                        jQuery('#ivole-import-text').html(ivoleImporterStrings.importing.replace('%s', processed).replace('%s', response.reviews.total));
                        jQuery('#ivole-progress-bar').val(percentage);
                    } else if(response.status === 'failed') {
                        ivoleImporter.import_failed();
                    } else if (response.status === 'complete') {
                        var processed = response.reviews.imported + response.reviews.skipped + response.reviews.errors;
                        var percentage = Math.floor((processed / response.reviews.total) * 100);
                        jQuery('#ivole-progress-bar').val(percentage);
                        ivoleImporter.import_completed(response);
                    } else if (response.status === 'cancelled') {
                        ivoleImporter.import_cancelled(response);
                    }
                } else if (response === false) {
                    ivoleImporter.import_failed();
                }
            });
        },

        cancel_import: function() {
            var $cancel_button = jQuery('#ivole-import-cancel');
            $cancel_button.prop('disabled', true);
            $cancel_button.html(ivoleImporterStrings.cancelling);
            jQuery.post(
                ajaxurl,
                {
                    action: 'ivole_cancel_import',
                    progress_id: ivoleImporter.progress_id
                }
            ).done(function(response){
              ivoleImporter.import_cancelled(response);
            }).fail(function(response) {
              jQuery('#ivole-import-result-status').html(ivoleImporterStrings.upload_cancelled);
              clearInterval(ivoleImporter.__progress_check_interval);
              if (window.localStorage) {
                  localStorage.removeItem('ivole_import_data');
              }
              setTimeout(function() {
                $cancel_button.prop('disabled', false);
                $cancel_button.html(ivoleImporterStrings.cancel);
                jQuery('#ivole-import-progress').hide();
                jQuery('#ivole-import-results').show();
              }, 1000);
            });
        }
    };

    ivoleImporter.init();
})
