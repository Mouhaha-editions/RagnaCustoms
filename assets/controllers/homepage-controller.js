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

        $('.circle').each(function(){
            let items =$(this).find('.menuItem');

            for(let i = 0, l = items.length; i < l; i++) {
                items[i].style.left = (50 - 35*Math.cos(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";

                items[i].style.top = (50 + 35*Math.sin(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";
            }
        })

    }

}