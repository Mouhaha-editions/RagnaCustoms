import {Controller} from '@hotwired/stimulus';
import 'jquery'
import Dropzone from "dropzone";
require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');



export default class extends Controller {
    static targets = ['img', 'background']
    ragna = null;

    connect() {
        $("[data-toggle=tooltip]").tooltip();
        Dropzone.autoDiscover = false;
        let myDropzone = new Dropzone("#my-form",{url:"/upload/bundle/song/add"});
        myDropzone.on("addedfile", file => {
        });
        myDropzone.on("success", (file) => {
            let resp = JSON.parse(file.xhr.response);
            if(resp.success){
                $(file.previewTemplate).find(" .dz-image").css({'background':"url("+resp.cover+")",'background-size':'cover'})
            }
        });
        $('.circle').each(function(){
            let items =$(this).find('.menuItem');

            for(let i = 0, l = items.length; i < l; i++) {
                items[i].style.left = (50 - 35*Math.cos(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";

                items[i].style.top = (50 + 35*Math.sin(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";
            }
        })
    }


    disconnect() {
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}
