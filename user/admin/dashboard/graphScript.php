<script>
    const room = document.getElementById('roomChart').getContext('2d');
    const roomChart = new Chart(room, {
        type: 'bar',
        data: {
            labels: <?= $labels_room ?>,
            datasets: [{
                label: 'Number of Reservations',
                data: <?= $data_room ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Most Booked / Reserved Rooms'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    const ctx = document.getElementById('reservationChart').getContext('2d');
    const reservationChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $labels ?>,
            datasets: [{
                label: 'Number of Reservations',
                data: <?= $data ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });



    const income = document.getElementById('incomeChart').getContext('2d');
    const incomeChart = new Chart(income, {
        type: 'bar',
        data: {
            labels: <?= $labels_income ?>,
            datasets: [{
                label: 'Total Income',
                data: <?= $data_income ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Monthly Income for Year <?= $year ?>'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    const services = document.getElementById('serviceChart').getContext('2d');
    const serviceChart = new Chart(services, {
        type: 'bar',
        data: {
            labels: <?= $labels_service ?>,
            datasets: [{
                label: 'Number of Avails',
                data: <?= $data_service ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Most Availed Services'
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    stepSize: 1
                }
            }
        }
    });
</script>