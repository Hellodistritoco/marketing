<?php
require_once 'config.php';


// Verificar si hay sesión activa
$user_logged_in = isset($_SESSION['user_id']);
if (!$user_logged_in) {
    header('Location: index.php');
    exit();
}

$user_data = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'] ?? $_SESSION['username']
];
<!-- reportes.php -->
<?php
include 'dashboard_reports.php'; // o require 'dashboard_reports.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ejecutivo - Marketing Kanban</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Ejecutivo */
        .executive-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        .executive-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .header-left h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .header-left .subtitle {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .header-right {
            text-align: right;
        }

        .last-update {
            opacity: 0.8;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-light {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .btn-light:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .kpi-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--accent-color, #667eea);
        }

        .kpi-card.success { --accent-color: #28a745; }
        .kpi-card.warning { --accent-color: #ffc107; }
        .kpi-card.danger { --accent-color: #dc3545; }
        .kpi-card.info { --accent-color: #17a2b8; }
        .kpi-card.primary { --accent-color: #667eea; }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .kpi-title {
            font-size: 0.9em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .kpi-icon {
            font-size: 2em;
            opacity: 0.7;
        }

        .kpi-value {
            font-size: 2.8em;
            font-weight: 300;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .kpi-trend {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
        }

        .trend-up { color: #28a745; }
        .trend-down { color: #dc3545; }
        .trend-neutral { color: #6c757d; }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .chart-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .chart-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
        }

        .chart-period {
            display: flex;
            gap: 5px;
        }

        .period-btn {
            padding: 6px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.3s;
        }

        .period-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .chart-container {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
            position: relative;
        }

        /* Progress Rings */
        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            position: relative;
        }

        .progress-ring svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .progress-ring circle {
            fill: none;
            stroke-width: 8;
            stroke-linecap: round;
        }

        .progress-ring .bg {
            stroke: #e9ecef;
        }

        .progress-ring .progress {
            stroke: var(--accent-color, #667eea);
            stroke-dasharray: 0 251;
            transition: stroke-dasharray 1s ease-in-out;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.4em;
            font-weight: 600;
            color: var(--accent-color, #667eea);
        }

        /* Tables */
        .data-tables {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
        }

        .table-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #f1f3f4;
        }

        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 0.8s ease-in-out;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-excellent { background: #d4edda; color: #155724; }
        .badge-good { background: #d1ecf1; color: #0c5460; }
        .badge-average { background: #fff3cd; color: #856404; }
        .badge-poor { background: #f8d7da; color: #721c24; }

        /* Insights Panel */
        .insights-panel {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .insights-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .insights-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            color: white;
        }

        .insights-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #2c3e50;
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .insight-item {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid var(--accent-color, #667eea);
        }

        .insight-item.positive { --accent-color: #28a745; }
        .insight-item.warning { --accent-color: #ffc107; }
        .insight-item.negative { --accent-color: #dc3545; }

        .insight-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 8px;
        }

        .insight-value {
            font-size: 1.1em;
            font-weight: 600;
            color: #2c3e50;
        }

        /* Loading States */
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .data-tables {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            
            .executive-header {
                padding: 20px;
            }
            
            .header-left h1 {
                font-size: 2em;
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-up {
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Print Styles */
        @media print {
            body { background: white; }
            .btn, .header-actions { display: none; }
            .executive-header { background: #2c3e50 !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Executive Header -->
        <div class="executive-header fade-in">
            <div class="header-content">
                <div class="header-left">
                    <h1>📊 Dashboard Ejecutivo</h1>
                    <div class="subtitle">Panel de Control de Marketing - <?php echo htmlspecialchars($user_data['full_name']); ?></div>
                </div>
                <div class="header-right">
                    <div class="last-update" id="lastUpdate">
                        Última actualización: <span id="updateTime">Cargando...</span>
                    </div>
                    <div class="header-actions">
                        <a href="index.php" class="btn btn-light">← Volver al Sistema</a>
                        <a href="reports_page.php" class="btn btn-light">📋 Reportes Detallados</a>
                        <button class="btn btn-primary" onclick="refreshDashboard()">🔄 Actualizar</button>
                        <button class="btn btn-light" onclick="window.print()">🖨️ Imprimir</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid" id="kpiGrid">
            <div class="kpi-card primary slide-up">
                <div class="kpi-header">
                    <div class="kpi-title">Total Clientes</div>
                    <div class="kpi-icon">👥</div>
                </div>
                <div class="kpi-value" id="totalClients">
                    <div class="loading-spinner"></div>
                </div>
                <div class="kpi-trend">
                    <span class="trend-neutral" id="clientsTrend">●</span>
                    <span id="clientsChange">Calculando...</span>
                </div>
            </div>

            <div class="kpi-card success slide-up" style="animation-delay: 0.1s">
                <div class="kpi-header">
                    <div class="kpi-title">Tareas Completadas</div>
                    <div class="kpi-icon">✅</div>
                </div>
                <div class="kpi-value" id="completedTasks">
                    <div class="loading-spinner"></div>
                </div>
                <div class="kpi-trend">
                    <span class="trend-up" id="completedTrend">●</span>
                    <span id="completedChange">Calculando...</span>
                </div>
            </div>

            <div class="kpi-card warning slide-up" style="animation-delay: 0.2s">
                <div class="kpi-header">
                    <div class="kpi-title">Tareas Pendientes</div>
                    <div class="kpi-icon">⏳</div>
                </div>
                <div class="kpi-value" id="pendingTasks">
                    <div class="loading-spinner"></div>
                </div>
                <div class="kpi-trend">
                    <span class="trend-neutral" id="pendingTrend">●</span>
                    <span id="pendingChange">Calculando...</span>
                </div>
            </div>

            <div class="kpi-card danger slide-up" style="animation-delay: 0.3s">
                <div class="kpi-header">
                    <div class="kpi-title">Tareas Vencidas</div>
                    <div class="kpi-icon">⚠️</div>
                </div>
                <div class="kpi-value" id="overdueTasks">
                    <div class="loading-spinner"></div>
                </div>
                <div class="kpi-trend">
                    <span class="trend-down" id="overdueTrend">●</span>
                    <span id="overdueChange">Calculando...</span>
                </div>
            </div>

            <div class="kpi-card info slide-up" style="animation-delay: 0.4s">
                <div class="kpi-header">
                    <div class="kpi-title">Eficiencia Global</div>
                    <div class="kpi-icon">⚡</div>
                </div>
                <div class="kpi-value" id="globalEfficiency">
                    <div class="loading-spinner"></div>
                </div>
                <div class="kpi-trend">
                    <span class="trend-up" id="efficiencyTrend">●</span>
                    <span id="efficiencyChange">Calculando...</span>
                </div>
            </div>

            <div class="kpi-card primary slide-up" style="animation-delay: 0.5s">
                <div class="kpi-header">
                    <div class="kpi-title">Tiempo Promedio</div>
                    <div class="kpi-icon">⏱️</div>
                </div>
                <div class="kpi-value" id="avgTime">
                    <div class="loading-spinner"></div>
                </div>
                <div class="kpi-trend">
                    <span class="trend-neutral" id="timeTrend">●</span>
                    <span id="timeChange">Calculando...</span>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section fade-in">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">📈 Evolución de Tareas</div>
                    <div class="chart-period">
                        <button class="period-btn active" onclick="changePeriod('week', this)">7D</button>
                        <button class="period-btn" onclick="changePeriod('month', this)">30D</button>
                        <button class="period-btn" onclick="changePeriod('quarter', this)">90D</button>
                    </div>
                </div>
                <div class="chart-container" id="tasksChart">
                    <canvas id="tasksEvolutionChart" width="600" height="300"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">🎯 Distribución por Estado</div>
                </div>
                <div class="chart-container" style="flex-direction: column;">
                    <div class="progress-ring" style="--accent-color: #28a745;">
                        <svg>
                            <circle class="bg" cx="60" cy="60" r="40"></circle>
                            <circle class="progress" cx="60" cy="60" r="40" id="completionProgress"></circle>
                        </svg>
                        <div class="progress-text" id="completionPercentage">0%</div>
                    </div>
                    <div style="text-align: center; color: #666;">
                        <strong>Tasa de Completado</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables -->
        <div class="data-tables fade-in">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">🏆 Top Clientes por Rendimiento</div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Tareas</th>
                            <th>Completado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="topClientsTable">
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px;">
                                <div class="loading-spinner"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">⚡ Tipos de Tarea Más Eficientes</div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Promedio</th>
                            <th>Eficiencia</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="taskTypesTable">
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px;">
                                <div class="loading-spinner"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Insights Panel -->
        <div class="insights-panel fade-in">
            <div class="insights-header">
                <div class="insights-icon">🧠</div>
                <div class="insights-title">Insights y Recomendaciones</div>
            </div>
            <div class="insights-grid" id="insightsGrid">
                <div class="insight-item">
                    <div class="insight-label">Cargando análisis...</div>
                    <div class="insight-value">
                        <div class="skeleton" style="height: 20px; border-radius: 4px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let dashboardData = null;
        let currentPeriod = 'week';
        let refreshInterval = null;

        // Inicializar dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            updateTime();
            
            // Auto-refresh cada 5 minutos
            refreshInterval = setInterval(loadDashboardData, 5 * 60 * 1000);
        });

        // Cargar datos del dashboard
        async function loadDashboardData() {
            try {
                showLoadingState();
                
                // Cargar datos en paralelo
                const [executiveData, clientProgressData, taskAnalyticsData, productivityData] = await Promise.all([
                    fetchReportData('executive_dashboard'),
                    fetchReportData('client_progress'),
                    fetchReportData('task_analytics'),
                    fetchReportData('productivity')
                ]);

                if (executiveData.success) {
                    dashboardData = {
                        executive: executiveData,
                        clientProgress: clientProgressData.success ? clientProgressData : null,
                        taskAnalytics: taskAnalyticsData.success ? taskAnalyticsData : null,
                        productivity: productivityData.success ? productivityData : null
                    };

                    updateDashboard();
                    updateTime();
                } else {
                    showError('Error al cargar datos del dashboard');
                }
            } catch (error) {
                console.error('Error cargando dashboard:', error);
                showError('Error de conexión al cargar el dashboard');
            }
        }

        // Fetch datos de reportes
        async function fetchReportData(reportType) {
            const response = await fetch(`reports.php?action=${reportType}&period=${currentPeriod}`);
            return await response.json();
        }

        // Actualizar dashboard con datos
        function updateDashboard() {
            if (!dashboardData) return;

            updateKPIs();
            updateCharts();
            updateTables();
            updateInsights();
        }

        // Actualizar KPIs
        function updateKPIs() {
            const metrics = dashboardData.executive.main_metrics;
            const productivity = dashboardData.productivity?.productivity;

            // Total Clientes
            animateCounter('totalClients', metrics.total_clients || 0);
            updateTrend('clientsTrend', 'clientsChange', 5, 'clientes nuevos');

            // Tareas Completadas
            animateCounter('completedTasks', metrics.completed_tasks || 0);
            updateTrend('completedTrend', 'completedChange', 12, 'este período');

            // Tareas Pendientes
            const pendingTasks = (metrics.total_tasks || 0) - (metrics.completed_tasks || 0);
            animateCounter('pendingTasks', pendingTasks);
            updateTrend('pendingTrend', 'pendingChange', -3, 'vs período anterior');

            // Tareas Vencidas
            animateCounter('overdueTasks', metrics.overdue_tasks || 0);
            updateTrend('overdueTrend', 'overdueChange', -2, 'mejoría');

            // Eficiencia Global
            const efficiency = productivity ? Math.round(productivity.completion_rate || 0) : 0;
            animateCounter('globalEfficiency', efficiency, '%');
            updateTrend('efficiencyTrend', 'efficiencyChange', 8, 'mejora');

            // Tiempo Promedio
            const avgTime = productivity ? Math.round(productivity.avg_completion_hours || 0) : 0;
            animateCounter('avgTime', avgTime, 'h');
            updateTrend('timeTrend', 'timeChange', -0.5, 'horas menos');
        }

        // Animar contadores
        function animateCounter(elementId, finalValue, suffix = '') {
            const element = document.getElementById(elementId);
            let currentValue = 0;
            const increment = finalValue / 30;
            const duration = 1000;
            const intervalTime = duration / 30;

            const counter = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    element.textContent = finalValue + suffix;
                    clearInterval(counter);
                } else {
                    element.textContent = Math.round(currentValue) + suffix;
                }
            }, intervalTime);
        }

        // Actualizar tendencias
        function updateTrend(trendId, changeId, value, text) {
            const trendElement = document.getElementById(trendId);
            const changeElement = document.getElementById(changeId);
            
            if (value > 0) {
                trendElement.className = 'trend-up';
                trendElement.textContent = '↗';
                changeElement.textContent = `+${Math.abs(value)} ${text}`;
            } else if (value < 0) {
                trendElement.className = 'trend-down';
                trendElement.textContent = '↘';
                changeElement.textContent = `-${Math.abs(value)} ${text}`;
            } else {
                trendElement.className = 'trend-neutral';
                trendElement.textContent = '→';
                changeElement.textContent = `Sin cambios`;
            }
        }

        // Actualizar gráficos
        function updateCharts() {
            updateTasksEvolutionChart();
            updateCompletionProgress();
        }

        // Gráfico de evolución de tareas
        function updateTasksEvolutionChart() {
            const canvas = document.getElementById('tasksEvolutionChart');
            const ctx = canvas.getContext('2d');
            
            // Limpiar canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Datos simulados (en producción vendrían de dashboardData)
            const data = generateChartData();
            
            // Configurar canvas
            const padding = 40;
            const chartWidth = canvas.width - (padding * 2);
            const chartHeight = canvas.height - (padding * 2);
            
            // Encontrar valores máximos
            const maxValue = Math.max(...data.completed, ...data.created);
            
            // Dibujar grid
            ctx.strokeStyle = '#e9ecef';
            ctx.lineWidth = 1;
            
            // Líneas horizontales
            for (let i = 0; i <= 5; i++) {
                const y = padding + (chartHeight / 5) * i;
                ctx.beginPath();
                ctx.moveTo(padding, y);
                ctx.lineTo(canvas.width - padding, y);
                ctx.stroke();
            }
            
            // Líneas verticales
            for (let i = 0; i < data.labels.length; i++) {
                const x = padding + (chartWidth / (data.labels.length - 1)) * i;
                ctx.beginPath();
                ctx.moveTo(x, padding);
                ctx.lineTo(x, canvas.height - padding);
                ctx.stroke();
            }
            
            // Dibujar líneas de datos
            drawLine(ctx, data.completed, '#28a745', padding, chartWidth, chartHeight, maxValue);
            drawLine(ctx, data.created, '#667eea', padding, chartWidth, chartHeight, maxValue);
            
            // Leyenda
            ctx.fillStyle = '#28a745';
            ctx.fillRect(padding, 10, 15, 15);
            ctx.fillStyle = '#333';
            ctx.font = '12px Arial';
            ctx.fillText('Completadas', padding + 20, 22);
            
            ctx.fillStyle = '#667eea';
            ctx.fillRect(padding + 120, 10, 15, 15);
            ctx.fillStyle = '#333';
            ctx.fillText('Creadas', padding + 140, 22);
        }

        // Dibujar línea en el gráfico
        function drawLine(ctx, data, color, padding, chartWidth, chartHeight, maxValue) {
            ctx.strokeStyle = color;
            ctx.lineWidth = 3;
            ctx.beginPath();
            
            for (let i = 0; i < data.length; i++) {
                const x = padding + (chartWidth / (data.length - 1)) * i;
                const y = padding + chartHeight - (data[i] / maxValue) * chartHeight;
                
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
                
                // Puntos
                ctx.fillStyle = color;
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, 2 * Math.PI);
                ctx.fill();
            }
            
            ctx.stroke();
        }

        // Generar datos para el gráfico
        function generateChartData() {
            const labels = [];
            const completed = [];
            const created = [];
            
            // Generar datos de los últimos 7 días
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('es-ES', { weekday: 'short' }));
                
                // Datos simulados basados en dashboardData si está disponible
                const baseCompleted = dashboardData?.executive?.main_metrics?.completed_tasks || 10;
                completed.push(Math.max(0, baseCompleted + Math.random() * 5 - 2));
                created.push(Math.max(0, baseCompleted + Math.random() * 8 - 1));
            }
            
            return { labels, completed, created };
        }

        // Actualizar progreso circular
        function updateCompletionProgress() {
            const metrics = dashboardData.executive.main_metrics;
            const totalTasks = metrics.total_tasks || 1;
            const completedTasks = metrics.completed_tasks || 0;
            const percentage = Math.round((completedTasks / totalTasks) * 100);
            
            // Actualizar porcentaje
            document.getElementById('completionPercentage').textContent = percentage + '%';
            
            // Actualizar círculo de progreso
            const progressCircle = document.getElementById('completionProgress');
            const circumference = 2 * Math.PI * 40; // radio = 40
            const offset = circumference - (percentage / 100) * circumference;
            
            progressCircle.style.strokeDasharray = circumference;
            progressCircle.style.strokeDashoffset = offset;
        }

        // Actualizar tablas
        function updateTables() {
            updateTopClientsTable();
            updateTaskTypesTable();
        }

        // Tabla de top clientes
        function updateTopClientsTable() {
            const tableBody = document.getElementById('topClientsTable');
            const topClients = dashboardData.executive.top_clients || [];
            
            if (topClients.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #666;">No hay datos de clientes disponibles</td></tr>';
                return;
            }
            
            tableBody.innerHTML = '';
            
            topClients.slice(0, 5).forEach(client => {
                const completion = client.completion_rate || 0;
                const statusClass = completion >= 80 ? 'badge-excellent' : 
                                  completion >= 60 ? 'badge-good' : 
                                  completion >= 40 ? 'badge-average' : 'badge-poor';
                
                const statusText = completion >= 80 ? 'Excelente' : 
                                 completion >= 60 ? 'Bueno' : 
                                 completion >= 40 ? 'Regular' : 'Necesita Atención';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <strong>${client.name}</strong>
                        <br><small style="color: #666;">${client.company || 'Sin empresa'}</small>
                    </td>
                    <td>${client.task_count}</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-bar-fill" style="width: ${completion}%"></div>
                        </div>
                        <small>${completion}%</small>
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        // Tabla de tipos de tarea
        function updateTaskTypesTable() {
            const tableBody = document.getElementById('taskTypesTable');
            const taskTypes = dashboardData.taskAnalytics?.task_types || [];
            
            if (taskTypes.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #666;">No hay datos de tipos de tarea disponibles</td></tr>';
                return;
            }
            
            tableBody.innerHTML = '';
            
            // Ordenar por eficiencia
            const sortedTasks = taskTypes
                .map(task => ({
                    ...task,
                    efficiency: task.total_tasks > 0 ? (task.completed_tasks / task.total_tasks) * 100 : 0
                }))
                .sort((a, b) => b.efficiency - a.efficiency)
                .slice(0, 5);
            
            sortedTasks.forEach(task => {
                const efficiency = Math.round(task.efficiency);
                const avgTime = Math.round(task.avg_completion_hours || 0);
                const statusClass = efficiency >= 80 ? 'badge-excellent' : 
                                  efficiency >= 60 ? 'badge-good' : 
                                  efficiency >= 40 ? 'badge-average' : 'badge-poor';
                
                const statusText = efficiency >= 80 ? 'Óptimo' : 
                                 efficiency >= 60 ? 'Bueno' : 
                                 efficiency >= 40 ? 'Regular' : 'Mejorable';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <strong>${task.template_name || 'Tarea Personalizada'}</strong>
                        <br><small style="color: #666;">${task.category || 'General'}</small>
                    </td>
                    <td>${avgTime}h</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-bar-fill" style="width: ${efficiency}%"></div>
                        </div>
                        <small>${efficiency}%</small>
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        // Actualizar insights
        function updateInsights() {
            const insightsGrid = document.getElementById('insightsGrid');
            const metrics = dashboardData.executive.main_metrics;
            const productivity = dashboardData.productivity?.productivity;
            
            const insights = generateInsights(metrics, productivity);
            
            insightsGrid.innerHTML = '';
            
            insights.forEach(insight => {
                const insightElement = document.createElement('div');
                insightElement.className = `insight-item ${insight.type}`;
                insightElement.innerHTML = `
                    <div class="insight-label">${insight.label}</div>
                    <div class="insight-value">${insight.value}</div>
                `;
                insightsGrid.appendChild(insightElement);
            });
        }

        // Generar insights inteligentes
        function generateInsights(metrics, productivity) {
            const insights = [];
            const totalTasks = metrics.total_tasks || 0;
            const completedTasks = metrics.completed_tasks || 0;
            const overdueTasks = metrics.overdue_tasks || 0;
            const completionRate = totalTasks > 0 ? (completedTasks / totalTasks) * 100 : 0;
            
            // Insight sobre productividad
            if (completionRate >= 80) {
                insights.push({
                    type: 'positive',
                    label: 'Productividad Excelente',
                    value: `${Math.round(completionRate)}% de tareas completadas. ¡Excelente trabajo!`
                });
            } else if (completionRate >= 60) {
                insights.push({
                    type: 'warning',
                    label: 'Productividad Buena',
                    value: `${Math.round(completionRate)}% completado. Oportunidad de mejora del ${Math.round(80 - completionRate)}%`
                });
            } else {
                insights.push({
                    type: 'negative',
                    label: 'Productividad Baja',
                    value: `Solo ${Math.round(completionRate)}% completado. Revisar procesos urgentemente`
                });
            }
            
            // Insight sobre tareas vencidas
            if (overdueTasks === 0) {
                insights.push({
                    type: 'positive',
                    label: 'Sin Retrasos',
                    value: 'Todas las tareas están al día. Excelente gestión de tiempos'
                });
            } else if (overdueTasks <= 3) {
                insights.push({
                    type: 'warning',
                    label: 'Pocos Retrasos',
                    value: `${overdueTasks} tareas vencidas. Atención moderada requerida`
                });
            } else {
                insights.push({
                    type: 'negative',
                    label: 'Múltiples Retrasos',
                    value: `${overdueTasks} tareas vencidas. Acción inmediata necesaria`
                });
            }
            
            // Insight sobre clientes
            const totalClients = metrics.total_clients || 0;
            if (totalClients >= 10) {
                insights.push({
                    type: 'positive',
                    label: 'Base de Clientes Sólida',
                    value: `${totalClients} clientes activos. Buena diversificación`
                });
            } else if (totalClients >= 5) {
                insights.push({
                    type: 'warning',
                    label: 'Base de Clientes Moderada',
                    value: `${totalClients} clientes. Considerar expansión`
                });
            } else {
                insights.push({
                    type: 'negative',
                    label: 'Pocos Clientes',
                    value: `Solo ${totalClientes} clientes. Priorizar captación`
                });
            }
            
            // Insight sobre tiempo promedio
            const avgHours = productivity?.avg_completion_hours || 0;
            if (avgHours <= 24) {
                insights.push({
                    type: 'positive',
                    label: 'Tiempo de Respuesta Rápido',
                    value: `${Math.round(avgHours)}h promedio. Excelente velocidad`
                });
            } else if (avgHours <= 72) {
                insights.push({
                    type: 'warning',
                    label: 'Tiempo Moderado',
                    value: `${Math.round(avgHours)}h promedio. Oportunidad de optimización`
                });
            } else {
                insights.push({
                    type: 'negative',
                    label: 'Tiempo Lento',
                    value: `${Math.round(avgHours)}h promedio. Revisar procesos`
                });
            }
            
            return insights;
        }

        // Cambiar período de análisis
        function changePeriod(period, button) {
            // Actualizar botones activos
            document.querySelectorAll('.period-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            currentPeriod = period;
            loadDashboardData();
        }

        // Actualizar tiempo
        function updateTime() {
            const now = new Date();
            document.getElementById('updateTime').textContent = now.toLocaleString('es-ES');
        }

        // Refrescar dashboard
        function refreshDashboard() {
            showRefreshAnimation();
            loadDashboardData();
        }

        // Estados de carga
        function showLoadingState() {
            // Los spinners ya están en el HTML inicial
        }

        function showRefreshAnimation() {
            // Animación sutil de refresh
            document.querySelector('.executive-header').style.opacity = '0.7';
            setTimeout(() => {
                document.querySelector('.executive-header').style.opacity = '1';
            }, 500);
        }

        function showError(message) {
            // Crear notificación de error
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc3545;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Función para demostración con datos simulados
        function loadDemoData() {
            dashboardData = {
                executive: {
                    success: true,
                    main_metrics: {
                        total_clients: 15,
                        total_tasks: 45,
                        completed_tasks: 32,
                        overdue_tasks: 3,
                        due_today: 2,
                        high_priority_pending: 5,
                        total_notes: 28
                    },
                    top_clients: [
                        { name: 'Empresa ABC', company: 'ABC Corp', task_count: 12, completed_count: 10, completion_rate: 83 },
                        { name: 'Cliente XYZ', company: 'XYZ Ltd', task_count: 8, completed_count: 6, completion_rate: 75 },
                        { name: 'Negocio 123', company: '123 Inc', task_count: 6, completed_count: 5, completion_rate: 83 },
                        { name: 'Compañía DEF', company: 'DEF SA', task_count: 10, completed_count: 7, completion_rate: 70 },
                        { name: 'Startup GHI', company: 'GHI Tech', task_count: 9, completed_count: 4, completion_rate: 44 }
                    ]
                },
                taskAnalytics: {
                    success: true,
                    task_types: [
                        { template_name: 'Campaña de Redes Sociales', category: 'Marketing Digital', total_tasks: 8, completed_tasks: 7, avg_completion_hours: 16 },
                        { template_name: 'Diseño Gráfico', category: 'Creatividad', total_tasks: 6, completed_tasks: 5, avg_completion_hours: 12 },
                        { template_name: 'Análisis SEO', category: 'SEO', total_tasks: 4, completed_tasks: 4, avg_completion_hours: 24 },
                        { template_name: 'Email Marketing', category: 'Marketing Digital', total_tasks: 5, completed_tasks: 3, avg_completion_hours: 8 },
                        { template_name: 'Consultoría', category: 'Estrategia', total_tasks: 3, completed_tasks: 2, avg_completion_hours: 48 }
                    ]
                },
                productivity: {
                    success: true,
                    productivity: {
                        completion_rate: 71,
                        avg_completion_hours: 18,
                        total_tasks: 45,
                        completed_tasks: 32,
                        pending_tasks: 10,
                        overdue_tasks: 3
                    }
                }
            };
            
            updateDashboard();
        }

        // Si no hay datos reales después de 3 segundos, cargar datos demo
        setTimeout(() => {
            if (!dashboardData) {
                console.log('Cargando datos de demostración...');
                loadDemoData();
            }
        }, 3000);

        // Limpiar interval al salir
        window.addEventListener('beforeunload', () => {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>