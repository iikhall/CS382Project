/* Dashboard - jQuery + AJAX ($.getJSON) + Chart.js. */
$(function () {
  var GRADE = {
    'Excellent':  '#2E9E6B',
    'Very Good':  '#5DBF94',
    'Good':       '#E8C547',
    'Acceptable': '#E89545',
    'Fail':       '#D9534F'
  };
  var DOT = {
    'Excellent':  'b-excellent',
    'Very Good':  'b-very-good',
    'Good':       'b-good',
    'Acceptable': 'b-acceptable',
    'Fail':       'b-fail'
  };

  if (window.Chart) {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#8A7A5C';
  }

  // Draws an em-dash beneath x-positions that have no attendance data.
  var gapDashPlugin = {
    id: 'gapDash',
    afterDatasetsDraw: function (chart) {
      var ds = chart.data.datasets[0];
      var meta = chart.getDatasetMeta(0);
      var ctx = chart.ctx;
      ctx.save();
      ctx.fillStyle = '#8A7A5C';
      ctx.font = '600 13px Inter, sans-serif';
      ctx.textAlign = 'center';
      ds.data.forEach(function (v, i) {
        if (v === null || v === undefined) {
          var x = meta.data[i].x;
          ctx.fillText('—', x, chart.scales.y.bottom - 6);
        }
      });
      ctx.restore();
    }
  };

  // Center text for academic donuts (dominant band % + label).
  function centerTextPlugin(pct, label) {
    return {
      id: 'centerText',
      afterDraw: function (chart) {
        var ctx = chart.ctx;
        var x = (chart.chartArea.left + chart.chartArea.right) / 2;
        var y = (chart.chartArea.top + chart.chartArea.bottom) / 2;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = GRADE[label] || '#3D2F12';
        ctx.font = '700 22px Inter, sans-serif';
        ctx.fillText(pct + '%', x, y - 8);
        ctx.fillStyle = '#8A7A5C';
        ctx.font = '500 12px Inter, sans-serif';
        ctx.fillText(label, x, y + 14);
        ctx.restore();
      }
    };
  }

  function renderAttendance(att) {
    var el = document.getElementById('attendanceChart');
    if (!el || !window.Chart) { return; }
    $('#attendanceEmpty').prop('hidden', true);
    new Chart(el, {
      type: 'line',
      data: {
        labels: att.labels,
        datasets: [{
          label: 'Attendance %',
          data: att.values,
          borderColor: '#4CAF82',
          backgroundColor: 'rgba(76,175,130,0.15)',
          fill: true,
          tension: 0.35,
          spanGaps: false,
          pointBackgroundColor: '#4CAF82',
          pointRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } } },
          x: { grid: { display: false } }
        }
      },
      plugins: [gapDashPlugin]
    });
  }

  function renderAcademic(groups) {
    var $wrap = $('#academicGroups');
    $('#academicLoading').remove();
    if (!groups.length) {
      $wrap.append('<p class="subtle">No academic data available.</p>');
      return;
    }
    groups.forEach(function (g, gi) {
      var $group = $('<div class="donut-group"></div>');
      $group.append($('<div class="donut-group-title"></div>').text(g.group));
      var $grid = $('<div class="donut-grid"></div>');

      g.donuts.forEach(function (d, di) {
        var cid = 'donut-' + gi + '-' + di;
        var $card = $('<div class="donut-card"></div>');
        $card.append($('<h4></h4>').text(d.subject));
        if (d.teacher) {
          $card.append($('<p class="subtle donut-teacher"></p>').text('Teacher: ' + d.teacher));
        }
        $card.append('<div class="donut-canvas-box"><canvas id="' + cid + '"></canvas></div>');

        var $legend = $('<ul class="donut-legend"></ul>');
        Object.keys(d.bands).forEach(function (band) {
          var total = Object.keys(d.bands).reduce(function (a, k) { return a + d.bands[k]; }, 0) || 1;
          var pct = Math.round(d.bands[band] * 100 / total);
          $legend.append(
            '<li><span class="legend-dot ' + DOT[band] + '"></span>' +
            band + '<span class="legend-pct">' + pct + '%</span></li>'
          );
        });
        $card.append($legend);
        $grid.append($card);

        // Defer chart creation until the canvas is in the DOM.
        setTimeout(function () {
          var el = document.getElementById(cid);
          if (!el || !window.Chart) { return; }
          new Chart(el, {
            type: 'doughnut',
            data: {
              labels: Object.keys(d.bands),
              datasets: [{
                data: Object.values(d.bands),
                backgroundColor: Object.keys(d.bands).map(function (b) { return GRADE[b]; }),
                borderWidth: 0
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              cutout: '70%',
              plugins: { legend: { display: false }, tooltip: { enabled: true } }
            },
            plugins: [centerTextPlugin(d.dominant_pct, d.dominant)]
          });
        }, 0);
      });

      $group.append($grid);
      $wrap.append($group);
    });
  }

  $.getJSON('api/dashboard_data.php')
    .done(function (res) {
      if (!res || !res.ok) { return; }
      renderAttendance(res.attendance);
      renderAcademic(res.academic);
    })
    .fail(function () {
      $('#attendanceEmpty').prop('hidden', false).text('Failed to load chart data.');
      $('#academicLoading').text('Failed to load academic data.');
    });
});
