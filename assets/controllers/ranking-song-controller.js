import {Controller} from '@hotwired/stimulus';
import 'jquery'
import Chart from "chart.js/auto";

require('../js/base');
require('../js/plugins/ajax_link');
require('../js/plugins/rating');

export default class extends Controller {
  static targets = ['img', 'background']

  connect() {
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
    $(document).on('modalformloaded', function() {
      $('input[id^="form_accuracy_"]').each(function() {
        var parentRow = $(this).parent().parent().parent();
        var index = parentRow.data('index');
        var diffId = parentRow.data('diff');
        
        var canvas = $(`#rank_pp_chart_${index}`);
        canvas.parent().append(`<canvas id='rank_pp_chart_${index}'></canvas>`);
        canvas.remove();
        var chart = new Chart($(`#rank_pp_chart_${index}`), Object.assign({}, ppPlotConfig));

        function refreshChart(chart, index, diffId) {
          var accuracy = $(`#form_accuracy_${index}`);
          var leaderboard = $(`#form_leaderboard_${index}`);
          $(`#rank_pp_chart_${index}`).parent().append(`<div class="spinner-overlay" id="pp-plot-spinner-${index}"><div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div></div>`);
          $.ajax({
            url: '/stats/pp-chart/' + leaderboard.val() + '/' + diffId + '?new_average=' + accuracy.val(),
            dataType: 'json',
            success: function (response) {
              chart.data.datasets = [];
              chart.data = response.datasets;
              chart.update();
              $(`#pp-plot-spinner-${index}`).remove();
            }
          });
        }

        $(this).on('change', () => refreshChart(chart, index, diffId));
        $(`#form_leaderboard_${index}`).on('change', () => refreshChart(chart, index, diffId));
        refreshChart(chart, index, diffId);
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
