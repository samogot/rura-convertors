<?php
namespace Ruranobe\Converters;

require_once(__DIR__ . '/../lib/docx/simplehtmldom/simple_html_dom.php');
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
        if ($this->annotation) {
            $descr['annotation'] = "\n\n" . $this->annotation . "\n\n";
            $descr['annotation'] = preg_replace('@\'\'\'(.*?)\'\'\'@', "<b>\\1</b>", $descr['annotation']);
            $descr['annotation'] = preg_replace('@\'\'(.*?)\'\'@', "<i>\\1</i>", $descr['annotation']);
            $descr['annotation'] = preg_replace('/\n{3,}/', "\n\n\n\n", $descr['annotation']);
            $descr['annotation'] = preg_replace('/(?<=\n\n)(.*?)(?=\n\n)/s', "<p>\\1</p>", $descr['annotation']);
            $descr['annotation'] = preg_replace('/\n\n+/', "\n", $descr['annotation']);
            $descr['annotation'] = str_replace('<p><empty-line /></p>', '\n', $descr['annotation']);
            $descr['annotation'] = trim($descr['annotation']);
            $descr['annotation'] = "<h2>Аннотация</h2>$descr[annotation]";
        }

        $images = [];
        if ($this->cover) {
            if (Title::makeTitle(NS_FILE, $this->cover . '.png')->exists()) {
                $cover = $this->cover . '.png';
            } else {
                $cover = $this->cover . '.jpg';
            }

            $descr['coverpage']   = "<div class=\"center\"><img src=\"images/" . str_replace(
                    ' ',
                    '_',
                    $cover
                ) . "\" alt=\"" . str_replace(' ', '_', $cover) . "\"/></div>";
            $images[]             = $cover;
            $descr['coverpage_n'] = $cover;
        }

        if ($this->translators) {
            foreach (explode(',', $this->translators) as $tr) {
                $descr['translator'] .= "<p class=\"translator\">$tr</p>";
            }
        }

        if ($this->seriestitle) {
            $descr['sequence'] = "{$this->seriestitle}" . ($this->seriesnum ? " {$this->seriesnum}" : '');
        }

        $descr['date2']   = date('j F Y, H:i', strtotime($this->touched));
        $descr['version'] = $this->revcount;
        //$descr['src_url'] = Title::makeTitle(NS_MAIN, $this->nameurl)->getFullUrl('', false, PROTO_HTTP);
        $descr['id']      = 'RuRa_' . str_replace('/', '_', $this->nameurl);

        if ($this->isbn) {
            $descr['isbn'] = ";isbn:{$this->isbn}";
        }

        if ($this->command == 'RuRa-team') {
            $credit = "<h2>Реквизиты переводчиков</h2>
 				         <p>Над переводом работала команда <b>RuRa-team</b></p>\n";
            foreach (explode('|-', trim($this->workers)) as $wk) {
                $wk = explode('|', trim($wk));
                $credit .= "<p>$wk[1]: <b>$wk[3]</b></p>\n";
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
            foreach (explode('|-', trim($this->workers)) as $wk) {
                $wk = explode('|', trim($wk));
                $credit .= "<p>$wk[1]: <b>$wk[3]</b></p>\n";
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
            foreach (explode('|-', trim($this->workers)) as $wk) {
                $wk = explode('|', trim($wk));
                $credit .= "<p>$wk[1]: <b>$wk[3]</b></p>";
            }
            $credit .= '<p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
						  <p><b>Любое коммерческое использование данного текста или его фрагментов запрещено</b></p>';
        }

        $credit = $credit;

        $text = str_replace(
            '<span style="position: relative; text-indent: 0;"><span style="display: inline-block; font-style: normal">『　　　』</span><span style="position: absolute; font-size: .7em; top: -11px; left: 50%"><span style="position: relative; left: -50%;">',
            '[<sup>',
            $text
        );
        $text = str_replace('</span></span></span>', '</sup>]', $text);
        //$text = preg_replace('@『.*?』.{0,300}<span>([а-яА-Я]+?)</span>@s','『<sup>\1</sup>』',$text);
        $text = preg_replace('@<span id="\w+" class="ancor"></span>\n@', '', $text);
        $text = preg_replace('@</?span.*?>@', '', $text);
        $text = preg_replace('@</?nowiki>@', '', $text);
        $text = $this->wgParser->doDoubleUnderscore($text);
        $text = $this->wgParser->doHeadings($text);
        $text = preg_replace('@\'\'\'(.*?)\'\'\'@', "<b>\\1</b>", $text);
        $text = preg_replace('@\'\'(.*?)\'\'@', "<i>\\1</i>", $text);
        $text = $this->wgParser->replaceExternalLinks($text);
        //$text=preg_replace('@\s*()\s*@',"\n\n\\1\n\n",$text);
        $text = preg_replace(
            '@\s*(<h\d>.*</h\d>|\[\[File:.*\]\]|<center .*?(?:)>.*</center>)\s*@',
            "\n\n\\1\n\n",
            $text
        );
        $text = preg_replace('@(</center>|</h\d>|\]\])\n*(<center |<h\d>|\[\[File:)@', "\\1\n\n\\2", $text);
        $text = "\n\n" . trim($text) . "\n\n";
        $text = preg_replace('/\n{3,}/', "\n\n<empty-line />\n\n", $text);
        $text = preg_replace('/(?<=\n\n)(.*?)(?=\n\n)/s', "<p>\\1</p>", $text);
        $text = preg_replace('/\n\n+/', "\n", $text);
        //$text=preg_replace('@<(/?)b>@','<\b>',$text);
        //$text=preg_replace('@<(/?)i>@','<\i>',$text);
        $text = str_replace('<p><empty-line /></p>', '<br />', $text);
        $text = str_replace("<p><!--MWTOC--></p>\n?", '', $text);
        $text = preg_replace('@<p>(<h\d>.*?</h\d>)</p>@', '\1', $text);
        $text = preg_replace('@<p><center .*?(?:)>(.*?)</center></p>@', '<p class="center">\1</p>', $text);
        //$text=preg_replace('@(<p>)?<center .*?(?:)>(.*)</center>(</p>)?@',"<h2>\\2</h2>",$text);
        //$text=preg_replace('@\[(https?://[^ ]*) ([^\]]*)\]@','<a href="\1">\2</a>',$text);

        if ($this->height == 0) {
            $text = preg_replace('/(<p>)?\[\[File:.*\]\](<\/p>)?/u', '', $text);
        } else {
            $text = preg_replace_callback(
                '/(<p>)?\[\[File:(.*?)\|.*\]\](<\/p>)?/u',
                function ($match) use (&$images) {
                    $images[] = $match[2];
                    /* Width and height are unimportant. Actual resizing is done not in this class. We must save aspect ratio though. */
                    return "<div class=\"center\"><img src=\"images/" . str_replace(
                        ' ',
                        '_',
                        $match[2]
                    ) . "\" alt=\"" . str_replace(' ', '_', $match[2]) . "\"/></div>";
                },
                $text
            );
        }

        // $text=preg_replace('/(<p>)?(==+.*==+)(<\/p>)?/',"\\2",$text);
        // preg_match_all('/(^(={2,})[^\n]*\2$)(.{50})/ms',$text,$m);
        // for($i=1;$i<count($m[0]);$i++)
        // {
        // $d=strlen($m[2][$i])-strlen($m[2][$i-1]);
        // if($d>0)
        // {
        // for($j=0;$j<$d;$j++)
        // {
        // $text=str_replace($m[0][$i-1],$m[1][$i-1]."\n<section>".$m[3][$i-1],$text);
        // }
        // }
        // elseif($d<0)
        // {
        // for($j=0;$j<(-$d);$j++)
        // {
        // $text=str_replace($m[0][$i],"</section>\n".$m[0][$i],$text);
        // }
        // }
        // }
        // $text=preg_replace('/==+(.*?)==+/',"</section>\n<section>\n<h1>\\1</h1>",$text);
        // $text="<section>\n<br/>\n".trim($text);
        // for($i=0;$i<strlen($m[2][count($m[2])-1])-1;$i++)
        // $text.="\n</section>";
        // if(count($m[2])<1)	$text.="\n</section>";
        $text = preg_replace('@<br />(?!\s*<p>)\n*@', '', $text);
        //$text=preg_replace('@(<h1>.*?</h1>)\s*(<section>)\s*(<img .*?/>)@',"\\1\n\\3\n\\2",$text);
        $text = preg_replace('@(?<!</p>\n)<br />\n*@', '', $text);
        //$text=preg_replace('@<section>\s*</section>\n*@','',$text);
        //$text=preg_replace('@</h1>\s*</section>@',"</h1>\n<br/>\n</section>",$text);
        //echo '<xmp>'.$text;exit;

        if (preg_match_all('@<ref>(.*?)</ref>@s', $text, $m)) {
            $notes = "<h2>Примечания</h2>\n\t\n";
            for ($i = 1; $i <= count($m[0]); $i++) {
                /*
                Footnotes are temporary unavailable. Docx is a pain in an ass.*/

                $text = str_replace(
                    $m[0][$i - 1],
                    "<a epub:type=\"noteref\"  href=\"notes.xhtml#note$i\" class=\"reference\" id=\"ref$i\">[$i]</a>",
                    $text
                );
                $notes .= "\t<div class=\"notes\"><aside epub:type=\"rearnote\" class=\"note\" id=\"note$i\">$i. " . $m[1][$i - 1] . "</aside></div>\n";

            }
            //$notes.='</div>';
        }

        $text     = trim($text);
        $epubText = "$descr[annotation]$credit$text$notes";

        $epubText = preg_replace('@section@', "div", $epubText);

        /* Delete __TOC__ text */
        //$epubText=preg_replace('@<p>\s*__TOC__\s*</p>@','',$epubText);

        /* Delete extra <br/> tag before images */
        $epubText = preg_replace('@<div>(.){0,20}<br/>(.){0,20}<img src@s', '<div><img src', $epubText);

        /* Added page breaks before all <h2> */
        //$epubText=str_replace('<h2>', '<pb/><h2>', $epubText);

        /* Swap h2 and img tags if img follows h2. (It gave a bad look in docx). Also deletes <pb/> tag which is
           redunt after swap. */
        $epubText = preg_replace('@<pb/>(<h2>.{0,100}</h2>).{0,20}(<img .{0,200}/>)@s', '\\2\\1', $epubText);
        $epubText = preg_replace('@(<h2>.{0,100}</h2>).{0,20}(<img .{0,200}/>)@s', '\\2\\1', $epubText);

        /* Delete extra <pb/> tag in case immediately before <pb/> there is an image */
        $epubText = preg_replace('@(<img.*?/>)(.{0,20})<pb/><h2>@s', '\\1\\2<h2>', $epubText);

        /* After swap we often needs to further lift img tag in previous <div> tag */
        $epubText = preg_replace(
            '@</div>.{0,20}<div>.{0,20}(<img.{0,200}/>).{0,20}<h2@s',
            '\\1</div><div><h2',
            $epubText
        );

        /* After swap we often needs to further lift img tag in previous <div> tag */
        $epubText = preg_replace(
            '@</div>.{0,20}<div>.{0,20}(<img.{0,200}/>).{0,20}<h2@s',
            '\\1</div><div><h2',
            $epubText
        );

        /* Eliminate caret return before <h1> (Each div starts with caret return in h2d_htmlconverter.php) */
        $epubText = preg_replace('@\s*<div>(.{0,40})(<h1>.*?</h1>)@s', '\\1\\2<div>', $epubText);

        /* Suddenly realized, that without page breaks it is better). Just delete the next line to add page breaks. */
        $epubText = preg_replace('@pb@', "br", $epubText);

        /*
           ---------------------------------------------------------------------------------------------------
           Common part (Some pieces like images were docx specific though) with DOCX Ended. Epub specific part
           ---------------------------------------------------------------------------------------------------
        */

        /* Eliminate warning "element "br" not allowed here;..." */
        $epubText = preg_replace('@(<br.*?/>)@', "<p>\\1</p>", $epubText);

        $epub = new EPub();
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

        $i      = 0;
        $images = RepoGroup::singleton()->getLocalRepo()->findFiles($images);
        foreach ($images as $imagename => $imagefile) {
            $aspectratio = ((int)$imagefile->width) / ((int)$imagefile->height);
            if ($this->height > 0) {
                if ($aspectratio > 1.0) {
                    $resizedwidth  = $this->height;
                    $resizedheight = $this->height / $aspectratio;
                } else {
                    $resizedheight = $this->height;
                    $resizedwidth  = ($this->height) * ($aspectratio);
                }

                $imageurl = $_SERVER['DOCUMENT_ROOT'] . $imagefile->transform(
                        [
                            'width'  => $resizedwidth,
                            'height' => $resizedheight,
                        ]
                    )->getUrl();
                //$imageurl=$_SERVER['DOCUMENT_ROOT'].$imagefile->transform(array('width'=>$this->height*2+300,'height'=>$this->height))->getUrl();
                $i = $i + 1;

                $epub->addFile(
                    "images/" . str_replace(' ', '_', $imagename),
                    "image-$i",
                    file_get_contents($imageurl),
                    mime_content_type($imageurl)
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
        $epub->setDescription("EPubConverter. Author: Keiko. Troublshooting: convert_fb2@ruranobe.ru");
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