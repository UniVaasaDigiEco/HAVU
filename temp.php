<?php
echo "JEE!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEST</title>
    <link rel="icon" type="image/x-icon" href="./favicon.ico">
    <link rel="stylesheet" href="css/bs-custom.css">
    <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-5 col-4">
    <h1>Testi</h1>
    <label for="date" class="form-label">Date</label>
    <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" max="<?php echo date('Y-m-d', strtotime('-18 years')) ?>">
</div>
</body>
</html>