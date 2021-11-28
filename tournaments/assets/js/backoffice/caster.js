import * as $ from "jquery";
import "admin-lte/plugins/datatables/jquery.dataTables.min"
import "admin-lte/plugins/datatables-bs4/js/dataTables.bootstrap4.min"

$(function () {
    let columnsCount = $("tr:first th").length;

    for (let i = 0; columnsCount > i; i++) {
        if(i === 0){
            continue;
        }
        let max = 0;
        let min = 99999999999999;
        let maxTd = [];
        let minTd = [];
        $("tr").each(function () {
            if($(this).find('td:eq('+i+')').length === 0){
                return;
            }
            let td = $(this).find('td:eq('+i+')');
            let value = parseInt(td.text().replace(' ',''));
            if(value > max && value !== 0 ){
                maxTd = [td];
                max = value;
            }

            if(value === max && value !== 0 ){
                maxTd.push(td);
            }
            if(value < min && value !== 0 ){
                minTd = [td];
                min = value;
            }
            if(value === min){
                minTd.push(td);
            }
        });
        for(let j =0; maxTd.length> j; j++){
            maxTd[j].addClass('bg-green disabled');
        }
        for(let j =0; minTd.length> j; j++){
            minTd[j].addClass('bg-orange disabled');
        }
    }
    $('.dataTable').DataTable( {
        "pageLength": 500,
        "searching": false,
        "ordering": true,
        "paging": false,
        "order": [[1, "desc" ]]
    } );
});