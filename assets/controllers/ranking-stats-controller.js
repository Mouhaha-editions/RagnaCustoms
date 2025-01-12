import {Controller} from '@hotwired/stimulus';
import 'jquery'
import Chart from "chart.js/auto";

require('../js/base');

export default class extends Controller {
    static values = {
      leaderboard: String,
      user: String
    }

    connect() {
      const ppHistogramConfig = {
        type: 'bar',
        options: {
          xAxis: {
            key: 'x'
          },
          yAxis: {
            key: 'y'
          },
          plugins: {
            legend: {
              display: false
            },
          },
          scales: {
            y: {
              title: {
                display: true,
                text: '# of scores',
              },
              ticks: {
                type: 'linear',
                min: 0,
                stepSize: 1
              },
              grace: 0,
              bounds: 'data',
              position: 'left',
            },
            x: {
              title: {
                display: true,
                text: 'PP',
              },
              suggestedMin: 0,
              suggestedMax: 800,
              type: 'linear',
              grace: 0,
              bounds: 'data',
              position: 'bottom',
            }
          }
        }
      };
      var canvas = $('#pp-histogram');
      canvas.parent().append("<canvas id='pp-histogram'></canvas>");
      canvas.remove();
      var chart = new Chart($('#pp-histogram'), ppHistogramConfig);
      $('#pp-histogram').parent().append('<div class="spinner-overlay" id="pp-histogram-spinner"><div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div></div>');
      $.ajax({
        url: '/stats/pp-histogram/' + this.leaderboardValue + '/' + this.userValue,
        dataType: 'json',
        success: function (response) {
          chart.data.datasets = [];
          chart.data = response.datasets;
          chart.update();
          $('#pp-histogram-spinner').remove();
        }
      });

      const ppPlotConfig = {
        options: {
          interaction: {
            intersect: false,
            mode: 'nearest',
            axes: 'x'
          },
          xAxis: {
            key: 'x'
          },
          yAxis: {
            key: 'y'
          },
          plugins: {
            tooltip: {
              callbacks: {
                title: function(ctx) {
                  if (ctx[0].dataset.label == 'est. average' || ctx[0].dataset.label == 'act. average') {
                    return ctx[0].dataset.label;
                  }
                  return ctx[0].label + 'm';
                },
                label: function(ctx) {
                  if (ctx.dataset.label == 'est. average' || ctx.dataset.label == 'act. average') {
                    return ctx.raw.accuracy + '% accuracy';
                  }
                  let label = (ctx.dataset.label == 'players' ? ctx.raw.username : ctx.dataset.label) || '';
                  if (label) {
                    label += ': ';
                  }
                  if (ctx.formattedValue !== null) {
                    label += ctx.formattedValue + ' PP';
                  }
                  return label;
                }
              }
            }
          },
          scales: {
            y: {
              title: {
                display: true,
                text: 'PP',
              },
              type: 'linear',
              beginAtZero: true,
              grace: 0,
              bounds: 'data',
              position: 'left',
            },
            x: {
              title: {
                display: true,
                text: 'distance',
              },
              type: 'linear',
              grace: 0,
              bounds: 'data',
              position: 'bottom'
            }
          }
        }
      };
      var canvas = $('#pp-plot');
      canvas.parent().append("<canvas id='pp-plot'></canvas>");
      canvas.remove();
      var chart2 = new Chart($('#pp-plot'), ppPlotConfig);
      $('.pp-chart').on('click', function () {
        $('#pp-plot').parent().append('<div class="spinner-overlay" id="pp-plot-spinner"><div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div></div>');
        $.ajax({
          url: '/stats/pp-chart/' + $(this).data('leaderboard') + '/' + $(this).data('diff') + '?highlight_user=' + $(this).data('user'),
          dataType: 'json',
          success: function (response) {
            chart2.data.datasets = [];
            chart2.data = response.datasets;
            chart2.update();
            $('#pp-plot-spinner').remove();
          }
        });
      });
    }
}