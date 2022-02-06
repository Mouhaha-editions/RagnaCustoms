$(document).on("click", ".ajax-link", function () {
    let t = $(this);
    let action = t.data('success-action');
    $.ajax({
        url: t.data('url'),
        dataType: 'json',
        success: function (data) {
            if (data.error) {
                alert(data.errorMessage);
                return;
            }
            switch (action) {
                case "replace":
                    console.log('replace'+t.data('replace-selector'))
                    console.log(data.result)
                    $(t.data('replace-selector')).replaceWith(data.result);
                    break;
                case "remove":
                    $(t.data('remove-selector')).remove();
                    break;
            }
        },
        error: function (data) {
            alert('Erreur lors de la requete');
        }
    });
    return false;
});