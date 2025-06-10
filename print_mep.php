<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $conn->prepare("SELECT c.name AS category_name, d.name AS dish_name,
           s.status, s.notes, s.priority
    FROM mep_categories c
    LEFT JOIN mep_dishes d ON c.id = d.category_id
    LEFT JOIN mep_day_data s ON d.id = s.dish_id AND s.date = ?
    ORDER BY c.sort_order ASC, d.sort_order ASC");
$stmt->execute([$date]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = [];
foreach ($results as $row) {
    $cat_name = $row['category_name'] ?? 'Onbekend';
    if (!isset($categories[$cat_name])) {
        $categories[$cat_name] = [
            'priority' => [],
            'normal' => []
        ];
    }
    $status = $row['status'] ?? 'from_scratch';
    $notes = $row['notes'] ?? '';
    $priority = $row['priority'] ?? '';

    $dish = [
        'dish_name' => $row['dish_name'],
        'status' => $status,
        'notes' => $notes,
        'priority' => $priority
    ];

    if ($priority) {
        $categories[$cat_name]['priority'][] = $dish;
    } else {
        $categories[$cat_name]['normal'][] = $dish;
    }
}

ob_start();
?>
<div id="pdf-overlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:white; overflow:auto; z-index:9999; padding:40px;">
    <button onclick="document.getElementById('pdf-overlay').style.display='none'" style="position:absolute; top:10px; right:10px; font-size:18px;">✖ Sluit</button>
    <button onclick="window.print()" style="position:absolute; top:10px; left:10px; font-size:18px;">🖨️ Print</button>
    <button onclick="downloadPDF()" style="position:absolute; top:10px; left:110px; font-size:18px;">⬇ Download PDF</button>
    <h1 style="text-align:center; font-size:28px;">MEP voor <?= htmlspecialchars($date) ?></h1>
    <div style="font-size:18px;">
    <?php foreach ($categories as $cat_name => $dish_groups): ?>
        <div class="category" style="margin-top:30px;">
            <h2><?= htmlspecialchars($cat_name) ?></h2>
            <?php foreach (['priority', 'normal'] as $type): ?>
                <?php foreach ($dish_groups[$type] as $dish): ?>
                    <?php if ($dish['status'] !== 'not_required'): ?>
                        <div class="dish" style="margin-left:20px; margin-bottom:5px; white-space:pre;">
                            <?= $dish['priority'] ? '<span style="font-weight:bold; color:orange;">⭐</span> ' : '' ?>
                            <strong><?= htmlspecialchars($dish['dish_name']) ?></strong>
                            <span style="font-style:italic; color:#555;"> [<?= htmlspecialchars($dish['status']) ?>]</span>
                            <?php if (!empty($dish['notes'])): ?>
                                <br>	<span style="color:#777;">→ <?= htmlspecialchars($dish['notes']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function showPDFOverlay() {
    document.getElementById('pdf-overlay').style.display = 'block';
}

function downloadPDF() {
    const element = document.getElementById('pdf-overlay');
    const opt = {
        margin:       0.5,
        filename:     'MEP_<?= $date ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

<button onclick="showPDFOverlay()">TOON MEP OVERLAY</button>
<?php
$html = ob_get_clean();
echo $html;
?>
