import {Controller} from '@hotwired/stimulus';
import 'jquery'
import Swup from 'swup';
import 'bootstrap';
import Chart from 'chart.js/auto';

require('../js/base');
//require('../js/plugins/ajax_link');
require('../js/plugins/rating');
window.$ = window.jQuery = $;

export default class extends Controller {
    connect() {



        console.log("fin")
    }

}