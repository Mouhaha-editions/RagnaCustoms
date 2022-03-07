import {Controller} from '@hotwired/stimulus';
import 'jquery'
import Swup from 'swup';
import {RagnaBeat} from "../js/ragna-beat/ragnabeat";
import 'bootstrap';

require('../js/base');
require('../js/plugins/modal_ajax');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');
window.$ = window.jQuery = $;

export default class extends Controller {
    connect() {
        // const swup = new Swup();



    }

}