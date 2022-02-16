import { Controller } from '@hotwired/stimulus';
import { average } from 'color.js'
import  'jquery'

export default class extends Controller {
    static targets = ['img', 'background']
    connect() {
        average(this.imgTarget.src, { amount: 1 }).then(color => {
            $("body").attr('style', " background: radial-gradient(100% 100% at 0% 0%, rgba("+color[0]+", "+color[1]+", "+color[2]+", 0.2) 0%, rgba(0, 0, 0, 0) 100%), #2B2B2B;");
        });
          $(".back-button").attr('href', document.referrer !== undefined ?document.referrer:"#");
    }
    back() {
       // history.back();// Swup instance
       //  return false;
    }
}