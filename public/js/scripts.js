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

$(document).ready(function() {
    $(".js-datepicker").datepicker({
        dateFormat: 'dd.mm.yy',
        minDate: 0
    });

    $('.js-promises').slick({
        dots: true,
        arrows: true,
        prevArrow: ".arrowLeft",
        nextArrow: ".arrowRight",
        appendDots: ".promisesItem__btns"
    });

    var result = document.querySelector('.result');
    var auth = document.querySelector('.auth');
    var reg = document.querySelector('.reg');
    var reset = document.querySelector('.reset');
    var payment = document.querySelector('.payment');
    var mobileMenu = document.querySelector('.mobile-menu');
    document.body.addEventListener('click', function(e) {
        if (e.target.closest('.js-result-popup')) {
            e.preventDefault();
            result.classList.add('active');
        }
        if (e.target.closest('.js-auth-popup')) {
            e.preventDefault();
            auth.classList.add('active');
        }
        if (e.target.closest('.modal__reg-link')) {
            e.preventDefault();
            reg.classList.add('active');
            auth.classList.remove('active');
            reset.classList.remove('active');
            result.classList.remove('active');
        }
        if (e.target.closest('.modal__auth-link')) {
            e.preventDefault();
            reg.classList.remove('active');
            auth.classList.add('active');
            reset.classList.remove('active');
            result.classList.remove('active');
        }
        if (e.target.closest('.modal__reset-link')) {
            e.preventDefault();
            reg.classList.remove('active');
            auth.classList.remove('active');
            reset.classList.add('active');
            result.classList.add('active');
        }

        if (e.target.closest('.menu-mobile-toggle')) {
            mobileMenu.classList.add('active');
            document.body.classList.add('noscroll');
        }

        if (e.target.closest('.mobile-menu__close')) {
            mobileMenu.classList.remove('active');
            document.body.classList.remove('noscroll');
        }

        if (e.target.closest('.js-reg-close')
            || e.target.closest('.js-auth-close')
            || e.target.closest('.js-reset-close')
            || e.target.closest('.js-payment-close')
            || e.target.closest('.js-result-close')) {
            reg.classList.remove('active');
            auth.classList.remove('active');
            reset.classList.remove('active');
            result.classList.remove('active');
            payment.classList.remove('active');

            if (e.target.closest('.js-result-close')) {
                $('.js-work-type').val(0);
                $('input[name=work_email]').val('');
                $('input[name=work_fio]').val('');
                $('input[name=work_deadline]').val('');
                $('.js-subject').val('');
                $('.js-delete-file').click();
            }
        }

        if (e.target.closest('.js-payment-popup')) {
            var order_id = $(e.target).attr('data-id');
            $('.js-payment-error').html('');
            $.ajax({
                url: "/lk/ajax",
                type: "POST",
                data: {
                    "act": "get_order_payment",
                    "order_id": order_id
                },
                success: function (html) {
                    var result = JSON.parse(html);
                    if (result.error) {
                        alert(result.error);
                    }
                    else {
                        reg.classList.remove('active');
                        auth.classList.remove('active');
                        reset.classList.remove('active');
                        payment.classList.add('active');

                        $('input[name=payment_sum]').prop('checked', false);
                        $('input[name=paymentType]').prop('checked', false);
                        $('.js-payment-amount').html('');
                        $('input[name=sum]').val(0);

                        $('.js-payment-order-number').text(order_id);
                        $('.js-label-order-id').val(order_id);
                        $('input[name=targets]').val(order_id);

                        $('.js-sum50-val', $(payment)).val(result.sum50);
                        $('.js-sum100-val', $(payment)).val(result.sum100);
                        $('.js-sum-last-val', $(payment)).val(result.sum_last);

                        if (+result.sum50 > 0) {
                            $('.js-sum50', $(payment)).text(result.sum50);
                            $('.js-sum50-val', $(payment)).parent().show();
                        }
                        else {
                            $('.js-sum50-val', $(payment)).parent().hide();
                        }

                        if (+result.sum100 > 0) {
                            $('.js-sum100', $(payment)).text(result.sum100);
                            $('.js-sum100-val', $(payment)).parent().show();
                        }
                        else {
                            $('.js-sum100-val', $(payment)).parent().hide();
                        }

                        if (+result.sum_last > 0) {
                            $('.js-sum-last', $(payment)).text(result.sum_last);
                            $('.js-sum-last-val', $(payment)).parent().show();
                        }
                        else {
                            $('.js-sum-last-val', $(payment)).parent().hide();
                        }
                    }
                }
            });
        }
    });

    $('a[href*="#"]').not('[href="#"]').not('[href="#0"]').click(function(event) {
        $('.mobile-menu__close').click();

        var headerHeight = $('header').height() - 2;
        if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
            var target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');

            if (target.length) {
                event.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top
                }, 1000);
            }
        }
    });

    function validateEmail(email) {
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    $(document).on('click', '.js-req', function(){
        $('.js-req-error').html('');
        var error = '';

        var email = $('.js-reg-email').val();
        var fio = $('.js-reg-fio').val();
        var phone = $('.js-reg-phone').val();
        var password = $('.js-reg-password').val();
        var password2 = $('.js-reg-password2').val();
        var agree = $('.js-agree').prop('checked');
        var subscribe = $('.js-subscribe').prop('checked');

        if (!validateEmail(email)) {
            error = (!email) ? 'Укажите эл. почту' : 'В почтовом адресе обнаружены ошибки';
        }
        else if (!fio) {
            error = 'Укажите ваше имя';
        }
        else if (!password) {
            error = 'Укажите пароль';
        }
        else if (!password2) {
            error = 'Подтвердите пароль';
        }
        else if (password !== password2) {
            error = 'Пароли не совпадают';
        }
        else if (!agree) {
            error = 'Необходимо ваше согласие на обработку персональных данных';
        }

        if (error) {
            $('.js-req-error').html(error);
        }
        else {
            $.ajax({
                url: "/lk/ajax",
                type: "POST",
                data: {
                    "act": "req",
                    "email": email,
                    "fio": fio,
                    "phone": phone,
                    "password": password,
                    "subscribe": +subscribe
                },
                success: function (html) {
                    var result = JSON.parse(html);
                    if (result.error) {
                        $('.js-req-error').html(result.error);
                    }
                    else {
                        $('.js-reg-form').html(result.result);
                    }
                }
            });
        }
    });

    $(document).on('click', '.js-login', function(){
        $('.js-login-error').html('');
        var error = '';

        var email = $('.js-login-email').val();
        var password = $('.js-login-password').val();

        if (!validateEmail(email)) {
            error = (!email) ? 'Укажите эл. почту' : 'В почтовом адресе обнаружены ошибки';
        }
        else if (!password) {
            error = 'Укажите пароль';
        }

        if (error) {
            $('.js-login-error').html(error);
        }
        else {
            $.ajax({
                url: "/lk/ajax",
                type: "POST",
                data: {
                    "act": "login",
                    "email": email,
                    "password": password
                },
                success: function (html) {
                    var result = JSON.parse(html);
                    if (result.error) {
                        $('.js-login-error').html(result.error);
                    }
                    else {
                        window.location.href = "/lk";
                    }
                }
            });
        }
    });

    $(document).on('click', '.js-reset', function(){
        $('.js-reset-error').html('');
        var error = '';

        var email = $('.js-reset-email').val();
        if (!validateEmail(email)) {
            error = (!email) ? 'Укажите эл. почту' : 'В почтовом адресе обнаружены ошибки';
        }

        if (error) {
            $('.js-reset-error').html(error);
        }
        else {
            $.ajax({
                url: "/lk/ajax",
                type: "POST",
                data: {
                    "act": "reset",
                    "email": email
                },
                success: function (html) {
                    var result = JSON.parse(html);
                    if (result.error) {
                        $('.js-reset-error').html(result.error);
                    }
                    else {
                        $('.js-reset-success').html(result.success);
                    }
                }
            });
        }
    });

    $(function() {
        $(window).scroll(function() {
            if ($(this).scrollTop() != 0) {
                $('.js-btn-up').fadeIn();
            } else {
                $('.js-btn-up').fadeOut();
            }
        });

        $('.js-btn-up').click(function() {
            $('body,html').animate({
                scrollTop: 0
            }, 800);
        });
    });

    $(document).on('click', '.js-send-message', function(){
        $('.js-message-error').html('');
        $('.js-files-label').html('');
        var error = '';

        var fio = $('.js-message-fio').val();
        var email = $('.js-message-email').val();
        var phone = $('.js-message-phone').val();
        var text = $('.js-message-text').val();
        var agree = $('.js-message-agree').prop('checked');

        if (!fio) {
            error = 'Укажите ваше имя';
        }
        else if (!validateEmail(email)) {
            error = (!email) ? 'Укажите эл. почту' : 'В почтовом адресе обнаружены ошибки';
        }
        else if (!text) {
            error = 'Укажите текст сообщения';
        }
        else if (!agree) {
            error = 'Необходимо ваше согласие на обработку персональных данных';
        }

        if (error) {
            $('.js-message-error').html(error);
        }
        else {
            $.ajax({
                url: "/index/ajax",
                type: "POST",
                data: {
                    "act": "send_message",
                    "fio": fio,
                    "email": email,
                    "phone": phone,
                    "text": text
                },
                success: function (html) {
                    var result = JSON.parse(html);
                    if (result.error) {
                        $('.js-message-error').html(result.error);
                    }
                    else {
                        $('.js-message-success').html(result.result);
                    }
                }
            });
        }
    });

    /*$(document).on("change", ".js-work-type", function(){
        var work_type = $(this).val();
        if (work_type != 0) {
            $('.js-check-type').each(function(){
                var types = $(this).attr('data-types');
                types = types.split(",");
                if (types.includes(work_type)){
                    $(this).show();
                }
                else {
                    $(this).hide();
                }

                if ($(this).attr('name') === 'work_count_page') {
                    var placeholder = (work_type == 22) ? 'Количество слайдов' : 'Количество страниц';
                    $(this).attr('placeholder', placeholder);
                }
            });

            $('.js-order-params').slideDown({
                start: function () {
                    $(this).css({
                        display: "inherit"
                    })
                }
            });

            $('html, body').animate({
                scrollTop: $('#order_data').offset().top
            }, 1000);
        }
        else {
            $('.js-order-params').slideUp();
        }

        $('.js-order-params input[type=text], .js-order-params textarea').each(function(){
           $(this).val('');
        });

        $('.js-order-params input[type=checkbox], .js-order-params input[type=radio]').each(function(){
            $(this).prop('checked',false);
        });

        $('.js-order-params select').each(function(){
            $(this).val(0);
        });
    });*/

    if (navigator.userAgent.indexOf('Android') === -1) {
        $("input[type=tel]").mask("+7 (999) 999-99-99");
    }

    $(document).on('click', '.js-create-order', function(){
        var btn = $(this);
        btn.addClass('btnRose_loader');
        btn.attr('disabled', true);

        var error = '';
        $('.js-order-error').html('');
        $('.js-order-success').html('');
        $('.js-login-error').html('');

        $('#order_data .js-require-field').each(function() {
            if (!$(this).val()) {
                error += 'Заполните обязательные поля<br>';
                return false;
            }
        });

        if (!error) {
            var agree = $('#order_data input[name=work_agree]').prop('checked');
            if (!agree) {
                error += "Необходимо ваше согласие на обработку персональных данных<br>";
            }
        }

        /*if (!error) {
            var email = $('#order_data input[name=work_email]').val();
            if (email && !validateEmail(email)) {
                error += "В почтовом адресе обнаружены ошибки";
            }
        }*/

        for (let e of formDataFiles.entries()){
            if (e[1].size >= 30000000) {
                error += 'Размер файла "'+ e[1].name + '" превышает допустимый размер 30Мб<br>';
            }
        }

        if (error) {
            $('.js-order-error').html(error);
            btn.attr('disabled', false).removeClass('btnRose_loader');
            return false;
        }

        var params_context = $('#order_data');

        var data = new FormData();
        data.append('act', 'create_order');

        data.append('work_type', $('select[name=work_type]', params_context).val());
        data.append('work_deadline', $('input[name=work_deadline]', params_context).val());
        data.append('work_email', $('input[name=work_email]', params_context).val());
        data.append('work_fio', $('input[name=work_fio]', params_context).val());

        for (let e of formDataFiles.entries()){
            data.append(e[0], e[1]);
        }

        data.append('work_subject', $('input[name=work_subject]', params_context).val());

        $.ajax({
            url: "/index/ajax",
            type: "POST",
            data: data,
            cache: false,
            processData: false,
            contentType: false,
            success: function(html) {
                btn.attr('disabled', false).removeClass('btnRose_loader');
                var result = JSON.parse(html);
                if (result.error) {
                    $('.js-order-error').html(result.error);
                }
                else {
                    $('.js-result .js-result-success').html("Ваша заявка №" + result.order_id + " успешно отправлена.<br>Мы свяжемся с Вами в <a href='https://www.whatsapp.com/' target='_blank' style='color:#2cb742; text-decoration: underline'>WhatsApp</a> по указанному номеру в ближайшее время.");
                    $('.js-result').addClass('active');
                }
            }
        });
    });

    $(document).on('click', '.js-save-lk-personal', function(){
        $('.lkPersonal .js-error').html('');
        $('.lkPersonal .js-success').html('');

        var data = {};
        data.act = "save_lk_personal";
        data.fio = $('.lkPersonal input[name=fio]').val();
        data.city = $('.lkPersonal input[name=city]').val();
        data.vuz = $('.lkPersonal input[name=vuz]').val();
        data.faculty = $('.lkPersonal input[name=faculty]').val();
        data.specialty = $('.lkPersonal input[name=specialty]').val();
        data.course = $('.lkPersonal input[name=course]').val();

        $.ajax({
            url: "/lk/ajax",
            type: "POST",
            data: data,
            success: function (html) {
                var result = JSON.parse(html);
                if (result.error) {
                    $('.lkPersonal .js-error').html(result.error);
                }
                else {
                    $('.lkPersonal .js-success').html(result.result);
                }
            }
        });
    });

    $(document).on('click', '.js-save-lk-contact', function(){
        $('.lkContacts .js-error').html('');
        $('.lkContacts .js-success').html('');

        var data = {};
        data.act = "save_lk_contacts";
        data.phone = $('.lkContacts input[name=phone]').val();
        data.subscribe = +$('.lkContacts input[name=subscribe]').prop('checked');

        $.ajax({
            url: "/lk/ajax",
            type: "POST",
            data: data,
            success: function (html) {
                var result = JSON.parse(html);
                if (result.error) {
                    $('.lkContacts .js-error').html(result.error);
                }
                else {
                    $('.lkContacts .js-success').html(result.result);
                }
            }
        });
    });

    $(document).on('click', '.js-lk-change-password', function(){
        var error_item = $('.lkPassword .js-error');
        var success_item = $('.lkPassword .js-success');
        error_item.html('');
        success_item.html('');

        var error = '';

        var old_password = $('.lkPassword input[name=old_password]').val();
        var new_password = $('.lkPassword input[name=new_password]').val();
        var new_password2 = $('.lkPassword input[name=new_password2]').val();

        if (!old_password) {
            error = 'Укажите старый пароль';
        }
        else if (!new_password){
            error = 'Укажите новый пароль';
        }
        else if (!new_password2){
            error = 'Повторите новый пароль';
        }
        else if (new_password !== new_password2) {
            error = 'Новые пароли не совпадают';
        }

        if (error) {
            error_item.html(error);
        }
        else {
            $.ajax({
                url: "/lk/ajax",
                type: "POST",
                data: {
                    "act": "change_password",
                    "old_password": old_password,
                    "new_password": new_password
                },
                success: function (html) {
                    var result = JSON.parse(html);
                    if (result.error) {
                        error_item.html(result.error);
                    }
                    else {
                        success_item.html(result.result);
                    }
                }
            });
        }
    });

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
            url: "/lk/ajax",
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

    $(document).on('change', 'select', function () {
        if ($(this).val() == 0) {
            $(this).css('color', '#808080');
        }
        else {
            $(this).css('color', '#444');
        }
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
    });

    $(document).on('click', function (e) {
        if ($(".js-subject-items").is(":visible")){
            if (!$(e.target).is('.js-subject') && !$(e.target).is('.js-subject-item')){
                $('.js-subject-items').html('').hide();
            }
        }


    });

    $(document).on('click', '.js-update-order-status', function () {
        var btn = $(this);
        var status = +btn.attr('data-status');
        var confirm_msg = (status === 7) ? "отменить" : "восстановить заказ";
        if (confirm("Вы уверены, что хотите " + confirm_msg +  " этот заказ?")) {
            var order_id = btn.attr('data-id');
            $.ajax({
                url: "/index/ajax",
                type: "POST",
                data: {
                    "act": "update_order_status",
                    "order_id": order_id,
                    "status": status
                },
                success: function (html) {
                    var result = JSON.parse(html);
                    if (result.error) {
                        alert(result.error);
                    }
                    else {
                        location.href = "/lk/moi_zakazy";
                    }
                }
            });
        }
    });

    $(document).on('submit', '.js-payment-form', function(e){
        $('.js-payment-error').html('');
        var error = '';
        if (!$('input[name=payment_sum]').is(":checked")) {
            error += "Выберите сумму оплаты";
        }
        else if (!$('input[name=paymentType]').is(":checked")) {
            error += "Выберите способ оплаты";
        }

        if (error) {
            $('.js-payment-error').html(error);
            e.preventDefault();
        }
        else {
            var amount_due = +$('input[name=payment_sum]:checked').val();
            var payment_type = $('input[name=paymentType]:checked').val();
            var a, sum;
            if (payment_type == 'PC') {
                a = 0.005;
                sum = amount_due / ( 1 - ( a / ( 1 + a) ) );
            }
            else {
                a = 0.02;
                sum = amount_due / ( 1 - a );
            }
            sum = sum.toFixed(2);
            $('input[name=sum]').val(sum);
        }
    });

    $(document).on('click', 'input[name=paymentType], input[name=payment_sum]', function(){
        var amount_due = +$('input[name=payment_sum]:checked').val();
        var payment_type = $('input[name=paymentType]:checked').val();

        if (amount_due && payment_type) {
            var a, sum, k;
            if (payment_type == 'PC') {
                a = 0.005;
                k = ( 1 - ( a / ( 1 + a) ) );
                sum = amount_due / k;
            }
            else {
                a = 0.02;
                k = ( 1 - a );
                sum = amount_due / k;
            }
            sum = sum.toFixed(2);

            $('.js-payment-amount').html('Итого к списанию: ' + sum + ' руб.');
            $('input[name=sum]').val(sum);
        }
    });

    $(document).on('click', '.js-close-policy', function(){
        $.ajax({
            url: "/index/ajax",
            type: "POST",
            data: {
                "act": "accept_policy"
            },
            success: function (html) {
                $('.js-policy-info').remove();
            }
        });
    });

    $(document).on('click', '.js-footer-subscribe', function(){
        var email = $('.subscribe__email').val();
        if (email && validateEmail(email)) {
            $.ajax({
                url: "/index/ajax",
                type: "POST",
                data: {
                    "act": "subscribe",
                    "email": email
                },
                success: function (html) {
                    $('.subscribe').html('Подписка успешно оформлена');
                }
            });
        }
    });

    $('a[href^="#"]').click(function(e){
        e.preventDefault();
        var anchor = $(this).attr('href');
        var h = $('.header_fixed').height();
        $('html, body').stop().animate({
            scrollTop:  $(anchor).offset().top - h
        }, 600);
    });
});
