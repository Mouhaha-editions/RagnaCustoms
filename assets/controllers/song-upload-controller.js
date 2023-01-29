import {Controller} from '@hotwired/stimulus';
import 'jquery'
const Swal = require('sweetalert2/dist/sweetalert2.js');

import 'select2/dist/js/select2.full.min';
require('../../public/bundles/tetranzselect2entity/js/select2entity');
require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');

export default class extends Controller {
    static targets = ['img', 'background']
    ragna = null;

    connect() {
        $('.select2entity[data-autostart="true"]').select2entity();
        $('.select2').select2();
        $(document).on('mousedown','.switch',function(){
            let enabled = $(this).find('.enable-song').is(':checked');
            if(!enabled){
                if(confirm('You are going to enable this song at the programmed date, are you sure to continue ?')){
                    $(this).find('.enable-song').prop('checked',true);
                    $(this).find('.enable-song').trigger('change');
                    return true;
                }
                return false;
            }

        });
        $('.enable-song').on('change',function(){
            let song = $(this).data('song');
            let checked = $(this).is(':checked');
            $.ajax({
                url: '/upload/song/toggle/'+song,
                data:{
                    song: song,
                    checked : checked
                },
                type:'post',
                dataType:'json',
                success: function(data){
                    if(!data.success){
                        $(this).find('.enable-song').prop('checked',false);
                        Swal.fire({
                            title: 'Activation error',
                            html: data.message,
                            icon:"error",
                            confirmButtonText: 'close'
                        });
                    }
                }
            });
        });

    }

    disconnect() {
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}