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
        let myDropzone = new Dropzone("#my-form",{url:});
        myDropzone.on("addedfile", file => {
            console.log(`File added: ${file.name}`);
        });
    }


    disconnect() {
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}