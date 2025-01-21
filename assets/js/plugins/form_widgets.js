$(document).on('change','input[type="file"]', function (e) {
    let fileName = e.target.files[0].name;
    $('.custom-file-label').html(fileName);
});


function loadForm(modalform, content) {
    modalform.html(content);
    modalform.children('form').on('submit', function () {
        modalform.html("<div class=\"popup-box-actions white full void\">Sending your form, please wait ... </div> " +
            "<div class='progress-container'><div class='progress'></div></div>");
        let tt = $(this);

        $.ajax({
            xhr: function () {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        let percentComplete = evt.loaded / evt.total;
                        $('.progress').css({
                            width: percentComplete * 100 + '%'
                        });
                        if (percentComplete === 1) {
                            $('.progress').addClass('hide');
                        }
                    }
                }, false);
                xhr.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        let percentComplete = evt.loaded / evt.total;
                        $('.progress').css({
                            width: percentComplete * 100 + '%'
                        });
                    }
                }, false);
                return xhr;
            },
            url: tt.attr('action'),
            data: new FormData(this),
            type: tt.attr('method'),
            processData: false,
            contentType: false,
            success: function (data) {
                if (data.goto !== false) {
                    window.location.href = data.goto;
                }
                if (data.reload) {
                    window.location.reload();
                }
                if (data.error === true || data.success === false) {
                    modalform.html(data.response);
                    loadForm(data.response);

                } else {
                    tt.closest(tt.data('replace-selector')).html(data.response);
                    $(tt).closest(".modal").modal('hide');
                }
            }
        });


        $("#form-review").html("<div class=\"popup-box-actions white full void\">Sending your form</div>");
        return false;
    });
    $(document).trigger('modalformloaded', modalform);
}


$(document).on('click', ".ajax-modal-form", function () {
    let t = $(this);
    $(t.data('modal')).modal('show');
    console.log('coucou');
    $.ajax({
        url: t.attr('href'),
        success: function (data) {
            loadForm($(t.data('modalform')), data.response);
        }
    });
    return false;

});

