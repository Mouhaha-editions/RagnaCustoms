export class RagnaBeat {
    uid;
    c;
    ctx;
    file;
    levelDetail;
    audio;
    infoDat;
    animationFrame;
    drumSounds;
    isPlaying = false;
    startTime = 0;
    moveSpeed = 0;
    jsonIteration = 0;
    songBPS = 0;
    runes = {};
    rings = [];
    ratio = 2.5;
    levelDetails = {};
    spawnDistance = 600;
    circleRadius = 30;
    margin = 12;
    image_drum = new Image;
    image_runes = [
        new Image,
        new Image,
        new Image,
        new Image,
        new Image
    ];

    audio_drums = [
        new Audio,
        new Audio,
        new Audio,
        new Audio
    ];


    singleton = false;
    fps = 60;
    now;
    then = Date.now();
    interval = 1000 / this.fps;
    delta;

    init() {
        let diffsWrapper = $(this.uid + " #ragna-beat-diffs");
        let buttonsWrapper = $(this.uid + " #ragna-beat-buttons");
        let volumesWrapper = $(this.uid + " #ragna-beat-volumes");
        let soundsWrapper = $(this.uid + " #ragna-beat-sounds");
        let t = this;
        $.ajax({
            url: t.file,
            type: 'GET',
            dataType: 'JSON',
            success: function (result) {
                t.infoDat = result;
                t.songBPS = t.infoDat._beatsPerMinute / 60;
                t.moveSpeed = t.infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps[0]._noteJumpMovementSpeed / 3 * t.ratio;
                t.setDelay();
                let song = t.infoDat._songFilename;
                let fileSong = t.file.replace('Info.dat', song).replace('info.dat', song);

                $(t.uid + ' #ragna-beat-duration .max').text(new Date(t.infoDat._songApproximativeDuration * 1000).toISOString().substr(14, 5));

                buttonsWrapper.append("<button id='ragna-beat-play' data-level='play' class='btn-warning btn btn-sm test-map mr-2 mb-2'><i class='fas fa-play'></i></button>");
                buttonsWrapper.append("<button id='ragna-beat-stop' data-level='stop' class='btn-danger btn btn-sm test-map mr-2 mb-2'><i class='fas fa-stop'></i></button>");

                volumesWrapper.append("<div>Music volume: <input id=\"vol-control\" value='20' type=\"range\" min=\"0\" max=\"100\" step=\"1\"></input></div>");
                volumesWrapper.append("<div>Drum volume: <input id=\"drum-vol-control\" value='20' type=\"range\" min=\"0\" max=\"100\" step=\"1\"></input></div>");

                t.audio = new Audio(fileSong);
                t.audio.level = $("#vol-control").val() / 100;
                t.audio.preload = "auto";
                t.audio.volume = 0.2;

                $(t.audio).on('ended', function () {
                    t.isPlaying = false;
                    stopSong();
                });

                t.audio.addEventListener("timeupdate", function () {
                    let percent = t.audio.currentTime / t.infoDat._songApproximativeDuration * 100;
                    $('#ragna-beat-duration .current').text(new Date(t.audio.currentTime * 1000).toISOString().substr(14, 5));
                    $('#ragna-beat-duration input').val(percent);
                });

                t.drumSounds = [
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

                for (let i = 0; i < t.drumSounds.length; i++) {
                    soundsWrapper.append("<button class='btn-info btn btn-sm test-map mr-2 mb-2'>" + t.drumSounds[i].name + "</button>");
                }
                soundsWrapper.find('button').first().addClass('btn-dark');

                for (let i in t.audio_drums) {
                    t.audio_drums[i].src = t.drumSounds[0].url;
                    t.audio_drums[i].volume = $("#drum-vol-control").val() / 100;
                }

                t.c = $(t.uid + " #ragna-canvas")[0];
                t.ctx = t.c.getContext("2d");
                t.checkTop = t.c.height - t.circleRadius - t.margin - t.spawnDistance;

                t.image_drum.src = "/ragna-beat-assets/image_drum.png";
                t.image_runes[0].src = "/ragna-beat-assets/image_rune_0.png";
                t.image_runes[1].src = "/ragna-beat-assets/image_rune_1.png";
                t.image_runes[2].src = "/ragna-beat-assets/image_rune_2.png";
                t.image_runes[3].src = "/ragna-beat-assets/image_rune_3.png";
                t.image_runes[4].src = "/ragna-beat-assets/image_rune_4.png";
                t.image_drum.addEventListener('load', e => {
                    t.drawDrums();
                });

                for (let i = 0; i < t.infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps.length; i++) {
                    let niveau = t.infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps[i];
                    let level = niveau._beatmapFilename;
                    let fileLevel = t.file.replace('Info.dat', level).replace('info.dat', level);
                    diffsWrapper.append("<button data-level='" + niveau._difficulty + "' class='ragna-beat-diff btn-info btn btn-sm test-map mr-2 mb-2'>level " + niveau._difficultyRank + "</button>");

                    $.ajax({
                        url: fileLevel,
                        type: 'GET',
                        dataType: 'JSON',
                        indexValue: i,
                        success: function (result) {
                            t.levelDetails[this.indexValue] = result;
                            t.levelDetail = t.levelDetails[0]; //hack
                        }
                    });
                }
                $('.ragna-beat-diff').first().addClass('btn-dark');
            }
        });
    }

    enableModal() {
        this.isModal = true;
    }

    startInit(divId, file) {
        let t = this;
        let randUid = "ragna" + (Math.random().toString(36).slice(-6));
        $("#" + divId).each(function () {
            $(this).addClass(randUid);
        });
        t.uid = "#" + divId + "." + randUid;
        t.file = file;
        t.init();
        $(document).on('click', t.uid + ' #ragna-beat-buttons #ragna-beat-play', function () {
            let level = $(this).attr('data-level');
            if (level === 'play') {
                if ($(this).hasClass('playing')) {
                    t.startTime = Date.now() - t.audio.currentTime * 1000 - t.delay - 1000 / t.fps * 2;
                    t.audio.play();
                } else {
                    t.startTime = Date.now();

                    setTimeout(function () {
                        t.audio.play();
                    }, t.delay);

                    $(this).addClass('playing');
                }

                $(this).attr('data-level', 'pause');
                $(this).html('<i class="fas fa-pause"></i>');
                t.isPlaying = true;
                t.animationFrame = requestAnimationFrame(function () {
                    t.animate()
                });
            } else if (level === "pause") {
                $(this).attr('data-level', 'play');
                $(this).html('<i class="fas fa-play"></i>');
                t.audio.pause();
                t.isPlaying = false;
                cancelAnimationFrame(t.animationFrame);
            }
        });

        $(document).on('click', t.uid + ' #ragna-beat-diffs .ragna-beat-diff', function () {
            t.stopSong();
            $('.ragna-beat-diff.btn-dark').removeClass('btn-dark');
            let index = $(this).index('.ragna-beat-diff');
            $('.ragna-beat-diff').eq(index).addClass('btn-dark');
            t.levelDetail = t.levelDetails[index];
            t.moveSpeed = t.infoDat._difficultyBeatmapSets[0]._difficultyBeatmaps[index]._noteJumpMovementSpeed / 3 * t.ratio;
            t.setDelay();
        });

        $(document).on('click', t.uid + ' #ragna-beat-buttons #ragna-beat-stop', function () {
            t.stopSong();
        });

        $(document).on('change', this.uid + ' #vol-control', function () {
            t.audio.volume = parseInt($(this).val()) / 100;
        });

        $(document).on('change', this.uid + ' #drum-vol-control', function () {
            let value = parseInt($(this).val()) / 100;
            for (let index in t.audio_drums) {
                t.audio_drums[index].volume = value;
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === "visible" && t.isPlaying) {
                t.rings = [];
                for (let index in t.runes) delete t.runes[index];
                t.drawDrums();
                let elapsedTime = (Date.now() - t.startTime) / 1000;
                let timeStamp = 0;
                for (let i = 0; i < t.levelDetail._notes.length; i++) {
                    timeStamp = t.levelDetail._notes[i]._time / t.songBPS;
                    if (timeStamp <= elapsedTime && i !== t.levelDetail._notes.length) {
                        t.this.jsonIteration = i + 1;
                    }
                }
            }
        });

        $(document).on('mousedown', t.uid + ' #ragna-beat-duration input', function () {
            t.audio.pause();
            t.isPlaying = false;
            for (let index in t.runes) delete t.runes[index];
            t.drawDrums();
            cancelAnimationFrame(t.animationFrame);
        });

        $(document).on('mouseup', t.uid + ' #ragna-beat-duration input', function () {
            let value = $(this).val() / 100 * t.infoDat._songApproximativeDuration;
            t.audio.currentTime = value;
            t.startTime = Date.now() - value * 1000 - t.delay - 1000 / t.fps * 2;
            t.isPlaying = true;
            t.rings = [];
            t.animationFrame = requestAnimationFrame(function () {
                t.animate()
            });
            t.audio.play();
            t.jsonIterationToCurrentTime(value);
            $(t.uid + ' #ragna-beat-play').attr('data-level', 'pause');
            $(t.uid + ' #ragna-beat-play').html('<i class="fas fa-pause"></i>');
            $(t.uid + ' #ragna-beat-play').addClass('playing');
        });

        $(document).on('input', t.uid + ' #ragna-beat-duration input', function () {
            let value = $(this).val() / 100 * t.infoDat._songApproximativeDuration;
            $(t.uid + ' #ragna-beat-duration .current').text(new Date(value * 1000).toISOString().substr(14, 5));
        });

        $(document).on('click', t.uid + ' #ragna-beat-sounds button', function () {
            let index = $(this).index(t.uid + ' #ragna-beat-sounds button');

            $('#ragna-beat-sounds button').removeClass('btn-dark');
            $(this).addClass('btn-dark');

            for (let i in t.audio_drums) {
                t.audio_drums[i].src = t.drumSounds[index].url;
            }
        });
    }

    jsonIterationToCurrentTime(elapsedTime) {
        for (let index in this.runes) delete this.runes[index];
        let timeStamp = 0;
        for (let i = 0; i < this.levelDetail._notes.length; i++) {
            timeStamp = this.levelDetail._notes[i]._time / this.songBPS;
            if (timeStamp <= elapsedTime + this.delay / 1000 && i !== this.levelDetail._notes.length) {
                this.jsonIteration = i + 1;
            }
        }
    }

    stopSong() {
        this.rings = [];
        this.isPlaying = false;
        this.audio.pause();
        this.audio.currentTime = 0;
        this.jsonIteration = 0;
        $(this.uid + ' #ragna-beat-play').removeClass('playing');
        $(this.uid + ' #ragna-beat-play').html('<i class="fas fa-play"></i>');
        $(this.uid + ' #ragna-beat-play').attr('data-level', 'play');
        for (let index in this.runes) delete this.runes[index];
        this.drawDrums();
        cancelAnimationFrame(this.animationFrame);
    }

    drawDrums() {
        this.ctx.clearRect(0, 0, this.c.width, this.c.height);
        this.ctx.drawImage(this.image_drum, this.margin, this.c.height - this.margin - this.circleRadius * 2, this.circleRadius * 2, this.circleRadius * 2);
        this.ctx.drawImage(this.image_drum, this.circleRadius * 2 + this.margin * 2, this.c.height - this.margin - this.circleRadius * 2, this.circleRadius * 2, this.circleRadius * 2);
        this.ctx.drawImage(this.image_drum, this.circleRadius * 4 + this.margin * 3, this.c.height - this.margin - this.circleRadius * 2, this.circleRadius * 2, this.circleRadius * 2);
        this.ctx.drawImage(this.image_drum, this.circleRadius * 6 + this.margin * 4, this.c.height - this.margin - this.circleRadius * 2, this.circleRadius * 2, this.circleRadius * 2);
    }

    setDelay() {
        this.delay = this.spawnDistance / this.moveSpeed * 1000 / this.fps;
    }

    animate() {
        let t = this;
        this.animationFrame = requestAnimationFrame(function () {
            t.animate()
        });
        this.now = Date.now();
        this.delta = this.now - this.then;

        if (this.delta > this.interval) {
            this.then = this.now - (this.delta % this.interval);

            if (!this.isPlaying) return;

            this.drawDrums();

            if (this.jsonIteration < this.levelDetail._notes.length) {
                let noteTimestamp = this.levelDetail._notes[this.jsonIteration]._time / this.songBPS;
                let elapsedTime = (Date.now() - this.startTime) / 1000;

                if (noteTimestamp.toFixed(5) - elapsedTime.toFixed(5) < 0.005 && this.runes[this.jsonIteration] === undefined) {
                    let lineIndex = this.levelDetail._notes[this.jsonIteration]._lineIndex;
                    let runeIndex = parseInt(this.levelDetail._notes[this.jsonIteration]._time.toFixed(2).split(".")[1]) / 25;
                    if (!Number.isInteger(runeIndex)) {
                        runeIndex = 4;
                    }

                    this.runes[this.jsonIteration] = {
                        'lineIndex': lineIndex,
                        'runeIndex': runeIndex,
                        'positionTop': this.c.height - this.circleRadius - this.margin - this.spawnDistance,
                        'sound': true
                    };

                    let nextIteration = this.jsonIteration + 1;
                    if (nextIteration <= this.levelDetail._notes.length - 1) {
                        let nextNoteTimestamp = this.levelDetail._notes[this.jsonIteration + 1]._time / this.songBPS;
                        let lineIndex = this.levelDetail._notes[this.jsonIteration + 1]._lineIndex;
                        let runeIndex = parseInt(this.levelDetail._notes[this.jsonIteration]._time.toFixed(2).split(".")[1]) / 25;
                        if (!Number.isInteger(runeIndex)) {
                            runeIndex = 4;
                        }

                        if (noteTimestamp.toFixed(5) === nextNoteTimestamp.toFixed(5)) {

                            this.runes[this.jsonIteration + 1] = {
                                'lineIndex': lineIndex,
                                'runeIndex': runeIndex,
                                'positionTop': this.c.height - this.circleRadius - this.margin - this.spawnDistance,
                                'sound': false
                            };

                            this.jsonIteration++;
                        }
                    }

                    this.jsonIteration++;
                }
            }

            for (let i = 0; i < this.rings.length; i++) {
                if (this.rings[i].ringCounter < 35) {
                    this.rings[i].ringRadius += 1.5;
                } else {
                    this.rings[i].ringRadius = 0;
                }

                this.ctx.lineWidth = 3;
                this.ctx.beginPath();
                this.ctx.arc(this.rings[i].ringX, this.rings[i].ringY, this.rings[i].ringRadius, 0, Math.PI * 2);
                let opacity = (35 - this.rings[i].ringCounter) / 50;
                this.ctx.strokeStyle = this.rings[i].ringColor.replace('%a', opacity);

                this.ctx.stroke();
                this.ctx.closePath();

                this.rings[i].ringCounter += this.rings[i].ringCounterVelocity;

                if (this.rings[i].ringCounter > 35) {
                    this.rings.splice(i, 1);
                }

            }

            for (const [i, value] of Object.entries(this.runes)) {
                if (this.runes[i].lineIndex === 0) {
                    this.ctx.drawImage(this.image_runes[this.runes[i].runeIndex], this.margin, this.runes[i].positionTop - this.margin - this.circleRadius / 2, this.circleRadius * 2, this.circleRadius * 2);
                } else if (this.runes[i].lineIndex === 1) {
                    this.ctx.drawImage(this.image_runes[this.runes[i].runeIndex], this.circleRadius * 2 + this.margin * 2, this.runes[i].positionTop - this.margin - this.circleRadius / 2, this.circleRadius * 2, this.circleRadius * 2);
                } else if (this.runes[i].lineIndex === 2) {
                    this.ctx.drawImage(this.image_runes[this.runes[i].runeIndex], this.circleRadius * 4 + this.margin * 3, this.runes[i].positionTop - this.margin - this.circleRadius / 2, this.circleRadius * 2, this.circleRadius * 2);
                } else if (this.runes[i].lineIndex === 3) {
                    this.ctx.drawImage(this.image_runes[this.runes[i].runeIndex], this.circleRadius * 6 + this.margin * 4, this.runes[i].positionTop - this.margin - this.circleRadius / 2, this.circleRadius * 2, this.circleRadius * 2);
                }

                this.runes[i].positionTop += this.moveSpeed;
                let distance = this.c.height - this.circleRadius - this.margin - this.runes[i].positionTop;

                if (distance < this.moveSpeed / 2 && distance > -this.moveSpeed / 2) {
                    let ringX;
                    let lineIndex = this.runes[i].lineIndex;

                    if (this.runes[i].sound) {
                        this.audio_drums[lineIndex].currentTime = 0;
                        this.audio_drums[lineIndex].play();
                    }

                    switch (lineIndex) {
                        case 0:
                            ringX = this.margin + this.circleRadius;
                            break;
                        case 1:
                            ringX = this.circleRadius * 3 + this.margin * 2;
                            break;
                        case 2:
                            ringX = ringX = this.circleRadius * 6 + this.margin / 2;
                            break;
                        case 3:
                            ringX = this.circleRadius * 7 + this.margin * 4;
                            break;
                    }

                    this.rings.push({
                        ringX: ringX,
                        ringY: this.c.height - this.margin - this.circleRadius * 2 + this.circleRadius,
                        ringRadius: this.circleRadius - 5,
                        ringCounter: 0,
                        ringCounterVelocity: 3,
                        ringColor: 'rgba(189,217,255,%a)'
                    });
                }

                if (this.runes[i].positionTop > 700) {
                    delete this.runes[i];
                }
            }
        }

    }
}