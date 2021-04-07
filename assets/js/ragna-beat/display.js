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
        ragnaSelector.before("<button data-level='stop' class='btn-danger btn btn-sm test-map mr-2 mb-2'><i class='fas fa-stop'></i></button>");
        // ragnaSelector.before("<input type='number'  min='0' max='100' value='50'/>");
        audio = new Audio(fileSong)
        audio.level = $("#vol-control").val()/100;
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
                    ragnaSelector.find(".rune-pack#" + niveau._difficulty).append("<div class=\"rune data-level-"+niveau._difficultyRank+"\" style='bottom:" + ((ratio * ratio2 * note._time)) + "px' id='drum-" + (note._lineIndex + 1) + "'>X</div>");
                }
            });
        }
    });
}

let isPlaying = null;

console.log('v1.0.2');
$(function () {
    draw();

    $(document).on('change', '#vol-control', function () {
        audio.volume = parseInt($(this).val()) / 100;
    });
    $(document).on('mousedown', ".test-map", function () {
        let niveau = $(this).data("level");
        if (niveau === isPlaying) {
            return;
        } else if (isPlaying !== null) {
            audio.pause();
            $('.rune-pack#' + isPlaying).stop(true).css({top: 'inherit'}).hide();
        }
        console.log(niveau);

        if (niveau !== "stop") {
            isPlaying = niveau;
            audio.volume =  $("#vol-control").val()/100;
            audio.load();
            audio.addEventListener('canplaythrough', function () {
                audio.play();
                console.log(niveau,isPlaying);
                if(isPlaying === niveau) {
                    let pack = $('.rune-pack#' + niveau);
                    pack.show();
                    pack.animate({'top': "600px"}, pack.data('duration') * 1000, "linear");
                }
            })

        }
    });
})