<?php
namespace Ruranobe\Converters;
/**
 * Class Fb2Converter
 * Author: Samogot
 */
class Fb2Converter extends Converter
{
    protected function getMime()
    {
        return "application/x-fictionbook; charset=utf-8";
    }
    protected function getExt()
    {
        return ".fb2";
    }
    protected function convertImpl($text)
    {
        $descr['book_title'] = $this->nameru;
        $descr['author'] = "";
        foreach ([$this->author, $this->illustrator] as $aut) {
            if ($aut) {
                foreach (explode(',', $aut) as $au) {
                    $a = explode(' ', trim($au));
                    $descr['author'] .= "<author>";
                    if (isset($a[1])) {
                        $descr['author'] .= '<first-name>' . array_shift($a) . '</first-name>';
                        $descr['author'] .= '<last-name>' . array_pop($a) . '</last-name>';
                        if ($a) {
                            $descr['author'] .= '<middle-name>' . implode(' ', $a) . '</middle-name>';
                        }
                    } else {
                        $descr['author'] .= "<nickname>$a[0]</nickname>";
                    }
                    $descr['author'] .= "</author>";
                }
            }
        }
        if($this->annotation) {
			$this->annotation = preg_replace('@\n@', '</p><p>', $this->annotation);
			$this->annotation = preg_replace("@'''(.*?)'''@", '<strong>\\1</strong>', $this->annotation);
			$this->annotation = preg_replace("@''(.*?)''@", '<emphasis>\\1</emphasis>', $this->annotation);
			$this->annotation = preg_replace('@<p></p>@', '<empty-line/>', $this->annotation);		
        	$descr['annotation'] = '<annotation><p>' . $this->annotation . '</p></annotation>';
        }
        $images = [];
        if ($this->covers) {
            $cover                = $this->covers[0];
			$image                = $this->images[$cover];
			// $thumbnail            = sprintf($image['thumbnail'], $this->height);
			$title                = $image['title'];
            $descr['coverpage']   = "<coverpage><image l:href=\"#" . str_replace(' ', '_', $title) . "\"/></coverpage>";
            $images[]             = $cover;
            // $descr['coverpage_n'] = $cover;
        }
        $descr['translator'] = "";
        if ($this->translators) {
            foreach ($this->translators as $translator) {
                $descr['translator'] .= "<translator><nickname>$translator</nickname></translator>";
            }
        }
        if ($this->seriestitle) {
            $descr['sequence'] = "<sequence name=\"{$this->seriestitle}\"" . ($this->seriesnum ? " number=\"{$this->seriesnum}\"" : '') . " />";
        }
        $descr['date2'] = '<date value=' . date('"Y-m-d">j F Y, H:i', strtotime($this->touched)) . '</date>';
        $descr['id'] = 'RuRa_' . str_replace('/', '_', $this->nameurl);
        $descr['isbn'] = "";
        if ($this->isbn) {
            $descr['isbn'] = "<publish-info><isbn>{$this->isbn}</isbn></publish-info>";
        }
        if ($this->command == 'RuRa-team') {
            $credit = "<section>
					<title><p>Реквизиты переводчиков</p></title>
					<p>Над переводом работала команда <strong>RuRa-team</strong></p>\n";
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <strong>' . implode('</strong>, <strong>', $workers) . "</strong></p>\n";
            }
            $credit .= '<p>Самый свежий перевод всегда можно найти на сайте нашего проекта:</p>
					<p><a l:href="http://ruranobe.ru">http://ruranobe.ru</a></p>
					<p>Чтобы оставаться в курсе всех новостей, вступайте в нашу группу в Контакте:</p>
					<p><a l:href="http://vk.com/ru.ranobe">http://vk.com/ru.ranobe</a></p>
					<empty-line/>
					<p>Для желающих отблагодарить переводчика материально имеются webmoney-кошельки команды:</p>
					<p><strong>R125820793397</strong></p>
					<p><strong>U911921912420</strong></p>
					<p><strong>Z608138208963</strong></p>
					<p>QIWI-кошелек:</p>
					<p><strong>+79116857099</strong></p>
					<p>Яндекс-деньги:</p>
					<p><strong>410012692832515</strong></p>
                    <p>PayPal:</p>
                    <p><strong>paypal@ruranobe.ru</strong></p>
					<p>А так же счет для перевода с кредитных карт:</p>
					<p><strong>4890 4941 5384 9302</strong></p>
					<empty-line/>
					<p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
					<empty-line/>
					<empty-line/>
					<empty-line/>
					<p><strong>Любое распространение перевода за пределами нашего сайта запрещено. Если вы скачали файл на другом сайте - вы поддержали воров</strong></p>
					<empty-line/>
					<empty-line/>
					<empty-line/>
					</section>';
        } elseif (strpos($this->command, 'RuRa-team') !== false) {
            $credit = "<section>
					<title><p>Реквизиты переводчиков</p></title>
					<p>Над релизом работали {$this->command}</p>\n";
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <strong>' . implode('</strong>, <strong>', $workers) . "</strong></p>\n";
            }
            $credit .= '<p>Самый свежий перевод всегда можно найти на сайте нашего проекта:</p>
					<p><a l:href="http://ruranobe.ru">http://ruranobe.ru</a></p>
					<p>Чтобы оставаться в курсе всех новостей, вступайте в нашу группу в Контакте:</p>
					<p><a l:href="http://vk.com/ru.ranobe">http://vk.com/ru.ranobe</a></p>
					<empty-line/>
					<p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
					<empty-line/>
					<p><strong>Любое коммерческое использование данного текста или его фрагментов запрещено</strong></p>
					</section>';
        } else {
            $credit = "<section>\n<title><p>Реквизиты переводчиков</p></title>\n";
            if ($this->command) {
                $credit .= "<p>Перевод команды {$this->command}</p>\n";
            }
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <strong>' . implode('</strong>, <strong>', $workers) . "</strong></p>\n";
            }
            $credit .= '<p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
					<empty-line/>
					<p><strong>Любое коммерческое использование данного текста или его фрагментов запрещено</strong></p>
					</section>';
        }
        if ($this->height == 0) {
            $text = preg_replace('/(<p[^>]*>)?<img[^>]*>(<\/p>)?/u', '', $text);
        } else {
        	for ($i = 1; $i < count($this->covers); ++$i) {
				$image = $this->images[$this->covers[$i]];
				$images[] = $this->covers[$i];
				$title = $image['title'];
				$text = "<image l:href=\"#" . str_replace(' ', '_', $title) . "\"/>" . $text;
        	}
            $text = preg_replace_callback(
                '/(<a[^>]*>)?<img[^>]*data-resource-id="(\d*)"[^>]*>(<\/a>)?/u',
                function ($match) use (&$images) {
					$image = $this->images[$match[2]];
					$images[] = $match[2];
					$title = $image['title'];
                    return "<image l:href=\"#" . str_replace(' ', '_', $title) . "\"/>";
                },
                $text
            );
            $firstImage = strpos($text,'<image');
            if($firstImage !== false && $firstImage < strpos($text,'<h'))
				$text = "<h2>Начальные иллюстрации</h2>" . $text;
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
        $binary = "";
		
        if ($images) {
            foreach ($images as $imageid) {
				$image = $this->images[$imageid];
				$thumbnail = sprintf($image['thumbnail'], 
					min($image['width'], floor($this->height * $image['width'] / $image['height'])));
                $fileContents = file_get_contents($thumbnail);
                if ($fileContents) {
					$title = $image['title'];
                    $binary .= '<binary id="' . $title . '" content-type="' . $image['mime_type'] . '">' . "\n" . base64_encode(
                            $fileContents
                        ) . "\n</binary>";
                }
            }
        }
		
		
		// replace invalid for fb2 format h1-h4 headers with title
		$text = preg_replace('@<h1[^>]*>([^<]*)<\/h1@', '<title><p>\\1</p></title', $text);
		$text = preg_replace('@<h2[^>]*>([^<]*)<\/h2@', '<title><p>\\1</p></title', $text);
		$text = preg_replace('@<h3[^>]*>([^<]*)<\/h3@', '<title><p>\\1</p></title', $text);
		$text = preg_replace('@<h4[^>]*>([^<]*)<\/h4@', '<title><p>\\1</p></title', $text);
		
		// delete unnecessary paragraph tag
		$text = preg_replace('@<p[^>]*><title>.*?</title></p>@', '', $text);
		
		// insert sections
		$text = preg_replace('@<title>@', '</section><section><title>', $text);
		
		// delete first </section> tag which doesn't have a matching <section> tag because of the previous step
		$text = preg_replace('@</section><section><title>@', '<section><title>', $text, 1);
		
		// insert closing section tag at the end
		$text .= "</section>";

		// where is heading levels - nestsd sections?
		// where is subtitle tag?
		// need to escape extra < > ang & symbols
		
		// delete p tag attributes such as data-chapter-id and so on.
		$text = preg_replace('@<p[^>]*>@', '<p>', $text);
		
		// delete strange tags combination which i saw once in fb2 
		$text = preg_replace('@<p></p>@', '<empty-line/>', $text);		
		
		// xml tags in attributes are not supported. Delete xml tags from data-content attribute
		//$text = preg_replace('@(data-content[^\"]*\"[^\"]*)<[^>]*>([^\<]*)<\/[^>]*>([^\"]*\")@', '\\1\\2\\3', $text);	
		
		// delete data-content attribute
		$text = preg_replace('@data-content.*?class="[^>]*@', '', $text);	
		//$text = preg_replace('@(data-content[^\"]*\"[^\"]*\")@', '', $text);	
		
		// replace <i>text</i> with <emphasis>text</emphasis> and <b>text</b> with <strong>text</strong>
		$text = preg_replace('@<i>(.*?)<\/i>@', '<emphasis>\\1</emphasis>', $text);	
		$text = preg_replace('@<b>(.*?)<\/b>@', '<strong>\\1</strong>', $text);	
		
		// delete unsupported div tags
		$text = preg_replace('@<div[^>]*>(.*?)<\/div>@u', '\\1', $text);	
		
		// delete unsupported span tags
		$text = preg_replace('@<span[^>]*>(.*?)<\/span>@u', '\\1', $text);	
		
		// delete unsupported img tags
		$text = preg_replace('@<img [^>]*>(.*?)<\/img>@u', '\\1', $text);	
		$text = preg_replace('@<img.*?\/>@u', '', $text);	
		
		// change href to l:href and delete unsupported a attributes
		$text = preg_replace('@<a(.*?)(href=\"[^\"]*\")(.*?)>@', '<a l:\\2>', $text);	
		
		// replace <i>text</i> with <emphasis>text</emphasis> and <b>text</b> with <strong>text</strong>
		$notes = preg_replace('@<i>(.*?)<\/i>@', '<emphasis>\\1</emphasis>', $notes);	
		$notes = preg_replace('@<b>(.*?)<\/b>@', '<strong>\\1</strong>', $notes);	
		
		// change href to l:href and delete unsupported a attributes
		$notes = preg_replace('@<a(.*?)(href=\"[^\"]*\")(.*?)>@', '<a l:\\2>', $notes);	
		
		
		
//        $text = trim($text);
        $fb2 = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
	<FictionBook xmlns=\"http://www.gribuser.ru/xml/fictionbook/2.0\" xmlns:l=\"http://www.w3.org/1999/xlink\">
		<description>
			<title-info>
				<genre>sf</genre>
				$descr[author]
				<book-title>$descr[book_title]</book-title>
				$descr[annotation]
				$descr[coverpage]
				<lang>ru</lang>
				<src-lang>jp</src-lang>
				$descr[translator]
				$descr[sequence]
			</title-info>
			<document-info>
				<author>
					<nickname>Samogot</nickname>
					<home-page>http://ruranobe.ru/r/User:Samogot</home-page>
					<email>convert_fb2@ruranobe.ru</email>
				</author>
				<program-used>Samogot`s wiki to fb2 converter</program-used>
				$descr[date2]
				<src-url>$descr[src_url]</src-url>
				<id>$descr[id]</id>
				<version>1</version>
			</document-info>
			$descr[isbn]
		</description>
		<body>
			<title><p>$descr[book_title]</p></title>
			$credit
			$text
		</body>
	$notes
	$binary
	</FictionBook>";
        // $old=error_reporting( -1 );
        // ob_start();
        // $dom = new DOMDocument();
        // $dom->loadXML($fb2);
        // $valid=$dom->schemaValidate('FictionBook.xsd');
        // $err=ob_get_clean();
        // error_reporting( $old );
        // if(!$valid){
        // $message = "Возникла ошибка при конвертации $descr[src_url] в формат fb2 с размером изображений $this->height\nПодробности:\n\n$err\n";
        // $message = wordwrap($message, 70);
        // mail('convert_fb2@ruranobe.ru', "Fb2 Validation Error: $descr[src_url]", $message);
        // }
        return $fb2;
    }
}