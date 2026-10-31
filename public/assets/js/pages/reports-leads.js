/* Page script for reports-leads */
let repReportTable = null;
let sourceBarChart = null;
let funnelAreaChart = null;
let statusDonutChart = null;
let pipelineBarChart = null;

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Flatpickr Date Range Picker
    const reportDateRangeInput = document.querySelector('#reportDateRange');
    
    let defaultStartDate = new Date();
    defaultStartDate.setMonth(defaultStartDate.getMonth() - 6);
    let defaultEndDate = new Date();

    if (reportDateRangeInput && typeof flatpickr !== 'undefined') {
        flatpickr(reportDateRangeInput, {
            mode: "range",
            dateFormat: "d M Y",
            defaultDate: [defaultStartDate, defaultEndDate]
        });
    }

    // 2. Initialize DataTable (empty for now, will be populated via AJAX)
    repReportTable = $('#repPerformanceTable').DataTable({
        responsive: false,
        scrollX: false,
        autoWidth: false,
        pageLength: 10,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Filter report records..."
        },
        data: [],
        columns: [
            { data: 'name', className: 'fw-bold text-dark fs-13' },
            { data: 'inforce', className: 'fs-13' },
            { 
                data: 'conversion_rate', 
                className: 'fs-13 fw-bold text-success',
                render: function(data, type, row) {
                    return data + '%';
                }
            },
            { 
                data: 'anp', 
                className: 'fs-13 fw-bold text-success',
                render: function(data, type, row) {
                    return '$' + parseFloat(data).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            }
        ]
    });

    // 3. Define Chart Options (Charts will be initialized after fetch)
    const generationOptions = {
        chart: { type: "line", height: 260, toolbar: { show: false } },
        colors: ["#0B2545"],
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 5 },
        legend: { show: false },
        noData: { text: 'No data available' }
    };

    const areaOptions = {
        chart: { type: "area", height: 260, toolbar: { show: false } },
        colors: ["#00A8B5"],
        fill: { type: "gradient", gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        noData: { text: 'No data available' }
    };

    const donutOptions = {
        chart: { type: "donut", height: 260 },
        colors: ["#00A8B5", "#22C55E", "#64748B", "#F59E0B", "#EF4444", "#8B5CF6"],
        labels: [],
        dataLabels: { enabled: true, dropShadow: { enabled: false } },
        legend: { position: 'bottom' },
        noData: { text: 'No data available' }
    };

    const pipelineBarOptions = {
        chart: { type: "bar", height: 260, toolbar: { show: false } },
        colors: ["#00A8B5"],
        plotOptions: { bar: { borderRadius: 4, horizontal: true, distributed: true } },
        dataLabels: { enabled: true },
        legend: { show: false },
        xaxis: { categories: [] },
        noData: { text: 'No data available' }
    };

    // 4. Fetch Data Function
    function fetchReportData() {
        const dateRange = document.querySelector('#reportDateRange')?.value || '';
        const advisorId = document.querySelector('#reportAdvisorSelect')?.value || '';
        const sourceId = document.querySelector('#reportSourceSelect')?.value || '';

        fetch('/crm/reports/data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                date_range: dateRange,
                advisor_id: advisorId,
                source_id: sourceId
            })
        })
        .then(response => response.json())
        .then(data => {
            // Update Generation Chart
            try {
                const barEl = document.querySelector("#reportSourceBarChart");
                if (barEl && typeof ApexCharts !== "undefined") {
                    if (!sourceBarChart) {
                        generationOptions.series = [{ name: 'Clients Generated', data: data.generationOverTime.data }];
                        generationOptions.xaxis = { categories: data.generationOverTime.categories };
                        sourceBarChart = new ApexCharts(barEl, generationOptions);
                        sourceBarChart.render();
                    } else {
                        sourceBarChart.updateOptions({ xaxis: { categories: data.generationOverTime.categories } });
                        sourceBarChart.updateSeries([{ name: 'Clients Generated', data: data.generationOverTime.data }]);
                    }
                }
            } catch(e) { console.error("Error rendering generation chart", e); }

            // Update Area Chart
            try {
                const areaEl = document.querySelector("#reportFunnelAreaChart");
                if (areaEl && typeof ApexCharts !== "undefined") {
                    if (!funnelAreaChart) {
                        areaOptions.series = [{ name: 'Conversion %', data: data.funnelChart.data }];
                        areaOptions.xaxis = { categories: data.funnelChart.categories };
                        funnelAreaChart = new ApexCharts(areaEl, areaOptions);
                        funnelAreaChart.render();
                    } else {
                        funnelAreaChart.updateOptions({ xaxis: { categories: data.funnelChart.categories } });
                        funnelAreaChart.updateSeries([{ name: 'Conversion %', data: data.funnelChart.data }]);
                    }
                }
            } catch(e) { console.error("Error rendering funnel area chart", e); }

            // Update Status Donut Chart
            try {
                const donutEl = document.querySelector("#reportStatusDonutChart");
                if (donutEl && typeof ApexCharts !== "undefined") {
                    const dData = data.statusDistribution.data;
                    const dCats = data.statusDistribution.categories;
                    
                    // Fallback to prevent ApexCharts donut crash on empty data
                    const safeData = dData.length > 0 ? dData : [0.1];
                    const safeCats = dCats.length > 0 ? dCats : ['No Data'];
                    
                    if (!statusDonutChart) {
                        donutOptions.series = safeData;
                        donutOptions.labels = safeCats;
                        statusDonutChart = new ApexCharts(donutEl, donutOptions);
                        statusDonutChart.render();
                    } else {
                        statusDonutChart.updateOptions({ labels: safeCats });
                        statusDonutChart.updateSeries(safeData);
                    }
                }
            } catch(e) { console.error("Error rendering donut chart", e); }

            // Update Pipeline Funnel Chart
            try {
                const pipelineEl = document.querySelector("#reportPipelineBarChart");
                if (pipelineEl && typeof ApexCharts !== "undefined") {
                    if (!pipelineBarChart) {
                        pipelineBarOptions.series = [{ name: 'Count', data: data.pipelineSnapshot.data }];
                        pipelineBarOptions.xaxis = { categories: data.pipelineSnapshot.categories };
                        pipelineBarChart = new ApexCharts(pipelineEl, pipelineBarOptions);
                        pipelineBarChart.render();
                    } else {
                        pipelineBarChart.updateOptions({ xaxis: { categories: data.pipelineSnapshot.categories } });
                        pipelineBarChart.updateSeries([{ name: 'Count', data: data.pipelineSnapshot.data }]);
                    }
                }
            } catch(e) { console.error("Error rendering pipeline chart", e); }

            // Update DataTable
            if (repReportTable) {
                repReportTable.clear();
                repReportTable.rows.add(data.performanceTable);
                repReportTable.draw();
            }
        })
        .catch(error => console.error('Error fetching report data:', error));
    }

    // Initial fetch
    fetchReportData();

    // 4b. Setup Polling (Automatically refresh data every 30 seconds)
    setInterval(fetchReportData, 30000);

    // 5. Filter Apply Button Feedback & Action
    const btnApply = document.querySelector('#btnApplyReportFilter');
    if (btnApply) {
        btnApply.addEventListener('click', function () {
            fetchReportData();

            btnApply.classList.add('btn-success');
            btnApply.innerHTML = '<i class="feather-check me-1"></i> Applied';
            setTimeout(() => {
                btnApply.classList.remove('btn-success');
                btnApply.innerHTML = '<i class="feather-filter me-1"></i> Apply Filters';
            }, 1200);
        });
    }
});

document.getElementById('mobile-collapse')?.addEventListener('click', function () {
    document.getElementById('mainSidebar')?.classList.toggle('mob-navigation-active');
});
