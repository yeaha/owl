<?php
namespace Tests;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $view = new \Owl\Mvc\View(TEST_DIR.'/fixture/view');

        $output = $view->render('page');
        $output = trim($output, "\n");

        $this->assertEquals('<html><body>foobar</body></html>', $output);
    }
}
