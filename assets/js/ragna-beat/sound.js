window.AudioContext = window.AudioContext || window.webkitAudioContext;
const context = new AudioContext();
export class FireAndForgetSound {
    url = '';
    buffer = null;

    constructor(url) {
        this.url = url;
    }

    load() {
        if (!this.url) return Promise.reject(new Error('Missing or invalid URL: ', this.url));
        if (this.buffer) return Promise.resolve(this.buffer);
        return new Promise((resolve, reject) => {
            const request = new XMLHttpRequest();
            request.open('GET', this.url, true);
            request.responseType = 'arraybuffer';
            request.onload = () => {
                context.decodeAudioData(request.response, (buffer) => {
                    if (!buffer) {
                        console.log(`Sound decoding error: ${ this.url }`);
                        reject(new Error(`Sound decoding error: ${ this.url }`));
                        return;
                    }
                    this.buffer = buffer;
                    resolve(buffer);
                });
            };
            request.onerror = (err) => {
                console.log('Sound XMLHttpRequest error:', err);
                reject(err);
            };
            request.send();
        });
    }

    play(volume = 1, time = 0) {
        if (!this.buffer) return;

        const source = context.createBufferSource();
        source.buffer = this.buffer;
        source.onended = () => {
            source.stop(0);
        };
        const gainNode = context.createGain();
        gainNode.gain.value = volume;
        source.connect(gainNode).connect(context.destination);
        source.start(time);
    }
}