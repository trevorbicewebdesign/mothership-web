document.addEventListener('DOMContentLoaded', function() {
    var data = {
        labels: RSFirewallChartLabels,
        datasets: [
            {
                label: Joomla.JText._('COM_RSFIREWALL_LEVEL_LOW'),
                backgroundColor: 'rgb(146,255,99)',
                borderColor: 'rgb(146,255,99)',
                data: []
            },
            {
                label: Joomla.JText._('COM_RSFIREWALL_LEVEL_MEDIUM'),
                backgroundColor: 'rgb(255,161,99)',
                borderColor: 'rgb(255,161,99)',
                data: []
            },
            {
                label: Joomla.JText._('COM_RSFIREWALL_LEVEL_HIGH'),
                backgroundColor: 'rgb(255, 99, 132)',
                borderColor: 'rgb(255, 99, 132)',
                data: []
            },
            {
                label: Joomla.JText._('COM_RSFIREWALL_LEVEL_CRITICAL'),
                backgroundColor: '#FF2D55',
                borderColor: '#ff0233',
                data: []
            }
        ]
    };

    for (var i = 0; i < RSFirewallChartDatasets.length; i++)
    {
        data.datasets[0].data.push(RSFirewallChartDatasets[i].low);
        data.datasets[1].data.push(RSFirewallChartDatasets[i].medium);
        data.datasets[2].data.push(RSFirewallChartDatasets[i].high);
        data.datasets[3].data.push(RSFirewallChartDatasets[i].critical);
    }

    new Chart(
        document.getElementById('com-rsfirewall-logs-chart'),
        {
            type: 'line',
            data: data,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    );
});