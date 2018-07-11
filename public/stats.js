var allTimeSeries = {};
var allValueLabels = {};
var descriptions = {
    'Processes': {
        'r': '等待CPU时间片的进程数',
        'b': '等待资源的进程数'
    },
    'Memory': {
        'swpd': '切换到内存交换区的内存数(k)',
        'free': '空闲内存数(k)',
        'buff': 'buffer缓存的内存数',
        'cache': 'page缓存的内存数'
    },
    'Swap': {
        'si': '从磁盘交换的内存量',
        'so': '交换到磁盘的内存量'
    },
    'IO': {
        'bi': '读磁盘 (blocks/s)',
        'bo': '写磁盘 (blocks/s)'
    },
    'System': {
        'in': '每秒中断次数，包括时钟',
        'cs': '每秒上下文切换次数'
    },
    'CPU': {
        'us': '运行非内核代码所花费的时间',
        'sy': '运行内核代码花费的时间',
        'id': '空闲时间',
        'wa': 'IO等待时间'
    }
};

function streamStats() {

    var ws = new ReconnectingWebSocket("ws://192.168.33.10:9100");
    // var ws = new ReconnectingWebSocket("ws://139.199.6.179:9100");

    ws.onopen = function () {
        console.log('connect');
    };

    ws.onclose = function () {
        console.log('disconnect');
    };

    ws.onmessage = function (e) {
        var keys = ['r', 'b', 'swpd', 'free', 'buff', 'cache', 'si', 'so', 'bi', 'bo', 'in', 'cs', 'us', 'sy', 'id', 'wa', 'st'];
        var values = e.data.trim().split(',');
        console.log(values);
        if (keys.length == values.length) {
            var stats = [];
            for (i = 0; i < keys.length; i++) {
                stats[keys[i]] = parseInt(values[i]);
            }
            receiveStats(stats);
        }
    };
}

function initCharts() {
    Object.each(descriptions, function (sectionName, values) {
        var section = $('.chart.template').clone().removeClass('template').appendTo('#charts');

        section.find('.title').text(sectionName);

        var smoothie = new SmoothieChart({
            grid: {
                sharpLines: true,
                verticalSections: 5,
                strokeStyle: 'rgba(119,119,119,0.45)',
                millisPerLine: 1000
            },
            minValue: 0,
            labels: {
                disabled: true
            }
        });
        smoothie.streamTo(section.find('canvas').get(0), 1000);

        var colors = chroma.brewer['Pastel2'];
        var index = 0;
        Object.each(values, function (name, valueDescription) {
            var color = colors[index++];

            var timeSeries = new TimeSeries();
            smoothie.addTimeSeries(timeSeries, {
                strokeStyle: color,
                fillStyle: chroma(color).darken().alpha(0.5).css(),
                lineWidth: 3
            });
            allTimeSeries[name] = timeSeries;

            var statLine = section.find('.stat.template').clone().removeClass('template').appendTo(section.find('.stats'));
            statLine.attr('title', valueDescription).css('color', color);
            statLine.find('.stat-name').text(name);
            allValueLabels[name] = statLine.find('.stat-value');
        });
    });
}

function receiveStats(stats) {
    Object.each(stats, function (name, value) {
        var timeSeries = allTimeSeries[name];
        if (timeSeries) {
            timeSeries.append(Date.now(), value);
            allValueLabels[name].text(value);
        }
    });
}

$(function () {
    initCharts();
    streamStats();
});