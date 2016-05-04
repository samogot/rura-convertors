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
    private function escapexml($text)
    {
    	return htmlspecialchars($text, ENT_XML1, ini_get("default_charset"), false);
    }
    protected function convertImpl($text)
    {
        $descr['book_title'] = $this->escapexml($this->nameru);
        $descr['author'] = "";
        foreach ([$this->author, $this->illustrator] as $aut) {
            if ($aut) {
                foreach (explode(',', $aut) as $au) {
                    $a = explode(' ', trim($au));
                    $descr['author'] .= "<author>";
                    if (isset($a[1])) {
                        $fn = '<first-name>' . $this->escapexml(array_shift($a)) . '</first-name>';
                        $ln = '<last-name>' . $this->escapexml(array_pop($a)) . '</last-name>';

                        $descr['author'] .= $fn;
                        if ($a) {
                            $descr['author'] .= '<middle-name>' . $this->escapexml(implode(' ', $a)) . '</middle-name>';
                        }
                        $descr['author'] .= $ln;
                    } else {
                        $descr['author'] .= "<nickname>".$this->escapexml($a[0])."</nickname>";
                    }
                    $descr['author'] .= "</author>";
                }
            }
        }
        if(!$descr['author']) $descr['author'] = '<author><nickname></nickname></author>';
        $descr['annotation']='';
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
			$title                = $image['title'];
            $descr['coverpage']   = "<coverpage><image l:href=\"#img_$cover\"/></coverpage>";
            $images[]             = $cover;
        }
        $descr['translator'] = "";
        if ($this->translators) {
            foreach ($this->translators as $translator) {
                $descr['translator'] .= "<translator><nickname>".$this->escapexml($translator)."</nickname></translator>";
            }
        }
        if ($this->seriestitle) {
        	$this->seriesnum = floor($this->seriesnum);
            $descr['sequence'] = "<sequence name=\"".$this->escapexml($this->seriestitle)."\"" . ($this->seriesnum ? " number=\"{$this->seriesnum}\"" : '') . " />";
        }
        $descr['date2'] = '<date value=' . date('"Y-m-d">j F Y, H:i', $this->touched) . '</date>';
        $descr['id'] = 'RuRa_' . str_replace('/', '_', $this->nameurl);
        $descr['src_url'] = 'https://ruranobe.ru/r/' . $this->nameurl;
        $descr['isbn'] = "";
        if ($this->isbn) {
            $descr['isbn'] = "<publish-info><isbn>".$this->escapexml($this->isbn)."</isbn></publish-info>";
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
					<p>Версия от ' . date('d.m.Y', $this->touched) . '</p>
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
					<p>Версия от ' . date('d.m.Y', $this->touched) . '</p>
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
            $credit .= '<p>Версия от ' . date('d.m.Y', $this->touched) . '</p>
					<empty-line/>
					<p><strong>Любое коммерческое использование данного текста или его фрагментов запрещено</strong></p>
					</section>';
        }
        if ($this->height == 0) {
            $text = preg_replace('/(<p[^>]*>)?(<a[^>]*>)?<img[^>]*>(<\/a>)?(<\/p>)?/u', '', $text);
            $this->height = 1080;
        } else {
        	for ($i = 1; $i < count($this->covers); ++$i) {
				$image = $this->images[$this->covers[$i]];
				$images[] = $this->covers[$i];
				$title = $image['title'];
				$text = "<image l:href=\"#img_{$this->covers[$i]}\"/>" . $text;
        	}
            $text = preg_replace_callback(
				'/(<a[^>]*>)?<img[^>]*data-resource-id="(-?\d*)"[^>]*>(<\/a>)?/u',
				function ($match) use (&$images) {
					if($match[2] < 0) return '';
					$image = $this->images[$match[2]];
					$images[] = $match[2];
					$title = $image['title'];
                    return "<image l:href=\"#img_$match[2]\"/>";
                },
                $text
            );
        }
        $firstImage = strpos($text,'<image');            
        $firstHeading = strpos($text,'<h');
        if($firstImage !== false && ($firstImage < $firstHeading || $firstHeading === false) || substr($text, $firstHeading+2, 1)=="3")
			$text = "<h2>Начальные иллюстрации</h2>" . $text;
        $j         = 1;
        $notes     = '';
        $isnotes   = false;
        $footnotes = explode(',;,', $this->footnotes);
        for ($i = 0; $i < sizeof($footnotes); $i++) {
            if (is_numeric($footnotes[$i])) {
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
            foreach (array_unique($images) as $imageid) {
				$image = $this->images[$imageid];
                $fileContents = file_get_contents($image['thumbnail']);
                if ($fileContents) {
					$binary .= '<binary id="img_' . $imageid . '" content-type="image/jpeg">' . "\n" . base64_encode(
                            $fileContents
                        ) . "\n</binary>";
                }
            }
        }
				
		// add type attribute to footnotes
		$footnote_num=1;
		$text = preg_replace_callback(
                '@<span[^>]*class="reference"><a ([^>]*)>\*</a></span>@',
                function ($match) use (&$footnote_num) {
                    return '<a type="note" '.$match[1].'>'.($footnote_num++).'</a>';
                },
                $text
            );

        // delete p tag attributes such as data-chapter-id and so on.
		$text = preg_replace('@<p[^>]*>@', '<p>', $text);
		$notes = preg_replace('@<p[^>]*>@', '<p>', $notes);
		
		// delete strange tags combination which i saw once in fb2 
		$text = preg_replace('@<p></p>@', '<empty-line/>', $text);	

        // delete p tag around headers and atributes
		$text = preg_replace('@(<p>\s*)?(<(h[2-4])[^>]*>(.*?)</\3>)(\s*</p>)?@', '<\\3>\\4</\\3>', $text);

		// delete data-content attribute
		$text = preg_replace('@data-content.*?class="[^>]*@', '', $text);	
		//$text = preg_replace('@(data-content[^\"]*\"[^\"]*\")@', '', $text);	
		
		// add subtitles
        $text = preg_replace('@<div[^>]*class=\"center subtitle\">(.*?)<\/div>@u', '<subtitle>\\1</subtitle>', $text); 
		$text = preg_replace('@<p>\s*<subtitle>(.*?)</subtitle>\s*<\/p>@u', '<subtitle>\\1</subtitle>', $text); 

		// delete unsupported div tags
		$text = preg_replace('@<div[^>]*>(.*?)<\/div>@u', '\\1', $text);	
		$notes = preg_replace('@<div[^>]*>(.*?)<\/div>@u', '\\1', $notes);	
		
		// delete unsupported span tags
		$text = preg_replace('@<span[^>]*>(.*?)<\/span>@u', '\\1', $text);	
		$notes = preg_replace('@<span[^>]*>(.*?)<\/span>@u', '\\1', $notes);	

        // delete p tag around images
		$text = preg_replace('@(<p>\s*)?(<image[^>]*>)(\s*</p>)?@', '\\2', $text);
		
		// delete unsupported img tags
		$text = preg_replace('@<img [^>]*>(.*?)<\/img>@u', '\\1', $text);	
		$text = preg_replace('@<img.*?\/>@u', '', $text);	
		
		// find all headings
		preg_match_all('@(<h([2-4])>.*?</h\2>)(.{50})@ms', $text, $m);
		for($i=1; $i<count($m[0]); $i++) {
			$d = $m[2][$i] - $m[2][$i-1]; // diff between cur and prev heading levels
			if($d > 0) // if cur is nested - add open sections
				for($j = 0; $j < $d; $j++) 
					$text=str_replace($m[0][$i-1], $m[1][$i-1] . "\n<section>" . $m[3][$i-1], $text);
			elseif($d < 0) // if pref is nested - add close sections
				for($j = 0; $j < -$d; $j++) 
					$text=str_replace($m[0][$i], "</section>\n" . $m[0][$i] , $text);
		}

		//replace headings to section brake + title
		$text=preg_replace('@<h([2-4])>(.*?)</h\1>@',"</section>\n<section>\n<title><p>\\2</p></title>",$text);

		// add first open section tag
		$text="<section>\n<empty-line/>\n" . trim($text);

		// add last close section tags according to last heading level
		if(count($m[2])>0)
			for($i = 0; $i < $m[2][count($m[2]) - 1] - 1; $i++)
				$text.="\n</section>";

		// even if no headings - add only section closing tag
		if(count($m[2])<1)	$text.="\n</section>";

		// delete explicit empty-line before all except paragraph 
		$text=preg_replace('@<empty-line/>(?!\s*<p>)\n*@','',$text);

		// get images out from sections
		$text=preg_replace('@(<title>.*?</title>)\s*(<section>)\s*(<image .*?/>)@',"\\1\n\\3\n\\2",$text);

		// delete explicit empty-line after all except paragraph 
		$text=preg_replace('@(?<!</p>)<empty-line/>\n*@','',$text);

		// delete empty sections 
		$text=preg_replace('@<section>\s*(<empty-line/>)?\s*</section>\n*@','',$text);

		// no empty-with-title sections allowed. add emptyline
		$text=preg_replace('@</title>\s*</section>@',"</title>\n<empty-line/>\n</section>",$text);

		// no empty-with-only-image sections allowed. add emptyline
		$text=preg_replace('@(<section>\s*<title>.*?</title>\s*<image .*?/>)\s*</section>@',"\\1\n<empty-line/>\n</section>",$text);

		// add empty lines
		$text = preg_replace('@</p>\s*<subtitle>@', "</p>\n<empty-line/>\n<subtitle>", $text);
		$text = preg_replace('@</subtitle>\s*<p>@', "</subtitle>\n<empty-line/>\n<p>", $text);
		$text = preg_replace('@(<image .*?/>)(?!\s</?section)@', "\\1\n<empty-line/>", $text);
		$text = preg_replace('@</p>\s*<image@', "</p>\n<empty-line/>\n<image", $text);
		
		// replace <i>text</i> with <emphasis>text</emphasis> and <b>text</b> with <strong>text</strong>
		$text = preg_replace('@<i>(.*?)<\/i>@', '<emphasis>\\1</emphasis>', $text);	
		$text = preg_replace('@<b>(.*?)<\/b>@', '<strong>\\1</strong>', $text);	
		$notes = preg_replace('@<i>(.*?)<\/i>@', '<emphasis>\\1</emphasis>', $notes);	
		$notes = preg_replace('@<b>(.*?)<\/b>@', '<strong>\\1</strong>', $notes);	
		
		// change href to l:href and delete unsupported a attributes
		$text = preg_replace('@<a( type="note")?.*?(href="[^"]*").*?>@', "<a\\1 l:\\2>", $text);
		$notes = preg_replace('@<a.*?(href="[^"]*").*?>@', "<a l:\\1>", $notes);
		$descr['annotation'] = preg_replace('@<a\s+.*?(href="[^"]*").*?>@', "<a l:\\1>", $descr['annotation']);
		
        $text = trim($text);
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