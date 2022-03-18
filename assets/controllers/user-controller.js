import {Controller} from '@hotwired/stimulus';
import {average} from 'color.js'
import 'jquery'
import {RagnaBeat} from "../js/ragna-beat/ragnabeat";

import 'select2/dist/js/select2.full.min';

require('../../public/bundles/tetranzselect2entity/js/select2entity');
import '../js/plugins/modal_ajax';
require('../js/base');
require('../js/plugins/modal_ajax');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');

export default class extends Controller {
    static targets = ['img', 'background', 'info']
    ragna = null;

    connect() {
        average(this.imgTarget.src, {amount: 1}).then(color => {
            $("body").attr('style', " background: radial-gradient(100% 100% at 0% 0%, rgba(" + color[0] + ", " + color[1] + ", " + color[2] + ", 0.2) 0%, rgba(0, 0, 0, 0) 100%), #2B2B2B;");
        });
    }

    disconnect() {
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}