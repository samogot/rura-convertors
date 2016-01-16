<?php
namespace Ruranobe\Converters;

require_once(__DIR__ . '/../lib/docx/htmltodocx_converter/h2d_htmlconverter.php');
require_once(__DIR__ . '/../lib/docx/example_files/styles.inc');
require_once(__DIR__ . '/../lib/docx/documentation/support_functions.inc');

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
		$descr['author'] = "";
        foreach ([$this->author, $this->illustrator] as $aut) {
            if ($aut) {
                foreach (explode(',', $aut) as $au) {
                    $a = explode(' ', trim($au));
                    if (!empty($a[1])) {
                        $descr['author'] .= array_shift($a) . ' ';
                        $descr['author'] .= array_pop($a);
                        if ($a) {
                            $descr['author'] .= ' ' . implode(' ', $a) . ' ';
                        }
                    } else {
                        $descr['author'] .= $a[0];
                    }
                    $descr['author'] .= "\n";
                }
            }
        }
		
		$descr['annotation'] = '';
		if($this->annotation) {
			$this->annotation = preg_replace('@\n@', '</p><p>', $this->annotation);
			$this->annotation = preg_replace("@'''(.*?)'''@", '<b>\\1</b>', $this->annotation);
			$this->annotation = preg_replace("@''(.*?)''@", '<i>\\1</i>', $this->annotation);
			$this->annotation = preg_replace('@<p></p>@', '<br/>', $this->annotation);
			$descr['annotation'] = "<h2>Аннотация</h2><p>$this->annotation</p>";
        }
		
        $images = [];
        if ($this->covers) {
			$cover = $this->covers[0];
			$image = $this->images[$cover];
			$thumbnail = sprintf($image['thumbnail'],
			                     min($image['width'], floor($this->height * $image['width'] / $image['height']));
			$imagename = preg_replace('#^https?://#', '', $thumbnail);
			$descr['coverpage'] = "<div class=\"center\"><img src=\"images/" . str_replace(
                        ' ',
                        '_',
                        $imagename
                    ) . "\" alt=\"" . str_replace(' ', '_', $imagename) . "\"/></div>";
			$images[]             = $cover;
            $descr['coverpage_n'] = $cover;			
        }

        if ($this->translators) {
            foreach ($this->translators as $translator) {
                $descr['translator'] .= "<p class=\"translator\">$translator</p>";
            }
        }

        if ($this->seriestitle) {
            $descr['sequence'] = "{$this->seriestitle}" . ($this->seriesnum ? " {$this->seriesnum}" : '');
        }

        $descr['date2'] = date('j F Y, H:i', strtotime($this->touched));
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
						  <p>А так же счет для перевода с кредитных карт:</p>
						  <p><b>4890 4941 5384 9302</b></p>
						  <p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
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
						  <p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
						  <p><b>Любое коммерческое использование данного текста или его фрагментов запрещено</b></p>';
        } else {
            $credit = "<h2>Реквизиты переводчиков</h2>";
            if ($this->command) {
                $credit .= "<p>Перевод команды {$this->command}</p>";
            }
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <b>' . implode('</b>, <b>', $workers) . "</b></p>\n";
            }
            $credit .= '<p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
						  <p><b>Любое коммерческое использование данного текста или его фрагментов запрещено</b></p>';
        }
        if ($this->height == 0) {
            $text = preg_replace('/(<p[^>]*>)?<img[^>]*>(<\/p>)?/u', '', $text);
        } else {
            $text = preg_replace_callback(
                '/(<a[^>]*>)?<img[^>]*data-resource-id="(\d*)"[^>]*>(<\/a>)?/u',
                function ($match) use (&$images) {
                    $image = $this->images[$match[2]];
					$thumbnail = sprintf($image['thumbnail'], 
					                     min($image['width'], floor($this->height * $image['width'] / $image['height']));
					$imagename = preg_replace('#^https?://#', '', $thumbnail);
					$images[] = $match[2];
                    return "<div class=\"center\"><img src=\"images/" . str_replace(
                        ' ',
                        '_',
                        $imagename
                    ) . "\" alt=\"" . str_replace(' ', '_', $imagename) . "\"/></div>";
					
                },
                $text
            );
			$firstImage = strpos($text,'<image');
			if ($firstImage !== false && $firstImage < strpos($text,'<h'))
			{
				$text = "<h2>Начальные иллюстрации</h2>" . $text;
			}
        }		
		
        $j         = 0;
        $notes     = '';
        $isnotes   = false;
        $footnotes = explode(',;,', $this->footnotes);
        for ($i = 0; $i < sizeof($footnotes); $i++) {
            if (is_numeric($footnotes[$i])) {
                $j = 0;
                if ($isnotes == false) {
                    $notes .= "<body name=\"notes\">\n\t<title><p>Примечания</p></title>\n";
                }
                $isnotes = true;
                $notes .= "\t<section id=\"cite_note-$footnotes[$i]\">\n\t\t<title><p>$j</p></title>\n\t\t<p>" . $footnotes[$i + 1] . "</p>\n\t</section>\n";
                $i++;
                $j++;
            }
        }
        if ($isnotes == true) {
            $notes .= '</body>';
        }

        $text     = trim($text);
        $epubText = "$descr[annotation]$credit$text$notes";

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

        $epub = new \PHPePub\Core\EPub();

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
        foreach ($chapters as $chapterbody) {
            if (strpos($chapterbody, '<h1') === 0 || strpos($chapterbody, '<h2') === 0) {
                $header = $chapterbody;
                continue;
            }

            if (preg_match('@<h[1,2]>(.*?)</h[1,2]>@s', $header, $match)) {
                $j           = $j + 1;
                $chaptername = $match[1];
                if ($chaptername === 'Примечания') // Primechania
                {
                    array_push($tableOfContentsLinks, "notes.xhtml");
                    array_push($tableOfContentsChapterNames, $chaptername);
                } else {
                    array_push($tableOfContentsLinks, "part-$j.xhtml");
                    array_push($tableOfContentsChapterNames, $chaptername);
                }
            }
        }
        /* Doublirovanie koda. Should be rewritten somehow in future. */

        $tableOfContents = $this->createTableOfContentsText($tableOfContentsLinks, $tableOfContentsChapterNames);
        $epub->addChapter('Содержание', "toc.xhtml", $tableOfContents, false);

        $j      = 0;
        $header = '';
        foreach ($chapters as $chapterbody) {
            if (strpos($chapterbody, '<h1') === 0 || strpos($chapterbody, '<h2') === 0) {
                $header = $chapterbody;
                continue;
            }

            if (preg_match('@<h[1,2]>(.*?)</h[1,2]>@s', $header, $match)) {
                $j           = $j + 1;
                $chaptername = $match[1];
                if ($chaptername === 'Примечания') // Primechania
                {
                    $chapter = $this->wrapHtmlInHtmlTags($header . $chapterbody, $chaptername);
                    $epub->addChapter($chaptername, "notes.xhtml", $chapter, false);
                } else {
                    $chapter = $this->wrapHtmlInHtmlTags($header . $chapterbody, $chaptername);
                    $epub->addChapter($chaptername, "part-$j.xhtml", $chapter, false);
                }
            }
        }

//echo '<xmp>'.$epubText;exit;
        //$epub->addChapter("part-1", "part", $epubText, true);

        //

		
		
        $i = 0;
		foreach ($images as $imageid) {
			$image = $this->images[$imageid];
			$aspectratio = ($image['width']) / ($image['height']);
			if ($this->height > 0) {
				if ($aspectratio > 1.0) {
					$resizedwidth  = $this->height;
					$resizedheight = $this->height / $aspectratio;
				} else {
					$resizedheight = $this->height;
					$resizedwidth  = ($this->height) * ($aspectratio);
				}

				$imageurl = sprintf($image['thumbnail'], 
				                    min($image['width'], floor($this->height * $image['width'] / $image['height']));
				$imagename = preg_replace('#^https?://#', '', $imageurl);
				
				$i = $i + 1;

				$epub->addFile(
					"images/" . str_replace(' ', '_', $imagename),
					"image-$i",
					file_get_contents($imageurl),
					$image['mime_type']
				);
			}
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