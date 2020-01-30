<?php

use Illuminate\Support\Arr;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Webuni\FrontMatter\Document;
use Webuni\FrontMatter\FrontMatter;

class TongsFilter extends php_user_filter
{
    protected $frontmatter;
    public $params;

    public function filter(
        $in,
        $out,
        &$consumed,
        $closing
    ): int {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $data = $bucket->data;
            $consumed += $bucket->datalen;
            $bucket->data = $this->bucket($bucket->data);
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    public function onCreate(): bool
    {
        $this->frontmatter = new FrontMatter();

        return true;
    }

    protected function pipe(): Pipe
    {
        return $this->params['pipe'];
    }
}

class MarkdownFilter extends TongsFilter
{
    protected $parser;

    public function onCreate(): bool
    {
        if (!parent::onCreate()) {
            return false;
        }

        $this->parser = new Parsedown();

        return true;
    }

    public function bucket(string $data): string
    {
        $document = $this->frontmatter->parse($data);
        $document->setContent($this->parser->parse($document->getContent()));

        return $this->frontmatter->dump($document);
    }
}

class TwigFilter extends TongsFilter
{
    public $params;
    protected $twig;

    public function onCreate(): bool
    {
        if (!parent::onCreate()) {
            return false;
        }

        $params = $this->params;
        $paths = Arr::pull($this->params, 'paths', []);

        $loader = new FilesystemLoader($paths);
        $this->twig = new Environment($loader, $params);

        return true;
    }

    public function bucket(string $data): string
    {
        $document = $this->frontmatter->parse($data);
        $locals = $document->getDataWithContent('contents');

        return $this->twig->render($locals['view'] . '.html', $locals);
    }
}

stream_filter_register('markdown', MarkdownFilter::class);
stream_filter_register('twig', TwigFilter::class);
