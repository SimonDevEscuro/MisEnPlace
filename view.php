<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT c.id AS category_id, c.name AS category_name, c.sort_order,
           d.id AS dish_id, d.name AS dish_name, d.sort_order AS dish_order
    FROM mep_categories c
    LEFT JOIN mep_dishes d ON c.id = d.category_id
    ORDER BY c.sort_order ASC, d.sort_order ASC
");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cat_stmt = $conn->query("SELECT id, name FROM mep_categories ORDER BY sort_order ASC");
$all_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = [];
foreach ($results as $row) {
    $cat_id = $row['category_id'];
    if (!isset($categories[$cat_id])) {
        $categories[$cat_id] = [
            'name' => $row['category_name'],
            'sort_order' => $row['sort_order'],
            'dishes' => []
        ];
    }
    if ($row['dish_id']) {
        $categories[$cat_id]['dishes'][] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mise en Place</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        .date-controls { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; }
        .category { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; }
        .category h2 { display: flex; justify-content: space-between; align-items: center; }
        .dish-list { margin-top: 10px; }
        .dish { display: flex; justify-content: space-between; align-items: center; margin: 4px 0; padding: 4px; border-bottom: 1px solid #eee; }
        .dish-left { flex-grow: 1; text-align: left; font-weight: bold; }
        .dish-right { display: flex; gap: 10px; align-items: center; position: relative; }
        .priority { font-weight: bold; }
        .optional { font-style: italic; opacity: 0.6; }
        .prepped { color: blue; }
        .print-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; overflow: auto; z-index: 999; padding: 20px; }
        textarea { resize: vertical; min-height: 30px; }
        .form-inline { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
        .star-toggle {
            cursor: pointer;
            display: inline-block;
            width: 24px;
            height: 24px;
            background: url('star-outlined.png') no-repeat center center;
            background-size: contain;
            transition: background 0.3s ease;
        }
        .star-toggle.active {
            background: url('star-filled.png') no-repeat center center;
            background-size: contain;
        }
        .status-icon { width: 16px; height: 16px; margin-right: 4px; vertical-align: middle; }
        .save-mep {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }
        .save-mep button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="/recepten/view.php" class="btn">← Terug naar recepten</a>
</div>

<h1>Mise en Place voor <span id="selected-date"><?= htmlspecialchars($date) ?></span></h1>

<div class="date-controls">
    <button onclick="changeDate(-1)">←</button>
    <input type="date" id="date-picker" value="<?= htmlspecialchars($date) ?>">
    <button onclick="changeDate(1)">→</button>
    <button onclick="goToday()">Vandaag</button>
    <button onclick="printMEPForSelectedDay()">PRINT MEP FOR THIS DAY</button>
</div>

<div class="form-inline">
    <form method="POST" action="add_category.php">
        <input type="text" name="name" placeholder="Nieuwe categorie..." required>
        <button type="submit">➕ Categorie</button>
    </form>

    <form method="POST" action="add_dish.php">
        <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
        <input type="text" name="name" placeholder="Nieuw gerecht..." required>
        <select name="category_id" required>
            <option value="">Kies categorie</option>
            <?php foreach ($all_categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">➕ Gerecht</button>
    </form>
</div>

<div id="mep-list">
    <?php foreach ($categories as $cat_id => $cat): ?>
        <div class="category" data-id="<?= $cat_id ?>">
            <h2>
                <span><?= htmlspecialchars($cat['name']) ?></span>
                <a href="delete_category.php?id=<?= $cat_id ?>&date=<?= $date ?>" onclick="return confirm('Categorie verwijderen?')">🗑️</a>
            </h2>
            <div class="dish-list" data-category-id="<?= $cat_id ?>">
                <?php foreach ($cat['dishes'] as $dish): ?>
                    <?php
                        $status = $dish['status'] ?? '';
$class = ($status == 'prepped') ? 'prepped' : (($status == 'optional') ? 'optional' : '');

                    ?>
                    <div class="dish <?= $class ?>" data-id="<?= $dish['dish_id'] ?>">
                        <div class="dish-left">
                            <?= htmlspecialchars($dish['dish_name']) ?>
                        </div>
                        <div class="dish-right">
                            <label><img src="0.png" class="status-icon"><input type="radio" name="status_<?= $dish['dish_id'] ?>" class="status-select" data-id="<?= $dish['dish_id'] ?>" value="from_scratch" <?= ($dish['status'] == 'from_scratch') ? 'checked' : '' ?>></label>
                            <label><img src="Fridge.png" class="status-icon"><input type="radio" name="status_<?= $dish['dish_id'] ?>" class="status-select" data-id="<?= $dish['dish_id'] ?>" value="prepped" <?= ($dish['status'] == 'prepped') ? 'checked' : '' ?>></label>
                            <label><img src="Question.png" class="status-icon"><input type="radio" name="status_<?= $dish['dish_id'] ?>" class="status-select" data-id="<?= $dish['dish_id'] ?>" value="optional" <?= ($dish['status'] == 'optional') ? 'checked' : '' ?>></label>
                            <label><img src="not_required.png" class="status-icon"><input type="radio" name="status_<?= $dish['dish_id'] ?>" class="status-select" data-id="<?= $dish['dish_id'] ?>" value="not_required" <?= ($dish['status'] == 'not_required') ? 'checked' : '' ?>></label>
                            <span class="star-toggle<?= ($dish['priority']) ? ' active' : '' ?>" data-id="<?= $dish['dish_id'] ?>"></span>
<textarea class="notes" data-id="<?= $dish['dish_id'] ?>" placeholder="Opmerking..."><?= htmlspecialchars($dish['notes'] ?? '') ?></textarea>
                            <a href="delete_dish.php?id=<?= $dish['dish_id'] ?>&date=<?= $date ?>" onclick="return confirm('Verwijderen?')">🗑️</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="save-mep">
    <button type="button" onclick="saveMEP()">SAVE MEP</button>
</div>

<script>
function saveMEP() {
    const data = gatherMEPData(); // Verzamel data uit de interface
    const date = currentDate;
    if (!confirm("Ben je zeker dat je deze MEP wil opslaan?")) return;
    fetch('save_mep.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            data: JSON.stringify(data),
            date: date
        })
    })
    .then(response => response.text())
    .then(result => {
        alert(result); // Melding dat alles goed is opgeslagen
    })
    .catch(error => {
        console.error('Fout bij opslaan:', error);
        alert('Er ging iets mis bij het opslaan.');
    });
}

function gatherMEPData() {
    const data = [];
    $('.dish').each(function () {
        const id = $(this).find('.star-toggle').data('id');
        const priority = $(this).find('.star-toggle').hasClass('active') ? 1 : 0;
        const status = $(this).find('.status-select:checked').val() || '';
        const notes = $(this).find('.notes').val() || '';
        const name = $(this).find('.dish-name').text().trim() || '';
        data.push({ id, name, status, notes, priority });
    });
    return data;
}
        const notes = $(`textarea.notes[data-id='${id}']`).val() || '';
        const name = $(`.dish-name[data-id='${id}']`).text().trim() || '';
        data.push({ id, name, status, notes, priority });
    });
    return data;
}
        const notesInput = document.querySelector(`textarea.notes[data-id='${id}']`);
        const nameEl = document.querySelector(`.dish-name[data-id='${id}']`); // optioneel
        const name = nameEl ? nameEl.textContent.trim() : '';
        const status = statusInput ? statusInput.value : '';
        const notes = notesInput ? notesInput.value : '';
        data.push({ id, name, status, notes, priority });
    });
    return data;
}
    });
    return data;
}
    });
    return data;
}
    $('.dish').each(function () {
        const id = $(this).data('id');
        const name = $(this).find('.dish-left').text().trim();
        const notes = $(this).find('textarea').val().trim();
        const status = $(this).find('input[type=radio]:checked').val();
        const priority = $(this).find('.star-toggle').hasClass('active') ? '⭐' : '';
        data.push({ id, name, notes, status, priority });
    });

    existingSaves[currentDate] = data;
    localStorage.setItem('savedMEPData', JSON.stringify(existingSaves));
    alert('MEP opgeslagen!');
}


function printSavedMEP() {
    const data = JSON.parse(localStorage.getItem('savedMEP')) || [];
    let content = '<h1>MEP LIST</h1><ul>';
    data.forEach(item => {
        content += `<li>${item.priority} <strong>${item.name}</strong> [${item.status}] - ${item.notes}</li>`;
    });
    content += '</ul>';

    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>MEP Print</title></head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function printMEPForSelectedDay() {
    const date = document.getElementById('date-picker').value;
    window.open('print_mep.php?date=' + date, '_blank');
}


function changeDate(delta) {
    const input = document.getElementById('date-picker');
    const date = new Date(input.value);
    date.setDate(date.getDate() + delta);
    const newDate = date.toISOString().split('T')[0];
    window.location.href = '?date=' + newDate;
}

function goToday() {
    const today = new Date().toISOString().split('T')[0];
    window.location.href = '?date=' + today;
}

$(document).on('click', '.star-toggle', function () {
    const $el = $(this);
    const id = $el.data('id');
    const isActive = $el.hasClass('active') ? 0 : 1;
    $.post('update_mep.php', { id, field: 'priority', value: isActive }, () => {
        $el.toggleClass('active');
    });
});
</script>
// All existing PHP and HTML remains unchanged...

<script>
$(function () {
    // Categorieën sorteren
    $('#mep-list').sortable({
        handle: 'h2',
        items: '.category',
        update: function () {
            const sortedIDs = $(this).children('.category').map(function () {
                return $(this).data('id');
            }).get();
            $.post('reorder_categories.php', { order: sortedIDs });
        }
    });

    // Gerechten sorteerbaar maken binnen elke categorie
    function initDishSorting() {
        $('.dish-list').sortable({
            items: '.dish',
            connectWith: '.dish-list',
            update: function () {
                const categoryID = $(this).data('category-id');
                const sortedDishIDs = $(this).children('.dish').map(function () {
                    return $(this).data('id');
                }).get();
                $.post('reorder_dishes.php', { category_id: categoryID, order: sortedDishIDs });
            }
        });
    }

    // Initialiseren
    initDishSorting();
});
</script>

</body>
</html>


