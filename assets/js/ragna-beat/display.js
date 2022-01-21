let c,ctx,levelDetail,audio,infoDat,animationFrame,drumSounds;
let isPlaying = false;
let startTime = 0;
let moveSpeed = 0;
let jsonIteration = 0;
let songBPS = 0;
let runes = {};
let rings = [];
let ratio = 2.5;
let levelDetails = {};
let spawnDistance = 600;
let circleRadius = 30;
let margin = 12;
let image_drum = new Image;
let image_runes = [
    new Image,
    new Image,
    new Image,
    new Image,
    new Image
];
let audio_drums = [
    new Audio,
    new Audio,
    new Audio,
    new Audio
];


function init() {
    let diffsWrapper = $("#ragna-beat-diffs");
    let buttonsWrapper = $("#ragna-beat-buttons");
    let volumesWrapper = $("#ragna-beat-volumes");
    let soundsWrapper = $("#ragna-beat-sounds");

    $.ajax({
        url: $("#info-dat").data('file'),
        type: 'GET',
        dataType: 'JSON',
        success: function(result) {
            infoDat = result;
            songBPS = infoDat._beatsPerMinute / 60;
            moveSpeed = infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps[0]._noteJumpMovementSpeed / 3 * ratio;
            setDelay();
            let song = infoDat._songFilename;
            let fileSong = $("#info-dat").data('file').replace('Info.dat', song).replace('info.dat', song);

            $('#ragna-beat-duration .max').text(new Date(infoDat._songApproximativeDuration * 1000).toISOString().substr(14, 5));

            buttonsWrapper.append("<button id='ragna-beat-play' data-level='play' class='btn-warning btn btn-sm test-map mr-2 mb-2'><i class='fas fa-play'></i></button>");
            buttonsWrapper.append("<button id='ragna-beat-stop' data-level='stop' class='btn-danger btn btn-sm test-map mr-2 mb-2'><i class='fas fa-stop'></i></button>");

            volumesWrapper.append("<div>Music volume: <input id=\"vol-control\" value='20' type=\"range\" min=\"0\" max=\"100\" step=\"1\"></input></div>");
            volumesWrapper.append("<div>Drum volume: <input id=\"drum-vol-control\" value='20' type=\"range\" min=\"0\" max=\"100\" step=\"1\"></input></div>");

            audio = new Audio(fileSong);
            audio.level = $("#vol-control").val() / 100;
            audio.preload = "auto";
            audio.volume = 0.2;

            $(audio).on('ended', function() {
                isPlaying = false;
                stopSong();
            });

            audio.addEventListener("timeupdate", function() {
                let percent = audio.currentTime / infoDat._songApproximativeDuration * 100;
                $('#ragna-beat-duration .current').text(new Date(audio.currentTime * 1000).toISOString().substr(14, 5));
                $('#ragna-beat-duration input').val(percent);
            });

            drumSounds = [
                {
                    name: 'Ragna drum',
                    url: '/ragna-beat-assets/drumhit_mixed.wav'
                },
                {
                    name: 'Metronome',
                    url: '/ragna-beat-assets/metronome.wav'
                },
                {
                    name: 'Quack',
                    url: '/ragna-beat-assets/quack.wav'
                }
            ];

            for(let i=0;i<drumSounds.length;i++) {
                soundsWrapper.append("<button class='btn-info btn btn-sm test-map mr-2 mb-2'>"+drumSounds[i].name+"</button>");
            }
            soundsWrapper.find('button').first().addClass('btn-dark');

            for (let i in audio_drums) {
                audio_drums[i].src = drumSounds[0].url;
                audio_drums[i].volume = $("#drum-vol-control").val() / 100;
            }

            c = document.getElementById("ragna-canvas");
            ctx = c.getContext("2d");
            checkTop = c.height - circleRadius - margin - spawnDistance;

            image_drum.src = "/ragna-beat-assets/image_drum.png";
            image_runes[0].src = "/ragna-beat-assets/image_rune_0.png";
            image_runes[1].src = "/ragna-beat-assets/image_rune_1.png";
            image_runes[2].src = "/ragna-beat-assets/image_rune_2.png";
            image_runes[3].src = "/ragna-beat-assets/image_rune_3.png";
            image_runes[4].src = "/ragna-beat-assets/image_rune_4.png";
            image_drum.addEventListener('load', e => {
                drawDrums();
        });

            for (let i = 0; i < infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps.length; i++) {
                let niveau = infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps[i];
                let level = niveau._beatmapFilename;
                let fileLevel = $("#info-dat").data('file').replace('Info.dat', level).replace('info.dat', level);
                diffsWrapper.append("<button data-level='" + niveau._difficulty + "' class='ragna-beat-diff btn-info btn btn-sm test-map mr-2 mb-2'>level " + niveau._difficultyRank + "</button>");

                $.ajax({
                    url: fileLevel,
                    type: 'GET',
                    dataType: 'JSON',
                    indexValue: i,
                    success: function(result) {
                        levelDetails[this.indexValue] = result;
                        levelDetail = levelDetails[0]; //hack
                    }
                });
            }
            $('.ragna-beat-diff').first().addClass('btn-dark');
        }
    });
}

$(function () {
    init();

    $(document).on('click','#ragna-beat-play', function () {
        let level = $(this).attr('data-level');

        if (level === 'play') {

            if ($(this).hasClass('playing')) {
                startTime = Date.now() - audio.currentTime * 1000 - delay - 1000 / fps * 2;
                audio.play();
            }
            else {
                startTime = Date.now();

                setTimeout(function(){
                    audio.play();
                }, delay);

                $(this).addClass('playing');
            }

            $(this).attr('data-level','pause');
            $(this).html('<i class="fas fa-pause"></i>');
            isPlaying = true;
            animationFrame = requestAnimationFrame(animate);
        }
        else if (level === "pause") {
            $(this).attr('data-level','play');
            $(this).html('<i class="fas fa-play"></i>');
            audio.pause();
            isPlaying = false;
            cancelAnimationFrame(animationFrame);
        }
    });

    $(document).on('click','.ragna-beat-diff', function () {
        stopSong();
        $('.ragna-beat-diff.btn-dark').removeClass('btn-dark');
        let index = $(this).index('.ragna-beat-diff');
        $('.ragna-beat-diff').eq(index).addClass('btn-dark');
        levelDetail = levelDetails[index];
        moveSpeed = infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps[index]._noteJumpMovementSpeed / 3 * ratio;
        setDelay();
    });

    $(document).on('click','#ragna-beat-stop', function () {
        stopSong();
    });

    $(document).on('change','#vol-control', function () {
        audio.volume = parseInt($(this).val()) / 100;
    });

    $(document).on('change','#drum-vol-control', function () {
        let value = parseInt($(this).val()) / 100;
        for (let index in audio_drums) {
            audio_drums[index].volume = value;
        }
    });

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === "visible" && isPlaying) {
            rings = [];
            for (let index in runes) delete runes[index];
            drawDrums();
            let elapsedTime = (Date.now() - startTime) / 1000;
            let timeStamp = 0;
            for(let i=0;i<levelDetail._notes.length;i++) {
                timeStamp = levelDetail._notes[i]._time / songBPS;
                if (timeStamp <= elapsedTime && i !== levelDetail._notes.length) {
                    jsonIteration = i + 1;
                }
            }
        }
    });

    $(document).on('mousedown','#ragna-beat-duration input', function () {
        audio.pause();
        isPlaying = false;
        for (let index in runes) delete runes[index];
        drawDrums();
        cancelAnimationFrame(animationFrame);
    });

    $(document).on('mouseup','#ragna-beat-duration input', function () {
        let value = $(this).val() / 100 * infoDat._songApproximativeDuration;
        audio.currentTime = value;
        startTime = Date.now() - value * 1000 - delay - 1000 / fps * 2;
        isPlaying = true;
        rings = [];
        animationFrame = requestAnimationFrame(animate);
        audio.play();
        jsonIterationToCurrentTime(value);
        $('#ragna-beat-play').attr('data-level','pause');
        $('#ragna-beat-play').html('<i class="fas fa-pause"></i>');
        $('#ragna-beat-play').addClass('playing');
    });

    $(document).on('input','#ragna-beat-duration input', function () {
        let value = $(this).val() / 100 * infoDat._songApproximativeDuration;
        $('#ragna-beat-duration .current').text(new Date(value * 1000).toISOString().substr(14, 5));
    });

    $(document).on('click','#ragna-beat-sounds button', function () {
        let index = $(this).index('#ragna-beat-sounds button');

        $('#ragna-beat-sounds button').removeClass('btn-dark');
        $(this).addClass('btn-dark');

        for (let i in audio_drums) {
            audio_drums[i].src = drumSounds[index].url;
        }
    });

});

function jsonIterationToCurrentTime(elapsedTime) {
    for (let index in runes) delete runes[index];
    let timeStamp = 0;
    for(let i=0;i<levelDetail._notes.length;i++) {
        timeStamp = levelDetail._notes[i]._time / songBPS;
        if (timeStamp <= elapsedTime + delay / 1000 && i !== levelDetail._notes.length) {
            jsonIteration = i + 1;
        }
    }
}

function stopSong() {
    rings = [];
    isPlaying = false;
    audio.pause();
    audio.currentTime = 0;
    jsonIteration = 0;
    $('#ragna-beat-play').removeClass('playing');
    $('#ragna-beat-play').html('<i class="fas fa-play"></i>');
    $('#ragna-beat-play').attr('data-level','play');
    for (let index in runes) delete runes[index];
    drawDrums();
    cancelAnimationFrame(animationFrame);
}

function drawDrums() {
    ctx.clearRect(0, 0, c.width, c.height);
    ctx.drawImage(image_drum, margin,  c.height - margin - circleRadius*2, circleRadius*2, circleRadius*2);
    ctx.drawImage(image_drum, circleRadius * 2 + margin * 2,  c.height - margin - circleRadius*2, circleRadius*2, circleRadius*2);
    ctx.drawImage(image_drum, circleRadius * 4 + margin * 3,  c.height - margin - circleRadius*2, circleRadius*2, circleRadius*2);
    ctx.drawImage(image_drum, circleRadius * 6 + margin * 4,  c.height - margin - circleRadius*2, circleRadius*2, circleRadius*2);
}

function setDelay() {
    delay = spawnDistance / moveSpeed * 1000 / fps;
}


let fps = 60;
let now;
let then = Date.now();
let interval = 1000 / fps;
let delta;

function animate() {
    animationFrame = requestAnimationFrame(animate);
    now = Date.now();
    delta = now - then;

    if (delta > interval) {
        then = now - (delta % interval);

        if (!isPlaying) return;

        drawDrums();

        if (jsonIteration < levelDetail._notes.length) {
            let noteTimestamp = levelDetail._notes[jsonIteration]._time / songBPS;
            let elapsedTime = (Date.now() - startTime) / 1000;

            if (noteTimestamp.toFixed(5) - elapsedTime.toFixed(5) < 0.005 && runes[jsonIteration] === undefined) {
                let lineIndex = levelDetail._notes[jsonIteration]._lineIndex;
                let runeIndex = parseInt(levelDetail._notes[jsonIteration]._time.toFixed(2).split(".")[1])/25;
                if (!Number.isInteger(runeIndex)) {
                    runeIndex = 4;
                }

                runes[jsonIteration] = {
                    'lineIndex': lineIndex,
                    'runeIndex': runeIndex,
                    'positionTop': c.height - circleRadius - margin - spawnDistance,
                    'sound': true
                };

                let nextIteration = jsonIteration + 1;
                if (nextIteration <= levelDetail._notes.length - 1) {
                    let nextNoteTimestamp = levelDetail._notes[jsonIteration + 1]._time / songBPS;
                    let lineIndex = levelDetail._notes[jsonIteration + 1]._lineIndex;
                    let runeIndex = parseInt(levelDetail._notes[jsonIteration]._time.toFixed(2).split(".")[1])/25;
                    if (!Number.isInteger(runeIndex)) {
                        runeIndex = 4;
                    }

                    if (noteTimestamp.toFixed(5) === nextNoteTimestamp.toFixed(5)) {

                        runes[jsonIteration + 1] = {
                            'lineIndex': lineIndex,
                            'runeIndex': runeIndex,
                            'positionTop': c.height - circleRadius - margin - spawnDistance,
                            'sound': false
                        };

                        jsonIteration++;
                    }
                }

                jsonIteration++;
            }
        }

        for (i = 0; i < rings.length; i++) {
            if (rings[i].ringCounter < 35) {
                rings[i].ringRadius += 1.5;
            }
            else {
                rings[i].ringRadius = 0;
            }

            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(rings[i].ringX, rings[i].ringY, rings[i].ringRadius, 0, Math.PI * 2);
            let opacity = (35 - rings[i].ringCounter) / 50;
            ctx.strokeStyle = rings[i].ringColor.replace('%a',opacity);

            ctx.stroke();
            ctx.closePath();

            rings[i].ringCounter += rings[i].ringCounterVelocity;

            if (rings[i].ringCounter > 35) {
                rings.splice(i,1);
            }

        }

        for (const [i, value] of Object.entries(runes)) {
            if (runes[i].lineIndex === 0) {
                ctx.drawImage(image_runes[runes[i].runeIndex], margin,  runes[i].positionTop - margin - circleRadius/2, circleRadius*2, circleRadius*2);
            }
            else if (runes[i].lineIndex === 1) {
                ctx.drawImage(image_runes[runes[i].runeIndex], circleRadius * 2 + margin * 2,  runes[i].positionTop - margin - circleRadius/2, circleRadius*2, circleRadius*2);
            }
            else if (runes[i].lineIndex === 2) {
                ctx.drawImage(image_runes[runes[i].runeIndex], circleRadius * 4 + margin * 3,  runes[i].positionTop - margin - circleRadius/2, circleRadius*2, circleRadius*2);
            }
            else if (runes[i].lineIndex === 3) {
                ctx.drawImage(image_runes[runes[i].runeIndex], circleRadius * 6 + margin * 4,  runes[i].positionTop - margin - circleRadius/2, circleRadius*2, circleRadius*2);
            }

            runes[i].positionTop += moveSpeed;
            let distance = c.height - circleRadius - margin - runes[i].positionTop;

            if (distance < moveSpeed / 2 && distance > -moveSpeed / 2 ) {
                let ringX;
                let lineIndex = runes[i].lineIndex;

                if (runes[i].sound) {
                    audio_drums[lineIndex].currentTime = 0;
                    audio_drums[lineIndex].play();
                }

                switch(lineIndex) {
                    case 0:
                        ringX = margin + circleRadius;
                        break;
                    case 1:
                        ringX = circleRadius * 3 + margin * 2;
                        break;
                    case 2:
                        ringX =  ringX =  circleRadius * 6 + margin / 2;
                        break;
                    case 3:
                        ringX =  circleRadius * 7 + margin * 4;
                        break;
                }

                rings.push({
                    ringX : ringX,
                    ringY : c.height - margin - circleRadius*2 + circleRadius,
                    ringRadius : circleRadius - 5,
                    ringCounter : 0,
                    ringCounterVelocity : 3,
                    ringColor: 'rgba(189,217,255,%a)'
                });
            }

            if (runes[i].positionTop > 700) {
                delete runes[i];
            }
        }
    }

}
