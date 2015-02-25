<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
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

    if ($exception instanceof \Lysine\Exception && ($more = $exception->getMore())) {
        $output .= '<h2>More Information</h2>';
        $output .= '<pre>'. var_export($more, true) .'</pre>';
    }

    if ($previous = $exception->getPrevious())
        $output = showException($previous) . $output;

    return $output;
}
