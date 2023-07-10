function copyToClipboard(str) {
    const el = document.createElement('textarea');
    el.value = str;
    el.setAttribute('readonly', '');
    el.style.position = 'absolute';
    el.style.left = '-9999px';
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
}

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
    }, 500);

    return false;
});