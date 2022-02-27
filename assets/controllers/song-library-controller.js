import {Controller} from '@hotwired/stimulus';
import {average} from 'color.js'
import 'jquery'
import {RagnaBeat} from "../js/ragna-beat/ragnabeat";

import 'select2/dist/js/select2.full.min';
require('../../public/bundles/tetranzselect2entity/js/select2entity');


export default class extends Controller {
    static targets = ['img', 'background']
    ragna = null;
    connect() {
        $('.select2entity[data-autostart="true"]').select2entity();
        $('.select2').select2();
    }
disconnect() {
}

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}