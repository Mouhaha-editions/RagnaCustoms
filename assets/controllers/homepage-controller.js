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
        let maxBg = 21;
        let currentBg = Math.floor(Math.random() * maxBg-1) +1;

        var images = [];

        function preload() {
            for (var i = 1; i < maxBg; i++) {
                images[i] = new Image();
                images[i].src = '/bg/' + currentBg + '.webp';
            }
        }
        let switchBg = function () {
            $("header").css({"background-image": 'url("/bg/' + currentBg + '.webp")'});
            currentBg += 1;
            if (currentBg > maxBg) {
                currentBg = 1;
            }
        };

        preload();
        switchBg();

        $('.circle').each(function(){
            let items =$(this).find('.menuItem');

            for(let i = 0, l = items.length; i < l; i++) {
                items[i].style.left = (50 - 35*Math.cos(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";

                items[i].style.top = (50 + 35*Math.sin(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";
            }
        })

    }

}
