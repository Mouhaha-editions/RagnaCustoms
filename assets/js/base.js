const {RagnaBeat} = require("./ragna-beat/ragnabeat");
const Swal = require('sweetalert2/dist/sweetalert2.js');
import Swup from 'swup';
import "./PureSnow";
import SwupMatomoPlugin from '@swup/matomo-plugin';

const swup = new Swup({
    plugins: [new SwupMatomoPlugin()]
});
let swl = null;

//region snowflake
let snowflakes_count = 300;

// let base_css = ``; // Put your custom base css here

if (typeof total !== 'undefined'){
    snowflakes_count = total;
}

var displayed = true;
// This function allows you to turn on and off the snow
function toggle_snow() {
    displayed = !displayed;
    if (displayed == true) {
        document.getElementById('snow').style.display = "block";
    }
    else {
        document.getElementById('snow').style.display = "none";
    }
}

// Creating snowflakes
function spawn_snow(snow_density = 200) {
    snow_density -= 1;

    for (let x = 0; x < snow_density; x++) {
        let board = document.createElement('div');
        board.className = "snowflake";

        document.getElementById('snow').appendChild(board);
    }
}

// Append style for each snowflake to the head
function add_css(rule) {
    let css = document.createElement('style');
    css.type = 'text/css';
    css.appendChild(document.createTextNode(rule)); // Support for the rest
    document.getElementsByTagName("head")[0].appendChild(css);
}



// Math
function random_int(value = 100){
    return Math.floor(Math.random() * value) + 1;
}

function random_range(min, max) {
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(Math.random() * (max - min + 1)) + min;
}


// Create style for snowflake
function spawnSnowCSS(snow_density = 200){
    let snowflake_name = "snowflake";
    let rule = ``;
    if (typeof base_css !== 'undefined'){
        rule = base_css;
    }

    for(let i = 1; i < snow_density; i++){
        let random_x = Math.random() * 100; // vw
        let random_offset = random_range(-100000, 100000) * 0.0001; // vw;
        let random_x_end = random_x + random_offset;
        let random_x_end_yoyo = random_x + (random_offset / 2);
        let random_yoyo_time = random_range(30000, 80000) / 100000;
        let random_yoyo_y = random_yoyo_time * 100; // vh
        let random_scale = Math.random();
        let fall_duration = random_range(10, 30) * 1; // s
        let fall_delay = random_int(30) * -1; // s
        let opacity_ = Math.random();

        rule += `
        .${snowflake_name}:nth-child(${i}) {
            opacity: ${opacity_};
            transform: translate(${random_x}vw, -10px) scale(${random_scale});
            animation: fall-${i} ${fall_duration}s ${fall_delay}s linear infinite;
        }

        @keyframes fall-${i} {
            ${random_yoyo_time*100}% {
                transform: translate(${random_x_end}vw, ${random_yoyo_y}vh) scale(${random_scale});
            }

            to {
                transform: translate(${random_x_end_yoyo}vw, 100vh) scale(${random_scale});
            }
            
        }
        `;
    }

    add_css(rule);
}

// Load the rules and execute after the DOM loads
window.onload = function() {
    spawnSnowCSS(snowflakes_count);
    spawn_snow(snowflakes_count);
    console.log("start");
};
//endregion
$(function () {
    $(document).on('click','.snowflake-toggle',function(){
        toggle_snow();
    })
    $("[data-toggle=tooltip]").tooltip();
    let maxBg = 21;
    let currentBg = Math.floor(Math.random() * maxBg-1) +1;

    var images = [];

    function preload() {
        for (var i = 1; i < maxBg; i++) {
            images[i] = new Image();
            images[i].src = '/bg/' + currentBg + '.webp';
        }
    }
    let switchBg = function () {
        $("header").css({"background-image": 'url("/bg/' + currentBg + '.webp")'});
        currentBg += 1;
        if (currentBg > maxBg) {
            currentBg = 1;
        }
    };

    preload();
    switchBg();

    // setInterval(switchBg, 10000);
    $(".alert").each(function () {
        Swal.fire({
            title: $(this).data('title'),
            html: $(this).html(),
            icon: $(this).data('type') === "danger" ? "error" : $(this).data('type'),
            confirmButtonText: 'close'
        });
    });
});

$(document).on('click', '[data-confirm]', function () {
    return confirm($(this).data('confirm'));
});

$(document).on('change', 'input[type="file"]', function (e) {
    let fileName = e.target.files[0].name;
    $('.custom-file-label').html(fileName);
});


$("[data-toggle=\"tooltip\"]").tooltip({delay: 100});

$(document).on('click', '.open-download-buttons', function () {
    let t = $(this).closest('.on-hover').find('.big-buttons');
    t.toggleClass('d-none');
    return false;
});

$(document).on('preview-ready', function (evt, p) {
    let ragnabeat = new RagnaBeat();
    if (p.type === "modal") {
        ragnabeat.enableModal();
    }
    ragnabeat.startInit(p.uid, p.file);

    $("#previewSong").on("hide.bs.modal", function () {
        ragnabeat.stopSong();
    });
});


$(document).on('click', ".ajax-load", function () {
    let t = $(this);
    let body = $(t.data('target') + " .modal-body");
    body.html("loading ...");

    $.ajax({
        url: t.data('url'),
        data: {
            id: t.data('song-id')
        },
        success: function (data) {
            body.html(data.response);
            body.find("[data-toggle=tooltip]").tooltip();
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


$(document).on('change', 'input[type="file"]', function (e) {
    let fileName = e.target.files[0].name;
    $('.custom-file-label').html(fileName);
});


function loadForm(content) {
    $("#form-edit").html(content);
    $("#form-edit form").on('submit', function () {
        $("#form-edit").html("<div class=\"popup-box-actions white full void\">Sending your form, please wait ... </div> " +
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
                console.log(data);
                if (data.goto !== undefined && data.goto !== false) {
                    window.location.href = data.goto;
                }
                if (data.reload) {
                    window.location.reload();
                }
                if (data.error === true || data.success === false) {
                    $("#form-edit").html(data.response);
                    loadForm(data.response);
                    $('.select2entity').select2entity();

                } else {
                    tt.closest(tt.data('replace-selector')).html(data.response);
                    $(tt).closest(".modal").modal('hide');
                }
            }
        });


        $("#form-review").html("<div class=\"popup-box-actions white full void\">Sending your form</div>");
        return false;
    });
}

$(document).on('click', ".ajax-modal-form", function () {
    let t = $(this);
    $(t.data('modal')).modal('show');
    $(t.data('modal')).find('.modal-title').html(t.data('title'));
    $.ajax({
        url: t.data('url'),
        success: function (data) {
            loadForm(data.response);
            $('.select2entity').select2entity();
        }
    });
    return false;

});