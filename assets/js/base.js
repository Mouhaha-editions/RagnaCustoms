const {RagnaBeat} = require("./ragna-beat/ragnabeat");


$(document).on('click', '[data-confirm]', function () {
    return confirm($(this).data('confirm'));
});

$(document).on('change','input[type="file"]', function (e) {
    let fileName = e.target.files[0].name;
    $('.custom-file-label').html(fileName);
});

$('[data-load]').each( function(){
    let t = $(this);
    $.ajax({
        url : t.data('load'),
        dataType:'html',
        success : function(data){
            console.log(data)
            t.html(data)
        }
    })
});

$(document).on('click', ".copy-clipboard", function () {
    let t = $(this);
    let title = t.attr('original-title');
    copyToClipboard($(this).data('to-copy'));
    t.tooltip('hide')
        .attr('data-original-title', "copied !")
        .tooltip('show')
    setTimeout(function () {
        // t.tooltip('toggleEnabled');
        t.tooltip('hide')
            .attr('data-original-title', title)
            .tooltip('show')
    }, 1000);

    return false;
});

$("[data-toggle=\"tooltip\"]").tooltip({delay:100});

$(document).on('click', '.open-download-buttons', function () {
    let t = $(this).closest('.on-hover').find('.big-buttons')
    t.toggleClass('d-none');
    return false;
});

$(document).on('preview-ready', function (evt,p) {
    let ragnabeat = new RagnaBeat();
    if(p.type === "modal"){
        ragnabeat.enableModal();
    }
    ragnabeat.startInit(p.uid,p.file);

    $("#previewSong").on("hide.bs.modal", function () {
        ragnabeat.stopSong();
        ragnabeat = null;
    });
});