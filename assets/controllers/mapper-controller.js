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
        $('.circle').each(function(){
            let items =$(this).find('.menuItem');

            for(let i = 0, l = items.length; i < l; i++) {
                items[i].style.left = (50 - 35*Math.cos(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";

                items[i].style.top = (50 + 35*Math.sin(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";
            }
        })
    }

    disconnect() {
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}
