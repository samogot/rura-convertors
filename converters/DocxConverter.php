<?php
namespace Ruranobe\Converters;

require_once(__DIR__ . '/../lib/docx/simplehtmldom/simple_html_dom.php');
require_once(__DIR__ . '/../lib/docx/htmltodocx_converter/h2d_htmlconverter.php');
require_once(__DIR__ . '/../lib/docx/example_files/styles.inc');
require_once(__DIR__ . '/../lib/docx/documentation/support_functions.inc');

$wgShowSQLErrors        = 1;
$wgShowExceptionDetails = true;
$wgDevelopmentWarnings  = true;
error_reporting(-1);
ini_set('display_errors', 1);

/**
 * Author: Keiko
 * Class DocxConverter
 * @package Ruranobe\Converters
 */
class DocxConverter extends Converter
{
    protected function getMime()
    {
        return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
    }

    protected function getExt()
    {
        return ".docx";
    }

    protected function convertImpl($text)
    {
        $descr['book_title'] = $this->nameru;
        foreach ([$this->author, $this->illustrator] as $aut) {
            if ($aut) {
                foreach (explode(',', $aut) as $au) {
                    $a               = explode(' ', trim($au));
                    $descr['author'] = (isset($descr['author']) ? $descr['author'] : '') . "<h1>";
                    if (!empty($a[1])) {
                        $descr['author'] .= array_shift($a) . ' ';
                        $descr['author'] .= array_pop($a);
                        if ($a) {
                            $descr['author'] .= ' ' . implode(' ', $a) . ' ';
                        }
                    } else {
                        $descr['author'] .= $a[0];
                    }
                    $descr['author'] .= "</h1>";
                }
            }
        }

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
        } else {
            $descr['annotation'] = '';
        }

        $images = [];
        if ($this->cover) {
            /*if (Title::makeTitle(NS_FILE, $this->cover . '.png')->exists()) {
                $cover = $this->cover . '.png';
            } else {
                $cover = $this->cover . '.jpg';
            }*/
            $cover = $this->cover;

            $imagefile   = RepoGroup::singleton()->getLocalRepo()->findFile($cover);
            $aspectratio = ((int)$imagefile->width) / ((int)$imagefile->height);
            $imageurl    = null;
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
                /* Width and height are unimportant. Actual resizing is done not in this class. We must save aspect ratio though. */
                $descr['coverpage'] = "<img src=\"" . $imageurl . "\" width=\"" . $imagefile->width . "\" height=\"" . $imagefile->height . "\" />";
            }
            $images[]             = $cover;
            $descr['coverpage_n'] = $cover;
        }

        if ($this->translators) {
            foreach (explode(',', $this->translators) as $tr) {
                $descr['translator'] .= "<p name=\"translator\">$tr</p>";
            }
        }

        if ($this->seriestitle) {
            $descr['sequence'] = "<h1>{$this->seriestitle}" . ($this->seriesnum ? " {$this->seriesnum}" : '') . " </h1>";
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
						  <p><a l:href="http://ruranobe.ru">http://ruranobe.ru</a></p>
						  <p>Чтобы оставаться в курсе всех новостей, вступайте в нашу группу в Контакте:</p>
						  <p><a l:href="http://vk.com/ru.ranobe">http://vk.com/ru.ranobe</a></p>
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
            '&#12302;<sup>',
            $text
        );
        //echo '<xmp>'.$text;die;
        //Пробелом
        $text = str_replace('</span></span></span>', '</sup>&#12303;', $text);
        //$text = preg_replace('@&#12302;.*?&#12303;.{0,300}<span>([а-яА-Я]+?)</span>@s','&#12302;<sup>\\1</sup>&#12303;',$text);
        $text = preg_replace('@<span id="\w+" class="ancor"></span>\n@', '', $text);
        $text = preg_replace('@</?span.*?\s*>@', '', $text);
        $text = preg_replace('@</?nowiki>@', '', $text);

        /*$text = $this->wgParser->doDoubleUnderscore( $text );
        $text = $this->wgParser->doHeadings( $text );*/

        $text = preg_replace('@\'\'\'(.*?)\'\'\'@', "<b>\\1</b>", $text);
        $text = preg_replace('@\'\'(.*?)\'\'@', "<i>\\1</i>", $text);

        /*$text = $this->wgParser->replaceExternalLinks( $text );*/

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
        $text = preg_replace('@<p><center .*?(?:)>(.*?)</center></p>@', '<center>\1</center>', $text);
        //$text=preg_replace('@(<p>)?<center .*?(?:)>(.*)</center>(</p>)?@',"<h2>\\2</h2>",$text);
        //$text=preg_replace('@\[(https?://[^ ]*) ([^\]]*)\]@','<a href="\1">\2</a>',$text);

        if ($this->height == 0) {
            $text = preg_replace('/(<p>)?\[\[File:.*\]\](<\/p>)?/u', '', $text);
        } else {
            $innerHeight = $this->height;
            $text        = preg_replace_callback(
                '/(<p>)?\[\[File:(.*?)\|.*\]\](<\/p>)?/u',
                function ($match) use (&$images, &$innerHeight) {
                    $images[] = $match[2];

                    $innerImageFile   = RepoGroup::singleton()->getLocalRepo()->findFile($match[2]);
                    $innerAspectRatio = $innerImageFile->width / $innerImageFile->height;
                    $innerImageUrl    = null;
                    if ($innerHeight > 0) {
                        if ($innerAspectRatio > 1.0) {
                            $innerResizedWidth  = $innerHeight;
                            $innerResizedHeight = $innerHeight / $innerAspectRatio;
                        } else {
                            $innerResizedHeight = $innerHeight;
                            $innerResizedWidth  = $innerHeight * $innerAspectRatio;
                        }
                    }

                    if (!$innerAspectRatio) {
                        return "";
                    }

                    $innerImageUrl = $_SERVER['DOCUMENT_ROOT'] . $innerImageFile->transform(
                            [
                                'width'  => $innerResizedWidth,
                                'height' => $innerResizedHeight,
                            ]
                        )->getUrl();
                    /* Width and height are unimportant. Actual resizing is done not in this class. We must save aspect ratio though. */
                    return "<img src=\"" . $innerImageUrl . "\" width=\"" . $innerImageFile->width . "\" height=\"" . $innerImageFile->height . "\" />";
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
                $text = str_replace($m[0][$i - 1], "<footnote>" . $m[1][$i - 1] . "</footnote>", $text);

                //$notes.="\n\t\t<footnote text=\"\">$i.\n\t\t"."   ".$m[1][$i-1]."</footnote>\n\t";
            }
            //$notes.='</div>';
        }

        $text     = trim($text);
        $epubText = "<html>
	<body>
		$descr[coverpage]
		$descr[author]
		$descr[sequence]
	        $descr[annotation]
		$credit
		$text
	</body>
	</html>";

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

        $epubText = preg_replace('@(<img.*?/>)(.{0,20})<pb/><h2>@', '\\1\\2<h2>', $epubText);

        //echo $epubText;exit;

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

        /* NGNL Specific names */
        //$text=str_replace('<span style="position: relative; text-indent: 0;"><span style="display: inline-block; font-style: normal">&#12302;&#12288;&#12288;&#12288;&#12303;</span><span style="position: absolute; font-size: .7em; top: -11px; left: 50%"><span style="position: relative; left: -50%;">','&#12302;<sup>',$text);
        //$text=str_replace('</span></span></span>','</sup>&#12303;',$text);

        // Styles of elements in which footnote is nested should not count. Thus close them
        $epubText = preg_replace('@pb@', "br", $epubText);

        //var_dump($epubText);

        //echo '<xmp>'.$epubText;exit;

        $phpword_object = new \PhpOffice\PhpWord\PhpWord();
        \PhpOffice\PhpWord\Settings::setCompatibility(false);

        $html_dom = new simple_html_dom();
        $html_dom->load($epubText);

        $html_dom_array = $html_dom->find('html', 0)->children();

        $paths = htmltodocx_paths();

        $initial_state = [
            // Required parameters:
            'phpword_object'                  => &$phpword_object,
            // Must be passed by reference.
            // 'base_root' => 'http://test.local', // Required for link elements - change it to your domain.
            // 'base_path' => '/htmltodocx/documentation/', // Path from base_root to whatever url your links are relative to.
            'base_root'                       => $paths['base_root'],
            'base_path'                       => $paths['base_path'],
            // Optional parameters - showing the defaults if you don't set anything:
            'current_style'                   => ['size' => '11'],
            // The PhpWord style on the top element - may be inherited by descendent elements.
            'parents'                         => [0 => 'body'],
            // Our parent is body.
            'list_depth'                      => 0,
            // This is the current depth of any current list.
            'context'                         => 'section',
            // Possible values - section, footer or header.
            'pseudo_list'                     => true,
            // NOTE: Word lists not yet supported (TRUE is the only option at present).
            'pseudo_list_indicator_font_name' => 'Wingdings',
            // Bullet indicator font.
            'pseudo_list_indicator_font_size' => '7',
            // Bullet indicator size.
            'pseudo_list_indicator_character' => 'l ',
            // Gives a circle bullet point with wingdings.
            'table_allowed'                   => true,
            // Note, if you are adding this html into a PhpWord table you should set this to FALSE: tables cannot be nested in PhpWord.
            'treat_div_as_paragraph'          => true,
            // If set to TRUE, each new div will trigger a new line in the Word document.
            'structure_headings'              => true,
            'structure_document'              => true,
            // Optional - no default:
            'style_sheet'                     => htmltodocx_styles_example(),
            // This is an array (the "style sheet") - returned by   htmltodocx_styles_example() here (in styles.inc) - see this function for an example of how to construct this array.
        ];

        htmltodocx_insert_html($phpword_object, $html_dom_array[0]->nodes, $initial_state);

        //$section->addText($epubText);

        //var_dump($phpword_object);
        //var_dump($html_dom_array[0]->nodes);
        $html_dom->clear();
        unset($html_dom);
        $h2d_file_uri = tempnam(sys_get_temp_dir(), 'htd');
        if ($h2d_file_uri === false) {
            var_dump(sys_get_temp_dir());
        }
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpword_object, 'Word2007');
        $objWriter->save($h2d_file_uri);

        $bin = file_get_contents($h2d_file_uri);
        unlink($h2d_file_uri);
        return $bin;
    }
}