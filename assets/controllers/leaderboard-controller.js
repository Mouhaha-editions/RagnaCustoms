import {Controller} from '@hotwired/stimulus';
import 'jquery'

export default class extends Controller {
    static targets = ['img', 'background']
    connect() {
        $("[data-toggle=tooltip]").tooltip();
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