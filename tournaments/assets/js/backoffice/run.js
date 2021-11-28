import * as $ from "jquery";

function inputCreation() {
    $('input').each(function () {
        let t = $(this);
        let type = t.closest('td').data('input-type')
        let value = t.closest('td').data('default-value')
        if (type !== undefined) {
            switch (type) {
                case 200:
                    let input = "<select name='" + t.attr('name') + "' class='form-control form-control-sm' id='" + t.attr('id') + "'><option value=''>-- sélectionner -- </option>";
                    let values = value.split(';');
                    for (let i = 0; values.length > i; i++) {
                        let select = values[i].split(':');
                        let val = select[0];
                        let text = select[1] !== undefined ? select[1] : select[0];
                        input += "<option " + (val === t.val() ? "selected='selected'" : "") + " value='" + val + "'>" + text + "</option>";
                    }
                    input += "</select>";
                    t.replaceWith(input);
                    break;
                case 300:
                    let checkbox = "<input type='checkbox' " + (1 === parseFloat(t.val()) ? "checked='checked'" : "") + " id='" + t.attr('id') + "' name='" + t.attr('name') + "' value='1'/>";
                    t.replaceWith(checkbox);
                    break;
                case 400:
                    t.attr('type', 'number');
                    break;
                default:
                case 100:
                    break;

            }
        }
    });
    $("[type=checkbox]").val()

}


function loadRun(challenger, challenge) {
    $("#runScore").hide(300);
    $.ajax({
        url: '/admin/run/user/' + challenger,
        async: true,
        data: {
            challenge: challenge
        },
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                $("#runScore").html(data.html);
                inputCreation();
                $('[id^=run_runSettings]').each(function () {
                    updateLigne($(this).closest('tr.updatable'));
                });
            } else {
                $("#runScore").html(data.message);
            }
            $("#runScore").show(300);
        }
    });
}

function totalRun() {
    let sumMalusable = 0;
    let sum = 0;
    $(".sub-total").each(function () {
        $(this).text('0');
    });
    $(".total-line").each(function () {
            let value = $(this).text();
            let tr = $(this).closest('tr');
            if (tr.data("isusedforscore") === 1) {
                if (!isNaN(value) && value.length !== 0) {
                    if (tr.data("isaffectedbymalus") === 1) {
                        sumMalusable += parseFloat(value);
                    } else {
                        sum += parseFloat(value);
                    }
                }
            }
            let malus = parseFloat($("#malus-run").data('malus').toString().replace(',', '.'));
            $(".sub-total").each(function () {
                if ($(this).data('total') === tr.data('subtotal')) {
                    if (tr.data("isaffectedbymalus") === 1) {
                        $(this).text(Math.ceil(parseFloat($(this).text()) + Math.ceil(parseFloat(value) * malus)));
                    } else {
                        $(this).text(Math.ceil(parseFloat($(this).text()) + parseFloat(value)));
                    }
                }
            });
        }
    );
    let malus = parseFloat($("#malus-run").data('malus').toString().replace(',', '.'));
    let total_malus = sumMalusable * malus;
    let tempScore = $("#run_tempScore").val();
    if (tempScore !== null && tempScore !== "" && tempScore !== undefined) {
        $('.total-run-with-malus').html(Math.ceil(tempScore * malus + sum));
        $("#run_FinDeRun").attr('disabled', "disabled");
        $("#run_FinDeRun").attr('title', "Rentrez le détail pour pouvoir terminer la run");
    } else {
        $('.total-run-with-malus').html(Math.ceil(total_malus + sum));
        $("#run_FinDeRun").removeAttr('disabled');
        $("#run_FinDeRun").removeAttr('title');
    }

    $(".total-run").html(Math.ceil(sum + sumMalusable * malus));
}

function updateLigne(ligne) {
    let ratio = parseFloat(ligne.find('.ratio').data('ratio').replace(',', '.'));
    let value = 0;
    // console.log(ligne);
    // console.log(ligne.find("input"));
    let input = ligne.find("input");
    if (input !== undefined && input.length > 0) {
        if (input.is('[type=checkbox]')) {
            // console.log("checkbox")
            if (input.is(':checked')) {
                // console.log("checked")
                value = input.val();
            }
        } else {
            // console.log("input")
            value = input.val();
        }
    } else {
        // console.log("select")
        value = ligne.find("select").val();
    }
    // console.log("value: "+ value);
    value = parseFloat(value.toString().replace(',', '.'));
    // console.log("float value: "+ value);

    let total = ratio * value;
    total = total === undefined || total === null || total === "" || isNaN(total)  ?0:total;
    ligne.find('.total-line').html(Math.ceil(total));

    if (ligne.data("issteptovictory") === 1) {
        let min = parseFloat(ligne.data("steptovictorymin"));
        let max = parseFloat(ligne.data("steptovictorymax"));
        if (isNaN(min)) {
            min = -999999999;
        }
        if (isNaN(max)) {
            max = 999999999;
        }
        if (value <= max && value >= min) {
            ligne.removeClass('bg-orange');
            ligne.addClass('bg-green');
        } else {
            ligne.removeClass('bg-green');
            ligne.addClass('bg-orange');
        }
    }
    if (ligne.data("isusedforscore") === 1) {
        totalRun();
    }
}

$(function () {
    let cancelableXhr = null;
    $(".twitcher li a").on('click', function () {
        let url = $(this).data('url');
        let discordId = $(this).data('discordid');
        if (url !== undefined && url !== "") {
            $("#twitch_player").attr('src', url).show();
            $('.display-discord').hide();
        } else {
            $("#twitch_player").attr('src', url).hide();
            $('.display-discord .player').html(discordId)
            $('.display-discord').show();
        }
        loadRun($(this).data('challenger'), $(this).data('challenge'))
        return false;
    })
    inputCreation();

    $(document).on('keyup', '[id^=run_runSettings]', function () {
        let ligne = $(this).closest('tr');
        updateLigne(ligne);
    });

    $(document).on('keyup change', '[id^=run_runSettings_]', function () {
        $("form#runForm").submit();
        updateLigne($(this).closest('tr.updatable'));
    });

    $(document).on('keyup change', '#run_comment', function () {
        $("form#runForm").submit();
    });

    $(document).on('keyup change', '#run_tempScore', function () {
        $("form#runForm").submit();
        totalRun();
    });

    $(document).on('click', "form#runForm button", function () {
        if (cancelableXhr !== null) {
            cancelableXhr.abort();
        }
        let t = $(this)
        let form = $(this).closest('form');

        if(t.closest('td').data('babyproof')){
            if(!confirm('Vous allez confirmer une fin de run pour '+ t.closest('td').data('challenger')+", êtes vous sur(e) de vouloir continuer ? ")){
                return;
            }
        }
        cancelableXhr = $.ajax({
            url: form.attr('action'),
            type: 'post',
            data: form.serialize() + "&button=" + $(this).attr('id') + "&challenge=" + form.data('challenge'),
            success: function (data) {
                if (data.refresh) {

                    loadRun(form.data('challenger'), form.data('challenge'));
                }
            }
        });
        return false;
    });
    $(document).on('submit', "form#runForm", function () {
        if (cancelableXhr !== null) {
            cancelableXhr.abort();
        }
        let t = $(this)
        cancelableXhr = $.ajax({
            url: $(this).attr('action'),
            type: 'post',
            data: $(this).serialize() + "&challenge=" + t.data('challenge'),
            success: function (data) {
                if (data.refresh) {
                    loadRun(t.data('challenger'), t.data('challenge'));
                }
            }
        });
        return false;
    });


});