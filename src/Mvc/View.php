<?php

namespace Owl\Mvc;

class View
{
    const BLOCK_REPLACE = 'replace';
    const BLOCK_PREPEND = 'prepend';
    const BLOCK_APPEND = 'append';

    protected $loaded_js = [];
    protected $loaded_css = [];

    protected $directory;
    protected $extend_view;
    protected $vars = [];
    protected $block_content = [];
    protected $block_stack = [];
    protected $included_view = [];

    /**
     * @param string $directory View file directory
     */
    public function __construct($directory)
    {
        if (!realpath($directory)) {
            throw new \Exception('View directory "'.$directory.'" not exist!');
        }

        $directory = realpath($directory);
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    public function __clone()
    {
        $this->reset();
    }

    /**
     * 重置视图对象
     *
     * @return $this
     */
    public function reset()
    {
        $this->extend_view = null;
        $this->vars = [];
        $this->block_content = [];
        $this->block_stack = [];

        return $this;
    }

    /**
     * 设置视图变量.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->vars[$key] = $value;
    }

    /**
     * 获得视图变量.
     *
     * @param string $key
     *
     * @return mixed|false
     */
    public function get($key)
    {
        return isset($this->vars[$key]) ? $this->vars[$key] : false;
    }

    /**
     * 渲染视图.
     *
     * @param string $view
     * @param array  $vars
     *
     * @return string
     */
    public function render($view, array $vars = [])
    {
        if ($vars) {
            $this->vars = array_merge($this->vars, $vars);
        }

        $output = $this->includeView($view, $this->vars, $return_content = true);

        while ($this->block_stack) {
            $this->endBlock();
        }

        if (!$extend_view = $this->extend_view) {
            return $output;
        }

        $this->extend_view = null;

        return $this->render($extend_view);
    }

    /**
     * 继承视图.
     *
     * @param string $view
     */
    protected function extendView($view)
    {
        $this->extend_view = $view;
    }

    /**
     * 包含视图片段.
     *
     * @param string $view
     * @param array  $vars
     * @param bool   $return_content
     *
     * @return void|string
     */
    protected function includeView($view, array $vars = [], $return_content = false)
    {
        $view_file = $this->directory.$view.'.php';

        if (!$file = realpath($view_file)) {
            throw new \Exception('View file "'.$view_file.'" not exist!');
        }

        // 安全性检查，视图文件必须在视图目录下
        if (strpos($file, $this->directory) !== 0) {
            throw new \Exception('Invalid view file "'.$file.'"');
        }

        $this->included_view[$view] = true;

        $vars = $vars ? array_merge($this->vars, $vars) : $this->vars;

        $level = ob_get_level();
        ob_start();

        try {
            extract($vars);
            require $file;
        } finally {
            while (ob_get_level() > $level+1) {
                ob_end_clean();
            }
        }

        $output = ob_get_clean();

        if ($return_content) {
            return $output;
        }

        echo $output;
    }

    /**
     * 载入视图片段，忽略重复载入.
     *
     * @param string $view
     */
    protected function includeViewOnce($view)
    {
        if (!isset($this->included_view[$view])) {
            $this->includeView($view);
        }
    }

    /**
     * 开始块.
     *
     * @param string $name
     * @param string $method
     */
    protected function beginBlock($name, $method = null)
    {
        $this->block_stack[] = [$name, $method ?: self::BLOCK_REPLACE];
        ob_start();
    }

    /**
     * 结束块.
     */
    protected function endBlock()
    {
        if (!$this->block_stack) {
            return;
        }

        list($block_name, $block_method) = array_pop($this->block_stack);
        $output = ob_get_clean();

        if (isset($this->block_content[$block_name])) {
            if ($block_method == self::BLOCK_PREPEND) {
                $output = $this->block_content[$block_name].$output;
            } elseif ($block_method == self::BLOCK_APPEND) {
                $output = $output.$this->block_content[$block_name];
            } else {
                $output = $this->block_content[$block_name];
            }
        }

        if ($this->extend_view && !$this->block_stack) {
            $this->block_content[$block_name] = $output;
        } else {
            unset($this->block_content[$block_name]);
            echo $output;
        }
    }

    /**
     * 把内容中的html特殊字符转码后输出.
     *
     * @param string
     */
    protected function eprint($string)
    {
        echo htmlspecialchars($string);
    }

    /**
     * 显示已经生成好的块.
     *
     * @param string $name
     * @param bool   $remove Remove content after show
     */
    protected function showBlock($name, $remove = true)
    {
        if (isset($this->block_content[$name])) {
            echo $this->block_content[$name];

            if ($remove) {
                unset($this->block_content[$name]);
            }
        }
    }

    /**
     * 在视图内载入js文件，忽略重复载入.
     *
     * @param string $file
     * @param array  $properties
     */
    protected function loadJs($file, array $properties = [])
    {
        if (!isset($this->loaded_js[$file])) {
            $this->loaded_js[$file] = true;

            $properties = array_merge($properties, [
                'type' => 'text/javascript',
                'src' => $file,
            ]);

            echo $this->buildElement('script', $properties);
        }
    }

    /**
     * 在视图内载入css文件，忽略重复载入.
     *
     * @param string $file
     * @param array  $properties
     */
    protected function loadCss($file, array $properties = [])
    {
        if (!isset($this->loaded_css[$file])) {
            $this->loaded_css[$file] = true;

            $properties = array_merge($properties, [
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => $file,
            ]);

            echo $this->buildElement('link', $properties);
        }
    }

    /**
     * 生成html标签.
     *
     * @param string $tag
     * @param array  $properties
     *
     * @return string
     */
    protected function buildElement($tag, array $properties)
    {
        $self_close = [
            'input' => true,
            'link' => true,
            'meta' => true,
        ];

        $props = [];
        foreach ($properties as $key => $value) {
            $props[] = $key.'="'.$value.'"';
        }
        $props = $props ? ' '.implode(' ', $props) : '';

        return isset($self_close[$tag])
             ? sprintf('<%s%s/>', $tag, $props)
             : sprintf('<%s%s></%s>', $tag, $props, $tag);
    }
}
