import {Controller} from '@hotwired/stimulus';
import {average} from 'color.js'
import 'jquery'
import {RagnaBeat} from "../js/ragna-beat/ragnabeat";


require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');

export default class extends Controller {
    static targets = ['img', 'background']
    ragna = null;

    connect() {

    }

    disconnect() {
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}
