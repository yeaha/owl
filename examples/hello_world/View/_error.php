<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
</head>

<body>
<?php echo showException($exception); ?>
</body>
</html>

<?php
function showException($exception) {
    $output = '<h1>'. $exception->getMessage() .'</h1>';
    $output .= '<p>'. nl2br($exception->getTraceAsString()) .'</p>';

    if ($previous = $exception->getPrevious()) {
        $output = showException($previous) . $output;
    }

    return $output;
}
