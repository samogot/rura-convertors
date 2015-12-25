<?php
namespace Ruranobe\Converters;

require_once(__DIR__ . '/../lib/docx/simplehtmldom/simple_html_dom.php');
require_once(__DIR__ . '/../lib/docx/htmltodocx_converter/h2d_htmlconverter.php');
require_once(__DIR__ . '/../lib/docx/example_files/styles.inc');
require_once(__DIR__ . '/../lib/docx/documentation/support_functions.inc');

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
            $descr['annotation'] = $this->annotation;
            $descr['annotation'] = trim($descr['annotation']);
            $descr['annotation'] = "<h2>Аннотация</h2>$descr[annotation]";
        } else {
            $descr['annotation'] = '';
        }

        $images = [];
        if ($this->cover) {
            $cover                = $this->cover;
            $descr['coverpage']   = "<coverpage><image l:href=\"#" . str_replace(' ', '_', $cover) . "\"/></coverpage>";
            $images[]             = $cover;
            $descr['coverpage_n'] = $cover;
        }

        if ($this->translators) {
            foreach ($this->translators as $translator) {
                $descr['translator'] .= "<p name=\"translator\">$translator</p>";
            }
        }

        if ($this->seriestitle) {
            $descr['sequence'] = "<h1>{$this->seriestitle}" . ($this->seriesnum ? " {$this->seriesnum}" : '') . " </h1>";
        }

        $descr['date2'] = date('j F Y, H:i', strtotime($this->touched));
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
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <b>' . implode('</b>, <b>', $workers) . "</b></p>\n";
            }
            $credit .= '<p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
						  <p><b>Любое коммерческое использование данного текста или его фрагментов запрещено</b></p>';
        }

        if ($this->height == 0) {
            $text = preg_replace('/(<p[^>]*>)?<img[^>]*>(<\/p>)?/u', '', $text);

        } else {
            $innerHeight = $this->height;
            $text        = preg_replace_callback(
                '/<img[^>]*data-resource-id="(\d*)"[^>]*>/u',
                function ($match) use (&$images) {					
					$image = $this->images[$match[1]];
					$thumbnail = sprintf($image['thumbnail'], $this->height);
				
				    $innerImageUrl = $image['url'];
					$innerResizedWidth = $image['width'];
					$innerResizedHeight = $image['height'];
				
                    if (file_get_contents($thumbnail, 0, null, 0, 1)) {
                        $innerAspectRatio = $image['width'] / $image['height'];
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

                        $innerImageUrl = sprintf($image['thumbnail'], $innerResizedWidth);						
                    }

                    /* Width and height are unimportant. Actual resizing is done not in this class. We must save aspect ratio though. */
                    return "<img src=\"" . $innerImageUrl . "\" width=\"" . $innerResizedWidth . "\" height=\"" . $innerResizedHeight . "\" />";
                },
                $text
            );
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

        /* Delete extra <br/> tag before images */
        $epubText = preg_replace('@<div>(.){0,20}<br/>(.){0,20}<img src@s', '<div><img src', $epubText);

        /* Swap h2 and img tags if img follows h2. (It gave a bad look in docx). Also deletes <pb/> tag which is
           redunt after swap. */
        $epubText = preg_replace('@<pb/>(<h2>.{0,100}</h2>).{0,20}(<img .{0,200}/>)@s', '\\2\\1', $epubText);
        $epubText = preg_replace('@(<h2>.{0,100}</h2>).{0,20}(<img .{0,200}/>)@s', '\\2\\1', $epubText);

        /* Delete extra <pb/> tag in case immediately before <pb/> there is an image */

        $epubText = preg_replace('@(<img.*?/>)(.{0,20})<pb/><h2>@', '\\1\\2<h2>', $epubText);

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

        $phpword_object = new \PhpOffice\PhpWord\PhpWord();
        \PhpOffice\PhpWord\Settings::setCompatibility(false);

        $html_dom = new \simple_html_dom();
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