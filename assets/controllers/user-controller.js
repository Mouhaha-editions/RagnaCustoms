import {Controller} from '@hotwired/stimulus';
import {average} from 'color.js'
import 'jquery'
import Chart from 'chart.js/auto';
import {getRelativePosition} from 'chart.js/helpers';

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
        $("#utilisateur_usernameColor").on('input', function () {
            $(".username span").css({"color": $(this).val()});
        });
        $("#utilisateur_usernameColor").on('change', function () {
            let form = $(".username").closest('form');
            let formData = form.serialize();

            $.ajax({
                type: "POST",
                url: form.attr('action'),
                data: formData
            });
        });
        $("form[name=\"utilisateur\"] input,form[name=\"utilisateur\"] select,form[name=\"utilisateur\"] textarea").on('change', function () {
            let form = $(this).closest('form');
            let formData = form.serialize();
            $.ajax({
                type: "POST",
                url: form.attr('action'),
                data: formData
            });
        });
        $(".reset-api-key").on('click', function () {
            console.log("furet")
            if (confirm('You are going to change your api key, are you sure to continue ? ')) {
                $.ajax({
                    type: "POST",
                    url: '/reset/apikey',
                    dataType: 'json',
                    success: function (data) {
                        $("#ApiKey").val(data.value);
                    }
                });
            }

        });
        const data = [
            {year: 2010, count: 10},
            {year: 2011, count: 20},
            {year: 2012, count: 15},
            {year: 2013, count: 25},
            {year: 2014, count: 22},
            {year: 2015, count: 30},
            {year: 2016, count: 28},
        ];
        const chart = new Chart(document.getElementById('UserChart'), {
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

        console.log(document.getElementById('UserChart'));

    }

    disconnect() {
        $("#main").attr('style', " background: transparent");
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}