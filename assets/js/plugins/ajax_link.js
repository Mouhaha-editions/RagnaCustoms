$(document).on("click", ".ajax-link", function () {
    console.log("coucou");
    let t = $(this);
    $(this).tooltip("hide")
    let action = t.data('success-action');
    $.ajax({
        url: t.data('url'),
        dataType: 'json',
        success: function (data) {
            console.log(data);
            if (data.error) {
                alert(data.errorMessage);
                return;
            }
            switch (action) {
                case "replace":
                    $(t.data('replace-selector')).replaceWith(data.result);
                    $(t.data('replace-selector')+" [data-toggle=\"tooltip\"]").tooltip();
                    break;
                case "replace-html":
                    console.log(data);
                    console.log(t.data('replace-selector'));
                    $(t.data('replace-selector')).html(data.result);
                    $(t.data('replace-selector')+" [data-toggle=\"tooltip\"]").tooltip();
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