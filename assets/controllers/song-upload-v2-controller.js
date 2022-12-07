import {Controller} from '@hotwired/stimulus';
import 'jquery'
import Dropzone from "dropzone";
import 'select2/dist/js/select2.full.min';
require('../../public/bundles/tetranzselect2entity/js/select2entity');
require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');



export default class extends Controller {
    static targets = ['img', 'background']
    ragna = null;

    connect() {
        Dropzone.autoDiscover = false;
        let myDropzone = new Dropzone("#my-form",{url:"/upload/bundle/song/add"});
        myDropzone.on("addedfile", file => {
        });
        myDropzone.on("success", (file) => {
            let resp = JSON.parse(file.xhr.response);
            if(resp.success){
                $(file.previewTemplate).find(" .dz-image").css({'background':"url("+resp.cover+")"})
            }
        });
    }


    disconnect() {
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}