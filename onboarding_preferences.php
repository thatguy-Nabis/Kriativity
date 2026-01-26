<?php
require_once "init.php";

// OPTIONAL (recommended in production)
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Personalize Your Feed</title>

<link rel="stylesheet" href="styles.css">

<style>
/* ===========================
   ONBOARDING CONTAINER
   =========================== */
.onboarding-box {
    max-width: 760px;
    margin: 4rem auto;
    background: linear-gradient(
        180deg,
        rgba(206,161,245,0.06),
        rgba(21,5,29,0.95)
    );
    border: 1px solid rgba(206,161,245,0.25);
    border-radius: 18px;
    padding: 3rem;
    box-shadow: 0 25px 60px rgba(0,0,0,0.45);
}

.onboarding-box h1 {
    color: #CEA1F5;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.onboarding-box p {
    color: #b9b9b9;
    margin-bottom: 2rem;
}

/* ===========================
   OPTION GRID
   =========================== */
.option-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin: 1.5rem 0 2.5rem;
}

/* ===========================
   OPTION CARD
   =========================== */
.option {
    position: relative;
    padding: 0.9rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(206,161,245,0.25);
    background: rgba(206,161,245,0.04);
    cursor: pointer;
    text-align: center;
    font-weight: 600;
    color: #e0e0e0;
    transition: all 0.25s ease;
    user-select: none;
}

/* hide native checkbox */
.option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

/* hover */
.option:hover {
    background: rgba(206,161,245,0.12);
    border-color: rgba(206,161,245,0.5);
    transform: translateY(-2px);
}

/* selected */
.option.selected {
    background: rgba(206,161,245,0.25);
    border-color: #CEA1F5;
    box-shadow: 0 0 0 1px rgba(206,161,245,0.6);
    color: #ffffff;
}

/* keyboard accessibility */
.option:focus-within {
    outline: 2px solid rgba(206,161,245,0.8);
    outline-offset: 2px;
}

/* ===========================
   SELECT DROPDOWN
   =========================== */
.form-control {
    width: 100%;
    padding: 0.8rem 1rem;
    border-radius: 12px;
    background: rgba(21,5,29,0.95);
    border: 1px solid rgba(206,161,245,0.25);
    color: #e0e0e0;
    margin-top: 0.5rem;
}

.form-control:focus {
    outline: none;
    border-color: #CEA1F5;
}

/* ===========================
   SUBMIT BUTTON
   =========================== */
.submit-btn {
    width: 100%;
    padding: 1rem;
    margin-top: 2.5rem;
    border-radius: 14px;
    border: none;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    color: #15051d;
    background: linear-gradient(135deg, #CEA1F5, #a66fd9);
    transition: all 0.25s ease;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(206,161,245,0.45);
}

.submit-btn:active {
    transform: translateY(0);
}

/* ===========================
   MOBILE
   =========================== */
@media (max-width: 480px) {
    .onboarding-box {
        padding: 2rem 1.5rem;
        margin: 2rem 1rem;
    }
}
</style>
</head>

<body>

<div class="onboarding-box">
    <h1>Tell us what you like 🎨</h1>
    <p>This helps us personalize your recommendations.</p>

    <form method="POST" action="handlers/save_preferences.php">

        <h3>Preferred Categories</h3>
        <div class="option-grid">
            <?php
            $categories = ['Art','Photography','Digital','AI','Design','Illustration','3D'];
            foreach ($categories as $cat) {
                echo "
                <label class='option'>
                    <input type='checkbox' name='categories[]' value='{$cat}'>
                    <span>{$cat}</span>
                </label>";
            }
            ?>
        </div>

        <h3>Content Type</h3>
        <div class="option-grid">
            <?php
            $types = ['Image','Video','Collection','Tutorial'];
            foreach ($types as $type) {
                echo "
                <label class='option'>
                    <input type='checkbox' name='content_types[]' value='{$type}'>
                    <span>{$type}</span>
                </label>";
            }
            ?>
        </div>

        <h3>What do you want to discover?</h3>
        <select name="goal" class="form-control" required>
            <option value="">Select one</option>
            <option value="inspiration">Creative Inspiration</option>
            <option value="learning">Learning & Tutorials</option>
            <option value="trending">Trending Works</option>
            <option value="community">Community & Artists</option>
        </select>

        <button class="submit-btn">Continue</button>
    </form>
</div>

<script>
/* ===========================
   FIXED SELECTION LOGIC
   =========================== */
document.querySelectorAll('.option input').forEach(input => {
    const option = input.closest('.option');

    // sync initial state (useful if reused later)
    if (input.checked) {
        option.classList.add('selected');
    }

    input.addEventListener('change', () => {
        option.classList.toggle('selected', input.checked);
    });
});
</script>

</body>
</html>
