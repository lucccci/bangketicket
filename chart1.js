var ctx = document.getElementById('lineChart').getContext('2d');

var myChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Monthly Revenue',
            data: [150000, 130000, 950000, 200000, 100000, 150000, 120000, 150000, 200000, 130000, 150000, 13000],
            backgroundColor: ['rgba(3, 31, 78, 1)'],
            borderColor: ['rgba(3, 31, 78, 1)'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true
    }
});
