<?php
namespace Ruranobe\Converters;

/**
 * Author: Keiko
 * Class EpubConverter
 * @package Ruranobe\Converters
 */
class EpubConverter extends Converter
{
    protected function getMime()
    {
        return "application/epub+zip";
    }

    protected function getExt()
    {
        return ".epub";
    }

    protected function convertImpl($text)
    {
        $descr['book_title'] = $this->nameru;
        $descr['author']     = "";
        foreach ([$this->author, $this->illustrator] as $aut) {
            if ($aut) {
                foreach (explode(',', $aut) as $au) {
                    $a = explode(' ', trim($au));
                    $descr['author'] .= $this->escapexml(trim($au));
                    $descr['author'] .= "\n";
                }
            }
        }

        $descr['annotation'] = '';
        if ($this->annotation) {
            $this->annotation    = preg_replace('@\n@', '</p><p>', $this->annotation);
            $this->annotation    = preg_replace("@'''(.*?)'''@", '<b>\\1</b>', $this->annotation);
            $this->annotation    = preg_replace("@''(.*?)''@", '<i>\\1</i>', $this->annotation);
            $this->annotation    = preg_replace('@<p></p>@', '<br/>', $this->annotation);
            $descr['annotation'] = "<h2>Аннотация</h2><p>$this->annotation</p>";
        }

        $descr['coverpage']='';
        $images = [];
        if ($this->covers) {
            $cover                = $this->covers[0];
            $image                = $this->images[$cover];
            $title                = "img_{$cover}.jpg";
            $descr['coverpage']   = "<div class=\"center\"><img src=\"images/{$title}\" alt=\"{$title}\"/></div>";
            $images[]             = $cover;
            $descr['coverpage_n'] = $cover;
        }

        if ($this->translators) {
            foreach ($this->translators as $translator) {
                if (!array_key_exists('translator', $descr)) {
                    $descr['translator'] = '';
                }
                $descr['translator'] .= "<p class=\"translator\">" . $this->escapexml($translator) . "</p>";
            }
        }

        if ($this->seriestitle) {
            $descr['sequence'] = $this->escapexml($this->seriestitle) . ($this->seriesnum ? " {$this->seriesnum}" : '');
        }

        $descr['date2'] = date('j F Y, H:i', $this->touched);
        //$descr['version'] = $this->revcount;
        //$descr['src_url'] = Title::makeTitle(NS_MAIN, $this->nameurl)->getFullUrl('', false, PROTO_HTTP);
        $descr['id'] = 'RuRa_' . str_replace('/', '_', $this->nameurl);

        if ($this->isbn) {
            $descr['isbn'] = ";isbn:{$this->isbn}";
        }

        if ($this->command == 'RuRa-team') {
            $credit = "<h2>Реквизиты переводчиков</h2>
 				         <p>Над переводом работала команда <b>RuRa-team</b></p>\n";
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <b>' . implode('</b>, <b>', $workers) . "</b></p>\n";
            }
            $credit .= '<p>Самый свежий перевод всегда можно найти на сайте нашего проекта:</p>
				          <p><a href="http://ruranobe.ru">http://ruranobe.ru</a></p>
 				          <p>Чтобы оставаться в курсе всех новостей, вступайте в нашу группу в Контакте:</p>
				          <p><a href="http://vk.com/ru.ranobe">http://vk.com/ru.ranobe</a></p>
						  <p>Для желающих отблагодарить переводчика материально имеются webmoney-кошельки команды:</p>
						  <p><b>R125820793397</b></p>
						  <p><b>U911921912420</b></p>
						  <p><b>Z608138208963</b></p>
						  <p>QIWI-кошелек:</p>
						  <p><b>+79116857099</b></p>
						  <p>Яндекс-деньги:</p>
						  <p><b>410012692832515</b></p>
                          <p>PayPal:</p>
                          <p><b>paypal@ruranobe.ru</b></p>
						  <p>А также счет для перевода с кредитных карт:</p>
						  <p><b>4890 4941 5384 9302</b></p>
						  <p>Версия от ' . date('d.m.Y', $this->touched) . '</p>
						  <p></p>
						  <p></p>
						  <p></p>
						  <p><b>Любое распространение перевода за пределами нашего сайта запрещено. Если вы скачали файл на другом сайте - вы поддержали воров</b></p>
						  <p></p>
						  <p></p>
						  <p></p>';
        } elseif (strpos($this->command, 'RuRa-team') !== false) {
            $credit = "<h2>Реквизиты переводчиков</h2>
						 <p>Над релизом работали {$this->command}</p>\n";
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <b>' . implode('</b>, <b>', $workers) . "</b></p>\n";
            }
            $credit .= '<p>Самый свежий перевод всегда можно найти на сайте нашего проекта:</p>
						  <p><a href="http://ruranobe.ru">http://ruranobe.ru</a></p>
						  <p>Чтобы оставаться в курсе всех новостей, вступайте в нашу группу в Контакте:</p>
						  <p><a href="http://vk.com/ru.ranobe">http://vk.com/ru.ranobe</a></p>
						  <p>Версия от ' . date('d.m.Y', $this->touched) . '</p>
						  <p><b>Любое коммерческое использование данного текста или его фрагментов запрещено</b></p>';
        } else {
            $credit = "<h2>Реквизиты переводчиков</h2>";
            if ($this->command) {
                $credit .= "<p>Перевод команды {$this->command}</p>";
            }
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <b>' . implode('</b>, <b>', $workers) . "</b></p>\n";
            }
            $credit .= '<p>Версия от ' . date('d.m.Y', $this->touched) . '</p>
						  <p><b>Любое коммерческое использование данного текста или его фрагментов запрещено</b></p>';
        }
        if ($this->height == 0) {
            $text = preg_replace('@(<p[^>]*>)?(<a[^>]*>)?<img[^>]*>(</a>)?(</p>)?@u', '', $text);
        } else {
            for ($i = 1; $i < count($this->covers); ++$i) {
                $image    = $this->images[$this->covers[$i]];
                $images[] = $this->covers[$i];
                $title    = "img_{$this->covers[$i]}.jpg";
                $text     = "<img src=\"images/{$title}\" alt=\"{$title}\"/>" . $text;
            }
            $text       = preg_replace_callback(
                '@(<a[^>]*>)?<img[^>]*data-resource-id="(-?\d*)"[^>]*>(</a>)?@u',
                function ($match) use (&$images) {
                    if($match[2] < 0) return '';
                    $image     = $this->images[$match[2]];
                    $images[]  = $match[2];
                    $title    = "img_{$match[2]}.jpg";
                    return "<img src=\"images/{$title}\" alt=\"{$title}\"/>";
                },
                $text
            );
            $firstImage = strpos($text, '<img');   
            $firstHeading = strpos($text,'<h');
            if($firstImage !== false && ($firstImage < $firstHeading || $firstHeading === false)) {
                $text = "<h2>Начальные иллюстрации</h2>" . $text;
            }
        }

        $j         = 1;
        $notes     = "<h2>Примечания</h2>\n\t\n";
        $isnotes   = false;
        $footnotes = explode(',;,', $this->footnotes);

        for ($i = 0; $i < sizeof($footnotes); $i++) {
            if (is_numeric($footnotes[$i])) {
                $isnotes = true;
                $text    = str_replace(
                    '<a href="#cite_note-' . $footnotes[$i] . '">*</a>',
                    '<a epub:type="noteref"  href="notes.xhtml#note' . $j . '" class="reference" id="ref' . $j . '">[' . $j . ']</a>',
                    $text
                );
                $notes .= "\t<div class=\"notes\"><aside epub:type=\"rearnote\" class=\"note\" id=\"note$j\"><p>$j. " . $footnotes[$i + 1] . "</p></aside></div>\n";
                $i++;
                $j++;
            }
        }
        if ($isnotes == true) {
        } else {
            $notes = '';
        }

        $text     = trim($text);
        $epubText = "$descr[annotation]$credit$text$notes";

        $epubText = preg_replace('@section@', "div", $epubText);

        /* Delete extra <br/> tag before images */
        $epubText = preg_replace('@<div>\s*<br/>\s*<img src@', '<div><img src', $epubText);

        // Delete extra page breaks related to images.
        $epubText = preg_replace('@<div[^>]*>\s*(<img[^>]*>)\s*</div>@', '\1', $epubText);
        $epubText = preg_replace('@<p[^>]*>\s*(<img[^>]*>)\s*</p>@', '\1', $epubText);

        /* Swap h2 and img tags if img follows h2. (It gave a bad look in docx). */
        $epubText = preg_replace('@(<h2>[^<]*</h2>)(<img[^>]*>)@', '\2\1', $epubText);

        /* After swap we often needs to further lift img tag in previous <div> or <p> tag */
        $epubText = preg_replace(
            '@</div>(<img[^>]*>)<h2@',
            '\1</div><h2',
            $epubText
        );
        $epubText = preg_replace(
            '@</p>(<img[^>]*>)<h2@',
            '\1</p><h2',
            $epubText
        );

        /*
           ---------------------------------------------------------------------------------------------------
           Common part (Some pieces like images were docx specific though) with DOCX Ended. Epub specific part
           ---------------------------------------------------------------------------------------------------
        */

        /* Eliminate warning "element "br" not allowed here;..." */
        $epubText = preg_replace('@(<br.*?/>)@', "<p>\\1</p>", $epubText);

        // delete p tag attributes such as data-chapter-id and so on.
        $epubText = preg_replace('@<p[^>]*>@', '<p>', $epubText);

        // delete strange tags combination which i saw once in fb2
        $epubText = preg_replace('@<p></p>@', '<br/>', $epubText);

        $epub = new \PHPePub\Core\EPub(\PHPePub\Core\EPub::BOOK_VERSION_EPUB3, 'ru');

        $epub->setLanguage("ru");

        $epub->addCSSFile("epub_styles.css", "styles", file_get_contents(__DIR__ . "/epub_styles.css"));

        $coverpagetext = $descr['coverpage'] . "<p class=\"author\">" . $descr['author'] . "</p><p class=\"sequence\">" . $descr['sequence'] . "</p>";
        $coverpage     = $this->wrapHtmlInHtmlTags($coverpagetext, 'cover');
        $epub->addChapter('cover', "cover.xhtml", $coverpage, false);

        $chapters = preg_split(
            '@(<h[1,2]>.*?</h[1,2]>)@',
            $epubText,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );

        /* ------------------------------------------------- */

        $j                           = 0;
        $header                      = '';
        $tableOfContentsLinks        = [];
        $tableOfContentsChapterNames = [];
        $tableOfContentsBodies       = [];
        foreach ($chapters as $chapterbody) {
            if (strpos($chapterbody, '<h1') === 0 || strpos($chapterbody, '<h2') === 0) {
                $header = $chapterbody;
                continue;
            }

            if (preg_match('@<h[1,2]>(.*?)</h[1,2]>@s', $header, $match)) {
                $j           = $j + 1;
                $chaptername = $match[1];
                array_push($tableOfContentsLinks, "part-$j.xhtml");
                array_push($tableOfContentsChapterNames, $chaptername);
                array_push($tableOfContentsBodies, $header . $chapterbody);
            }
        }

        $tocSize = count($tableOfContentsChapterNames);
        if($tableOfContentsChapterNames[$tocSize - 1] === 'Примечания') {
            $tableOfContentsLinks[$tocSize - 1] = "notes.xhtml";
        }

        $tableOfContents = $this->createTableOfContentsText($tableOfContentsLinks, $tableOfContentsChapterNames);
        $epub->addChapter('Содержание', "toc.xhtml", $tableOfContents, false);

        for($i = 0; $i < $tocSize; ++$i) {
            $chapter = $this->wrapHtmlInHtmlTags($tableOfContentsBodies[$i], $tableOfContentsChapterNames[$i]);
            $epub->addChapter($tableOfContentsChapterNames[$i], $tableOfContentsLinks[$i], $chapter, false);
        }

        //

        $i = 0;
        foreach ($images as $imageid) {
            $image = $this->images[$imageid];
            $i = $i + 1;
            $epub->addFile(
                "images/img_{$imageid}.jpg",
                "image-$i",
                file_get_contents($image['thumbnail']),
                'image/jpeg'//$image['mime_type']
            );
        }

        if ($this->isbn) {
            $epub->setIdentifier($this->isbn, 'ISBN');
        } else {
            $epub->setIdentifier($epub->createUUID(), 'UUID');
        }

        if (!empty($descr['sequence'])) {
            $epub->setTitle($descr['sequence']);
        } else {
            $epub->setTitle($this->namemain);
        }
        $epub->setDescription("EPubConverter. Author: Keiko. Troubleshooting: convert_fb2@ruranobe.ru");
        $epub->setDate($this->touched);
        $epub->finalize();

        $bin = $epub->getBook();
        return $bin;
    }

    private function wrapHtmlInHtmlTags($text, $title)
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:epub=\"http://www.idpf.org/2007/ops\"><head><title>" . $title . "</title></head>
			<body>" . $text . "</body></html>";
    }

    private function createTableOfContentsText(&$tableOfContentsLinks, &$tableOfContentsChapterNames)
    {
        $tableOfContents = "<section class=\"frontmatter TableOfContents\" epub:type=\"frontmatter toc\">
				 <header>
					<h1>Содержание</h1>
				 </header>
				 <nav xmlns:epub=\"http://www.idpf.org/2007/ops\" epub:type=\"toc\" id=\"toc\">
				 <ol>";

        for ($i = 0; $i < count($tableOfContentsChapterNames); $i++) {
            $tableOfContents .= "<li><a href=\"" . $tableOfContentsLinks[$i] . "\">" . $tableOfContentsChapterNames[$i] . "</a></li>";
        }

        $tableOfContents .= "</ol></nav></section>";
        return $this->wrapHtmlInHtmlTags($tableOfContents, 'Contents');
    }
}