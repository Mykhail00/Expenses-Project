import "../css/dashboard.scss"
import Chart from 'chart.js/auto'
import {get} from './ajax'

window.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('yearToDateChart')
    const summaryYear = document.getElementById('summeryYear').value
    loadChart(ctx, summaryYear)

    const startDate = document.querySelector('#startDate').value
    const endDate = document.querySelector('#endDate').value
    loadTotals(startDate, endDate)

    document.querySelector('#summeryYear').addEventListener('change', function (event) {
        const summaryYear = event.target.value;

        loadChart(ctx, summaryYear)
    })

    document.querySelector('#timePeriod').addEventListener('change', function (event) {
        const startDate = document.querySelector('#startDate').value
        const endDate = document.querySelector('#endDate').value

        loadTotals(startDate, endDate)
    })
})

function loadTotals(startDate, endDate) {
    get('/stats/totals', {start: startDate, end: endDate})
        .then(response => response.json())
        .then(response => {
            document.getElementById('expense').innerHTML = '$' + parseFloat(response.expense).toFixed(2);
            document.getElementById('income').innerHTML = '$' + parseFloat(response.income).toFixed(2);

            const netElement = document.getElementById('net');
            const netValue = parseFloat(response.net).toFixed(2);
            netElement.innerHTML = '$' + netValue;

            netElement.classList.toggle('text-success', response.net >= 0);
            netElement.classList.toggle('text-danger', response.net < 0);
        })
        .catch(error => {
            console.error('Error loading totals:', error);
        });
}

function loadChart(ctx, year) {
    get('/stats/ytd', {year: year}).then(response => response.json()).then(response => {
        if (document.chart instanceof Chart) {
            document.chart.destroy()
        }

        let expensesData = Array(12).fill(null)
        let incomeData = Array(12).fill(null)

        response.forEach(({m, expense, income}) => {
            expensesData[m - 1] = expense
            incomeData[m - 1] = income
        })

        document.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Dec'],
                datasets: [
                    {
                        label: 'Expense',
                        data: expensesData,
                        borderWidth: 1,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                    },
                    {
                        label: 'Income',
                        data: incomeData,
                        borderWidth: 1,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        })
    })
}