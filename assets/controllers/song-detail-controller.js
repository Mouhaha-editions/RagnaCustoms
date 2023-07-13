import {Controller} from '@hotwired/stimulus';
import {average} from 'color.js'
import 'jquery'
// import 'jquery-ui/ui/'
import {RagnaBeat} from "../js/ragna-beat/ragnabeat";
import Chart from "chart.js/auto";
require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');


export default class extends Controller {
    static targets = ['img', 'background', 'info','readFeedback']
    ragna = null;

    connect() {
        let file= $(this.infoTarget).data('file');
        let divId= $(this.infoTarget).attr('id');
        // $("#main").attr('style',"transition:all linear 10s 2s;");
        // $("#main").attr('style',"background:#000");
        average(this.imgTarget.src, {amount: 1}).then(color => {
            $("#main").attr('style', "background: radial-gradient(100% 100% at 0% 0%, rgba(" + color[0] + ", " + color[1] + ", " + color[2] + ", 0.7) 0%, rgba(0, 0, 0, 0) 100%), #2B2B2B;background-position:0");
        });
        $(".back-button").attr('href', document.referrer !== undefined ? document.referrer : "#");
        // const swup = new Swup();
        this.ragna = new RagnaBeat();
        this.ragna.startInit(divId, file);
        $(".song-feedback").on("click",function(){
            $("#rating-box").hide("slow",function(){
                $("#feedback-box").show("slow");
            });
        });$(".back-feedback").on("click",function(){
            $("#feedback-box").hide("slow",function(){
                $("#rating-box").show("slow");
            });
        });



        const config = {
            type: 'scatter',
            data: null,
            options: {
                xAxis: {
                    key: 'x'
                },
                yAxis: {
                    key: 'y'
                },
                scales: {
                    y: {
                        suggestedMin: -100,
                        suggestedMax: 100
                    },

                    x: {
                        type: 'linear',
                        position: 'bottom'
                    }
                }
            }
        };
        var canvas = $('#scatter-plot');
        canvas.parent().append("<canvas id='scatter-plot'></canvas>");
        canvas.remove();
        const ctx = $('#scatter-plot');
        var chart = new Chart(ctx,config);
        $('.scatter-open-score').on('click', function(){
            $.ajax({
                url: '/stats/scatter-score/'+$(this).data('score'),
                dataType:'json',
                success: function (response) {
                    chart.data.datasets = [];
                    chart.data = response.datasets;
                    chart.update();
                }
            });
        })
        $('.scatter-open-score-history').on('click', function(){
            $.ajax({
                url: '/stats/scatter-score-history/'+$(this).data('score'),
                dataType:'json',
                success: function (response) {
                    chart.data.datasets = [];
                    chart.data = response.datasets;
                    chart.update();
                }
            })
        });

        $('.circle').each(function(){
            let items =$(this).find('.menuItem');

            for(let i = 0, l = items.length; i < l; i++) {
                items[i].style.left = (50 - 35*Math.cos(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";

                items[i].style.top = (50 + 35*Math.sin(-0.5 * Math.PI - 2*(1/l)*i*Math.PI)).toFixed(4) + "%";
            }
        })
    }

    disconnect() {
        $("#main").attr('style', "background: transparent;background-position:-3000px");
        this.ragna.stopSong();
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}