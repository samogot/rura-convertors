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
					<title><p>§²§Ö§Ü§Ó§Ú§Ù§Ú§ä§í §á§Ö§â§Ö§Ó§à§Õ§é§Ú§Ü§à§Ó</p></title>
					<p>§¯§Ñ§Õ §á§Ö§â§Ö§Ó§à§Õ§à§Þ §â§Ñ§Ò§à§ä§Ñ§Ý§Ñ §Ü§à§Þ§Ñ§ß§Õ§Ñ <strong>RuRa-team</strong></p>\n";
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <strong>' . implode('</strong>, <strong>', $workers) . "</strong></p>\n";
            }
            $credit .= '<p>§³§Ñ§Þ§í§Û §ã§Ó§Ö§Ø§Ú§Û §á§Ö§â§Ö§Ó§à§Õ §Ó§ã§Ö§Ô§Õ§Ñ §Þ§à§Ø§ß§à §ß§Ñ§Û§ä§Ú §ß§Ñ §ã§Ñ§Û§ä§Ö §ß§Ñ§ê§Ö§Ô§à §á§â§à§Ö§Ü§ä§Ñ:</p>
					<p><a l:href="http://ruranobe.ru">http://ruranobe.ru</a></p>
					<p>§¹§ä§à§Ò§í §à§ã§ä§Ñ§Ó§Ñ§ä§î§ã§ñ §Ó §Ü§å§â§ã§Ö §Ó§ã§Ö§ç §ß§à§Ó§à§ã§ä§Ö§Û, §Ó§ã§ä§å§á§Ñ§Û§ä§Ö §Ó §ß§Ñ§ê§å §Ô§â§å§á§á§å §Ó §¬§à§ß§ä§Ñ§Ü§ä§Ö:</p>
					<p><a l:href="http://vk.com/ru.ranobe">http://vk.com/ru.ranobe</a></p>
					<empty-line/>
					<p>§¥§Ý§ñ §Ø§Ö§Ý§Ñ§ð§ë§Ú§ç §à§ä§Ò§Ý§Ñ§Ô§à§Õ§Ñ§â§Ú§ä§î §á§Ö§â§Ö§Ó§à§Õ§é§Ú§Ü§Ñ §Þ§Ñ§ä§Ö§â§Ú§Ñ§Ý§î§ß§à §Ú§Þ§Ö§ð§ä§ã§ñ webmoney-§Ü§à§ê§Ö§Ý§î§Ü§Ú §Ü§à§Þ§Ñ§ß§Õ§í:</p>
					<p><strong>R125820793397</strong></p>
					<p><strong>U911921912420</strong></p>
					<p><strong>Z608138208963</strong></p>
					<p>QIWI-§Ü§à§ê§Ö§Ý§Ö§Ü:</p>
					<p><strong>+79116857099</strong></p>
					<p>§Á§ß§Õ§Ö§Ü§ã-§Õ§Ö§ß§î§Ô§Ú:</p>
					<p><strong>410012692832515</strong></p>
                    <p>PayPal:</p>
                    <p><strong>paypal@ruranobe.ru</strong></p>
					<p>§¡ §ä§Ñ§Ü §Ø§Ö §ã§é§Ö§ä §Õ§Ý§ñ §á§Ö§â§Ö§Ó§à§Õ§Ñ §ã §Ü§â§Ö§Õ§Ú§ä§ß§í§ç §Ü§Ñ§â§ä:</p>
					<p><strong>4890 4941 5384 9302</strong></p>
					<empty-line/>
					<p>§£§Ö§â§ã§Ú§ñ §à§ä ' . date('d.m.Y', $this->touched) . '</p>
					<empty-line/>
					<empty-line/>
					<empty-line/>
					<p><strong>§­§ð§Ò§à§Ö §â§Ñ§ã§á§â§à§ã§ä§â§Ñ§ß§Ö§ß§Ú§Ö §á§Ö§â§Ö§Ó§à§Õ§Ñ §Ù§Ñ §á§â§Ö§Õ§Ö§Ý§Ñ§Þ§Ú §ß§Ñ§ê§Ö§Ô§à §ã§Ñ§Û§ä§Ñ §Ù§Ñ§á§â§Ö§ë§Ö§ß§à. §¦§ã§Ý§Ú §Ó§í §ã§Ü§Ñ§é§Ñ§Ý§Ú §æ§Ñ§Û§Ý §ß§Ñ §Õ§â§å§Ô§à§Þ §ã§Ñ§Û§ä§Ö - §Ó§í §á§à§Õ§Õ§Ö§â§Ø§Ñ§Ý§Ú §Ó§à§â§à§Ó</strong></p>
					<empty-line/>
					<empty-line/>
					<empty-line/>
					</section>';
        } elseif (strpos($this->command, 'RuRa-team') !== false) {
            $credit = "<section>
					<title><p>§²§Ö§Ü§Ó§Ú§Ù§Ú§ä§í §á§Ö§â§Ö§Ó§à§Õ§é§Ú§Ü§à§Ó</p></title>
					<p>§¯§Ñ§Õ §â§Ö§Ý§Ú§Ù§à§Þ §â§Ñ§Ò§à§ä§Ñ§Ý§Ú {$this->command}</p>\n";
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <strong>' . implode('</strong>, <strong>', $workers) . "</strong></p>\n";
            }
            $credit .= '<p>§³§Ñ§Þ§í§Û §ã§Ó§Ö§Ø§Ú§Û §á§Ö§â§Ö§Ó§à§Õ §Ó§ã§Ö§Ô§Õ§Ñ §Þ§à§Ø§ß§à §ß§Ñ§Û§ä§Ú §ß§Ñ §ã§Ñ§Û§ä§Ö §ß§Ñ§ê§Ö§Ô§à §á§â§à§Ö§Ü§ä§Ñ:</p>
					<p><a l:href="http://ruranobe.ru">http://ruranobe.ru</a></p>
					<p>§¹§ä§à§Ò§í §à§ã§ä§Ñ§Ó§Ñ§ä§î§ã§ñ §Ó §Ü§å§â§ã§Ö §Ó§ã§Ö§ç §ß§à§Ó§à§ã§ä§Ö§Û, §Ó§ã§ä§å§á§Ñ§Û§ä§Ö §Ó §ß§Ñ§ê§å §Ô§â§å§á§á§å §Ó §¬§à§ß§ä§Ñ§Ü§ä§Ö:</p>
					<p><a l:href="http://vk.com/ru.ranobe">http://vk.com/ru.ranobe</a></p>
					<empty-line/>
					<p>§£§Ö§â§ã§Ú§ñ §à§ä ' . date('d.m.Y', $this->touched) . '</p>
					<empty-line/>
					<p><strong>§­§ð§Ò§à§Ö §Ü§à§Þ§Þ§Ö§â§é§Ö§ã§Ü§à§Ö §Ú§ã§á§à§Ý§î§Ù§à§Ó§Ñ§ß§Ú§Ö §Õ§Ñ§ß§ß§à§Ô§à §ä§Ö§Ü§ã§ä§Ñ §Ú§Ý§Ú §Ö§Ô§à §æ§â§Ñ§Ô§Þ§Ö§ß§ä§à§Ó §Ù§Ñ§á§â§Ö§ë§Ö§ß§à</strong></p>
					</section>';
        } else {
            $credit = "<section>\n<title><p>§²§Ö§Ü§Ó§Ú§Ù§Ú§ä§í §á§Ö§â§Ö§Ó§à§Õ§é§Ú§Ü§à§Ó</p></title>\n";
            if ($this->command) {
                $credit .= "<p>§±§Ö§â§Ö§Ó§à§Õ §Ü§à§Þ§Ñ§ß§Õ§í {$this->command}</p>\n";
            }
            foreach ($this->workers as $activity => $workers) {
                $credit .= '<p>' . $activity . ': <strong>' . implode('</strong>, <strong>', $workers) . "</strong></p>\n";
            }
            $credit .= '<p>§£§Ö§â§ã§Ú§ñ §à§ä ' . date('d.m.Y', $this->touched) . '</p>
					<empty-line/>
					<p><strong>§­§ð§Ò§à§Ö §Ü§à§Þ§Þ§Ö§â§é§Ö§ã§Ü§à§Ö §Ú§ã§á§à§Ý§î§Ù§à§Ó§Ñ§ß§Ú§Ö §Õ§Ñ§ß§ß§à§Ô§à §ä§Ö§Ü§ã§ä§Ñ §Ú§Ý§Ú §Ö§Ô§à §æ§â§Ñ§Ô§Þ§Ö§ß§ä§à§Ó §Ù§Ñ§á§â§Ö§ë§Ö§ß§à</strong></p>
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
            $firstImage = strpos($text,'<image');            
            $firstHeading = strpos($text,'<h');
            if($firstImage !== false && ($firstImage < $firstHeading || $firstHeading === false))
				$text = "<h2>§¯§Ñ§é§Ñ§Ý§î§ß§í§Ö §Ú§Ý§Ý§ð§ã§ä§â§Ñ§è§Ú§Ú</h2>" . $text;
        }
        $j         = 1;
        $notes     = '';
        $isnotes   = false;
        $footnotes = explode(',;,', $this->footnotes);
        for ($i = 0; $i < sizeof($footnotes); $i++) {
            if (is_numeric($footnotes[$i])) {
                if ($isnotes == false) {
                    $notes .= "<body name=\"notes\">\n\t<title><p>§±§â§Ú§Þ§Ö§é§Ñ§ß§Ú§ñ</p></title>\n";
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
        // $message = "§£§à§Ù§ß§Ú§Ü§Ý§Ñ §à§ê§Ú§Ò§Ü§Ñ §á§â§Ú §Ü§à§ß§Ó§Ö§â§ä§Ñ§è§Ú§Ú $descr[src_url] §Ó §æ§à§â§Þ§Ñ§ä fb2 §ã §â§Ñ§Ù§Þ§Ö§â§à§Þ §Ú§Ù§à§Ò§â§Ñ§Ø§Ö§ß§Ú§Û $this->height\n§±§à§Õ§â§à§Ò§ß§à§ã§ä§Ú:\n\n$err\n";
        // $message = wordwrap($message, 70);
        // mail('convert_fb2@ruranobe.ru', "Fb2 Validation Error: $descr[src_url]", $message);
        // }
        return $fb2;
    }
}