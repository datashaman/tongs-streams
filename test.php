<?php

require_once 'vendor/autoload.php';
require_once 'filters.php';

class Pipe
{
    protected $config; 

    public function __construct($config)
    {
        $this->config = $config;
    }

    function build()
    {
        foreach (new DirectoryIterator($this->config['source']) as $info) {
            if ($info->isDot()) {
                continue;
            }

            $pathname = $info->getPathname();

            $source = fopen($pathname, 'r');

            foreach ($this->config['filters'] as $filterName => $filterDefn) {
                $params = $filterDefn === true ? [] : $filterDefn;

                stream_filter_append($source, $filterName, 0, $params);
            }

            $destination = stream_get_contents($source);
            var_dump($destination);
        }
    }
}

$pipe = new Pipe(
    [
        'source' => 'src',
        'filters' => [
            'markdown' => true,
            'twig' => [
                'paths' => [
                    'views',
                ],
            ],
        ],
    ]
);

$pipe->build();
