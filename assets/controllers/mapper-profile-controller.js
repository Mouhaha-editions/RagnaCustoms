import {Controller} from '@hotwired/stimulus';
import {average} from 'color.js'
import 'jquery'

import 'select2/dist/js/select2.full.min';

require('../../public/bundles/tetranzselect2entity/js/select2entity');
require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');

export default class extends Controller {
    static targets = ['img', 'background', 'info']
    ragna = null;

    connect() {
        average(this.imgTarget.src, {amount: 1}).then(color => {
            $("#main").attr('style', " background: radial-gradient(100% 100% at 0% 0%, rgba(" + color[0] + ", " + color[1] + ", " + color[2] + ", 0.2) 0%, rgba(0, 0, 0, 0) 100%), #2B2B2B;");
        });
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