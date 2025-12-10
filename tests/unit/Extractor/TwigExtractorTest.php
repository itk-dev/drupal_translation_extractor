<?php

declare(strict_types=1);

use Drupal\Core\Template\TwigTransTokenParser;
use Drupal\itk_translation_extractor\ItkTranslationExtractorTwigExtension;
use Drupal\itk_translation_extractor\Translation\TwigExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigExtractorTest extends TestCase
{
    public function testTransMethod(): void
    {
        $extractor = new TwigExtractor($this->twig());
        $resource = [
            __DIR__.'/resources/my_template.html.twig',
        ];
        $locale = 'da';
        $messages = new MessageCatalogue($locale);
        $extractor->extract($resource, $messages);

        $domains = $messages->getDomains();

        $this->assertCount(3, $domains);
        $this->assertContains('', $domains);
        $this->assertContains('the context', $domains);
        $this->assertContains('another context', $domains);
        $this->assertCount(3, $messages->all(''));
        $this->assertCount(3, $messages->all('the context'));
        $this->assertCount(2, $messages->all('another context'));
    }

    private function twig(): Environment
    {
        $twig = new Environment(new FilesystemLoader());
        $trans = fn () => null;
        $twig->addFilter(new Twig\TwigFilter('t', $trans));
        $twig->addFilter(new Twig\TwigFilter('trans', $trans));
        $twig->addExtension(new ItkTranslationExtractorTwigExtension());
        $twig->addTokenParser(new TwigTransTokenParser());

        return $twig;
    }
}
