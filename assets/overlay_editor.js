import './styles/overlay_editor.scss';
import 'jquery-ui/ui/core'
import 'jquery-ui/ui/widget'
import 'jquery-ui/ui/widgets/draggable'
import 'jquery-ui/ui/widgets/droppable'

function resizeCanvas(width, height) {
    $("#canvas").animate({width: width, height: height});
}

function changeCanvasColor(color) {
    $("#canvas").css({"background-color": color});
}

let uniqElement = 0;

function addCustomText() {
    var text = prompt("What is the text ?");
    $("#canvas").append("<div class='draggable custom-text elt" + uniqElement + "'>" + text + "</div>");
    $(".elt" + uniqElement + "").draggable({containment: "parent",stop: function() {save();}});
    $(".customCss").append("#canvas .elt" + uniqElement + "{" +
        "color: #ffffff;" +
        "text-shadow: 0 0 10px #000000;" +
        "font-size:20px;" +
        "}\n");
    $(".customCss").trigger('change');
    $("ul").append("<li class='elt" + uniqElement + "'>text: " + text + " <a href='#' class='delElt' data-elt='" + uniqElement + "'>remove</a></li>")
    uniqElement++;
}


function addMapper() {
    $("#canvas").append("<div class='draggable mapper elt" + uniqElement + "'>Mapper NAME</div>");
    $(".elt" + uniqElement + "").draggable({containment: "parent",stop: function() {save();}});
    $(".customCss").append("#canvas .elt" + uniqElement + "{" +
        "color: #ffffff;" +
        "text-shadow: 0 0 10px #000000;" +
        "font-size:20px;" +
        "}\n");
    $(".customCss").trigger('change');
    $("ul").append("<li class='elt" + uniqElement + "'>Mapper <a href='#' class='delElt' data-elt='" + uniqElement + "'>remove</a></li>")
    uniqElement++;
}

function addArtist() {
    $("#canvas").append("<div class='draggable artist elt" + uniqElement + "'>Artist NAME</div>");
    $(".elt" + uniqElement + "").draggable({containment: "parent",stop: function() {save();}});
    $(".customCss").append("#canvas .elt" + uniqElement + "{" +
        "color: #ffffff;" +
        "text-shadow: 0 0 10px #000000;" +
        "font-size:20px;" +
        "}\n");
    $(".customCss").trigger('change');
    $("ul").append("<li class='elt" + uniqElement + "'>Artist <a href='#' class='delElt' data-elt='" + uniqElement + "'>remove</a></li>")
    uniqElement++;
}

function addTitleAndLevel() {
    $("#canvas").append("<div class='draggable song-title-level elt" + uniqElement + "'>Song title & difficulty</div>");
    $(".elt" + uniqElement + "").draggable({containment: "parent",stop: function() {save();}});
    $(".customCss").append("#canvas .elt" + uniqElement + "{" +
        "color: #ffffff;" +
        "text-shadow: 0 0 10px #000000;" +
        "font-size:20px;" +
        "}\n");
    $(".customCss").trigger('change');
    $("ul").append("<li class='elt" + uniqElement + "'>Song title & difficulty <a href='#' class='delElt' data-elt='" + uniqElement + "'>remove</a></li>")
    uniqElement++;
}

function addTitle() {
    $("#canvas").append("<div class='draggable song-title elt" + uniqElement + "'>Song title</div>");
    $(".elt" + uniqElement + "").draggable({containment: "parent",stop: function() {save();}});
    $(".customCss").append("#canvas .elt" + uniqElement + "{" +
        "color: #ffffff;" +
        "text-shadow: 0 0 10px #000000;" +
        "font-size:20px;" +
        "}\n");
    $(".customCss").trigger('change');
    $("ul").append("<li class='elt" + uniqElement + "'>Song title <a href='#' class='delElt' data-elt='" + uniqElement + "'>remove</a></li>")
    uniqElement++;
}

function addLevel() {
    $("#canvas").append("<div class='draggable song-level elt" + uniqElement + "'>Song difficulty</div>");
    $(".elt" + uniqElement + "").draggable({containment: "parent",stop: function() {save();}});
    $(".customCss").append("#canvas .elt" + uniqElement + "{" +
        "color: #ffffff;" +
        "text-shadow: 0 0 10px #000000;" +
        "font-size:20px;" +
        "}\n");
    $(".customCss").trigger('change');
    $("ul").append("<li class='elt" + uniqElement + "'>Song difficulty <a href='#' class='delElt' data-elt='" + uniqElement + "'>remove</a></li>")
    uniqElement++;
}

function addCover() {
    $("#canvas").append("<div class='draggable cover elt" + uniqElement + "'><img src='/covers/117.jpg'/></div>");
    $(".elt" + uniqElement + "").draggable({containment: "parent",stop: function() {save();}});
    $(".customCss").append("#canvas .elt" + uniqElement + " img{" +
        "width:150px;" +
        "}\n");
    $(".customCss").trigger('change');
    $("ul").append("<li class='elt" + uniqElement + "'>Cover <a href='#' class='delElt' data-elt='" + uniqElement + "'>remove</a></li>")
    uniqElement++;
}

function addDuration() {
    $("#canvas").append("<div class='draggable song-duration elt" + uniqElement + "'>0:00 / 0:00</div>");
    $(".elt" + uniqElement + "").draggable({containment: "parent",stop: function() {save();}});
    $(".customCss").append("#canvas .elt" + uniqElement + "{" +
        "color: #ffffff;" +
        "text-shadow: 0 0 10px #000000;" +
        "font-size:20px;" +
        "}\n");
    $(".customCss").trigger('change');
    $("ul").append("<li class='elt" + uniqElement + "'>Song duration <a href='#' class='delElt' data-elt='" + uniqElement + "'>remove</a></li>")
    uniqElement++;
}

function removeLine(id) {
    var elem = $('.customCss'),
        val = elem.val().split(/(\r\n|\n|\r)/g).filter(function (n) {
            return n.trim()
        });
    val = val.filter(function (item) {
        return !item.match(".elt" + id);
    });

    elem.html(val.join('\n') + "\n");
}

$(function () {

    $("[name=canvas_bg_color]").val($("#canvas").css('background-color'));
    $("[name=canvas_width]").val(Math.round($("#canvas").outerWidth()));
    $("[name=canvas_height]").val(Math.round($("#canvas").outerHeight()));


    $("[name=canvas_width], [name=canvas_height]").on("change", function () {
        resizeCanvas($("[name=canvas_width]").val(), $("[name=canvas_height]").val());
        save();
    });

    $("[name=canvas_bg_color]").on("change", function () {
        changeCanvasColor($("[name=canvas_bg_color]").val());
        save();
    });
    $("[name=canvas_bg_color],[name=canvas_width], [name=canvas_height]").trigger("change")
    $(".addElt").on('click', function () {
        eval($('.eltToAdd option:selected').data('fnc') + "()");
        save();
    });
    $(document).on('click', '.delElt', function () {
        let id = $(this).data('elt');
        $(".elt" + id).remove();
        removeLine(id);
        save();
    });
    $(".customCss").on('change', function () {
        $("#customCss").html($(this).val());
        save();
    });
    $("#canvas").droppable({});
    $(".draggable").draggable({containment: "parent",stop: function() {save();}})

    $(".customCss").trigger('change');

});

function save() {
    var serializer = new XMLSerializer();
    $("#canvasSave").html(serializer.serializeToString($("#canvas")[0]));
    let form = $("#save");
    $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: form.serialize()

    });
}
