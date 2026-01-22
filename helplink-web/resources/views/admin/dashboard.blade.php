@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')

<style>
/* ===================== CHART SIZING ===================== */
.chart-box {
    height: 260px;
    position: relative;
}

.chart-box-sm {
    height: 220px;
    position: relative;
}

/* ===================== CENTER DONUT PERFECTLY ===================== */
.chart-center {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

/* ===================== ACTIVITY SCROLL ===================== */
.activity-scroll {
    max-height: 320px;
    overflow-y: auto;
}

/* ================= DASHBOARD FIX ================= */
.dashboard-wrapper {
    width: 100%;
    overflow-x: hidden;
}

.dashboard-wrapper .row {
    margin-left: 0;
    margin-right: 0;
}

</style>

<div class="container-fluid py-4">
    <div class="dashboard-wrapper">

    {{-- ================= HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h2 class="fw-bold text-primary mb-0">Dashboard</h2>
            <small class="text-muted">
                Overview of system performance and recent activities
            </small>
        </div>

        <select id="rangeFilter" class="form-select form-select-sm w-auto">
            <option value="7">Last 7 days</option>
            <option value="14">Last 14 days</option>
            <option value="30">Last 30 days</option>
        </select>
    </div>

    {{-- ================= INSIGHT ================= --}}
    <div class="alert alert-info small py-2 mb-4" id="dashboardInsight">
        Loading system insight...
    </div>

    {{-- ================= STATS ================= --}}
    <div class="row g-3 mb-4">
        @php
            $stats = [
                ['id'=>'totalUsers','icon'=>'users','color'=>'primary','label'=>'Total Users'],
                ['id'=>'totalRequests','icon'=>'inbox','color'=>'warning','label'=>'All Requests'],
                ['id'=>'pendingRequests','icon'=>'clock','color'=>'warning','label'=>'Pending Requests'],
                ['id'=>'approvedRequests','icon'=>'check-circle','color'=>'success','label'=>'Approved Requests'],
                ['id'=>'fulfilledRequests','icon'=>'handshake','color'=>'info','label'=>'Fulfilled Requests'],
                ['id'=>'totalOffers','icon'=>'gift','color'=>'primary','label'=>'Total Offers'],
                ['id'=>'activeOffers','icon'=>'box-open','color'=>'success','label'=>'Active Offers'],
                ['id'=>'offerClaims','icon'=>'clipboard-check','color'=>'warning','label'=>'Offer Claims'],
            ];
        @endphp

        @foreach($stats as $s)
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 rounded-4 text-center">
                <div class="card-body py-3">
                    <i class="fas fa-{{ $s['icon'] }} fs-5 text-{{ $s['color'] }} mb-1"></i>
                    <h6 class="text-muted small mb-1">{{ $s['label'] }}</h6>
                    <h4 class="fw-bold mb-0">
                        <span id="{{ $s['id'] }}">0</span>
                    </h4>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ================= CHARTS ================= --}}
    <div class="row g-4">

        {{-- REQUEST TREND --}}
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">Requests Trend</h6>
                    <small class="text-muted d-block mb-2">
                        Number of requests submitted over selected period
                    </small>
                    <div class="chart-box">
                        <canvas id="requestTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- OFFERS VS CLAIMS --}}
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">Offers vs Claims</h6>
                    <small class="text-muted d-block mb-2">
                        Comparison between offers and successful claims
                    </small>
                    <div class="chart-box-sm">
                        <canvas id="offerClaimChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- REQUEST STATUS --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white">
                    <h6 class="fw-semibold mb-0">Request Status</h6>
                    <small class="text-muted">
                        Distribution of requests by current status
                    </small>
                </div>

                <div class="card-body">
                    <div class="chart-box-sm chart-center">
                        <canvas id="requestStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- LATEST ACTIVITIES --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white fw-semibold">
                    Latest Activities
                    <p class="text-muted small mb-0">
                        Recent actions performed within the system
                    </p>
                </div>

                <div class="card-body p-0">
                    <ul class="list-group list-group-flush activity-scroll" id="activityList">
                        <li class="list-group-item text-muted text-center small">
                            Loading activities...
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
    </div> <!-- dashboard-wrapper -->
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let trendChart, statusChart, offerChart;

async function fetchDashboardStats() {
    const range = rangeFilter.value;
    const res = await fetch(`{{ route('admin.dashboard.stats') }}?range=${range}`);
    const json = await res.json();
    if (!json.success) return;

    const d = json.data;

    totalUsers.innerText = d.total_users;
    totalRequests.innerText = d.total_requests;
    pendingRequests.innerText = d.pending_requests;
    approvedRequests.innerText = d.approved_requests;
    fulfilledRequests.innerText = d.fulfilled_requests;
    totalOffers.innerText = d.total_offers;
    activeOffers.innerText = d.active_offers;
    offerClaims.innerText = d.offer_claims;

    generateInsight(d);
    renderCharts(d.charts);
}

function generateInsight(d) {
    let text = 'System is operating normally. ';
    if (d.pending_requests > d.approved_requests) {
        text += 'There are pending requests requiring review. ';
    }
    if (d.offer_claims > 0) {
        text += 'Offers are actively being claimed.';
    }
    dashboardInsight.innerText = text;
}

function renderCharts(charts) {

    trendChart?.destroy();
    statusChart?.destroy();
    offerChart?.destroy();

    trendChart = new Chart(requestTrendChart, {
        type: 'line',
        data: {
            labels: charts.request_trend.map(i => i.date),
            datasets: [{
                data: charts.request_trend.map(i => i.total),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.12)',
                fill: true,
                tension: 0.4,
                pointRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    offerChart = new Chart(offerClaimChart, {
        type: 'bar',
        data: {
            labels: ['Offers','Claims'],
            datasets: [{
                data: [
                    charts.offer_vs_claim.offers,
                    charts.offer_vs_claim.claims
                ],
                backgroundColor: ['#3b82f6','#f97316'],
                barThickness: 28,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    statusChart = new Chart(requestStatusChart, {
        type: 'doughnut',
        data: {
            labels: ['Pending','Approved','Fulfilled'],
            datasets: [{
                data: [
                    charts.request_status.pending,
                    charts.request_status.approved,
                    charts.request_status.fulfilled
                ],
                backgroundColor: ['#facc15','#22c55e','#3b82f6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16 }
                }
            }
        }
    });
}

async function fetchLatestActivities() {
    const res = await fetch(`{{ route('admin.dashboard.activities') }}`);
    const json = await res.json();
    if (!json.success) return;

    activityList.innerHTML = '';

    if (!json.data.length) {
        activityList.innerHTML =
            `<li class="list-group-item text-muted text-center small">No recent activities</li>`;
        return;
    }

    json.data.forEach(a => {
        activityList.innerHTML += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong class="small">${a.message}</strong><br>
                    <span class="badge bg-${a.badge} small">${a.status}</span>
                </div>
                <small class="text-muted">${a.time}</small>
            </li>`;
    });
}

fetchDashboardStats();
fetchLatestActivities();
setInterval(fetchLatestActivities, 15000);
rangeFilter.addEventListener('change', fetchDashboardStats);
</script>

<script>
/* ================= FINAL & CORRECT FIX ================= */

function observeChart(chart, canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!chart || !canvas) return;

    const wrapper = canvas.parentElement;

    const ro = new ResizeObserver(() => {
        chart.resize();
    });

    ro.observe(wrapper);
}

/* Tunggu chart siap render */
setTimeout(() => {
    observeChart(trendChart, 'requestTrendChart');
    observeChart(offerChart, 'offerClaimChart');
    observeChart(statusChart, 'requestStatusChart');
}, 300);
</script>



@endsection
