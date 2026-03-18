<?php
/**
 * Module Calendrier & RDV
 * /admin/modules/rdv/index.php
 */

// Connexion BDD
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Erreur de connexion: ' . $e->getMessage() . '</div>');
}

// Créer la table des RDV si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    type ENUM('visite', 'estimation', 'signature', 'prospection', 'suivi', 'autre') DEFAULT 'visite',
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    location VARCHAR(255) DEFAULT NULL,
    lead_id INT DEFAULT NULL,
    contact_id INT DEFAULT NULL,
    property_id INT DEFAULT NULL,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    reminder_sent TINYINT(1) DEFAULT 0,
    notes TEXT DEFAULT NULL,
    color VARCHAR(20) DEFAULT '#6366f1',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_start (start_datetime),
    INDEX idx_lead (lead_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Date actuelle
$currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$currentMonth = date('Y-m', strtotime($currentDate));
$currentYear = date('Y', strtotime($currentDate));
$currentMonthNum = date('m', strtotime($currentDate));

// Premier et dernier jour du mois
$firstDayOfMonth = date('Y-m-01', strtotime($currentDate));
$lastDayOfMonth = date('Y-m-t', strtotime($currentDate));
$firstDayWeekday = date('N', strtotime($firstDayOfMonth)); // 1 = Lundi
$daysInMonth = date('t', strtotime($currentDate));

// Récupérer les RDV du mois
$stmt = $pdo->prepare("
    SELECT a.*, 
           l.firstname as lead_firstname, l.lastname as lead_lastname, l.phone as lead_phone
    FROM appointments a
    LEFT JOIN leads l ON a.lead_id = l.id
    WHERE DATE(a.start_datetime) BETWEEN ? AND ?
    ORDER BY a.start_datetime ASC
");
$stmt->execute([$firstDayOfMonth, $lastDayOfMonth]);
$appointments = $stmt->fetchAll();

// Organiser les RDV par jour
$appointmentsByDay = [];
foreach ($appointments as $apt) {
    $day = date('j', strtotime($apt['start_datetime']));
    $appointmentsByDay[$day][] = $apt;
}

// Récupérer les leads pour le formulaire
try {
    $leads = $pdo->query("SELECT id, firstname, lastname, email, phone FROM leads ORDER BY firstname, lastname")->fetchAll();
} catch (PDOException $e) {
    $leads = [];
}

// Statistiques du mois
$totalRdv = count($appointments);
$completedRdv = count(array_filter($appointments, fn($a) => $a['status'] === 'completed'));
$upcomingRdv = count(array_filter($appointments, fn($a) => $a['status'] === 'scheduled' && strtotime($a['start_datetime']) > time()));
$cancelledRdv = count(array_filter($appointments, fn($a) => $a['status'] === 'cancelled'));

// Types de RDV avec couleurs
$rdvTypes = [
    'visite' => ['label' => 'Visite', 'color' => '#6366f1', 'icon' => 'home'],
    'estimation' => ['label' => 'Estimation', 'color' => '#10b981', 'icon' => 'calculator'],
    'signature' => ['label' => 'Signature', 'color' => '#f59e0b', 'icon' => 'file-signature'],
    'prospection' => ['label' => 'Prospection', 'color' => '#ec4899', 'icon' => 'search'],
    'suivi' => ['label' => 'Suivi client', 'color' => '#06b6d4', 'icon' => 'phone'],
    'autre' => ['label' => 'Autre', 'color' => '#64748b', 'icon' => 'calendar']
];

// Noms des mois en français
$monthNames = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
?>

<style>
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.calendar-nav {
    display: flex;
    align-items: center;
    gap: 16px;
}

.calendar-nav-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    text-decoration: none;
}

.calendar-nav-btn:hover {
    background: #6366f1;
    color: white;
    border-color: #6366f1;
}

.calendar-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.calendar-today-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.calendar-today-btn:hover {
    background: #f8fafc;
    color: #1e293b;
}

/* Stats */
.calendar-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 16px 20px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 14px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-icon.purple { background: rgba(99,102,241,0.1); color: #6366f1; }
.stat-icon.green { background: rgba(16,185,129,0.1); color: #10b981; }
.stat-icon.orange { background: rgba(245,158,11,0.1); color: #f59e0b; }
.stat-icon.red { background: rgba(239,68,68,0.1); color: #ef4444; }

.stat-content { flex: 1; }
.stat-value { font-size: 24px; font-weight: 800; color: #1e293b; }
.stat-label { font-size: 13px; color: #64748b; }

/* Calendar Grid */
.calendar-container {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.calendar-weekday {
    padding: 16px;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-day {
    min-height: 120px;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    padding: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.calendar-day:nth-child(7n) {
    border-right: none;
}

.calendar-day:hover {
    background: #f8fafc;
}

.calendar-day.other-month {
    background: #fafafa;
}

.calendar-day.other-month .day-number {
    color: #cbd5e1;
}

.calendar-day.today {
    background: rgba(99,102,241,0.05);
}

.calendar-day.today .day-number {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
}

.day-number {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
}

.calendar-day.weekend .day-number {
    color: #ef4444;
}

/* Appointments in calendar */
.day-appointments {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.day-apt {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 500;
    color: white;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: all 0.2s ease;
}

.day-apt:hover {
    transform: scale(1.02);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.day-apt-time {
    font-weight: 700;
    margin-right: 4px;
}

.day-more {
    font-size: 11px;
    color: #6366f1;
    font-weight: 600;
    text-align: center;
    padding: 4px;
    cursor: pointer;
    border-radius: 4px;
}

.day-more:hover {
    background: rgba(99,102,241,0.1);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99,102,241,0.3);
}

.btn-secondary {
    background: white;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f8fafc;
    color: #1e293b;
}

.btn-danger {
    color: #ef4444;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.modal-overlay.active {
    display: flex;
}

.modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    color: #64748b;
    border: none;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #ef4444;
    color: white;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-footer-right {
    display: flex;
    gap: 10px;
}

/* Form */
.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
    font-family: inherit;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Type selector */
.type-selector {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.type-option {
    padding: 12px 8px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s ease;
}

.type-option:hover {
    border-color: #6366f1;
    background: rgba(99,102,241,0.05);
}

.type-option.selected {
    border-color: #6366f1;
    background: rgba(99,102,241,0.1);
}

.type-option i {
    font-size: 20px;
    margin-bottom: 6px;
    display: block;
}

.type-option span {
    font-size: 11px;
    font-weight: 600;
    color: #374151;
}

/* Upcoming list */
.upcoming-section {
    margin-top: 24px;
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.upcoming-header {
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.upcoming-list {
    padding: 12px;
}

.upcoming-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    border-radius: 12px;
    margin-bottom: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
}

.upcoming-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
}

.upcoming-item:last-child {
    margin-bottom: 0;
}

.upcoming-date {
    text-align: center;
    min-width: 50px;
    padding: 8px;
    background: #f1f5f9;
    border-radius: 10px;
}

.upcoming-date-day {
    font-size: 22px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
}

.upcoming-date-month {
    font-size: 10px;
    color: #64748b;
    text-transform: uppercase;
    margin-top: 2px;
}

.upcoming-type-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.upcoming-content {
    flex: 1;
    min-width: 0;
}

.upcoming-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.upcoming-meta {
    font-size: 12px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.upcoming-meta i {
    margin-right: 4px;
    color: #94a3b8;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.scheduled { background: #e0e7ff; color: #4338ca; }
.status-badge.confirmed { background: #d1fae5; color: #059669; }
.status-badge.completed { background: #dcfce7; color: #16a34a; }
.status-badge.cancelled { background: #fee2e2; color: #dc2626; }
.status-badge.no_show { background: #fef3c7; color: #d97706; }

/* View modal content */
.apt-detail-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.apt-detail-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
}

.apt-detail-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.apt-detail-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
}

.apt-detail-type {
    font-size: 13px;
    color: #64748b;
}

.apt-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.apt-info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #374151;
}

.apt-info-item i {
    width: 18px;
    color: #6366f1;
}

/* Empty state */
.empty-upcoming {
    text-align: center;
    padding: 30px;
    color: #94a3b8;
}

.empty-upcoming i {
    font-size: 32px;
    margin-bottom: 10px;
}

/* Responsive */
@media (max-width: 1024px) {
    .calendar-stats { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .calendar-stats { grid-template-columns: 1fr; }
    .calendar-day { min-height: 80px; padding: 4px; }
    .day-number { width: 24px; height: 24px; font-size: 11px; }
    .day-apt { padding: 2px 4px; font-size: 9px; }
    .form-row { grid-template-columns: 1fr; }
    .type-selector { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- Header -->
<div class="calendar-header">
    <div class="calendar-nav">
        <a href="?page=rdv&date=<?php echo date('Y-m-d', strtotime($currentDate . ' -1 month')); ?>" class="calendar-nav-btn">
            <i class="fas fa-chevron-left"></i>
        </a>
        <h2 class="calendar-title"><?php echo $monthNames[(int)$currentMonthNum] . ' ' . $currentYear; ?></h2>
        <a href="?page=rdv&date=<?php echo date('Y-m-d', strtotime($currentDate . ' +1 month')); ?>" class="calendar-nav-btn">
            <i class="fas fa-chevron-right"></i>
        </a>
        <a href="?page=rdv" class="calendar-today-btn">Aujourd'hui</a>
    </div>
    
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-plus"></i> Nouveau RDV
    </button>
</div>

<!-- Stats -->
<div class="calendar-stats">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalRdv; ?></div>
            <div class="stat-label">RDV ce mois</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $upcomingRdv; ?></div>
            <div class="stat-label">À venir</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $completedRdv; ?></div>
            <div class="stat-label">Réalisés</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $cancelledRdv; ?></div>
            <div class="stat-label">Annulés</div>
        </div>
    </div>
</div>

<!-- Calendar -->
<div class="calendar-container">
    <div class="calendar-weekdays">
        <?php foreach ($dayNames as $dayName): ?>
            <div class="calendar-weekday"><?php echo $dayName; ?></div>
        <?php endforeach; ?>
    </div>
    
    <div class="calendar-days">
        <?php
        // Jours du mois précédent
        $prevMonthDays = $firstDayWeekday - 1;
        $prevMonth = date('Y-m-t', strtotime($currentDate . ' -1 month'));
        $prevMonthLastDay = date('j', strtotime($prevMonth));
        
        for ($i = $prevMonthDays; $i > 0; $i--) {
            $day = $prevMonthLastDay - $i + 1;
            echo '<div class="calendar-day other-month">';
            echo '<div class="day-number">' . $day . '</div>';
            echo '</div>';
        }
        
        // Jours du mois actuel
        $today = date('Y-m-d');
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = $currentYear . '-' . str_pad($currentMonthNum, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $isToday = ($dateStr === $today) ? ' today' : '';
            $dayOfWeek = date('N', strtotime($dateStr));
            $isWeekend = ($dayOfWeek >= 6) ? ' weekend' : '';
            
            echo '<div class="calendar-day' . $isToday . $isWeekend . '" onclick="openAddModal(\'' . $dateStr . '\')">';
            echo '<div class="day-number">' . $day . '</div>';
            
            // Afficher les RDV du jour
            if (isset($appointmentsByDay[$day])) {
                echo '<div class="day-appointments">';
                $count = 0;
                foreach ($appointmentsByDay[$day] as $apt) {
                    if ($count < 3) {
                        $color = $rdvTypes[$apt['type']]['color'] ?? '#6366f1';
                        $time = date('H:i', strtotime($apt['start_datetime']));
                        echo '<div class="day-apt" style="background: ' . $color . ';" onclick="event.stopPropagation(); openViewModal(' . $apt['id'] . ')">';
                        echo '<span class="day-apt-time">' . $time . '</span>';
                        echo htmlspecialchars(substr($apt['title'], 0, 12));
                        echo '</div>';
                    }
                    $count++;
                }
                if ($count > 3) {
                    echo '<div class="day-more">+' . ($count - 3) . ' autres</div>';
                }
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Jours du mois suivant
        $totalCells = $prevMonthDays + $daysInMonth;
        $remainingCells = 7 - ($totalCells % 7);
        if ($remainingCells < 7) {
            for ($i = 1; $i <= $remainingCells; $i++) {
                echo '<div class="calendar-day other-month">';
                echo '<div class="day-number">' . $i . '</div>';
                echo '</div>';
            }
        }
        ?>
    </div>
</div>

<!-- Upcoming Appointments -->
<?php
$upcomingApts = array_filter($appointments, fn($a) => strtotime($a['start_datetime']) >= strtotime('today') && $a['status'] !== 'cancelled');
usort($upcomingApts, fn($a, $b) => strtotime($a['start_datetime']) - strtotime($b['start_datetime']));
$upcomingApts = array_slice($upcomingApts, 0, 5);
?>
<div class="upcoming-section">
    <div class="upcoming-header">
        <i class="fas fa-clock"></i> Prochains rendez-vous
    </div>
    <div class="upcoming-list">
        <?php if (empty($upcomingApts)): ?>
            <div class="empty-upcoming">
                <i class="fas fa-calendar-check"></i>
                <p>Aucun rendez-vous à venir</p>
            </div>
        <?php else: ?>
            <?php foreach ($upcomingApts as $apt): ?>
                <?php $typeInfo = $rdvTypes[$apt['type']] ?? $rdvTypes['autre']; ?>
                <div class="upcoming-item" onclick="openViewModal(<?php echo $apt['id']; ?>)">
                    <div class="upcoming-date">
                        <div class="upcoming-date-day"><?php echo date('d', strtotime($apt['start_datetime'])); ?></div>
                        <div class="upcoming-date-month"><?php echo substr($monthNames[(int)date('n', strtotime($apt['start_datetime']))], 0, 3); ?></div>
                    </div>
                    <div class="upcoming-type-dot" style="background: <?php echo $typeInfo['color']; ?>"></div>
                    <div class="upcoming-content">
                        <div class="upcoming-name"><?php echo htmlspecialchars($apt['title']); ?></div>
                        <div class="upcoming-meta">
                            <span><i class="fas fa-clock"></i><?php echo date('H:i', strtotime($apt['start_datetime'])); ?></span>
                            <?php if ($apt['lead_firstname']): ?>
                                <span><i class="fas fa-user"></i><?php echo htmlspecialchars($apt['lead_firstname'] . ' ' . $apt['lead_lastname']); ?></span>
                            <?php endif; ?>
                            <?php if ($apt['location']): ?>
                                <span><i class="fas fa-map-marker-alt"></i><?php echo htmlspecialchars(substr($apt['location'], 0, 25)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $apt['status']; ?>">
                        <?php 
                        echo match($apt['status']) {
                            'scheduled' => 'Planifié',
                            'confirmed' => 'Confirmé',
                            'completed' => 'Réalisé',
                            'cancelled' => 'Annulé',
                            'no_show' => 'Absent',
                            default => $apt['status']
                        };
                        ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ajouter/Modifier RDV -->
<div class="modal-overlay" id="rdvModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Nouveau rendez-vous</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="rdvForm">
            <div class="modal-body">
                <input type="hidden" id="rdvId" name="rdv_id" value="">
                
                <div class="form-group">
                    <label class="form-label">Type de rendez-vous</label>
                    <div class="type-selector">
                        <?php foreach ($rdvTypes as $key => $type): ?>
                            <div class="type-option <?php echo $key === 'visite' ? 'selected' : ''; ?>" 
                                 data-type="<?php echo $key; ?>"
                                 onclick="selectType('<?php echo $key; ?>', this)">
                                <i class="fas fa-<?php echo $type['icon']; ?>" style="color: <?php echo $type['color']; ?>"></i>
                                <span><?php echo $type['label']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="rdvType" name="type" value="visite">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Titre du rendez-vous *</label>
                    <input type="text" class="form-input" id="rdvTitle" name="title" required placeholder="Ex: Visite appartement 3 pièces">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date *</label>
                        <input type="date" class="form-input" id="rdvDate" name="date" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lead associé</label>
                        <select class="form-select" id="rdvLead" name="lead_id">
                            <option value="">-- Aucun --</option>
                            <?php foreach ($leads as $lead): ?>
                                <option value="<?php echo $lead['id']; ?>">
                                    <?php echo htmlspecialchars(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Heure début *</label>
                        <input type="time" class="form-input" id="rdvStartTime" name="start_time" required value="09:00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Heure fin *</label>
                        <input type="time" class="form-input" id="rdvEndTime" name="end_time" required value="10:00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lieu</label>
                    <input type="text" class="form-input" id="rdvLocation" name="location" placeholder="Adresse du rendez-vous">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-textarea" id="rdvNotes" name="notes" placeholder="Informations complémentaires..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <div></div>
                <div class="modal-footer-right">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Voir RDV -->
<div class="modal-overlay" id="viewModal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Détails du rendez-vous</h3>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewContent">
            <!-- Contenu dynamique -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-danger" onclick="deleteRdv()">
                <i class="fas fa-trash"></i> Supprimer
            </button>
            <div class="modal-footer-right">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="editRdv()">
                    <i class="fas fa-edit"></i> Modifier
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const API_URL = '/admin/modules/rdv/api.php';
let currentRdvId = null;

const rdvTypes = <?php echo json_encode($rdvTypes); ?>;
const statusLabels = {
    'scheduled': 'Planifié',
    'confirmed': 'Confirmé', 
    'completed': 'Réalisé',
    'cancelled': 'Annulé',
    'no_show': 'Absent'
};

// Type selector
function selectType(type, element) {
    document.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('rdvType').value = type;
}

// Open add modal
function openAddModal(date = null) {
    document.getElementById('modalTitle').textContent = 'Nouveau rendez-vous';
    document.getElementById('rdvForm').reset();
    document.getElementById('rdvId').value = '';
    
    if (date) {
        document.getElementById('rdvDate').value = date;
    } else {
        document.getElementById('rdvDate').value = new Date().toISOString().split('T')[0];
    }
    
    document.querySelectorAll('.type-option').forEach(opt => {
        opt.classList.toggle('selected', opt.dataset.type === 'visite');
    });
    document.getElementById('rdvType').value = 'visite';
    
    document.getElementById('rdvModal').classList.add('active');
}

function closeModal() {
    document.getElementById('rdvModal').classList.remove('active');
}

// View modal
function openViewModal(rdvId) {
    currentRdvId = rdvId;
    
    fetch(API_URL + '?action=get_rdv&rdv_id=' + rdvId)
    .then(r => r.json())
    .then(data => {
        if (data.success && data.rdv) {
            const rdv = data.rdv;
            const typeInfo = rdvTypes[rdv.type] || rdvTypes.autre;
            const startDate = new Date(rdv.start_datetime);
            const endDate = new Date(rdv.end_datetime);
            
            let html = `
                <div class="apt-detail-card">
                    <div class="apt-detail-header">
                        <div class="apt-detail-icon" style="background: ${typeInfo.color}">
                            <i class="fas fa-${typeInfo.icon}"></i>
                        </div>
                        <div>
                            <div class="apt-detail-title">${escapeHtml(rdv.title)}</div>
                            <div class="apt-detail-type">${typeInfo.label}</div>
                        </div>
                    </div>
                    <div class="apt-info-grid">
                        <div class="apt-info-item">
                            <i class="fas fa-calendar"></i>
                            ${startDate.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })}
                        </div>
                        <div class="apt-info-item">
                            <i class="fas fa-clock"></i>
                            ${startDate.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })} - ${endDate.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                        </div>
                        ${rdv.location ? `
                        <div class="apt-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            ${escapeHtml(rdv.location)}
                        </div>` : ''}
                        ${rdv.lead_firstname ? `
                        <div class="apt-info-item">
                            <i class="fas fa-user"></i>
                            ${escapeHtml(rdv.lead_firstname + ' ' + rdv.lead_lastname)}
                        </div>` : ''}
                    </div>
                </div>
                ${rdv.notes ? `
                <div style="margin-bottom: 16px;">
                    <label class="form-label">Notes</label>
                    <p style="font-size: 14px; color: #374151; background: #f8fafc; padding: 12px; border-radius: 8px;">${escapeHtml(rdv.notes)}</p>
                </div>` : ''}
                <div>
                    <label class="form-label">Statut</label>
                    <select class="form-select" onchange="updateStatus(${rdv.id}, this.value)">
                        ${Object.entries(statusLabels).map(([k, v]) => 
                            `<option value="${k}" ${rdv.status === k ? 'selected' : ''}>${v}</option>`
                        ).join('')}
                    </select>
                </div>
            `;
            
            document.getElementById('viewContent').innerHTML = html;
            document.getElementById('viewModal').classList.add('active');
        }
    });
}

function closeViewModal() {
    document.getElementById('viewModal').classList.remove('active');
    currentRdvId = null;
}

// Edit RDV
function editRdv() {
    if (!currentRdvId) return;
    
    fetch(API_URL + '?action=get_rdv&rdv_id=' + currentRdvId)
    .then(r => r.json())
    .then(data => {
        if (data.success && data.rdv) {
            const rdv = data.rdv;
            
            document.getElementById('modalTitle').textContent = 'Modifier le rendez-vous';
            document.getElementById('rdvId').value = rdv.id;
            document.getElementById('rdvTitle').value = rdv.title;
            document.getElementById('rdvDate').value = rdv.start_datetime.split(' ')[0];
            document.getElementById('rdvStartTime').value = rdv.start_datetime.split(' ')[1].substring(0, 5);
            document.getElementById('rdvEndTime').value = rdv.end_datetime.split(' ')[1].substring(0, 5);
            document.getElementById('rdvLocation').value = rdv.location || '';
            document.getElementById('rdvLead').value = rdv.lead_id || '';
            document.getElementById('rdvNotes').value = rdv.notes || '';
            document.getElementById('rdvType').value = rdv.type;
            
            document.querySelectorAll('.type-option').forEach(opt => {
                opt.classList.toggle('selected', opt.dataset.type === rdv.type);
            });
            
            closeViewModal();
            document.getElementById('rdvModal').classList.add('active');
        }
    });
}

// Delete RDV
function deleteRdv() {
    if (!currentRdvId) return;
    if (!confirm('Supprimer ce rendez-vous ?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_rdv');
    formData.append('rdv_id', currentRdvId);
    
    fetch(API_URL, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('RDV supprimé', 'success');
            closeViewModal();
            setTimeout(() => location.reload(), 500);
        } else {
            showNotification('Erreur: ' + (data.error || 'Inconnue'), 'error');
        }
    });
}

// Update status
function updateStatus(rdvId, status) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('rdv_id', rdvId);
    formData.append('status', status);
    
    fetch(API_URL, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('Statut mis à jour', 'success');
        }
    });
}

// Save RDV
document.getElementById('rdvForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const rdvId = document.getElementById('rdvId').value;
    const formData = new FormData(this);
    formData.append('action', rdvId ? 'update_rdv' : 'add_rdv');
    
    fetch(API_URL, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification(rdvId ? 'RDV modifié' : 'RDV créé', 'success');
            closeModal();
            setTimeout(() => location.reload(), 500);
        } else {
            showNotification('Erreur: ' + (data.error || 'Inconnue'), 'error');
        }
    });
});

// Helpers
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
    const colors = { success: '#10b981', error: '#ef4444', info: '#6366f1' };
    const notif = document.createElement('div');
    notif.style.cssText = `position: fixed; top: 20px; right: 20px; padding: 14px 20px; background: ${colors[type]}; color: white; border-radius: 10px; font-size: 14px; font-weight: 500; z-index: 99999; box-shadow: 0 4px 12px rgba(0,0,0,0.2);`;
    notif.textContent = message;
    document.body.appendChild(notif);
    setTimeout(() => { notif.style.opacity = '0'; setTimeout(() => notif.remove(), 300); }, 2000);
}

// Keyboard & click events
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closeViewModal(); } });
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) { if (e.target === this) { closeModal(); closeViewModal(); } });
});
</script>