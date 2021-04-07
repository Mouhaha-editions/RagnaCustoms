function readTextFile(file, callback) {
    var rawFile = new XMLHttpRequest();
    rawFile.overrideMimeType("application/json");
    rawFile.open("GET", file, true);
    rawFile.onreadystatechange = function () {
        if (rawFile.readyState === 4 && rawFile.status == "200") {
            callback(rawFile.responseText);
        }
    }
    rawFile.send(null);
}

let ratio = 500;
let audio;

function draw() {
    let ragnaSelector = $("#ragna-beat");
    ragnaSelector.append("<div class=\"drum\" id='drum-1'></div>");
    ragnaSelector.append("<div class=\"drum\" id='drum-2'></div>");
    ragnaSelector.append("<div class=\"drum\" id='drum-3'></div>");
    ragnaSelector.append("<div class=\"drum\" id='drum-4'></div>");
    // ragnaSelector.append("<div class=\"rune-pack\"></div>");
    readTextFile($("#info-dat").data('file'), function (text) {
        let infoDat = JSON.parse(text);
        let ratio2 = 60 / infoDat._beatsPerMinute;
        let song = infoDat._songFilename;
        let fileSong = $("#info-dat").data('file').replace('Info.dat', song).replace('info.dat', song);
        ragnaSelector.before("<input id=\"vol-control\" value='25' type=\"range\" min=\"0\" max=\"100\" step=\"1\"></input>");
        // ragnaSelector.after("<input id=\"time-control\" style='width:300px;' value='0' type=\"range\" min=\"0\" max=\"" + (infoDat._songApproximativeDuration * (ratio)) + "\" step=\"1\"></input>");
        ragnaSelector.before("<button data-level='pause' class='btn-warning btn btn-sm test-map mr-2 mb-2'><i class='fas fa-pause'></i></button>");
        ragnaSelector.before("<button data-level='stop' class='btn-danger btn btn-sm test-map mr-2 mb-2'><i class='fas fa-stop'></i></button>");
        // ragnaSelector.before("<input type='number'  min='0' max='100' value='50'/>");
        audio = new Audio(fileSong)
        audio.level = $("#vol-control").val() / 100;
        audio.load();

        for (let i = 0; i < infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps.length; i++) {
            let niveau = infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps[i];
            let level = niveau._beatmapFilename;
            let fileLevel = $("#info-dat").data('file').replace('Info.dat', level).replace('info.dat', level);
            ragnaSelector.before("<button data-level='" + niveau._difficulty + "' class='btn-info btn btn-sm test-map mr-2 mb-2'>level " + niveau._difficultyRank + "</button>");
            ragnaSelector.append("<div class=\"rune-pack\"  data-duration='" + infoDat._songApproximativeDuration + "' id='" + niveau._difficulty + "'></div>");
            $(".rune-pack#" + niveau._difficulty + "").css({
                height: (infoDat._songApproximativeDuration * (ratio)) + "px",
            });
            $('.rune-pack#' + niveau._difficulty).hide();

            readTextFile(fileLevel, function (text) {
                let levelDetail = JSON.parse(text);
                for (let i = 0; i < levelDetail._notes.length; i++) {
                    let note = levelDetail._notes[i];
                    ragnaSelector.find(".rune-pack#" + niveau._difficulty).append("<div class=\"rune data-level-" + niveau._difficultyRank + "\" style='bottom:" + ((ratio * ratio2 * note._time)) + "px' id='drum-" + (note._lineIndex + 1) + "'>X</div>");
                }
            });
        }
    });
}

let isPlaying = null;

console.log('v1.0.2');
$(function () {
    draw();

    $(document).on('input', '#vol-control', function () {
        audio.volume = parseInt($(this).val()) / 100;
    });
    // $(document).on('input', '#time-control', function () {
    //     let val = parseInt($(this).val());
    //     audio.currentTime = val / ratio;
    //
    //     let ratiotime = 1- audio.currentTime / audio.duration;
    //     let pack = $('.rune-pack.active');
    //     let top = pack.innerHeight();
    //     pack.finish();
    //     pack.css({"top" : (((top*ratiotime)-600)*-1)+"px"});
    //     //console.log(pack.data('duration') * (1 - ratiotime));
    //     // // pack.animate({'top': "600px"}, ((pack.data('duration')*(1-ratiotime)) * 1000), "linear");
    //
    // });
    $(document).on('mousedown', ".test-map", function () {
        let niveau = $(this).data("level");
        if (niveau === isPlaying) {
            return;
        } else if (isPlaying !== null) {
            console.log(niveau);
            if (niveau === "pause") {
                audio.pause();
                $('.rune-pack#' + isPlaying).stop(true);
                $(this).data('level', "play");
                $(this).html("<i class='fas fa-play'></i>");
            } else if (niveau === "play") {
                $(this).data('level', "pause");
                $(this).html("<i class='fas fa-pause'></i>");
                audio.play();
                $('.rune-pack#' + niveau._difficulty).addClass('active');
                let pack = $('.rune-pack#' + isPlaying);
                pack.animate({'top': "600px"}, pack.data('duration') * 1000, "linear");
            } else {
                $('.rune-pack.active#' + niveau).removeClass('active');
                audio.pause();
                $('.rune-pack#' + isPlaying).stop(true).css({top: 'inherit'}).hide();
            }
        }

        if (niveau !== "stop" && niveau !== "pause" && niveau !== "play") {
            isPlaying = niveau;
            audio.volume = $("#vol-control").val() / 100;
            audio.load();
            audio.ontimeupdate = function () {
                $("#time-control").val(audio.currentTime * ratio);
            };

            $('.rune-pack.active#' + niveau).removeClass('active');
            audio.addEventListener('canplaythrough', function () {
                audio.play();
                if (isPlaying === niveau) {
                    let pack = $('.rune-pack#' + niveau);
                    pack.show();
                    pack.addClass('active');
                    let ratiotime = 1-audio.currentTime/audio.duration;
                    $('.rune-pack.active#' + niveau).animate({'top': "600px"}, (pack.data('duration')*ratiotime) * 1000, "linear");
                }
            })

        }
    });
})