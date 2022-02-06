$(document).on('click', ".ajax-load", function () {
    let t = $(this);
    let body = $(t.data('target') + " .modal-body");
    body.html("loading ...");
    let target = t.data('target');
    $.ajax({
        url: t.data('url'),
        data: {
            id: t.data('song-id')
        },
        success: function (data) {
            body.html(data.response);
            $(".rating-list").on('change', function () {
                let t = $(this);
                $('input[name="' + t.data('input-selector') + '"]').val(t.data('rating'));
            });
            body.find("form").on('submit', function () {
                let test = true;
                $(this).find('input').each(function () {
                    if ($(this).is(".verify-voted") && ($(this).val() === undefined || $(this).val() === "")) {
                        test = false;
                    }
                });
                if (!test) {
                    alert("you need to rate each property");
                    return false;
                }

                let tt = $(this);
                $.ajax({
                    url: tt.data('url'),
                    type: tt.attr('method'),
                    data: tt.serialize(),
                    success: function (data) {
                        if (t.data('refresh')) {
                            window.location.reload();
                        }
                        t.closest(t.data('replace-closest-selector')).html(data.response);
                        $(t.data('replace-selector')).html(data.response);
                        $(".modal:visible").modal('hide');
                    }
                });

                body.html("<div class=\"popup-box-actions white full void\">Sending your form</div>");


                return false;
            });
        }
    });
    return false;

});

$(document).on('click', ".ajax-modal-form", function () {
    let t = $(this);

    $(t.data('modal')).modal('show');
    $.ajax({
        url: t.attr('href'),
        success: function (data) {
            console.log("load form");
            loadForm(data.response);
            console.log("enable select2");

            $('.select2entity').select2entity();
        }
    });
    return false;

});
