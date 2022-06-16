import {Controller} from '@hotwired/stimulus';
import {average} from 'color.js'
import 'jquery'
// import 'jquery-ui/ui/'
import {RagnaBeat} from "../js/ragna-beat/ragnabeat";
require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');


export default class extends Controller {
    static targets = ['img', 'background', 'info','readFeedback']
    ragna = null;

    connect() {
        let file= $(this.infoTarget).data('file');
        let divId= $(this.infoTarget).attr('id');
        average(this.imgTarget.src, {amount: 1}).then(color => {
            $("#main").attr('style', " background: radial-gradient(100% 100% at 0% 0%, rgba(" + color[0] + ", " + color[1] + ", " + color[2] + ", 0.7) 0%, rgba(0, 0, 0, 0) 100%), #2B2B2B;");
        });
        $(".back-button").attr('href', document.referrer !== undefined ? document.referrer : "#");
        // const swup = new Swup();
        this.ragna = new RagnaBeat();
        this.ragna.startInit(divId, file);
        $(".song-feedback").on("click",function(){
            $("#rating-box").hide("slow",function(){
                $("#feedback-box").show("slow");
            });
        });$(".back-feedback").on("click",function(){
            $("#feedback-box").hide("slow",function(){
                $("#rating-box").show("slow");
            });
        });
    }

    disconnect() {
        $("#main").attr('style', " background: transparent");
        this.ragna.stopSong();
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}