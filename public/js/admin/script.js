var formDataFiles = new FormData();

var ref_files_icons = [];
ref_files_icons['image/jpeg'] = "/images/files_icons/jpg.svg";
ref_files_icons['image/png'] = "/images/files_icons/png.svg";
ref_files_icons['application/pdf'] = "/images/files_icons/pdf.svg";
ref_files_icons['audio/mp3'] = "/images/files_icons/mp3.svg";
ref_files_icons['video/mp4'] = "/images/files_icons/mp4.svg";
ref_files_icons['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'] = "/images/files_icons/xls.svg";
ref_files_icons['application/vnd.ms-excel'] = "/images/files_icons/xls.svg";
ref_files_icons['application/vnd.openxmlformats-officedocument.wordprocessingml.document'] = "/images/files_icons/doc.svg";
ref_files_icons['application/msword'] = "/images/files_icons/doc.svg";
ref_files_icons['application/x-zip-compressed'] = "/images/files_icons/zip.svg";

function renderFilesIcon(formDataFiles, files_label){
    var file_info = '';
    for (let e of formDataFiles.entries()){
        var icon_path = (e[1].type && ref_files_icons[e[1].type]) ? ref_files_icons[e[1].type] : "/images/files_icons/file.svg";
        file_info += "<div class='fileIcon'>";
        file_info += "<img src='" + icon_path + "'>";
        var error_class = (e[1].size >= 30000000) ? 'fileName_error' : '';
        file_info += "<span class='" + error_class +"'>" + e[1].name + "</span>";
        file_info += "<div class='close js-delete-file' data-key='" + e[1].name + "'></div>";
        file_info += "</div>";
    }
    files_label.html(file_info);
}

$(document).ready(function(){
    var blockConfig = {
        selector: '.js-tinymce',
        menubar: false,
        inline: true,
        plugins: [
            'autolink',
            'link',
            'lists',
            'media',
            'powerpaste',
            'table',
            'image',
            'quickbars',
            'codesample',
            'help'
        ],
        toolbar: false,
        automatic_uploads: true,
        images_upload_url: '/admin/postacceptor',
        file_picker_types: 'image',
        file_picker_callback: function (cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.onchange = function () {
                var file = this.files[0];

                var reader = new FileReader();
                reader.onload = function () {
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);

                    cb(blobInfo.blobUri(), { title: file.name });
                };
                reader.readAsDataURL(file);
            };

            input.click();
        },
        quickbars_insert_toolbar: 'image quicktable',
        quickbars_selection_toolbar: 'bold italic underline | formatselect | blockquote quicklink',
        contextmenu: 'undo redo | inserttable | cell row column deletetable | help',
        powerpaste_word_import: 'clean',
        powerpaste_html_import: 'clean',
        content_style: 'img {width: 400px; height:400px}'
    };
    tinymce.init(blockConfig);

    $(document).ready(function(){
        $('.details_table').click(function(){
            var id = $(this).attr('data-id');
            if ($('#details_table_'+id).css('display') == 'none') {
                $('.table_dt').hide();
                $('#details_table_'+id).show();
            }
            else {
                $('.table_dt').hide();
            }
        });
    });

    setTimeout("$('#notice').hide()", 5000);

    function fixWidthHelper(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
            $(this).height($(this).height());
        });
        return ui;
    }

    $( function() {
        var arrayRate = [];
        var table = '';
        $(".sortable").sortable({
            helper: fixWidthHelper,
            update: function(event, ui){
                table = $(".sortable").attr('data-table');
                var count = $(".js-item").length;
                $(".js-item").each(function(){
                    arrayRate[$(this).attr('data-id')] = count;
                    $(this).attr('data-id', count--);
                });
            },
            stop: function(event, ui){
                $.ajax({
                    type: 'POST',
                    url: '/admin/ajax',
                    data: {
                        'act' : 'update_rate',
                        'array_rate' : arrayRate,
                        'table' : table
                    },
                    success: function(data){
                        var data = JSON.parse(data);
                        if (data.error) {
                            alert(data.error);
                        }
                        else {
                            location.reload();
                        }
                    }
                });
            }
        });
        $( ".sortable" ).disableSelection();
        $(".sortable").mousedown(function(){
            document.activeElement.blur();
        });
    });

    $(document).on('change', '.js-edit-order', function(e){
        var target = $(e.target);
        var field = target.attr('data-field');
        var value = (target.is(':checkbox')) ? +target.prop('checked') : target.val();
        var order_id = $(this).attr('data-order_id');

        $.ajax({
            url: "/admin/ajax",
            type: "POST",
            data: {
                "act": "edit_order",
                "order_id": order_id,
                "field": field,
                "value": value
            },
            success: function (html) {
                var result = JSON.parse(html);
                if (result.error) show_error(result.error);
                else show_success(result.success);
            }
        });
    });

    function show_error(text){
        $('.js-edit-error').html(text);
        $('.js-edit-error')[0].classList.add('visible');
        setTimeout(function(){
            $('.js-edit-error')[0].classList.remove('visible');
        }, 3000)
    }

    function show_success(text){
        $('.js-edit-success').html(text);
        $('.js-edit-success')[0].classList.add('visible');
        setTimeout(function(){
            $('.js-edit-success')[0].classList.remove('visible');
        }, 3000)
    }

    $(document).on('click', '.js-send-order-message', function(){
        var btn = $(this);
        btn.addClass('btnRose_loader');
        btn.attr('disabled', true);

        var order_id = $(this).attr('data-id');
        var text = $('.js-order-message').val();
        $('.js-message-error').html('');

        var error = "";
        if (!text) {
            error = "Введите сообщение";
        }
        else {
            for (let e of formDataFiles.entries()){
                if (e[1].size >= 30000000) {
                    error += 'Размер файла "'+ e[1].name + '" превышает допустимый размер 30Мб<br>';
                }
            }
        }

        if (error) {
            $('.js-message-error').html(error);
            btn.removeClass('btnRose_loader');
            btn.removeAttr('disabled');
            return false;
        }

        var data = new FormData();
        data.append('act', 'send_order_message');
        data.append('order_id', order_id);
        data.append('text', text);

        for (let e of formDataFiles.entries()){
            data.append(e[0], e[1]);
        }

        $.ajax({
            url: "/admin/ajax",
            type: "POST",
            data: data,
            cache: false,
            processData: false,
            contentType: false,
            success: function (html) {
                var result = JSON.parse(html);
                btn.removeClass('btnRose_loader');
                btn.removeAttr('disabled');
                if (result.error) {
                    alert(result.error);
                }
                else {
                    if ($('.js-messages .messages').length === 0) {
                        result.html = "<div class='messages'>" + result.html + "</div>";
                        $('.js-messages').append(result.html);
                    }
                    else {
                        $('.messages').append(result.html);
                    }

                    formDataFiles = new FormData();
                    $('input[name=work_files]').val('');
                    $('.js-order-message').val('');
                    $('.js-files-label').html('');
                }
            }
        });
    });

    //проверка файлов
    $(document).on('change', '.js-file-upload', function (event) {
        if (event.target.closest('.js-files-input')) {
            var context = $(this);
            var error_class = $(this).attr('data-error_class');
            var error_block = $("." + error_class);
            var files_label = $('.js-files-label');

            var upload_files = event.target.files;
            var error = '';

            error_block.html('');

            if (upload_files.length > 0) {
                $.each(upload_files, function(key, file_data){
                    if (file_data.size >= 30000000) {
                        error += 'Размер файла "'+ file_data.name + '" превышает допустимый размер 30Мб<br>';
                    }

                    if (!formDataFiles.has(file_data.name)) {
                        formDataFiles.append(file_data.name, file_data);
                    }
                });
            }

            if (error) {
                error_block.html(error).show();
            }

            renderFilesIcon(formDataFiles, files_label);
        }
    });

    $(document).on('click', '.js-delete-file', function(){
        formDataFiles.delete($(this).attr('data-key'));
        renderFilesIcon(formDataFiles, $('.js-files-label'));
    });

    $(document).on('keyup', '.js-subject', function () {
        var name = $(this).val();
        if (name.length > 3){
            $.ajax({
                url: "/index/ajax",
                type: "POST",
                data: {
                    "act": "find_subject",
                    "name": name
                },
                success: function (html) {
                    var result = JSON.parse(html);
                    if (result.error) {
                        alert(result.error);
                    }
                    else if (result.html){
                        $('.js-subject-items').html(result.html).show();
                    }
                    else {
                        $('.js-subject-items').hide();
                    }
                }
            });
        }
    });

    $(document).on('click', '.js-subject-item', function () {
        var subject = $(this).html();
        $('.js-subject').val(subject);
        $('.js-subject-items').html('').hide();
        $('.js-subject').change();
    });

    $(document).on('keyup', '.js-seo-item input[name=title], .js-seo-item textarea[name=description], .js-seo-item textarea[name=keywords]', function(){
        $(this).prev().find('span').html($(this).val().length);
    });

    $(document).on('click', '.js-change-temp-status', function(){
        var statuses = [];
        $('input[name^="statuses"]:checked').each(function(){
            statuses.push($(this).val());
        });
        statuses = statuses.join(",");
        $.ajax({
            url: "/admin/ajax",
            type: "POST",
            data: {
                "act": "update_temp_statuses",
                "statuses": statuses
            },
            success: function () {
                window.location.reload();
            }
        });
    });
});

