import {Controller} from '@hotwired/stimulus';
import {average} from 'color.js'
import 'jquery'
import Chart from 'chart.js/auto';
import {getRelativePosition} from 'chart.js/helpers';

require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');
require('../js/plugins/copy_to_clipboard');

export default class extends Controller {
    static targets = ['background', 'info']
    ragna = null;

    connect() {

        var canvas2 = $('#session-history');
        canvas2.parent().append("<canvas id='session-history'></canvas>");
        canvas2.remove();
        const ctx2 = $('#session-history');
        const chart = new Chart(ctx2, {
            type: 'line',
            options: {
                scales: {
                    y: {
                        // stacked: true,
                        beginAtZero: true

                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        // grid line settings
                        grid: {
                            drawOnChartArea: false, // only want the grid lines for one axis to show up
                        },
                    },
                },
                plugins: {
                    filler: {
                        propagate: false
                    },
                    'samples-filler-analyser': {
                        target: 'chart-analyser'
                    }
                },
                interaction: {
                    intersect: false,
                },
            },
            dataset: null
        });


        $("[data-toggle=tooltip]").tooltip();
        $(".more-stat").on('click', function () {
            $.ajax({
                url: '/user/more-stats',
                data: {
                    diff: $(this).data('song-difficulty')
                },
                success: function (response) {
                    chart.data.labels = [];
                    chart.data.datasets = [];
                    for (var i = 1; response.dataset[0].data.length >= i; i++) {
                        chart.data.labels.push("Session " + i);
                    }
                    chart.data.datasets = response.dataset;
                    chart.update();
                }
            })
        });

        const config = {
            type: 'scatter',
            data: null,
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                return "Drum "+tooltipItem.raw.drum+" : "+tooltipItem.raw.y+"ms";
                            }
                        }
                    },
                },
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
        const chart2 = new Chart(ctx, config);
        $('.scatter-open-score').on('click', function () {
            $.ajax({
                url: '/stats/scatter-score/' + $(this).data('score'),
                dataType: 'json',
                success: function (response) {
                    chart2.data.datasets = [];
                    chart2.data = response.datasets;
                    chart2.update();
                }
            });
        })
        $('.scatter-open-score-history').on('click', function () {
            $("#ScatterView .modal-header .modal-title").html($(this).data('title'));
            $.ajax({
                url: '/stats/scatter-score-history/' + $(this).data('score'),
                dataType: 'json',
                success: function (response) {
                    chart2.data.datasets = [];
                    chart2.data = response.datasets;
                    chart2.update();
                }
            })
        });
        $('.session-open-score-history').on('click', function () {
            $("#SessionHistoryView .modal-header .modal-title").html($(this).data('title'));
            $.ajax({
                url: '/user/more-stats',
                data: {
                    diff: $(this).data('song-difficulty')
                },
                success: function (response) {
                    chart.data.labels = [];
                    chart.data.datasets = [];
                    for (var i = 1; response.datasets.datasets[0].data.length >= i; i++) {
                        chart.data.labels.push("Session " + i);
                        console.log( "Session " + i)
                    }
                    chart.data.datasets = response.datasets.datasets;
                    chart.update();
                }
            });
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
        $("#main").attr('style', " background: transparent");
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}
