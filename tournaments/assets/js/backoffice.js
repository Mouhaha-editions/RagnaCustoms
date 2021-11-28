// any CSS you import will output into a single css file (app.css in this case)
import '../css/backoffice.scss';
// import moment from 'moment/moment'
import * as $ from 'jquery';
import 'moment/dist/moment'
import 'admin-lte/plugins/jquery-ui/jquery-ui'
// import 'admin-lte/plugins/moment/moment-with-locales.min'
import 'admin-lte/plugins/bootstrap/js/bootstrap.bundle.min'
import 'admin-lte/plugins/chart.js/Chart.bundle.min'
import 'admin-lte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min'
// import 'admin-lte/build/js/AdminLTE'
import 'admin-lte/plugins/summernote/summernote-bs4'
import 'trumbowyg/dist/trumbowyg.min'
import 'trumbowyg/dist/langs/fr.min'
import 'trumbowyg/plugins/table/trumbowyg.table'
import 'trumbowyg/plugins/allowtagsfrompaste/trumbowyg.allowtagsfrompaste'
import 'admin-lte/plugins/chart.js/Chart'
import 'admin-lte/dist/js/pages/dashboard'
import 'admin-lte/dist/js/adminlte.min'
import 'admin-lte/dist/js/demo'
import 'select2/dist/js/select2.full.min'
import  '../../public/bundles/pagination/js/see-more.js';

export {
    $
}


$(function () {

    $.trumbowyg.svgPath = "/build/icons_trumbowyg.svg";
    /** petit hack pour bootstrap file form widget */
    $(document).on("change", '[type=file]', function () {
        let value = $(this).val().replace('C:\\fakepath\\', '').trim();
        $(this).closest('div').find(".custom-file-label").text("" !== value ? value : $(this).attr('placeholder'));
    });
    $(".select2").select2();
    $(".ajax-link").on('click', function () {
        var t = $(this);

        $.ajax({
            url: t.attr('href'),
            dataType: 'json',
            type: 'get',
            success: function (data) {
                console.log(data);
                if (data.success) {
                    if (t.data('replace') === "self") {
                        t.html(data.replace);
                    }
                    if (t.data('remove') === "closestTr") {
                        t.closest("tr").remove();
                    }
                } else {
                    Swal({
                        type: 'error',
                        message: "La requete a échoué, contactez le developpeur."
                    });
                }
            }
        });
        return false;
    })
    $('#challenge_description').trumbowyg({
        lang: 'fr',
        btns: [
            ['viewHTML'],
            ['undo', 'redo'], // Only supported in Blink browsers
            ['formatting'],
            ['strong', 'em', 'del'],
            ['superscript', 'subscript'],
            ['link'],
            ['insertImage'],
            ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
            ['unorderedList', 'orderedList'],
            ['horizontalRule'],
            ['removeformat'],
            ['fullscreen']
        ],
        plugins: {}
    });
    $("[data-toggle='tooltip']").tooltip();
    if (labels !== undefined) {
        var ctx = $('#line-chart');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Inscriptions',
                    data: participations,
                    fill: false,
                    background: 'rgba(0, 0, 0, 0)',
                    borderColor: 'rgb(12,112,231)',
                    borderWidth: 1
                }
                ,{
                    label: 'No show',
                    data: noShow,
                    fill: false,
                    background: 'rgba(0, 0, 0, 0)',
                    borderColor: 'rgb(231,12,30)',
                    borderWidth: 1
                }]
                ,
            }
        });
    }
});