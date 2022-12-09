import {Controller} from '@hotwired/stimulus';
import {average} from 'color.js'
import 'jquery'

import 'select2/dist/js/select2.full.min';
require('../../public/bundles/tetranzselect2entity/js/select2entity');
require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');
require('../js/plugins/copy_to_clipboard');

export default class extends Controller {
    static targets = ['img', 'background', 'info']
    ragna = null;

    connect() {
        average(this.imgTarget.src, {amount: 1}).then(color => {
            $("#main").attr('style', " background: radial-gradient(100% 100% at 0% 0%, rgba(" + color[0] + ", " + color[1] + ", " + color[2] + ", 0.7) 0%, rgba(0, 0, 0, 0) 100%), #2B2B2B;");

            //$("body").attr('style', " background: radial-gradient(100% 100% at 0% 0%, rgba(" + color[0] + ", " + color[1] + ", " + color[2] + ", 0.2) 0%, rgba(0, 0, 0, 0) 100%), #2B2B2B;");
        });
        $("#utilisateur_usernameColor").on('input',function(){
            $(".username span").css({"color":$(this).val()});
        });
        $("#utilisateur_usernameColor").on('change',function() {
            let form = $(".username").closest('form');
            var formData = form.serialize() ;

            $.ajax({
                type: "POST",
                url: form.attr('action'),
                data: formData
            });
        });
        }

    disconnect() {
        $("#main").attr('style', " background: transparent");
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}